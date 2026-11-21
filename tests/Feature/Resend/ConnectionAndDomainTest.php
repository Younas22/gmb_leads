<?php

namespace Tests\Feature\Resend;

use App\Models\EmailCampaign;
use App\Models\ResendDomain;
use App\Models\ResendIntegration;
use App\Models\ResendSender;
use App\Models\ResendWebhookConfig;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** Phase 5: BYOK connection, key safety, domains, senders, webhook setup. */
class ConnectionAndDomainTest extends ResendTestCase
{
    private function domainsApi(array $list = null, array $details = null): void
    {
        $list ??= [['id' => 'dom_1', 'name' => 'mail.pixelstudio.example', 'status' => 'pending', 'region' => 'eu-west-1']];
        $this->respondWith(self::API . '/domains', fn (HttpRequest $r) => Http::response(['object' => 'list', 'has_more' => false, 'data' => $list]));
        $this->respondWith(self::API . '/domains/dom_1', fn () => Http::response($details ?? $this->domainPayload('dom_1', 'mail.pixelstudio.example', 'pending')));
    }

    public function test_connect_validates_the_key_and_stores_it_encrypted(): void
    {
        $user = $this->resendUser();
        $this->domainsApi();

        $this->actingAs($user)->post('/user/email-sending/resend/connect', ['api_key' => self::KEY])->assertSessionHas('success');

        $integration = ResendIntegration::sole();
        $this->assertSame(self::KEY, $integration->api_key);
        $this->assertNotSame(self::KEY, DB::table('resend_integrations')->value('api_key'), 'Encrypted at rest');
        $this->assertStringNotContainsString(self::KEY, json_encode($integration->toArray()));
        $this->assertSame('active', $integration->status);

        // Every call used the customer's key and a User-Agent, never the platform key
        foreach ($this->resendCalls() as $call) {
            $this->assertSame('Bearer ' . self::KEY, $call->header('Authorization')[0]);
            $this->assertNotEmpty($call->header('User-Agent')[0]);
            $this->assertStringNotContainsString('PLATFORM', $call->header('Authorization')[0]);
        }

        // DNS records shown exactly as Resend returned them; key masked
        $page = $this->actingAs($user)->get('/user/email-sending/resend')->assertOk();
        $page->assertSee('p=MIGfMA0GCSqDKIMtestvalue')->assertSee('resend._domainkey.mail')->assertSee('Verifying')
            ->assertSee('re_••••••••wxyz')->assertDontSee(self::KEY);
    }

    public function test_invalid_restricted_and_malformed_keys_are_refused(): void
    {
        $user = $this->resendUser();

        $this->actingAs($user)->post('/user/email-sending/resend/connect', ['api_key' => 'sk-not-resend'])->assertSessionHasErrors('api_key');
        $this->assertCount(0, $this->resendCalls());

        $this->respond(self::API . '/domains', [['statusCode' => 401, 'name' => 'restricted_api_key', 'message' => 'This API key is restricted to only send emails.'], 401]);
        $this->actingAs($user)->post('/user/email-sending/resend/connect', ['api_key' => self::KEY]);
        $this->assertStringContainsString('Full access', session('errors')->first('api_key'));

        $this->respond(self::API . '/domains', [['statusCode' => 403, 'name' => 'validation_error', 'message' => 'API key is invalid'], 401]);
        $this->actingAs($user)->post('/user/email-sending/resend/connect', ['api_key' => self::KEY])->assertSessionHasErrors('api_key');

        $this->assertSame(0, ResendIntegration::count());
    }

    public function test_health_check_detects_a_revoked_key(): void
    {
        $user = $this->resendUser();
        [$integration] = $this->connectResend($user);
        $this->respond(self::API . '/domains', [['name' => 'restricted_api_key', 'message' => 'API key is not active'], 403]);

        $this->actingAs($user)->post('/user/email-sending/resend/check')->assertSessionHas('error');

        $this->assertSame(ResendIntegration::INVALID, $integration->fresh()->status);
        $this->assertFalse($integration->fresh()->isUsable());
    }

