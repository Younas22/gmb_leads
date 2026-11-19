<?php

namespace Tests\Feature\Resend;

use App\Models\EmailFollowUp;
use App\Models\EmailSuppression;
use App\Models\EmailTrackingEvent;
use App\Models\MarketingContact;
use App\Models\OutboundEmail;
use App\Models\ResendWebhookConfig;
use App\Models\ResendWebhookReceipt;
use App\Models\User;
use Illuminate\Support\Str;

/** Phase 5: signed webhooks, replay protection, tenant isolation, event mapping. */
class WebhookTest extends ResendTestCase
{
    private function resendEmail(User $owner, array $attrs = []): OutboundEmail
    {
        $contact = $this->contact($owner, $attrs['to_email'] ?? 'info@smiledental.example');

        return OutboundEmail::create(array_merge([
            'owner_user_id' => $owner->id, 'provider' => 'resend', 'user_id' => $owner->id, 'marketing_contact_id' => $contact->id,
            'saved_lead_id' => $this->leadFor($owner)->id,
            'idempotency_key' => (string) Str::uuid(), 'from_email' => 'hello@mail.pixelstudio.example', 'to_email' => 'info@smiledental.example',
            'subject' => 'Hi', 'body' => 'Body', 'status' => 'sent', 'provider_message_id' => 're_msg_1', 'queued_at' => now(), 'sent_at' => now(),
        ], $attrs));
    }

    private function scenario(): array
    {
        $user = $this->resendUser();
        [$integration] = $this->connectResend($user);
        [$config, $secret] = $this->webhookFor($integration);

        return [$user, $config, $secret, $this->resendEmail($user)];
    }

    public function test_valid_signed_delivery_event_is_recorded(): void
    {
        [, $config, $secret, $email] = $this->scenario();

        $this->postWebhook($config, $secret, $this->event('email.delivered', ['email_id' => 're_msg_1', 'to' => ['info@smiledental.example']]))->assertOk();

        $email->refresh();
        $this->assertSame('delivered', $email->delivery_status);
        $this->assertNotNull($email->delivered_at);
        $event = EmailTrackingEvent::where('event_type', EmailTrackingEvent::DELIVERED)->sole();
        $this->assertSame(EmailTrackingEvent::SOURCE_RESEND_WEBHOOK, $event->event_source);
        $this->assertSame('resend', $event->provider);
        $this->assertSame('processed', ResendWebhookReceipt::sole()->status);
        $this->assertSame(1, $config->fresh()->verified_count);
    }

    public function test_bad_signature_stale_timestamp_and_unknown_endpoint_are_rejected(): void
    {
        [, $config, $secret, $email] = $this->scenario();
        $payload = $this->event('email.delivered', ['email_id' => 're_msg_1']);

        $this->postWebhook($config, 'whsec_' . base64_encode(random_bytes(32)), $payload)->assertStatus(401);
        $this->postWebhook($config, $secret, $payload, null, time() - 3600)->assertStatus(401);
        $this->postWebhook($config, $secret, $payload, null, null, 'v1,AAAA')->assertStatus(401);
        $this->call('POST', '/webhooks/resend/' . $config->endpoint_token, [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload))->assertStatus(401);

        $other = new ResendWebhookConfig(['endpoint_token' => Str::random(48)]);
        $this->postWebhook($other, $secret, $payload)->assertNotFound();

        $this->assertSame(0, ResendWebhookReceipt::count());
        $this->assertNull($email->fresh()->delivery_status);
        $this->assertSame(4, $config->fresh()->rejected_count);
        $this->assertSame('missing_headers', $config->fresh()->last_failure_reason);
    }

