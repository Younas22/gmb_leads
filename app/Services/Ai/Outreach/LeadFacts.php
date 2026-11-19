<?php

namespace App\Services\Ai\Outreach;

use App\Models\AiLeadAnalysis;
use App\Models\LeadWebsiteAudit;
use App\Models\SavedLead;
use App\Models\User;
use App\Models\UserOffer;

/**
 * Collects the verified inputs for one lead (lead data, website evidence and
 * the account's active offers) and the reference ids the AI may cite.
 * Shared by the analyzer and the draft writer so both use identical evidence.
 */
class LeadFacts
{
    /** @var array<string, string> ref => human label */
    public array $refs = [];

    /** @var array<int, UserOffer> active services/offers by id (skills excluded) */
    public array $offers = [];

    /** @var list<UserOffer> active skills */
    public array $skills = [];

    public function __construct(
        public readonly SavedLead $lead,
        public readonly AiLeadAnalysis $analysis,
        public readonly ?LeadWebsiteAudit $audit,
        public readonly User $owner,
    ) {
        foreach (UserOffer::ownedBy($owner)->active()->ordered()->get() as $offer) {
            if ($offer->type === UserOffer::TYPE_SKILL) {
                $this->skills[] = $offer;
            } else {
                $this->offers[$offer->id] = $offer;
            }
        }

        $this->refs['lead:name'] = 'business name';
        if ($this->category() !== '') {
            $this->refs['lead:category'] = 'business category';
        }
        if ($this->location() !== '') {
            $this->refs['lead:location'] = 'location';
        }
        if ($lead->rating !== null && (float) $lead->rating > 0) {
            $this->refs['lead:rating'] = 'Google rating';
            $this->refs['lead:reviews'] = 'Google review count';
        }

        if ($this->websiteVerified()) {
            foreach (['title', 'meta_description', 'headings', 'text'] as $part) {
                $this->refs['website:' . $part] = 'website ' . $part;
            }
            foreach ($this->findings() as $f) {
                $this->refs[$f['id']] = 'finding';
            }
            foreach ($this->strengths() as $s) {
                $this->refs[$s['id']] = 'strength';
            }
        }
    }

    public function websiteVerified(): bool
    {
        return $this->audit !== null && $this->audit->status === LeadWebsiteAudit::STATUS_VERIFIED;
    }

    public function findings(): array
    {
        return $this->audit?->verified_findings ?? [];
    }

    public function strengths(): array
    {
        return $this->audit?->extracted_signals['strengths'] ?? [];
    }

    public function category(): string
    {
        return PromptSanitizer::text($this->lead->category ?: $this->lead->search_query, 100);
    }

    public function location(): string
    {
        $parts = array_filter([
            $this->lead->cityRelation?->name,
            $this->lead->stateRelation?->name,
            $this->lead->countryRelation?->name,
        ]);

        return PromptSanitizer::text($parts ? implode(', ', $parts) : '', 150);
    }

    public function websiteNote(): string
    {
        return match ($this->analysis->website_status) {
            AiLeadAnalysis::WEBSITE_VERIFIED => 'The website was checked; only the findings listed below are verified.',
            AiLeadAnalysis::WEBSITE_NONE => 'No website URL is on file for this lead. The website was NOT checked. Do not say or imply the business has no website, and do not mention website problems.',
            AiLeadAnalysis::WEBSITE_INVALID => 'The website URL on file is not valid, so the website was NOT checked. Do not mention website problems.',
            AiLeadAnalysis::WEBSITE_BLOCKED => 'The website could not be checked (not allowed). Do not mention website problems.',
            default => 'The website could not be loaded when checked. Do not draw conclusions about it and do not mention website problems.',
        };
    }

