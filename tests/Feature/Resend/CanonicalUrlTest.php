<?php

namespace Tests\Feature\Resend;

use App\Models\OutboundEmail;
use App\Services\Gmail\OutboundEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Phase 5.2: the app answers on more than one hostname (v2.customernearme.com and the
 * main domain's /v2 folder). Links that leave the app (email links, the Resend webhook)
 * must always use APP_URL, whatever host the user happened to browse on.
 */
class CanonicalUrlTest extends ResendTestCase
{
    private const PUBLIC = 'https://v2.customernearme.example';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => self::PUBLIC]);
    }

    private function browseOn(string $root): void
    {
        app('url')->setRequest(Request::create($root . '/user/email-sending'));
    }

    public function test_resend_webhook_endpoint_uses_app_url_even_from_another_host(): void
    {
        $user = $this->resendUser();
        [$integration] = $this->connectResend($user);
        [$config] = $this->webhookFor($integration);

        $this->actingAs($user)->get('https://other-host.example/user/email-sending/resend')->assertSee(self::PUBLIC . '/webhooks/resend/', false);
        $this->browseOn('http://other-host.example');

        $this->assertSame(self::PUBLIC . '/webhooks/resend/' . $config->endpoint_token, $config->endpointUrl());
        $this->assertSame('http://other-host.example/x', url('/x'), 'Other URLs keep using the request host and scheme');
    }

    public function test_unsubscribe_link_is_signed_for_app_url_and_still_valid(): void
    {
        $user = $this->resendUser();
        $email = OutboundEmail::create([
            'owner_user_id' => $user->id, 'user_id' => $user->id, 'provider' => 'gmail', 'idempotency_key' => (string) Str::uuid(),
            'from_email' => 'ayesha@gmail.com', 'to_email' => 'info@smiledental.example', 'subject' => 's', 'body' => 'b',
            'status' => 'sent', 'queued_at' => now(), 'sent_at' => now(),
        ]);
        $this->browseOn('http://other-host.example');

        $link = app(OutboundEmailService::class)->unsubscribeUrl($email);

        $this->assertStringStartsWith(self::PUBLIC . '/email/unsubscribe/' . $email->id . '?', $link);
        $this->post($link)->assertOk();
        $this->assertTrue(\App\Models\EmailSuppression::isSuppressed($user->id, 'info@smiledental.example'));
        // A link pointing at the other host with the same signature is rejected
        $this->post(str_replace(self::PUBLIC, 'https://other-host.example', $link))->assertForbidden();
    }

    public function test_tracked_links_point_at_app_url(): void
    {
        [, $email] = $this->trackedEmail();

        $this->assertMatchesRegularExpression('~' . preg_quote(self::PUBLIC, '~') . '/r/[A-Za-z0-9_-]{32}~', $email->sent_body);
        $this->assertStringContainsString(self::PUBLIC . '/email/unsubscribe/', $email->sent_body);
    }

    public function test_misconfigured_app_url_falls_back_to_the_request_host(): void
    {
        config(['app.url' => 'not a url']);
        $this->browseOn('https://v2.customernearme.example');

        $this->assertSame('https://v2.customernearme.example/r/abc', \App\Support\CanonicalUrl::make(fn () => url('/r/abc')));
    }
}
