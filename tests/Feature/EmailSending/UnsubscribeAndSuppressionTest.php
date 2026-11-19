<?php

namespace Tests\Feature\EmailSending;

use App\Models\EmailSuppression;
use App\Models\OutboundEmail;
use App\Services\Gmail\OutboundEmailService;
use Illuminate\Support\Facades\Queue;

class UnsubscribeAndSuppressionTest extends GmailTestCase
{
    private function queuedEmail(): array
    {
        Queue::fake();
        $user = $this->sender();
        $this->connect($user);
        $draft = $this->draftFor($user);
        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload());
        auth()->logout();

        $email = OutboundEmail::sole();

        return [$user, $email, app(OutboundEmailService::class)->unsubscribeUrl($email)];
    }

    public function test_unsigned_or_tampered_links_are_rejected(): void
    {
        [, $email, $url] = $this->queuedEmail();

        $this->get("/email/unsubscribe/{$email->id}")->assertForbidden();
        $this->post("/email/unsubscribe/{$email->id}")->assertForbidden();
        $this->get(str_replace("/unsubscribe/{$email->id}", '/unsubscribe/' . ($email->id + 1), $url))->assertForbidden();

        $this->assertSame(0, EmailSuppression::count());
    }

    public function test_page_masks_address_and_get_does_not_unsubscribe(): void
    {
        [, , $url] = $this->queuedEmail();

        $this->get($url)->assertOk()
            ->assertSee('i***@smiledental.example')
            ->assertDontSee('info@smiledental.example')
            ->assertSee('noindex', false);

        $this->assertSame(0, EmailSuppression::count(), 'Link scanners pre-fetching GET must not unsubscribe');
    }

    public function test_confirming_unsubscribes_and_blocks_future_sends(): void
    {
        [$user, $email, $url] = $this->queuedEmail();

        $this->post($url)->assertOk()->assertSee("You're unsubscribed", false);
        $this->assertTrue(EmailSuppression::isSuppressed($user->id, 'info@smiledental.example'));
        $this->assertSame('unsubscribed', EmailSuppression::sole()->reason);

        // Idempotent
        $this->post($url)->assertOk();
        $this->assertSame(1, EmailSuppression::count());

        // Future drafts to that address are refused
        $draft = $this->draftFor($user);
        $this->actingAs($user)->post("/user/email-sending/drafts/{$draft->id}/send", $this->sendPayload())->assertSessionHasErrors('send');

        // Unsubscribes cannot be removed by the sender
        $this->delete('/user/email-sending/suppressions/' . EmailSuppression::sole()->id)->assertSessionHas('error');
        $this->assertSame(1, EmailSuppression::count());
    }

    public function test_one_click_post_works_without_csrf_token(): void
    {
        [$user, , $url] = $this->queuedEmail();

        // Real CSRF middleware (the test kernel normally skips it)
        $this->withMiddleware()->call('POST', $url, ['List-Unsubscribe' => 'One-Click'])->assertOk();
        $this->assertTrue(EmailSuppression::isSuppressed($user->id, 'info@smiledental.example'));
    }

    public function test_manual_suppressions_are_scoped_to_the_account(): void
    {
        $a = $this->sender();
        $b = $this->sender();

        $this->actingAs($a)->post('/user/email-sending/suppressions', ['email' => 'Blocked@Example.com'])->assertSessionHas('success');
        $this->post('/user/email-sending/suppressions', ['email' => 'not-an-email'])->assertSessionHasErrors('email');
        $row = EmailSuppression::sole();
        $this->assertSame('blocked@example.com', $row->email);
        $this->assertSame('manual', $row->reason);

        $this->assertTrue(EmailSuppression::isSuppressed($a->id, 'blocked@example.com'));
        $this->assertFalse(EmailSuppression::isSuppressed($b->id, 'blocked@example.com'));

        $this->actingAs($b)->delete("/user/email-sending/suppressions/{$row->id}")->assertNotFound();
        $this->actingAs($a)->delete("/user/email-sending/suppressions/{$row->id}")->assertSessionHas('success');
        $this->assertSame(0, EmailSuppression::count());
    }
}