    /** Untrusted lead + website data, sanitised and delimited. */
    public function leadBlock(): string
    {
        $lead = $this->lead;
        $lines = [
            '[lead:name] Business name: ' . PromptSanitizer::text($lead->name, 150),
            '[lead:category] Category: ' . ($this->category() ?: '(unknown)'),
            '[lead:location] Location: ' . ($this->location() ?: '(unknown)'),
        ];
        if (isset($this->refs['lead:rating'])) {
            $lines[] = '[lead:rating] Google rating: ' . number_format((float) $lead->rating, 1);
            $lines[] = '[lead:reviews] Google reviews: ' . (int) $lead->total_reviews;
        }
        $lines[] = 'Website status: ' . $this->websiteNote();

        $block = "<lead_data untrusted=\"true\">\n" . implode("\n", $lines) . "\n</lead_data>";

        if ($this->websiteVerified()) {
            $s = $this->audit->extracted_signals ?? [];
            $max = (int) config('ai.analysis.max_website_chars', 6000);
            $web = [
                '[website:title] Title: ' . PromptSanitizer::text($s['title'] ?? '', 200),
                '[website:meta_description] Meta description: ' . PromptSanitizer::text($s['meta_description'] ?? '', 400),
                '[website:headings] Headings: ' . PromptSanitizer::list(array_merge($s['h1'] ?? [], $s['h2'] ?? []), 10, 150),
                '[website:text] Visible text excerpt: ' . PromptSanitizer::text($s['text_excerpt'] ?? '', $max),
            ];
            foreach ($s['other_pages'] ?? [] as $page) {
                $web[] = '[website:text] Page ' . PromptSanitizer::text($page['page_url'] ?? '', 200) . ': ' . PromptSanitizer::text($page['text_excerpt'] ?? '', 1200);
            }
            $block .= "\n<website_content untrusted=\"true\">\n" . implode("\n", $web) . "\n</website_content>";

            $f = array_map(fn ($x) => '[' . $x['id'] . '] ' . PromptSanitizer::text($x['description'], 300) . ' (evidence: ' . PromptSanitizer::text($x['evidence'] ?? '', 200) . ')', $this->findings());
            $st = array_map(fn ($x) => '[' . $x['id'] . '] ' . PromptSanitizer::text($x['description'], 300), $this->strengths());
            $block .= "\n<verified_findings>\n" . ($f ? implode("\n", $f) : '(no issues found by the checks we run)') . "\n</verified_findings>";
            $block .= "\n<verified_strengths>\n" . ($st ? implode("\n", $st) : '(none recorded)') . "\n</verified_strengths>";
        }

        return $block;
    }

    /** The account's own offer catalogue (trusted: written by the account owner). */
    public function catalogBlock(): string
    {
        $lines = [];
        foreach ($this->offers as $id => $o) {
            $line = "[offer_id={$id}] ({$o->type}) " . PromptSanitizer::text($o->title, 150);
            if ($o->description) {
                $line .= ' — ' . PromptSanitizer::text($o->description, 400);
            }
            if ($o->price_hint) {
                $line .= ' | price: ' . PromptSanitizer::text($o->price_hint, 100);
            }
            if ($o->ideal_for) {
                $line .= ' | ideal for: ' . PromptSanitizer::text($o->ideal_for, 200);
            }
            $lines[] = $line;
        }
        $skills = array_map(fn ($s) => PromptSanitizer::text($s->title, 100), $this->skills);

        return "<offer_catalog>\n" . implode("\n", $lines) . "\nSkills (context only, not offers): " . ($skills ? implode(', ', $skills) : '(none)') . "\n</offer_catalog>";
    }

    /** All URLs the email may contain (the sender's own sites, the lead's verified site). */
    public function allowedHosts(array $profileContext): array
    {
        $urls = array_merge([$profileContext['website'] ?? null], $profileContext['portfolio_urls'] ?? []);
        if ($this->websiteVerified()) {
            $urls[] = $this->audit->url_final;
        }

        $hosts = [];
        foreach (array_filter($urls) as $u) {
            $h = strtolower((string) parse_url($u, PHP_URL_HOST));
            if ($h !== '') {
                $hosts[] = preg_replace('/^www\./', '', $h);
            }
        }

        return array_values(array_unique($hosts));
    }
}