    public function test_replayed_svix_id_is_processed_only_once(): void
    {
        [, $config, $secret] = $this->scenario();
        $payload = $this->event('email.opened', ['email_id' => 're_msg_1']);

        $this->postWebhook($config, $secret, $payload, 'msg_same')->assertOk();
        $this->postWebhook($config, $secret, $payload, 'msg_same')->assertOk()->assertJson(['duplicate' => true]);

        $this->assertSame(1, ResendWebhookReceipt::count());
        $this->assertSame(1, EmailTrackingEvent::where('event_type', EmailTrackingEvent::PROVIDER_OPENED)->count());
    }

    public function test_another_tenants_webhook_cannot_touch_this_tenants_messages(): void
    {
        [, , , $aliceEmail] = $this->scenario();
        $bob = $this->resendUser();
        [$bobIntegration] = $this->connectResend($bob);
        $bobIntegration->domains()->update(['provider_domain_id' => 'dom_b']);
        [$bobConfig, $bobSecret] = $this->webhookFor($bobIntegration);

        // Bob's (validly signed) webhook names Alice's Resend email id
        $this->postWebhook($bobConfig, $bobSecret, $this->event('email.bounced', ['email_id' => 're_msg_1', 'to' => ['info@smiledental.example'], 'bounce' => ['type' => 'Permanent']]))->assertOk();

        $this->assertNull($aliceEmail->fresh()->delivery_status);
        $this->assertFalse(EmailSuppression::isSuppressed($aliceEmail->owner_user_id, 'info@smiledental.example'));
        $this->assertSame(0, EmailTrackingEvent::where('event_type', EmailTrackingEvent::BOUNCED)->count());
    }

    public function test_out_of_order_events_never_move_state_backwards(): void
    {
        [, $config, $secret, $email] = $this->scenario();

        $this->postWebhook($config, $secret, $this->event('email.bounced', ['email_id' => 're_msg_1', 'bounce' => ['type' => 'Transient', 'subType' => 'General']], now()->toIso8601String()));
        $this->postWebhook($config, $secret, $this->event('email.sent', ['email_id' => 're_msg_1'], now()->subMinutes(5)->toIso8601String()));
        $this->postWebhook($config, $secret, $this->event('email.delivery_delayed', ['email_id' => 're_msg_1'], now()->subMinutes(4)->toIso8601String()));

        $this->assertSame('bounced', $email->fresh()->delivery_status);
        $this->assertFalse(EmailSuppression::isSuppressed($email->owner_user_id, $email->to_email), 'Transient bounces do not suppress');
        $this->assertSame(3, EmailTrackingEvent::where('provider', 'resend')->where('event_source', 'resend_webhook')->count());
    }

    public function test_hard_bounce_and_complaint_suppress_and_cancel_follow_ups(): void
    {
        [$user, $config, $secret, $email] = $this->scenario();
        EmailFollowUp::create(['owner_user_id' => $user->id, 'saved_lead_id' => $email->saved_lead_id, 'due_date' => today()->addDay(), 'status' => 'open']);

        $this->postWebhook($config, $secret, $this->event('email.bounced', ['email_id' => 're_msg_1', 'bounce' => ['type' => 'Permanent', 'subType' => 'General']]));

        $this->assertSame('bounced', EmailSuppression::sole()->reason);
        $this->assertNotNull(MarketingContact::sole()->bounced_at);
        $this->assertSame('cancelled', EmailFollowUp::sole()->status);
        $this->assertFalse(MarketingContact::sole()->isSendable());

        $second = $this->resendEmail($user, ['to_email' => 'owner@clinic.example', 'provider_message_id' => 're_msg_2']);
        $this->postWebhook($config, $secret, $this->event('email.complained', ['email_id' => 're_msg_2']));
        $this->assertNotNull($second->fresh()->complained_at);
        $this->assertTrue(EmailSuppression::isSuppressed($user->id, 'owner@clinic.example'));
        $this->assertSame('complaint', EmailSuppression::where('email', 'owner@clinic.example')->sole()->reason);
    }

