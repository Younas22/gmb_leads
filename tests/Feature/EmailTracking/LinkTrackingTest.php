<?php

namespace Tests\Feature\EmailTracking;

use App\Models\AiEmailDraft;
use App\Models\EmailTrackingEvent;
use App\Models\EmailTrackingLink;
use App\Models\EmailTrackingSetting;
use App\Models\OutboundEmail;
use App\Services\EmailTracking\LinkTracker;

/** Phase 4: link tracking in the send pipeline (Gmail faked throughout). */
class LinkTrackingTest extends TrackingTestCase
{
    public function test_tracking_is_off_by_default_and_links_are_sent_unchanged(): void
    {
        $user = $this->trackingSender();
        $this->connect($user);
        $draft = $this->draftFor($user, ['body' => self::LINK_BODY]);

        $email = $this->sendDraft($user, $draft);

        $this->assertFalse($email->link_tracking);
        $this->assertSame(0, EmailTrackingLink::count());
        $body = $this->decodedBody($this->mimeOf($this->requestsTo(self::SEND_URL)[0]));
        $this->assertStringContainsString('https://pixelstudio.example/work/clinic?ref=1.', $body);
        $this->assertStringNotContainsString('/r/', $body);
    }

    public function test_sender_setting_without_the_plan_feature_does_not_track(): void
    {
        $user = $this->trackingSender(['email_tracking_enabled' => '0']);
        $this->connect($user);
        $this->enableTracking($user);
        $draft = $this->draftFor($user, ['body' => self::LINK_BODY]);

        $email = $this->sendDraft($user, $draft, false);

        $this->assertFalse($email->link_tracking);
        $this->assertSame(0, EmailTrackingLink::count());
    }

    public function test_enabled_tracking_replaces_eligible_links_and_preserves_everything_else(): void
    {
        [$user, $email, $tokens] = $this->trackedEmail();

        $this->assertTrue($email->link_tracking);
        $this->assertCount(2, $tokens);
        $links = EmailTrackingLink::orderBy('link_position')->get();
        $this->assertSame(['https://pixelstudio.example/work/clinic?ref=1', 'https://pixelstudio.example/pricing'], $links->pluck('original_url')->all());
        $this->assertSame([1, 2], $links->pluck('link_position')->all());

        // What Gmail received: tracked links, trailing punctuation kept outside the URL
        $mime = $this->mimeOf($this->requestsTo(self::SEND_URL)[0]);
        $body = $this->decodedBody($mime);
        $this->assertStringContainsString('/r/' . $tokens[0] . '.', $body);
        $this->assertStringContainsString('(' . url('/r/' . $tokens[1]) . ')', $body);
        $this->assertStringNotContainsString('pixelstudio.example/pricing', $body);

        // Same subject, recipient, sender text; unsubscribe link and headers untouched
        $this->assertStringContainsString('Subject: A quick idea for your clinic', $mime);
        $this->assertStringContainsString('To: info@smiledental.example', $mime);
        $this->assertStringContainsString('Here is a similar clinic site we built:', $body);
        $unsubscribe = app(\App\Services\Gmail\OutboundEmailService::class)->unsubscribeUrl($email);
        $this->assertStringContainsString($unsubscribe, $body);
        $this->assertStringContainsString('List-Unsubscribe: <mailto:ayesha@gmail.com?subject=unsubscribe>, <' . $unsubscribe . '>', $mime);
        $this->assertStringContainsString('List-Unsubscribe-Post: List-Unsubscribe=One-Click', $mime);

        // The approved draft and the untracked body are unchanged; the sent body is kept
        $this->assertSame(self::LINK_BODY, AiEmailDraft::find($email->draft_id)->body);
        $this->assertStringContainsString('https://pixelstudio.example/pricing', $email->body);
        $this->assertSame($body, str_replace("\r\n", "\n", $email->sent_body));
        $this->assertSame(hash('sha256', $this->requestsTo(self::SEND_URL)[0]->data()['raw']), $email->mime_sha256);
    }

    public function test_tokens_are_unique_opaque_and_only_hashed_in_the_database(): void
    {
        [$user, $email, $tokens] = $this->trackedEmail();

        $this->assertNotSame($tokens[0], $tokens[1]);
        foreach ($tokens as $token) {
            $this->assertSame(32, strlen($token));
            $this->assertDatabaseMissing('email_tracking_links', ['token_hash' => $token]);
            $this->assertDatabaseHas('email_tracking_links', ['token_hash' => hash('sha256', $token)]);
            // No lead, user, email or Gmail account identifiers in the link
            foreach ([(string) $email->id, (string) $email->saved_lead_id, (string) $user->id, 'ayesha', 'smiledental'] as $id) {
                $this->assertStringNotContainsString('/' . $id . '/', url('/r/' . $token));
            }
        }

        $many = collect(range(1, 200))->map(fn () => LinkTracker::newToken());
        $this->assertSame(200, $many->unique()->count());
    }

