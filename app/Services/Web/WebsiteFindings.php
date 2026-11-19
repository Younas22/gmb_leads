<?php

namespace App\Services\Web;

/**
 * Turns extracted signals into findings that are directly verifiable from what
 * was fetched. Each finding states exactly what was checked; nothing claims a
 * site is slow, broken, insecure or "not mobile friendly" — those would need
 * tests this audit does not run.
 *
 * Finding: {id, type, description, evidence, source_url, verification}
 */
class WebsiteFindings
{
    /**
     * @param array      $home     signals of the homepage
     * @param list<array> $pages   signals of every page checked (homepage first)
     * @param array      $meta     ['final_url', 'http_status', 'https_checked' => bool, 'https_available' => ?bool, 'checked_at']
     * @return array{findings: list<array>, strengths: list<array>}
     */
    public function build(array $home, array $pages, array $meta): array
    {
        $findings = [];
        $strengths = [];
        $homeUrl = $meta['final_url'];
        $checkedUrls = array_values(array_unique(array_map(fn ($p) => SafeHttpFetcher::redact($p['page_url']), $pages)));
        $pageCount = count($checkedUrls);
        $when = $meta['checked_at'];

        $add = function (string $type, string $description, string $evidence, string $sourceUrl) use (&$findings) {
            $findings[] = [
                'id' => 'F' . (count($findings) + 1),
                'type' => $type,
                'description' => $description,
                'evidence' => mb_substr($evidence, 0, 300),
                'source_url' => SafeHttpFetcher::redact($sourceUrl),
                'verification' => 'verified',
            ];
        };
        $strength = function (string $type, string $description, string $evidence, string $sourceUrl) use (&$strengths) {
            $strengths[] = [
                'id' => 'S' . (count($strengths) + 1),
                'type' => $type,
                'description' => $description,
                'evidence' => mb_substr($evidence, 0, 300),
                'source_url' => SafeHttpFetcher::redact($sourceUrl),
                'verification' => 'verified',
            ];
        };

        $status = (int) ($meta['http_status'] ?? 0);
        if ($status >= 400) {
            $add('http_error', "The homepage returned HTTP {$status} when it was checked.", "HTTP status {$status} at {$when}", $homeUrl);
            return ['findings' => $findings, 'strengths' => $strengths];
        }

        if (($meta['https_checked'] ?? false) && $meta['https_available'] === false) {
            $add('no_https', 'The website loaded over plain HTTP and an HTTPS version could not be loaded when checked.', 'Final URL uses http://; the https:// request failed', $homeUrl);
        } elseif (str_starts_with(strtolower($homeUrl), 'https://')) {
            $strength('https', 'The website loads over HTTPS.', 'Final URL uses https://', $homeUrl);
        }

        if (empty($home['title'])) {
            $add('missing_title', 'The homepage has no page title (<title> tag is empty or missing).', 'No <title> text found', $homeUrl);
        }

        if (empty($home['meta_description'])) {
            $add('missing_meta_description', 'The homepage has no meta description, which search engines often show as the snippet.', 'No <meta name="description"> found', $homeUrl);
        }

        if (empty($home['h1'])) {
            $add('missing_h1', 'The homepage has no main heading (H1).', 'No <h1> element found', $homeUrl);
        }

        if (empty($home['has_viewport_meta'])) {
            $add('no_viewport_meta', 'The homepage does not declare a mobile viewport meta tag. (This alone does not prove how the site looks on phones.)', 'No <meta name="viewport"> found', $homeUrl);
        }

        $contactForms = array_sum(array_map(fn ($p) => (int) ($p['contact_forms'] ?? 0), $pages));
        if ($contactForms === 0) {
            $add('no_contact_form', "No contact form was found on the {$pageCount} page(s) checked.", 'Pages checked: ' . implode(', ', $checkedUrls), $homeUrl);
        } else {
            $strength('contact_form', 'A contact form was found.', "{$contactForms} contact form(s) on the pages checked", $homeUrl);
        }

        if (empty($home['ctas'])) {
            $add('no_cta_homepage', 'No clear call-to-action (such as "Book", "Call now" or "Get a quote") was found on the homepage.', 'No matching button or link text found', $homeUrl);
        } else {
            $strength('cta', 'The homepage has a call-to-action.', 'Examples: ' . implode(' | ', array_slice($home['ctas'], 0, 3)), $homeUrl);
        }

        $phones = array_merge(...array_map(fn ($p) => $p['phones'] ?? [], $pages ?: [[]]));
        if ($phones === []) {
            $add('no_phone_visible', "No phone number was visible on the {$pageCount} page(s) checked.", 'No tel: link or phone-like number found', $homeUrl);
        }

        $emails = array_merge(...array_map(fn ($p) => $p['emails'] ?? [], $pages ?: [[]]));
        if ($phones === [] && $emails === [] && $contactForms === 0) {
            $add('no_contact_method', 'No phone number, email address or contact form was found on the pages checked.', 'Pages checked: ' . implode(', ', $checkedUrls), $homeUrl);
        }

        if (empty($home['social_links'])) {
            $add('no_social_links', 'No links to social media profiles were found on the homepage.', 'No Facebook/Instagram/LinkedIn/X/YouTube/TikTok links found', $homeUrl);
        } else {
            $strength('social_links', 'The homepage links to social media profiles.', implode(', ', array_keys($home['social_links'])), $homeUrl);
        }

        if (($home['booking_links'] ?? 0) > 0) {
            $strength('online_booking', 'An online booking/scheduling link was found.', 'Booking service link on homepage', $homeUrl);
        }

        $localTypes = array_filter($home['structured_data_types'] ?? [], fn ($t) => preg_match('/LocalBusiness|Organization|Restaurant|Dentist|Store|Service|Clinic|Attorney|Hotel|Physician|Plumber|Electrician|AutoRepair/i', $t));
        if ($localTypes === []) {
            $add('no_business_schema', 'No business structured data (schema.org LocalBusiness/Organization) was found on the homepage.', 'No matching JSON-LD @type', $homeUrl);
        }

        if (($home['word_count'] ?? 0) < 80) {
            $add('thin_homepage_text', 'The homepage has very little readable text.', 'About ' . (int) ($home['word_count'] ?? 0) . ' words of visible text', $homeUrl);
        }

        return ['findings' => $findings, 'strengths' => $strengths];
    }
}
