<?php

namespace Tests\Feature\EmailTracking;

use App\Models\EmailTrackingEvent;
use App\Models\EmailTrackingLink;
use App\Models\OutboundEmail;
use App\Models\PackageFeature;
use Illuminate\Support\Facades\Schema;

/** Phase 4: the public /r/{token} redirect and how requests are recorded. */
class TrackedRedirectTest extends TrackingTestCase
{
    public function test_redirects_to_the_stored_destination_and_records_a_probable_click(): void
    {
        [, $email, $tokens] = $this->trackedEmail();
        $this->travel(5)->minutes();

        $this->click($tokens[0])
            ->assertRedirect('https://pixelstudio.example/work/clinic?ref=1')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $event = EmailTrackingEvent::where('event_type', EmailTrackingEvent::LINK_CLICK)->sole();
        $this->assertSame(EmailTrackingEvent::HUMAN, $event->classification);
        $this->assertSame(EmailTrackingEvent::SOURCE_REDIRECT, $event->event_source);
        $this->assertSame($email->id, $event->outbound_email_id);
        $this->assertSame(1, $event->link->link_position);
    }

    public function test_destination_never_comes_from_the_request(): void
    {
        [, , $tokens] = $this->trackedEmail();

        $this->call('GET', "/r/{$tokens[0]}?url=https://evil.example&next=//evil.example", [], [], [], ['HTTP_USER_AGENT' => self::BROWSER])
            ->assertRedirect('https://pixelstudio.example/work/clinic?ref=1');

        // Unknown, malformed and over-long tokens never redirect anywhere
        $this->click(str_repeat('A', 32))->assertNotFound()->assertSee('no longer available');
        $this->get('/r/short')->assertNotFound();
        $this->get('/r/' . str_repeat('A', 33))->assertNotFound();
        $this->get('/r/https:%2F%2Fevil.example')->assertNotFound();
    }

    public function test_a_tampered_or_disabled_destination_is_not_followed(): void
    {
        [, , $tokens] = $this->trackedEmail();
        $link = EmailTrackingLink::where('link_position', 1)->sole();

        foreach (['javascript:alert(1)', 'http://127.0.0.1/admin', 'https://a.example@evil.example/', "https://ok.example/\r\nSet-Cookie: x=1"] as $bad) {
            $link->forceFill(['original_url' => $bad])->save();
            $this->click($tokens[0])->assertNotFound();
        }

        $link->forceFill(['original_url' => 'https://pixelstudio.example/ok', 'is_active' => false])->save();
        $this->click($tokens[0])->assertNotFound();
        $this->assertSame(0, EmailTrackingEvent::where('event_type', EmailTrackingEvent::LINK_CLICK)->count());
    }

    public function test_scanners_are_separated_from_probable_people(): void
    {
        [, , $tokens] = $this->trackedEmail();

        // Seconds after sending: typical of security scanners
        $this->click($tokens[0])->assertRedirect();
        $this->assertSame('too_soon_after_send', EmailTrackingEvent::latest('id')->first()->metadata['reason']);

        $this->travel(10)->minutes();
        $this->click($tokens[0], 'Mozilla/5.0 (compatible; Proofpoint URL Defense)')->assertRedirect();
        $this->click($tokens[1], 'python-requests/2.31')->assertRedirect();
        $this->click($tokens[1], '')->assertRedirect();
        $this->click($tokens[0], self::BROWSER, 'HEAD');

        $this->assertSame(5, EmailTrackingEvent::where('classification', EmailTrackingEvent::SCANNER)->count());
        $this->assertSame(0, EmailTrackingEvent::where('classification', EmailTrackingEvent::HUMAN)->count());

        $this->click($tokens[1])->assertRedirect('https://pixelstudio.example/pricing');
        $this->assertSame(1, EmailTrackingEvent::where('classification', EmailTrackingEvent::HUMAN)->count());
    }

