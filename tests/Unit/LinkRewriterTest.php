<?php

namespace Tests\Unit;

use App\Services\EmailTracking\LinkRewriter;
use App\Services\EmailTracking\SafeUrl;
use PHPUnit\Framework\TestCase;

class LinkRewriterTest extends TestCase
{
    public function test_safe_url_accepts_only_public_http_destinations(): void
    {
        foreach (['https://example.com', 'http://shop.example.co.uk/p?a=1&b=2#x', 'https://xn--bcher-kva.example/', 'https://8.8.8.8/dns'] as $ok) {
            $this->assertSame($ok, SafeUrl::check($ok), $ok);
        }

        foreach ([
            'javascript:alert(1)', 'data:text/html,hi', 'ftp://example.com', '//example.com', 'example.com',
            'https://user:pass@example.com', 'https://example.com@evil.example', 'http://localhost', 'http://intranet.local/',
            'http://10.0.0.5/', 'http://127.0.0.1/', 'http://[::1]/', 'http://169.254.169.254/latest', 'https://exa mple.com',
            "https://example.com/\nX: y", 'https://bücher.example/', 'https://example.com/' . str_repeat('a', 2100), 'https://nodot/',
        ] as $bad) {
            $this->assertNull(SafeUrl::check($bad), $bad);
        }
    }

    public function test_text_links_keep_surrounding_punctuation(): void
    {
        $text = "See https://a.example/x. Or (https://b.example/y), and https://en.wikipedia.org/wiki/PHP_(language)!";
        $out = LinkRewriter::rewriteText($text, fn ($u) => '[' . $u . ']');

        $this->assertSame('See [https://a.example/x]. Or ([https://b.example/y]), and [https://en.wikipedia.org/wiki/PHP_(language)]!', $out);
        $this->assertSame(['https://a.example/x', 'https://b.example/y', 'https://en.wikipedia.org/wiki/PHP_(language)'], LinkRewriter::findInText($text));
    }

    public function test_callback_returning_null_keeps_the_original_text(): void
    {
        $text = "Keep https://a.example/x and https://b.example/y.";

        $this->assertSame($text, LinkRewriter::rewriteText($text, fn () => null));
    }

    public function test_unsubscribe_and_already_tracked_links_are_excluded(): void
    {
        $this->assertTrue(LinkRewriter::isExcluded('https://app.example/email/unsubscribe/5?signature=abc'));
        $this->assertTrue(LinkRewriter::isExcluded('https://example.com/Opt-Out'));
        $this->assertTrue(LinkRewriter::isExcluded('https://app.example/r/' . str_repeat('A', 32)));
        $this->assertTrue(LinkRewriter::isExcluded('https://x.example/exact', ['https://x.example/exact']));
        $this->assertFalse(LinkRewriter::isExcluded('https://example.com/pricing'));
    }
}
