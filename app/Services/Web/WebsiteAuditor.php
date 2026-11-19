<?php

namespace App\Services\Web;

use App\Models\AiLeadAnalysis;
use App\Models\LeadWebsiteAudit;
use App\Models\SavedLead;
use App\Support\SafeUrl;

/**
 * Runs a bounded, polite, SSRF-safe audit of a lead's website and stores the
 * evidence. Never stores full HTML. Throws FetchException(RATE_LIMITED) so the
 * job can retry later; every other failure is recorded on the audit row.
 */
class WebsiteAuditor
{
    public function __construct(
        private readonly SafeHttpFetcher $fetcher,
        private readonly RobotsTxt $robots,
        private readonly WebsiteSignalExtractor $extractor,
        private readonly WebsiteFindings $findings,
    ) {
    }

    /**
     * @return array{website_status: string, audit: ?LeadWebsiteAudit}
     */
    public function audit(SavedLead $lead, AiLeadAnalysis $analysis): array
    {
        $raw = trim((string) $lead->website);

        if ($raw === '') {
            return ['website_status' => AiLeadAnalysis::WEBSITE_NONE, 'audit' => null];
        }

        $base = [
            'owner_user_id' => $analysis->owner_user_id,
            'saved_lead_id' => $lead->id,
            'analysis_id' => $analysis->id,
            'url_requested' => mb_substr($raw, 0, 500),
            'audited_at' => now(),
        ];

        $url = SafeUrl::http($raw);
        if ($url === null) {
            return $this->failed($base, LeadWebsiteAudit::STATUS_INVALID_URL, AiLeadAnalysis::WEBSITE_INVALID, new FetchException(FetchException::INVALID_URL));
        }

        $deadline = microtime(true) + (float) config('ai.fetcher.audit_time_budget', 30);
        $fetchLog = [];

        try {
            if (!$this->robots->isAllowed($url, $deadline)) {
                return $this->failed($base, LeadWebsiteAudit::STATUS_BLOCKED, AiLeadAnalysis::WEBSITE_BLOCKED, null, 'robots_disallowed', 'The website\'s robots.txt does not allow this page to be checked.');
            }

            $home = $this->fetcher->get($url, deadline: $deadline);
        } catch (FetchException $e) {
            if ($e->reason === FetchException::RATE_LIMITED) {
                throw $e;
            }

            $blocked = in_array($e->reason, [FetchException::PRIVATE_ADDRESS, FetchException::BLOCKED_HOST, FetchException::BAD_PORT, FetchException::INVALID_URL], true);

            return $this->failed(
                $base,
                $blocked ? LeadWebsiteAudit::STATUS_BLOCKED : LeadWebsiteAudit::STATUS_UNREACHABLE,
                $blocked ? AiLeadAnalysis::WEBSITE_BLOCKED : AiLeadAnalysis::WEBSITE_UNREACHABLE,
                $e
            );
        }

        $fetchLog = array_merge($fetchLog, $home->hops);

        if (!$home->isSuccessful() || !$home->isHtml()) {
            $findings = $this->findings->build([], [], [
                'final_url' => $home->finalUrl, 'http_status' => $home->status, 'checked_at' => now()->toDateTimeString(),
            ]);

            return [
                'website_status' => AiLeadAnalysis::WEBSITE_UNREACHABLE,
                'audit' => LeadWebsiteAudit::create($base + [
                    'status' => LeadWebsiteAudit::STATUS_UNREACHABLE,
                    'url_final' => mb_substr($home->finalUrl, 0, 500),
                    'http_status' => $home->status,
                    'verified_findings' => $findings['findings'],
                    'fetch_log' => $fetchLog,
                    'error_code' => 'http_' . $home->status,
                    'error_message' => "The homepage returned HTTP {$home->status}.",
                ]),
            ];
        }

        $homeSignals = $this->extractor->extract($home->body, $home->finalUrl);
        $pages = [$homeSignals];

        // A few internal pages that commonly hold services / contact details
        $maxExtra = max(0, (int) config('ai.fetcher.max_pages', 3) - 1);
        $candidates = array_values(array_unique(array_filter([
            $homeSignals['contact_page_url'],
            $homeSignals['services_page_url'],
            $homeSignals['about_page_url'],
        ])));

        foreach (array_slice($candidates, 0, $maxExtra) as $pageUrl) {
            if (!$this->sameHost($pageUrl, $home->finalUrl)) {
                continue;
            }
            try {
                if (!$this->robots->isAllowed($pageUrl, $deadline)) {
                    $fetchLog[] = ['url' => SafeHttpFetcher::redact($pageUrl), 'status' => null, 'ms' => 0, 'note' => 'robots_disallowed'];
                    continue;
                }
                $page = $this->fetcher->get($pageUrl, deadline: $deadline);
                $fetchLog = array_merge($fetchLog, $page->hops);
                if ($page->isSuccessful() && $page->isHtml()) {
                    $signals = $this->extractor->extract($page->body, $page->finalUrl);
                    $signals['text_excerpt'] = mb_substr($signals['text_excerpt'], 0, 1500);
                    $pages[] = $signals;
                }
            } catch (FetchException $e) {
                $fetchLog[] = ['url' => SafeHttpFetcher::redact($pageUrl), 'status' => null, 'ms' => 0, 'note' => $e->reason];
            }
        }

        // HTTPS availability, only tested when the site ended up on plain HTTP
        $httpsChecked = false;
        $httpsAvailable = str_starts_with(strtolower($home->finalUrl), 'https://') ? true : null;
        if ($httpsAvailable === null) {
            $httpsChecked = true;
            $httpsUrl = 'https://' . parse_url($home->finalUrl, PHP_URL_HOST) . '/';
            try {
                $https = $this->fetcher->get($httpsUrl, deadline: $deadline, maxBytes: 20_000);
                $httpsAvailable = $https->status < 400 && str_starts_with(strtolower($https->finalUrl), 'https://');
                $fetchLog = array_merge($fetchLog, $https->hops);
            } catch (FetchException $e) {
                $httpsAvailable = $e->reason === FetchException::RATE_LIMITED || $e->reason === FetchException::BUDGET_EXCEEDED ? null : false;
                if ($httpsAvailable === null) {
                    $httpsChecked = false;
                }
            }
        }

        $built = $this->findings->build($homeSignals, $pages, [
            'final_url' => $home->finalUrl,
            'http_status' => $home->status,
            'https_checked' => $httpsChecked,
            'https_available' => $httpsAvailable,
            'checked_at' => now()->toDateTimeString(),
        ]);

        // Stored signals: homepage signals + short excerpts of the other pages
        $stored = $homeSignals;
        $stored['truncated'] = $home->truncated;
        $stored['strengths'] = $built['strengths'];
        $stored['other_pages'] = array_map(fn ($p) => [
            'page_url' => SafeHttpFetcher::redact($p['page_url']),
            'title' => $p['title'],
            'h1' => $p['h1'],
            'contact_forms' => $p['contact_forms'],
            'text_excerpt' => $p['text_excerpt'],
        ], array_slice($pages, 1));

        return [
            'website_status' => AiLeadAnalysis::WEBSITE_VERIFIED,
            'audit' => LeadWebsiteAudit::create($base + [
                'status' => LeadWebsiteAudit::STATUS_VERIFIED,
                'url_final' => mb_substr($home->finalUrl, 0, 500),
                'http_status' => $home->status,
                'https_available' => $httpsAvailable,
                'website_title' => $homeSignals['title'],
                'extracted_signals' => $stored,
                'verified_findings' => $built['findings'],
                'fetch_log' => $fetchLog,
                'content_hash' => hash('sha256', mb_strtolower($homeSignals['text_excerpt'])),
            ]),
        ];
    }

    private function failed(array $base, string $auditStatus, string $websiteStatus, ?FetchException $e, ?string $code = null, ?string $message = null): array
    {
        return [
            'website_status' => $websiteStatus,
            'audit' => LeadWebsiteAudit::create($base + [
                'status' => $auditStatus,
                'error_code' => $code ?? $e?->reason,
                'error_message' => mb_substr($message ?? $e?->getMessage() ?? 'The website could not be checked.', 0, 255),
                'verified_findings' => [],
            ]),
        ];
    }

    private function sameHost(string $a, string $b): bool
    {
        $strip = fn (string $u) => preg_replace('/^www\./', '', strtolower((string) parse_url($u, PHP_URL_HOST)));

        return $strip($a) !== '' && $strip($a) === $strip($b);
    }
}