    public function test_the_senders_own_test_clicks_are_marked_internal(): void
    {
        [$user, , $tokens] = $this->trackedEmail();
        $this->travel(5)->minutes();

        $this->actingAs($user)->call('GET', "/r/{$tokens[0]}", [], [], [], ['HTTP_USER_AGENT' => self::BROWSER])->assertRedirect();

        $this->assertSame(EmailTrackingEvent::INTERNAL, EmailTrackingEvent::where('event_type', EmailTrackingEvent::LINK_CLICK)->sole()->classification);
    }

    public function test_repeated_requests_are_deduplicated_and_bounded(): void
    {
        [, , $tokens] = $this->trackedEmail();
        $this->travel(5)->minutes();

        $this->click($tokens[0]);
        $this->click($tokens[0]);
        $this->click($tokens[0]);
        $this->assertSame(1, EmailTrackingEvent::where('event_type', EmailTrackingEvent::LINK_CLICK)->count(), 'Same client within a minute counts once');

        $this->travel(2)->minutes();
        $this->click($tokens[0]);
        $this->assertSame(2, EmailTrackingEvent::where('event_type', EmailTrackingEvent::LINK_CLICK)->count());

        // Per-link storage cap: the redirect keeps working, events stop growing
        $link = EmailTrackingLink::where('link_position', 1)->sole();
        \Illuminate\Support\Facades\RateLimiter::clear('track-link:' . $link->id);
        config(['email_tracking.max_events_per_link_per_hour' => 3]);
        for ($i = 0; $i < 5; $i++) {
            $this->travel(2)->minutes();
            $this->click($tokens[0])->assertRedirect();
        }
        $this->assertSame(2 + 3, EmailTrackingEvent::where('event_type', EmailTrackingEvent::LINK_CLICK)->count());
    }

    public function test_no_ip_address_or_user_agent_is_stored(): void
    {
        [, , $tokens] = $this->trackedEmail();
        $this->travel(5)->minutes();
        $this->click($tokens[0]);

        $event = EmailTrackingEvent::where('event_type', EmailTrackingEvent::LINK_CLICK)->sole();
        $row = json_encode($event->getAttributes());
        $this->assertStringNotContainsString('203.0.113', $row);
        $this->assertStringNotContainsString('Chrome', $row);
        $this->assertSame(16, strlen($event->client_hash));
        $this->assertFalse(Schema::hasColumn('email_tracking_events', 'ip'));
        $this->assertFalse(Schema::hasColumn('email_tracking_events', 'user_agent'));
    }

    public function test_nothing_is_recorded_for_unsent_emails_lost_plans_or_expired_retention(): void
    {
        [$user, $email, $tokens] = $this->trackedEmail();
        $this->travel(5)->minutes();

        // Plan no longer includes tracking: redirect works, nothing recorded
        PackageFeature::where('feature_key', 'email_tracking_enabled')->update(['feature_value' => '0']);
        $this->click($tokens[0])->assertRedirect();
        $this->assertSame(0, EmailTrackingEvent::where('event_type', EmailTrackingEvent::LINK_CLICK)->count());
        PackageFeature::where('feature_key', 'email_tracking_enabled')->update(['feature_value' => '1']);

        // Past the sender's retention period
        $this->travel(91)->days();
        $this->click($tokens[0])->assertRedirect();
        $this->assertSame(0, EmailTrackingEvent::where('event_type', EmailTrackingEvent::LINK_CLICK)->count());

        // Email that was never sent
        $email->forceFill(['status' => OutboundEmail::STATUS_CANCELLED, 'sent_at' => null])->save();
        $this->travelBack();
        $this->click($tokens[0])->assertRedirect();
        $this->assertSame(0, EmailTrackingEvent::where('event_type', EmailTrackingEvent::LINK_CLICK)->count());
    }

    public function test_redirect_page_reveals_no_private_data(): void
    {
        [, $email, ] = $this->trackedEmail();

        $response = $this->click(str_repeat('B', 32));
        foreach ([$email->to_email, 'Smile Dental', 'ayesha@gmail.com', 'Pixel Studio', 'pixelstudio.example'] as $secret) {
            $response->assertDontSee($secret);
        }
    }
}
