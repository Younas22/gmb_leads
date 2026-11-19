<?php

namespace Tests\Feature\EmailSending;

use App\Models\ConnectedEmailAccount;
use App\Models\EmailAuditLog;
use App\Models\OutboundEmail;
use App\Services\Gmail\GmailOAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class GmailOAuthTest extends GmailTestCase
{
    /** Starts the flow and returns [state, verifier] from the session. */
    private function startConnect($user): array
    {
        $response = $this->actingAs($user)->post('/user/email-sending/google/connect');
        $response->assertRedirect();

        $saved = session('gmail_oauth');
        $this->assertIsArray($saved);

        return [$saved['state'], $saved['verifier'], $response->headers->get('Location')];
    }

    public function test_connect_redirects_to_google_with_send_only_scope_state_and_pkce(): void
    {
        $user = $this->sender();
        [$state, $verifier, $location] = $this->startConnect($user);

        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $location);
        parse_str(parse_url($location, PHP_URL_QUERY), $q);

        $scopes = explode(' ', $q['scope']);
        sort($scopes);
        $this->assertSame(['email', 'https://www.googleapis.com/auth/gmail.send', 'openid'], $scopes);
        foreach ($scopes as $s) {
            $this->assertStringNotContainsString('gmail.readonly', $s);
            $this->assertStringNotContainsString('gmail.modify', $s);
            $this->assertNotSame('https://mail.google.com/', $s);
        }
        $this->assertSame($state, $q['state']);
        $this->assertSame('S256', $q['code_challenge_method']);
        $this->assertSame(GmailOAuth::challenge($verifier), $q['code_challenge']);
        $this->assertSame('offline', $q['access_type']);
        $this->assertSame('code', $q['response_type']);
        $this->assertSame(route('user.email-sending.callback'), $q['redirect_uri']);
        $this->assertGreaterThanOrEqual(43, strlen($verifier));
        $this->assertSame([], $this->google, 'Connecting must not call Google server-side');
    }

    public function test_successful_callback_stores_encrypted_tokens_and_shows_address(): void
    {
        $user = $this->sender();
        [$state, $verifier] = $this->startConnect($user);
        $this->respond(self::TOKEN_URL, $this->tokenResponse());

        $this->get("/user/email-sending/google/callback?state={$state}&code=auth-code-1")
            ->assertRedirect('/user/email-sending')
            ->assertSessionHas('success');

        $account = ConnectedEmailAccount::where('user_id', $user->id)->sole();
        $this->assertSame('ayesha@gmail.com', $account->email);
        $this->assertSame('google-sub-123', $account->google_sub);
        $this->assertSame('active', $account->status);
        $this->assertSame('ya29.access-token-1', $account->access_token);
        $this->assertSame('1//refresh-token-1', $account->refresh_token);
        $this->assertTrue($account->canSend());

        // Tokens are encrypted at rest
        $row = DB::table('connected_email_accounts')->where('id', $account->id)->first();
        $this->assertStringNotContainsString('refresh-token-1', $row->refresh_token);
        $this->assertStringNotContainsString('access-token-1', $row->access_token);
        $this->assertArrayNotHasKey('refresh_token', $account->toArray());

        // The exchange used PKCE and the configured client
        $exchange = $this->requestsTo(self::TOKEN_URL)[0]->data();
        $this->assertSame('authorization_code', $exchange['grant_type']);
        $this->assertSame($verifier, $exchange['code_verifier']);
        $this->assertSame('auth-code-1', $exchange['code']);

        // State is single use
        $this->assertNull(session('gmail_oauth'));
        $this->get("/user/email-sending/google/callback?state={$state}&code=auth-code-1")->assertSessionHas('error');
        $this->assertCount(1, $this->requestsTo(self::TOKEN_URL));

        $this->assertTrue(EmailAuditLog::where('action', 'gmail_connected')->exists());
        $this->get('/user/email-sending')->assertOk()->assertSee('ayesha@gmail.com')->assertSee('Disconnect');
    }

    public function test_callback_rejects_wrong_or_missing_state(): void
    {
        $user = $this->sender();
        $this->startConnect($user);

        $this->get('/user/email-sending/google/callback?state=forged&code=x')->assertSessionHas('error');
        $this->get('/user/email-sending/google/callback?code=x')->assertSessionHas('error');

        $this->assertSame([], $this->google);
        $this->assertSame(0, ConnectedEmailAccount::count());
        $this->assertTrue(EmailAuditLog::where('action', 'gmail_connect_rejected')->exists());
    }

    public function test_callback_rejects_state_started_by_another_user_or_expired(): void
    {
        $a = $this->sender();
        $b = $this->sender();
        [$state] = $this->startConnect($a);

        // Same session, different logged-in user (session fixation style)
        $this->actingAs($b)->get("/user/email-sending/google/callback?state={$state}&code=x")->assertSessionHas('error');

        [$state2] = $this->startConnect($a);
        $this->travel(11)->minutes();
        $this->get("/user/email-sending/google/callback?state={$state2}&code=x")->assertSessionHas('error');

        $this->assertSame([], $this->google);
        $this->assertSame(0, ConnectedEmailAccount::count());
    }

    public function test_user_denying_access_connects_nothing(): void
    {
        $user = $this->sender();
        [$state] = $this->startConnect($user);

        $this->get("/user/email-sending/google/callback?state={$state}&error=access_denied")
            ->assertSessionHas('error', 'Gmail was not connected because access was not granted.');
        $this->assertSame(0, ConnectedEmailAccount::count());
    }

    public function test_unticked_send_permission_is_rejected_and_token_revoked(): void
    {
        $user = $this->sender();
        [$state] = $this->startConnect($user);
        $this->respond(self::TOKEN_URL, $this->tokenResponse(['scope' => 'openid https://www.googleapis.com/auth/userinfo.email']));
        $this->respond(self::REVOKE_URL, [[], 200]);

        $this->get("/user/email-sending/google/callback?state={$state}&code=c")->assertSessionHas('error');

        $this->assertSame(0, ConnectedEmailAccount::count());
        $this->assertCount(1, $this->requestsTo(self::REVOKE_URL));
    }

    public function test_id_token_for_another_client_or_unverified_email_is_rejected(): void
    {
        $user = $this->sender();

        foreach ([['aud' => 'someone-else.apps.googleusercontent.com'], ['email_verified' => false], ['iss' => 'https://evil.example'], ['exp' => time() - 10]] as $bad) {
            [$state] = $this->startConnect($user);
            $this->respond(self::TOKEN_URL, $this->tokenResponse([], $bad));
            $this->get("/user/email-sending/google/callback?state={$state}&code=c")->assertSessionHas('error');
        }

        $this->assertSame(0, ConnectedEmailAccount::count());
    }

    public function test_missing_refresh_token_is_rejected(): void
    {
        $user = $this->sender();
        [$state] = $this->startConnect($user);
        $response = $this->tokenResponse();
        unset($response[0]['refresh_token']);
        $this->respond(self::TOKEN_URL, $response);

        $this->get("/user/email-sending/google/callback?state={$state}&code=c")->assertSessionHas('error');
        $this->assertSame(0, ConnectedEmailAccount::count());
    }

    public function test_token_endpoint_errors_are_handled_without_leaking_details(): void
    {
        $user = $this->sender();
        [$state] = $this->startConnect($user);
        $this->respond(self::TOKEN_URL, [['error' => 'invalid_grant', 'error_description' => 'secret detail'], 400]);

        $this->get("/user/email-sending/google/callback?state={$state}&code=c")
            ->assertSessionHas('error', fn ($m) => !str_contains($m, 'secret detail'));
        $this->assertSame(0, ConnectedEmailAccount::count());
    }

    public function test_disconnect_revokes_clears_tokens_and_cancels_pending_sends(): void
    {
        Queue::fake();
        $user = $this->sender();
        $account = $this->connect($user);
        $draft = $this->draftFor($user);
        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload())->assertSessionHas('success');
        $this->assertSame('pending', OutboundEmail::sole()->status);

        $this->respond(self::REVOKE_URL, [[], 200]);
        $this->post('/user/email-sending/google/disconnect')->assertSessionHas('success');

        $this->assertSame('1//refresh-valid', $this->requestsTo(self::REVOKE_URL)[0]->data()['token']);
        $account->refresh();
        $this->assertSame('disconnected', $account->status);
        $this->assertNull($account->refresh_token);
        $this->assertNull($account->access_token);
        $this->assertSame('cancelled', OutboundEmail::sole()->status);
        $this->assertTrue(EmailAuditLog::where('action', 'gmail_disconnected')->exists());
        $this->get('/user/email-sending')->assertSee('Connect Gmail');
    }

    public function test_disconnect_still_works_when_google_revoke_fails(): void
    {
        $user = $this->sender();
        $account = $this->connect($user);
        $this->respond(self::REVOKE_URL, [['error' => 'invalid_token'], 400]);

        $this->actingAs($user)->post('/user/email-sending/google/disconnect')->assertSessionHas('success');
        $this->assertSame('disconnected', $account->fresh()->status);
        $this->assertNull($account->fresh()->refresh_token);
    }

    public function test_gmail_connection_is_per_user_and_isolated(): void
    {
        $a = $this->sender();
        $b = $this->sender();
        $this->connect($a);

        $this->actingAs($b)->get('/user/email-sending')->assertOk()->assertDontSee('ayesha@gmail.com')->assertSee('Connect Gmail');
        $this->actingAs($b)->post('/user/email-sending/google/disconnect')->assertSessionHas('error');
        $this->assertSame('active', ConnectedEmailAccount::where('user_id', $a->id)->sole()->status);
    }

    public function test_feature_requires_plan_and_login(): void
    {
        $this->get('/user/email-sending')->assertRedirect();

        $user = $this->sender(['gmail_sending_enabled' => '0']);
        $this->actingAs($user)->get('/user/email-sending')->assertRedirect(route('user.subscription'));
        $this->actingAs($user)->post('/user/email-sending/google/connect')->assertRedirect(route('user.subscription'));
        $this->assertSame([], $this->google);
    }

    public function test_not_configured_server_does_not_start_oauth(): void
    {
        config(['gmail.client_id' => null, 'gmail.client_secret' => null]);
        $user = $this->sender();

        $this->actingAs($user)->post('/user/email-sending/google/connect')->assertRedirect('/user/email-sending')->assertSessionHas('error');
        $this->get('/user/email-sending')->assertSee('not configured');
    }
}
