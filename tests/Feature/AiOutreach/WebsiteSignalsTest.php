<?php

namespace Tests\Feature\AiOutreach;

use App\Services\Web\WebsiteFindings;
use App\Services\Web\WebsiteSignalExtractor;

class WebsiteSignalsTest extends OutreachTestCase
{
    private function extract(string $html, string $url = 'https://smile.example/'): array
    {
        return (new WebsiteSignalExtractor())->extract($html, $url);
    }

    public function test_extracts_verifiable_signals(): void
    {
        $html = <<<'HTML'
<html lang="en"><head>
<title>Smile Dental | Lahore</title>
<meta name="description" content="Family dentist in Lahore.">
<meta name="viewport" content="width=device-width">
<script type="application/ld+json">{"@context":"https://schema.org","@type":"Dentist","name":"Smile"}</script>
</head><body>
<h1>Gentle family dentistry</h1><h2>Whitening</h2>
<a href="/contact-us">Contact us</a>
<a href="/services">Our services</a>
<a href="tel:+92421234567">Call</a>
<a href="mailto:hello@smile.example?subject=x">Email</a>
<a href="https://www.instagram.com/smile?ref=1">Insta</a>
<a href="https://calendly.com/smile">Book an appointment</a>
<a href="javascript:alert(1)">bad</a>
<form><input type="email" name="email"><textarea></textarea><button>Send</button></form>
<script>document.write('hidden')</script>
<p>Visit us any weekday.</p>
</body></html>
HTML;

        $s = $this->extract($html);

        $this->assertSame('Smile Dental | Lahore', $s['title']);
        $this->assertSame('Family dentist in Lahore.', $s['meta_description']);
        $this->assertTrue($s['has_viewport_meta']);
        $this->assertSame(['Gentle family dentistry'], $s['h1']);
        $this->assertSame(1, $s['contact_forms']);
        $this->assertContains('Book an appointment', $s['ctas']);
        $this->assertContains('Contact us', $s['ctas']);
        $this->assertContains('+92421234567', $s['phones']);
        $this->assertContains('hello@smile.example', $s['emails']);
        $this->assertSame('https://www.instagram.com/smile', $s['social_links']['instagram']);
        $this->assertSame(1, $s['booking_links']);
        $this->assertSame(['Dentist'], $s['structured_data_types']);
        $this->assertSame('https://smile.example/contact-us', $s['contact_page_url']);
        $this->assertSame('https://smile.example/services', $s['services_page_url']);
        $this->assertStringNotContainsString('document.write', $s['text_excerpt']);
        $this->assertStringNotContainsString('schema.org', $s['text_excerpt']);
        $this->assertStringContainsString('Visit us any weekday.', $s['text_excerpt']);
    }

    public function test_malformed_html_does_not_crash(): void
    {
        $s = $this->extract("<html><body><h1>Unclosed <b>tags <p>text \xC3\x28 bad utf8");

        $this->assertCount(1, $s['h1']);
        $this->assertStringStartsWith('Unclosed tags', $s['h1'][0]);
        $this->assertTrue(mb_check_encoding($s['text_excerpt'], 'UTF-8'));
        $this->assertNull($s['title']);
    }

    public function test_findings_are_scoped_to_what_was_checked(): void
    {
        $home = $this->extract('<html><head><title></title></head><body><p>Hi</p></body></html>', 'https://plain.example/');

        $built = (new WebsiteFindings())->build($home, [$home], [
            'final_url' => 'https://plain.example/', 'http_status' => 200, 'https_checked' => false, 'https_available' => true, 'checked_at' => '2026-11-15 10:00:00',
        ]);

        $types = array_column($built['findings'], 'type');
        foreach (['missing_title', 'missing_meta_description', 'missing_h1', 'no_viewport_meta', 'no_contact_form', 'no_cta_homepage', 'no_phone_visible', 'no_contact_method', 'no_social_links', 'no_business_schema', 'thin_homepage_text'] as $expected) {
            $this->assertContains($expected, $types);
        }

        foreach ($built['findings'] as $f) {
            $this->assertMatchesRegularExpression('/^F\d+$/', $f['id']);
            $this->assertSame('verified', $f['verification']);
            $this->assertNotEmpty($f['evidence']);
            $this->assertSame('https://plain.example/', $f['source_url']);
            // Never claim things this audit cannot test
            $this->assertDoesNotMatchRegularExpression('/\b(slow|broken|hacked|unsafe|not mobile[- ]friendly)\b/i', $f['description']);
        }

        $this->assertNotContains('no_https', $types, 'HTTPS was available');
    }

    public function test_https_finding_only_when_actually_tested(): void
    {
        $home = $this->extract('<html><head><title>T</title></head><body><h1>H</h1></body></html>', 'http://old.example/');
        $meta = ['final_url' => 'http://old.example/', 'http_status' => 200, 'checked_at' => 'now'];

        $untested = (new WebsiteFindings())->build($home, [$home], $meta + ['https_checked' => false, 'https_available' => null]);
        $this->assertNotContains('no_https', array_column($untested['findings'], 'type'));

        $tested = (new WebsiteFindings())->build($home, [$home], $meta + ['https_checked' => true, 'https_available' => false]);
        $this->assertContains('no_https', array_column($tested['findings'], 'type'));
    }

    public function test_http_error_only_reports_the_status(): void
    {
        $built = (new WebsiteFindings())->build([], [], ['final_url' => 'https://down.example/', 'http_status' => 503, 'checked_at' => 'now']);

        $this->assertCount(1, $built['findings']);
        $this->assertSame('http_error', $built['findings'][0]['type']);
        $this->assertStringContainsString('503', $built['findings'][0]['description']);
    }
}