    public function test_rotating_to_another_resend_account_resets_domains_and_webhook(): void
    {
        $user = $this->resendUser();
        [$integration] = $this->connectResend($user);
        $this->webhookFor($integration);
        $this->respondWith(self::API . '/domains', fn () => Http::response(['object' => 'list', 'data' => [['id' => 'dom_other', 'name' => 'news.other.example', 'status' => 'verified']]]));
        $this->respondWith(self::API . '/domains/dom_other', fn () => Http::response($this->domainPayload('dom_other', 'news.other.example')));

        $this->actingAs($user)->post('/user/email-sending/resend/connect', ['api_key' => 're_secondKey_99998888'])->assertSessionHas('success');

        $this->assertSame(['dom_other'], ResendDomain::pluck('provider_domain_id')->all());
        $this->assertSame(0, ResendSender::count());
        $this->assertSame(ResendWebhookConfig::NEEDS_SETUP, ResendWebhookConfig::sole()->status);
        $this->assertNull(ResendWebhookConfig::sole()->signing_secret);
    }

    public function test_only_the_account_owner_manages_settings_and_tenants_are_isolated(): void
    {
        $company = $this->resendUser([], ['user_type' => 'company']);
        $member = $this->makeUser(['company_id' => $company->id]);
        [, $domain] = $this->connectResend($company);

        $this->actingAs($member)->get('/user/email-sending/resend')->assertOk()->assertSee('Only the account owner');
        $this->actingAs($member)->post('/user/email-sending/resend/connect', ['api_key' => self::KEY])->assertForbidden();
        $this->actingAs($member)->post('/user/email-sending/resend/disconnect')->assertForbidden();

        $other = $this->resendUser();
        $this->actingAs($other)->post("/user/email-sending/resend/domains/{$domain->id}/verify")->assertNotFound();
        $this->actingAs($other)->get('/user/email-sending/resend')->assertOk()->assertDontSee('mail.pixelstudio.example');
    }

    public function test_disconnect_deletes_the_key_but_not_while_a_campaign_is_scheduled(): void
    {
        $user = $this->resendUser();
        [$integration] = $this->connectResend($user);
        $campaign = EmailCampaign::create(['owner_user_id' => $user->id, 'resend_integration_id' => $integration->id, 'name' => 'N', 'subject' => 's', 'body' => 'b', 'status' => 'scheduled']);

        $this->actingAs($user)->post('/user/email-sending/resend/disconnect')->assertSessionHas('error');
        $this->assertNotNull($integration->fresh()->api_key);

        $campaign->update(['status' => 'cancelled']);
        $this->actingAs($user)->post('/user/email-sending/resend/disconnect')->assertSessionHas('success');
        $this->assertNull($integration->fresh()->api_key);
        $this->assertSame('disconnected', $integration->fresh()->status);
    }

    public function test_domains_are_added_verified_and_unverified_ones_cannot_get_senders(): void
    {
        $user = $this->resendUser();
        [$integration] = $this->connectResend($user);
        $this->respondWith(self::API . '/domains', fn (HttpRequest $r) => Http::response($this->domainPayload('dom_2', 'news.pixelstudio.example', 'not_started')));

        $this->actingAs($user)->post('/user/email-sending/resend/domains', ['domain' => 'news.pixelstudio.example', 'region' => 'eu-west-1'])->assertSessionHas('success');
        $this->assertSame(['name' => 'news.pixelstudio.example', 'region' => 'eu-west-1'], $this->resendCalls('POST', '/domains')[0]->data());
        $new = ResendDomain::where('provider_domain_id', 'dom_2')->sole();
        $this->assertSame('Pending', $new->statusLabel());
        $this->assertFalse($new->canSend());

        $this->actingAs($user)->post('/user/email-sending/resend/senders', ['domain_id' => $new->id, 'from_name' => 'A', 'local_part' => 'hi'])->assertSessionHasErrors('sender');
        $this->actingAs($user)->post('/user/email-sending/resend/domains', ['domain' => 'not a domain', 'region' => 'eu-west-1'])->assertSessionHasErrors('domain');

        // Verify: POST verify, then read the result
        $this->respondWith(self::API . '/domains/dom_2/verify', fn () => Http::response(['object' => 'domain', 'id' => 'dom_2']));
        $this->respondWith(self::API . '/domains/dom_2', fn () => Http::response($this->domainPayload('dom_2', 'news.pixelstudio.example', 'verified')));
        $this->actingAs($user)->post("/user/email-sending/resend/domains/{$new->id}/verify")->assertSessionHas('success');
        $this->assertTrue($new->fresh()->canSend());

        $this->actingAs($user)->post('/user/email-sending/resend/senders', ['domain_id' => $new->id, 'from_name' => 'News', 'local_part' => 'news'])->assertSessionHas('success');
        $this->assertDatabaseHas('resend_senders', ['from_email' => 'news@news.pixelstudio.example']);
    }