    public function test_unsafe_and_unsubscribe_links_are_never_tracked(): void
    {
        $user = $this->trackingSender();
        $this->connect($user);
        $this->enableTracking($user);
        $body = "Links: javascript:alert(1) ftp://files.example/x http://localhost/admin http://192.168.1.10/router "
            . "https://user:pw@evil.example/ https://example.com/unsubscribe?id=1 https://good.example/page\n";
        $draft = $this->draftFor($user, ['body' => $body]);

        $email = $this->sendDraft($user, $draft, true);

        $this->assertSame(['https://good.example/page'], EmailTrackingLink::pluck('original_url')->all());
        foreach (['javascript:alert(1)', 'ftp://files.example/x', 'http://localhost/admin', 'http://192.168.1.10/router', 'https://user:pw@evil.example/', 'https://example.com/unsubscribe?id=1'] as $kept) {
            $this->assertStringContainsString($kept, $email->sent_body);
        }
    }

    public function test_review_screen_discloses_tracking_and_send_requires_matching_disclosure(): void
    {
        $user = $this->trackingSender();
        $this->connect($user);
        $this->enableTracking($user);
        $offer = $this->offerFor($user);
        $lead = $this->leadFor($user, ['email' => 'info@smiledental.example']);
        $this->fakeAi($this->happyAi($offer->id));
        $this->actingAs($user)->postJson('/user/ai-outreach/analyze', ['lead_ids' => [$lead->id]])->assertOk();
        $draft = AiEmailDraft::sole();
        $draft->forceFill(['body' => self::LINK_BODY])->save();
        $this->post("/user/ai-outreach/drafts/{$draft->id}/approve")->assertRedirect();
        $analysis = \App\Models\AiLeadAnalysis::sole();

        $this->actingAs($user)->get("/user/ai-outreach/analyses/{$analysis->id}")
            ->assertOk()
            ->assertSee('Link tracking is on for 2 link(s)')
            ->assertSee('https://pixelstudio.example/pricing')
            ->assertSee('name="tracking_shown" value="1"', false);

        // Form shown before tracking was switched on: refused, nothing queued
        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload(['tracking_shown' => '0']))
            ->assertSessionHasErrors('send');
        $this->assertSame(0, OutboundEmail::count());
        $this->assertCount(0, $this->requestsTo(self::SEND_URL));
    }

    public function test_tracking_setup_failure_queues_and_sends_nothing(): void
    {
        $user = $this->trackingSender();
        $this->connect($user);
        $this->enableTracking($user);
        $draft = $this->draftFor($user, ['body' => self::LINK_BODY]);
        $this->app->instance(LinkTracker::class, new class extends LinkTracker {
            public function prepare(OutboundEmail $email, string $body, array $exclude = []): array
            {
                throw new \RuntimeException('db down');
            }
        });

        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload(['tracking_shown' => '1']))
            ->assertSessionHasErrors('send');

        $this->assertSame(0, OutboundEmail::count());
        $this->assertSame(0, EmailTrackingLink::count());
        $this->assertCount(0, $this->requestsTo(self::SEND_URL));
    }

    public function test_double_submit_and_retry_reuse_the_same_tracked_message(): void
    {
        $user = $this->trackingSender();
        $this->connect($user);
        $this->enableTracking($user);
        $draft = $this->draftFor($user, ['body' => self::LINK_BODY]);
        $payload = $this->sendPayload(['tracking_shown' => '1']);

        // Tests run the mail queue synchronously: the first send is rejected by Gmail (known failure)
        $this->respond(self::SEND_URL, [['error' => ['code' => 400, 'message' => 'Invalid To header', 'status' => 'INVALID_ARGUMENT']], 400]);
        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $payload);
        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $payload);
        $this->assertSame(1, OutboundEmail::count());
        $this->assertSame(2, EmailTrackingLink::count());
        $this->assertCount(1, $this->requestsTo(self::SEND_URL));

        $email = OutboundEmail::sole();
        $this->assertSame('failed', $email->status);
        $this->assertFalse($email->outcome_unknown);
        $sentBody = $email->sent_body;

        // Retry: same links, same body, no new link rows
        $this->gmailAccepts();
        $this->actingAs($user)->post("/user/email-sending/emails/{$email->id}/retry")->assertSessionHas('success');

        $this->assertSame('sent', $email->fresh()->status);
        $this->assertSame($sentBody, $email->fresh()->sent_body);
        $this->assertSame(2, EmailTrackingLink::count());
        $this->assertSame(2, EmailTrackingEvent::where('event_type', EmailTrackingEvent::QUEUED)->count());
    }

    public function test_html_bodies_rewrite_only_eligible_href_attributes(): void
    {
        $user = $this->trackingSender();
        $this->connect($user);
        $email = OutboundEmail::create([
            'owner_user_id' => $user->id, 'user_id' => $user->id, 'idempotency_key' => str_repeat('a', 32),
            'from_email' => 'ayesha@gmail.com', 'to_email' => 'x@example.com', 'subject' => 's', 'body' => 'b', 'status' => 'sent',
        ]);
        $html = '<p>See <a href="https://good.example/a?x=1&amp;y=2">our work</a>, '
            . '<a href=\'https://example.com/unsubscribe\'>unsubscribe</a> and <a href="mailto:a@b.example">mail</a>.</p>';

        $result = app(LinkTracker::class)->prepareHtml($email, $html);

        $this->assertSame(1, $result['count']);
        $this->assertSame('https://good.example/a?x=1&y=2', EmailTrackingLink::sole()->original_url);
        $this->assertStringContainsString('>our work</a>', $result['body']);
        $this->assertStringContainsString("href='https://example.com/unsubscribe'", $result['body']);
        $this->assertStringContainsString('href="mailto:a@b.example"', $result['body']);
        $this->assertMatchesRegularExpression('~href="http://localhost/r/[A-Za-z0-9_-]{32}"~', $result['body']);
    }

    public function test_tracked_message_is_valid_mime(): void
    {
        [, $email] = $this->trackedEmail();
        $mime = $this->mimeOf($this->requestsTo(self::SEND_URL)[0]);

        [$headers, $body] = explode("\r\n\r\n", $mime, 2);
        $this->assertStringContainsString("MIME-Version: 1.0\r\n", $headers);
        $this->assertStringContainsString('Content-Type: text/plain; charset=UTF-8', $headers);
        $this->assertStringContainsString('Content-Transfer-Encoding: base64', $headers);
        foreach (explode("\r\n", $body) as $line) {
            $this->assertLessThanOrEqual(76, strlen($line));
            $this->assertMatchesRegularExpression('~^[A-Za-z0-9+/=]*$~', $line);
        }
        $this->assertSame($email->rfc_message_id, trim(explode("\r\n", explode('Message-ID: ', $headers)[1])[0]));
    }

    public function test_tracking_settings_need_the_plan_and_an_accepted_disclosure(): void
    {
        $noPlan = $this->sender();
        $this->actingAs($noPlan)->put('/user/email-sending/tracking-settings', ['link_tracking_enabled' => '1', 'retention_days' => 90, 'accept_disclosure' => '1'])
            ->assertSessionHas('error');
        $this->assertFalse(EmailTrackingSetting::for($noPlan)->exists);

        $user = $this->trackingSender();
        $this->actingAs($user)->put('/user/email-sending/tracking-settings', ['link_tracking_enabled' => '1', 'retention_days' => 90])
            ->assertSessionHasErrors('accept_disclosure');
        $this->actingAs($user)->put('/user/email-sending/tracking-settings', ['link_tracking_enabled' => '1', 'retention_days' => 999, 'accept_disclosure' => '1'])
            ->assertSessionHasErrors('retention_days');

        $this->actingAs($user)->put('/user/email-sending/tracking-settings', ['link_tracking_enabled' => '1', 'retention_days' => 30, 'accept_disclosure' => '1'])
            ->assertSessionHas('success');
        $setting = EmailTrackingSetting::for($user);
        $this->assertTrue($setting->link_tracking_enabled);
        $this->assertSame(30, $setting->retention_days);
        $this->assertNotNull($setting->disclosure_accepted_at);

        $this->actingAs($user)->put('/user/email-sending/tracking-settings', ['link_tracking_enabled' => '0', 'retention_days' => 30])
            ->assertSessionHas('success');
        $this->assertFalse(EmailTrackingSetting::for($user)->link_tracking_enabled);

        $this->actingAs($user)->get('/user/email-sending')->assertOk()->assertSee('Track link clicks in emails I send');
    }
}