    public function test_events_arriving_before_the_row_is_finalised_are_retried(): void
    {
        [, $config, $secret, $email] = $this->scenario();
        $email->forceFill(['provider_message_id' => null])->save();

        $this->postWebhook($config, $secret, $this->event('email.delivered', ['email_id' => 're_msg_late']))->assertOk();
        $this->assertSame('unmatched', ResendWebhookReceipt::sole()->status);

        $email->forceFill(['provider_message_id' => 're_msg_late'])->save();
        $this->artisan('resend:maintain')->assertSuccessful();

        $this->assertSame('processed', ResendWebhookReceipt::sole()->status);
        $this->assertSame('delivered', $email->fresh()->delivery_status);
    }

    public function test_opens_and_clicks_need_the_tracking_feature_and_never_count_unsubscribe_links(): void
    {
        $user = $this->resendUser(['email_tracking_enabled' => '0']);
        [$integration] = $this->connectResend($user);
        [$config, $secret] = $this->webhookFor($integration);
        $this->resendEmail($user);

        $this->postWebhook($config, $secret, $this->event('email.opened', ['email_id' => 're_msg_1']));
        $this->postWebhook($config, $secret, $this->event('email.clicked', ['email_id' => 're_msg_1', 'click' => ['link' => 'https://pixelstudio.example', 'ipAddress' => '1.2.3.4', 'userAgent' => 'X']]));
        $this->assertSame(0, EmailTrackingEvent::whereIn('event_type', [EmailTrackingEvent::PROVIDER_OPENED, EmailTrackingEvent::PROVIDER_CLICKED])->count());

        [$u2, $c2, $s2] = $this->scenario();
        $this->postWebhook($c2, $s2, $this->event('email.clicked', ['email_id' => 're_msg_1', 'click' => ['link' => 'https://x.example/unsubscribe?id=1']]));
        $this->postWebhook($c2, $s2, $this->event('email.clicked', ['email_id' => 're_msg_1', 'click' => ['link' => 'https://pixelstudio.example/work', 'ipAddress' => '1.2.3.4', 'userAgent' => 'Chrome']]));

        $click = EmailTrackingEvent::where('event_type', EmailTrackingEvent::PROVIDER_CLICKED)->sole();
        $this->assertSame('https://pixelstudio.example/work', $click->metadata['link']);
        $this->assertStringNotContainsString('1.2.3.4', json_encode(ResendWebhookReceipt::all()->toArray()));
        $this->assertSame(0, EmailTrackingEvent::where('event_type', EmailTrackingEvent::LINK_CLICK)->count(), 'Provider clicks are not app link requests');
    }

    public function test_resend_unsubscribe_withdraws_the_contact(): void
    {
        [$user, $config, $secret] = $this->scenario();

        $this->postWebhook($config, $secret, ['type' => 'contact.updated', 'created_at' => now()->toIso8601String(), 'data' => ['id' => 'c_1', 'email' => 'Info@SmileDental.example', 'unsubscribed' => true]]);

        $this->assertTrue(EmailSuppression::isSuppressed($user->id, 'info@smiledental.example'));
        $contact = MarketingContact::sole();
        $this->assertSame('withdrawn', $contact->consent_status);
        $this->assertDatabaseHas('marketing_consent_logs', ['contact_id' => $contact->id, 'action' => 'unsubscribed_via_resend']);
    }

    public function test_platform_resend_webhook_route_is_untouched_and_separate(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes());
        $platform = $routes->first(fn ($r) => $r->getName() === 'resend.webhook');
        $customer = $routes->first(fn ($r) => $r->getName() === 'customer-resend.webhook');

        $this->assertNotNull($customer);
        $this->assertSame('webhooks/resend/{token}', $customer->uri());
        if ($platform) {
            $this->assertNotSame($platform->uri(), $customer->uri());
        }
        $this->assertSame('re_PLATFORM_secret_never_used', config('services.resend.key'));
    }
}