    public function test_provider_tracking_changes_need_confirmation_and_the_plan(): void
    {
        $user = $this->resendUser(['email_tracking_enabled' => '0']);
        [, $domain] = $this->connectResend($user);

        $this->actingAs($user)->put("/user/email-sending/resend/domains/{$domain->id}/tracking", ['open_tracking' => '1', 'click_tracking' => '1'])->assertSessionHasErrors('confirm');
        $this->actingAs($user)->put("/user/email-sending/resend/domains/{$domain->id}/tracking", ['open_tracking' => '1', 'click_tracking' => '0', 'confirm' => '1'])->assertSessionHas('error');
        $this->assertCount(0, $this->resendCalls('PATCH'));

        $tracking = $this->resendUser();
        [, $d2] = $this->connectResend($tracking);
        $this->respondWith(self::API . '/domains/dom_1', fn () => Http::response(['object' => 'domain', 'id' => 'dom_1']));
        $this->actingAs($tracking)->put("/user/email-sending/resend/domains/{$d2->id}/tracking", ['open_tracking' => '0', 'click_tracking' => '1', 'confirm' => '1'])->assertSessionHas('success');
        // No tracking subdomain yet: "links" is added, because Resend only tracks with one
        $this->assertSame(['open_tracking' => false, 'click_tracking' => true, 'tracking_subdomain' => 'links'], $this->resendCalls('PATCH')[0]->data());
    }

    public function test_webhook_can_be_created_through_the_api_with_confirmation(): void
    {
        $user = $this->resendUser();
        $this->connectResend($user);
        $secret = 'whsec_' . base64_encode(random_bytes(24));
        $this->respondWith(self::API . '/webhooks', fn () => Http::response(['object' => 'webhook', 'id' => 'wh_1', 'signing_secret' => $secret]));

        $this->actingAs($user)->post('/user/email-sending/resend/webhook')->assertSessionHasErrors('confirm');
        $this->actingAs($user)->post('/user/email-sending/resend/webhook', ['confirm' => '1'])->assertSessionHas('success');

        $config = ResendWebhookConfig::sole();
        $this->assertSame('wh_1', $config->provider_webhook_id);
        $this->assertSame($secret, $config->signing_secret);
        $this->assertNotSame($secret, DB::table('resend_webhook_configs')->value('signing_secret'));
        $body = $this->resendCalls('POST', '/webhooks')[0]->data();
        $this->assertSame(route('customer-resend.webhook', $config->endpoint_token), $body['endpoint']);
        $this->assertContains('email.bounced', $body['events']);
        $this->actingAs($user)->get('/user/email-sending/resend')->assertDontSee($secret);

        // Manual secret must look like a Svix secret
        $this->actingAs($user)->put('/user/email-sending/resend/webhook/secret', ['signing_secret' => 'nope'])->assertSessionHasErrors('signing_secret');
    }

    public function test_resend_is_off_by_default_for_existing_plans(): void
    {
        foreach (['resend_connection_enabled', 'resend_single_sending_enabled', 'resend_campaigns_enabled'] as $key) {
            $this->assertFalse(config("ai.feature_defaults.{$key}"));
        }
        $user = $this->trackingSender();

        $this->actingAs($user)->get('/user/email-sending/resend')->assertRedirect(route('user.subscription'));
        $this->actingAs($user)->get('/user/campaigns')->assertRedirect(route('user.subscription'));
        $this->actingAs($user)->get('/user/email-sending')->assertOk()->assertDontSee('fa-envelope-circle-check', false);
    }
}
