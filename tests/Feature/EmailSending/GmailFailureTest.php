<?php

namespace Tests\Feature\EmailSending;

use App\Jobs\SendGmailMessage;
use App\Models\ConnectedEmailAccount;
use App\Models\EmailSendAttempt;
use App\Models\EmailSuppression;
use App\Models\OutboundEmail;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Queue;

class GmailFailureTest extends GmailTestCase
{
    private function sendOnce(array $accountAttrs = []): array
    {
        $user = $this->sender();
        $account = $this->connect($user, $accountAttrs);
        $draft = $this->draftFor($user);

        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload());

        return [$user, $account, $draft, OutboundEmail::sole()];
    }

    public function test_rate_limited_send_is_kept_pending_for_automatic_retry(): void
    {
        $this->respond(self::SEND_URL, [['error' => ['code' => 429, 'status' => 'RESOURCE_EXHAUSTED']], 429]);
        [, , , $email] = $this->sendOnce();

        $this->assertSame('pending', $email->status);
        $this->assertSame('rate_limited', $email->error_code);
        $this->assertSame('failed', EmailSendAttempt::sole()->result);

        // The next delivery of the job succeeds; Gmail was called twice in total
        $this->gmailAccepts('msg-2');
        $this->runJob($email->id);
        $this->assertSame('sent', $email->fresh()->status);
        $this->assertSame('msg-2', $email->fresh()->gmail_message_id);
        $this->assertCount(2, $this->requestsTo(self::SEND_URL));
    }

    public function test_connection_refused_is_safe_to_retry(): void
    {
        $this->respondWith(self::SEND_URL, new ConnectionException('cURL error 7: Failed to connect to gmail.googleapis.com port 443'));
        [, , , $email] = $this->sendOnce();

        $this->assertSame('pending', $email->status);
        $this->assertSame('connect_failed', $email->error_code);
        $this->assertFalse($email->outcome_unknown);
    }

    public function test_permanent_rejection_fails_clearly_and_releases_the_draft(): void
    {
        $this->respond(self::SEND_URL, [['error' => ['code' => 400, 'message' => 'Invalid To header']], 400]);
        [$user, , $draft, $email] = $this->sendOnce();

        $this->assertSame('failed', $email->status);
        $this->assertFalse($email->outcome_unknown);
        $this->assertSame('invalid_request', $email->error_code);
        $this->assertNull($email->draft_lock);
        $this->assertCount(1, $this->requestsTo(self::SEND_URL));

        $this->actingAs($user)->get('/user/email-sending')->assertSee('Gmail rejected this message')->assertSee('Retry');

        // Safe retry (no confirmation needed: Gmail definitely did not send it)
        $this->gmailAccepts('msg-ok');
        $this->post("/user/email-sending/emails/{$email->id}/retry")->assertSessionHas('success');
        $this->assertSame('sent', $email->fresh()->status);
        $this->assertCount(2, $this->requestsTo(self::SEND_URL));
    }

    public function test_server_error_is_ambiguous_and_never_retried_automatically(): void
    {
        $this->respond(self::SEND_URL, [['error' => ['code' => 500]], 500]);
        [$user, , $draft, $email] = $this->sendOnce();

        $this->assertSame('failed', $email->status);
        $this->assertTrue($email->outcome_unknown);
        $this->assertSame('outcome_unknown', $email->error_code);
        $this->assertSame((string) $draft->id, $email->draft_lock, 'Draft stays locked: it may have been sent');
        $this->assertSame('unknown', EmailSendAttempt::sole()->result);
        $this->assertCount(1, $this->requestsTo(self::SEND_URL));

        // A stray re-delivery of the job does not resend
        $this->runJob($email->id);
        $this->assertCount(1, $this->requestsTo(self::SEND_URL));

        // A new send of the same draft is blocked
        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload())->assertSessionHasErrors('send');

        // Retry requires confirming the Sent folder was checked
        $this->get('/user/email-sending')->assertSee('I checked my Gmail')->assertSee('Check your Gmail');
        $this->post("/user/email-sending/emails/{$email->id}/retry")->assertSessionHas('error');
        $this->assertCount(1, $this->requestsTo(self::SEND_URL));

        $this->gmailAccepts('msg-after-check');
        $this->post("/user/email-sending/emails/{$email->id}/retry", ['confirm_not_sent' => '1'])->assertSessionHas('success');
        $this->assertSame('sent', $email->fresh()->status);
        $this->assertCount(2, $this->requestsTo(self::SEND_URL));
    }

    public function test_read_timeout_is_ambiguous(): void
    {
        $this->respondWith(self::SEND_URL, new ConnectionException('cURL error 28: Operation timed out after 20001 milliseconds with 0 bytes received'));
        [, , , $email] = $this->sendOnce();

        $this->assertSame('failed', $email->status);
        $this->assertTrue($email->outcome_unknown);
        $this->assertCount(1, $this->requestsTo(self::SEND_URL));
    }

    public function test_success_without_message_id_is_ambiguous(): void
    {
        $this->respond(self::SEND_URL, [[], 200]);
        [, , , $email] = $this->sendOnce();

        $this->assertSame('failed', $email->status);
        $this->assertTrue($email->outcome_unknown);
    }

    public function test_expired_access_token_is_refreshed_before_sending(): void
    {
        $this->respond(self::TOKEN_URL, [['access_token' => 'ya29.refreshed', 'expires_in' => 3599], 200]);
        $this->gmailAccepts();
        [, $account, , $email] = $this->sendOnce(['token_expires_at' => now()->subMinute()]);

        $this->assertSame('sent', $email->status);
        $refresh = $this->requestsTo(self::TOKEN_URL)[0]->data();
        $this->assertSame('refresh_token', $refresh['grant_type']);
        $this->assertSame('1//refresh-valid', $refresh['refresh_token']);
        $this->assertSame('Bearer ya29.refreshed', $this->requestsTo(self::SEND_URL)[0]->header('Authorization')[0]);
        $this->assertSame('ya29.refreshed', $account->fresh()->access_token);
    }

    public function test_401_triggers_one_refresh_and_resend(): void
    {
        $this->respond(self::SEND_URL, [['error' => ['code' => 401]], 401], [['id' => 'msg-retry', 'threadId' => 't'], 200]);
        $this->respond(self::TOKEN_URL, [['access_token' => 'ya29.new', 'expires_in' => 3599], 200]);
        [, , , $email] = $this->sendOnce();

        $this->assertSame('sent', $email->status);
        $this->assertSame('msg-retry', $email->gmail_message_id);
        $this->assertCount(2, $this->requestsTo(self::SEND_URL));
        $this->assertCount(1, $this->requestsTo(self::TOKEN_URL));
    }

    public function test_revoked_access_marks_account_for_reconnect(): void
    {
        $this->respond(self::TOKEN_URL, [['error' => 'invalid_grant', 'error_description' => 'Token has been expired or revoked.'], 400]);
        [$user, $account, , $email] = $this->sendOnce(['token_expires_at' => now()->subMinute()]);

        $this->assertSame('failed', $email->status);
        $this->assertFalse($email->outcome_unknown);
        $this->assertSame('auth_revoked', $email->error_code);
        $this->assertSame([], $this->requestsTo(self::SEND_URL));

        $account->refresh();
        $this->assertSame(ConnectedEmailAccount::STATUS_NEEDS_RECONNECT, $account->status);
        $this->assertNull($account->access_token);

        $this->actingAs($user)->get('/user/email-sending')->assertSee('expired or was revoked')->assertSee('Reconnect Gmail');
        $this->post("/user/email-sending/emails/{$email->id}/retry")->assertSessionHas('error', 'Reconnect your Gmail account first.');
    }

    public function test_missing_send_scope_from_gmail_requires_reconnect(): void
    {
        $this->respond(self::SEND_URL, [['error' => ['code' => 403, 'errors' => [['reason' => 'insufficientPermissions']]]], 403]);
        [, $account, , $email] = $this->sendOnce();

        $this->assertSame('missing_scope', $email->error_code);
        $this->assertSame('needs_reconnect', $account->fresh()->status);
    }

    public function test_recipient_unsubscribing_before_the_job_runs_stops_the_send(): void
    {
        Queue::fake();
        [$user, , , $email] = $this->sendOnce();
        EmailSuppression::suppress($user->id, $email->to_email, EmailSuppression::REASON_UNSUBSCRIBED, $email->id);

        $this->runJob($email->id);

        $this->assertSame('failed', $email->fresh()->status);
        $this->assertSame('suppressed', $email->fresh()->error_code);
        $this->assertSame([], $this->google);
        $this->actingAs($user)->post("/user/email-sending/emails/{$email->id}/retry")->assertSessionHas('error');
    }

    public function test_worker_killed_mid_send_becomes_unknown_outcome(): void
    {
        Queue::fake();
        [, , , $email] = $this->sendOnce();
        OutboundEmail::whereKey($email->id)->update(['status' => 'sending', 'updated_at' => now()->subMinutes(30)]);

        $this->artisan('email:recover-stuck')->assertSuccessful();

        $email->refresh();
        $this->assertSame('failed', $email->status);
        $this->assertTrue($email->outcome_unknown);
        $this->assertNotNull($email->draft_lock);
        $this->assertSame([], $this->google);
    }

    public function test_lost_pending_job_is_requeued_by_recovery(): void
    {
        Queue::fake();
        [, , , $email] = $this->sendOnce();
        OutboundEmail::whereKey($email->id)->update(['updated_at' => now()->subMinutes(30)]);

        $this->artisan('email:recover-stuck')->assertSuccessful();

        Queue::assertPushed(SendGmailMessage::class, 2);
        $this->assertSame('pending', $email->fresh()->status);
    }

    public function test_queue_giving_up_mid_send_is_marked_unknown(): void
    {
        Queue::fake();
        [, , , $email] = $this->sendOnce();
        OutboundEmail::whereKey($email->id)->update(['status' => 'sending']);

        (new SendGmailMessage($email->id))->failed(new \RuntimeException('timeout'));

        $this->assertSame('failed', $email->fresh()->status);
        $this->assertTrue($email->fresh()->outcome_unknown);
    }
}
