<?php

namespace Tests\Feature\EmailTracking;

use App\Http\Controllers\EmailTracking\OpenPixelController;
use App\Models\EmailReplyRecord;
use App\Models\EmailTrackingEvent;
use App\Models\OutboundEmail;
use App\Services\EmailTracking\EmailAnalytics;
use App\Services\Gmail\GmailMessageBuilder;

/** Gmail emails: open pixel (with tracking on) and replies found in the connected inbox. */
class OpensAndReplyDetectionTest extends TrackingTestCase
{
    private const READONLY = 'https://www.googleapis.com/auth/gmail.readonly';

    private function sentRaw(): string
    {
        $req = $this->requestsTo(config('gmail.endpoints.send'));
        $raw = end($req)->data()['raw'];

        return base64_decode(strtr($raw, '-_', '+/'));
    }

    public function test_tracked_email_carries_an_open_pixel_and_untracked_does_not(): void
    {
        [$user, $email] = $this->trackedEmail();
        $this->assertTrue($email->open_tracking);
        $mime = $this->sentRaw();
        $this->assertStringContainsString('multipart/alternative', $mime);
        $this->assertStringContainsString('Content-Type: text/plain', $mime);
        // The base64 body of the text/html part (up to the next boundary line)
        preg_match('/Content-Type: text\/html[^\r\n]*\r\nContent-Transfer-Encoding: base64\r\n\r\n([A-Za-z0-9+\/=\r\n]+)\r\n--/', $mime, $m);
        $html = base64_decode(preg_replace('/\s+/', '', $m[1] ?? ''));
        $this->assertStringContainsString('/o/' . $email->id . '.gif?signature=', html_entity_decode($html));

        // Tracking off: plain text only
        $plain = $this->trackingSender();
        $this->connect($plain, ['email' => 'plain@gmail.com', 'google_sub' => 'sub-plain']);
        $e2 = $this->sendDraft($plain, $this->draftFor($plain), null, '18cplain');
        $this->assertFalse((bool) $e2->open_tracking);
        $this->assertStringNotContainsString('multipart/alternative', $this->sentRaw());
    }

    public function test_html_part_escapes_text_and_keeps_links(): void
    {
        $html = GmailMessageBuilder::html("Hi <b>Sara</b>,\nSee https://x.example/a?b=1&c=2.", 'https://app.example/o/1.gif?signature=s');
        $this->assertStringContainsString('Hi &lt;b&gt;Sara&lt;/b&gt;,<br>', $html);
        $this->assertStringContainsString('<a href="https://x.example/a?b=1&amp;c=2">', $html);
        $this->assertStringContainsString('<img src="https://app.example/o/1.gif?signature=s"', $html);
    }

    public function test_pixel_records_an_open_once_per_half_hour_and_ignores_bad_signatures(): void
    {
        [$user, $email] = $this->trackedEmail();
        $email->forceFill(['sent_at' => now()->subMinutes(5)])->save();
        $url = OpenPixelController::urlFor($email);

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 5.1; rv:11.0) Gecko Firefox/11.0 (via ggpht.com GoogleImageProxy)'])
            ->get($url)->assertOk()->assertHeader('Content-Type', 'image/gif');
        $this->get($url)->assertOk();
        $this->get('/o/' . $email->id . '.gif?signature=forged')->assertOk(); // same GIF, nothing recorded

        $opens = EmailTrackingEvent::where('event_type', EmailTrackingEvent::OPENED)->get();
        $this->assertCount(1, $opens);
        $this->assertSame('gmail', $opens[0]->metadata['via']);
        $this->assertSame(EmailTrackingEvent::HUMAN, $opens[0]->classification);

