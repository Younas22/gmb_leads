<?php

namespace Tests\Feature;

use Tests\Feature\Security\SecurityTestCase;

/**
 * Homepage: section order, working CTAs, honest reviews, FAQ and footer.
 */
class HomePageTest extends SecurityTestCase
{
    public function test_sections_render_in_the_designed_order(): void
    {
        $this->get('/')->assertOk()->assertSeeInOrder([
            'id="navbar"', 'id="hero-heading"', '1,000+', 'id="features"', 'id="ai-tools"',
            'id="pricing"', 'id="faq"', 'id="final-cta-heading"', '<footer id="contact"',
        ], false);
    }

    public function test_ctas_point_to_real_routes(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('href="' . route('auth.show') . '"', false)
            ->assertSee('hpOpenDemo()', false)
            ->assertSee('data-src="https://www.youtube.com/embed/', false)
            ->assertSee('Get Started Free')
            ->assertSee('Watch Demo');
    }

    public function test_example_reviews_show_live_always_labelled_six_at_a_time(): void
    {
        $html = $this->get('/')->assertOk()
            ->assertSee('data-sample-reviews="1"', false)
            ->assertSee('Example reviews · not real customers')
            ->assertSee('not reviews from real customers')
            ->assertDontSee('What Our Users Say')
            ->assertSee('href="#reviews"', false)
            ->assertSee('Load More Reviews')
            ->assertSee('Showing 6 of 42')
            ->assertSee('Ali Raza')->assertSee('Eman Raza')
            ->assertDontSee('John Smith')
            ->getContent();

        $this->assertSame(42, substr_count($html, 'data-review-index='));
        $this->assertSame(36, preg_match_all('/class="hp-review [^"]*\bhidden\b/', $html));
        $this->assertSame(42, substr_count($html, '>Sample<'));
        $this->assertStringNotContainsString('>Verified<', $html);
    }

    public function test_faq_has_twenty_questions_and_matching_structured_data(): void
    {
        $html = $this->get('/')->assertOk()
            ->assertSee('Frequently Asked')
            ->assertSee('aria-controls="faq-a-0"', false)
            ->getContent();

        $this->assertSame(20, substr_count($html, 'class="hp-faq '));
        preg_match('#<script type="application/ld\+json">\s*(\{"@context":"https://schema.org","@type":"FAQPage".*?)\s*</script>#s', $html, $m);
        $this->assertNotEmpty($m, 'FAQPage structured data missing');
        $this->assertCount(20, json_decode($m[1], true)['mainEntity']);
    }

    public function test_footer_links_use_real_routes(): void
    {
        $this->get('/')->assertOk()
            ->assertSee(route('privacy.policy'), false)
            ->assertSee(route('terms'), false)
            ->assertSee(route('refund.policy'), false)
            ->assertDontSee('Company &amp; Legal', false)   // link columns removed from the footer
            ->assertSee('mailto:info@customernearme.com', false)
            ->assertSee('&copy; ' . date('Y'), false)
            ->assertDontSee('About Us')
            ->assertSee('on X (Twitter)"', false)
            ->assertDontSee('fa-x-twitter', false)   // not in Font Awesome 6.4.0; an SVG is used
            ->assertSee('id="ft-wordmark"', false);
    }
}