        $s = app(EmailAnalytics::class)->summary($user, ['from' => now()->subDays(7), 'to' => now()]);
        $this->assertSame(1, $s['opened_emails']);
        $this->assertSame(1, $s['gmail_unique_opens']);
        $this->actingAs($user)->get(route('user.email-analytics.index'))->assertOk()->assertSee('Opened');
    }

    public function test_load_right_after_sending_counts_as_scanner_not_open(): void
    {
        [$user, $email] = $this->trackedEmail();
        $email->forceFill(['sent_at' => now()])->save();
        $this->get(OpenPixelController::urlFor($email))->assertOk();

        $this->assertSame(EmailTrackingEvent::SCANNER, EmailTrackingEvent::where('event_type', 'opened')->sole()->classification);
        $this->assertSame(0, app(EmailAnalytics::class)->summary($user, ['from' => now()->subDays(7), 'to' => now()])['opened_emails']);
    }

    public function test_reply_in_the_gmail_thread_is_found_and_recorded(): void
    {
        $user = $this->trackingSender();
        $this->connect($user, ['scopes' => ['openid', 'https://www.googleapis.com/auth/gmail.send', self::READONLY]]);
        $email = $this->sendDraft($user, $this->draftFor($user), null, '18cthread');
        $email->forceFill(['gmail_thread_id' => 'thr1', 'sent_at' => now()->subHour()])->save();

        $msg = fn (string $from, int $minutesAgo) => [
            'id' => uniqid(), 'internalDate' => (string) (now()->subMinutes($minutesAgo)->getTimestampMs()),
            'payload' => ['headers' => [['name' => 'From', 'value' => $from]]],
        ];
        $this->respond(config('gmail.endpoints.thread') . 'thr1', [['messages' => [
            $msg('Ayesha <ayesha@gmail.com>', 60),
            $msg('Sara Khan <' . $email->to_email . '>', 10),
        ]]]);

        $this->artisan('email:detect-replies')->assertSuccessful();

        $reply = EmailReplyRecord::sole();
        $this->assertSame('replied', $reply->reply_status);
        $this->assertSame('gmail_inbox', $reply->source);
        $this->assertTrue(EmailTrackingEvent::where('event_type', EmailTrackingEvent::REPLY_DETECTED)->exists());
        $this->assertNotNull($email->fresh()->reply_checked_at);

        // Running again does not duplicate; a reply the user recorded is never overwritten
        $this->artisan('email:detect-replies')->assertSuccessful();
        $this->assertSame(1, EmailReplyRecord::count());

        $s = app(EmailAnalytics::class)->summary($user, ['from' => now()->subDays(7), 'to' => now()]);
        $this->assertSame(1, $s['replies']);
        $this->assertSame(1, $s['replies_detected']);
    }

    public function test_only_own_messages_in_thread_means_no_reply_and_no_readonly_scope_means_no_check(): void
    {
        $user = $this->trackingSender();
        $this->connect($user, ['scopes' => ['openid', 'https://www.googleapis.com/auth/gmail.send', self::READONLY]]);
        $email = $this->sendDraft($user, $this->draftFor($user), null, '18cown');
        $email->forceFill(['gmail_thread_id' => 'thr2', 'sent_at' => now()->subHour()])->save();
        $this->respond(config('gmail.endpoints.thread') . 'thr2', [['messages' => [[
            'id' => 'm1', 'internalDate' => (string) now()->subMinutes(5)->getTimestampMs(),
            'payload' => ['headers' => [['name' => 'From', 'value' => 'ayesha@gmail.com']]],
        ]]]]);
        $this->artisan('email:detect-replies')->assertSuccessful();
        $this->assertSame(0, EmailReplyRecord::count());

        // Send-only account: Gmail is never asked
        $other = $this->trackingSender();
        $this->connect($other, ['email' => 'b@gmail.com', 'google_sub' => 'sub-b']);
        $before = count($this->google);
        $this->artisan('email:detect-replies', ['--owner' => $other->id])->expectsOutputToContain('Nothing to check')->assertSuccessful();
        $this->assertSame($before, count($this->google));
    }

    public function test_readonly_scope_is_requested_only_when_reply_detection_is_on(): void
    {
        $this->assertNotContains(self::READONLY, config('gmail.scopes'));
    }
}
