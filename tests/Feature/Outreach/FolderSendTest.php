<?php

namespace Tests\Feature\Outreach;

use App\Models\AiEmailDraft;
use App\Models\AiLeadAnalysis;
use App\Models\EmailSuppression;
use App\Models\LeadFolder;
use App\Models\LeadFolderSend;
use App\Models\LeadFolderSendItem;
use App\Models\OutboundEmail;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Resend\ResendTestCase;

/** Lead folders in AI Outreach + "send to a lead folder" (each lead's own AI email, spaced out). */
class FolderSendTest extends ResendTestCase
{
    private const GMAIL_SEND = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send';

    /** A lead with its own analysis and current AI draft. */
    private function analysed(User $owner, string $name, ?string $email, array $draft = []): AiEmailDraft
    {
        $d = $this->draftFor($owner, array_merge(['review_status' => 'draft', 'approved_at' => null, 'subject' => "Idea for {$name}"], $draft), ['name' => $name, 'email' => $email]);
        $a = new AiLeadAnalysis();
        $a->forceFill(['owner_user_id' => $owner->id, 'saved_lead_id' => $d->saved_lead_id, 'status' => 'completed'])->save();
        $d->forceFill(['analysis_id' => $a->id])->save();

        return $d->fresh();
    }

    private function folderWith(User $owner, array $drafts, string $name = 'Dubai travel'): LeadFolder
    {
        $f = LeadFolder::create(['user_id' => $owner->id, 'name' => $name, 'color' => 'blue']);
        $f->leads()->attach(collect($drafts)->pluck('saved_lead_id')->all());

        return $f;
    }

    private function start(User $user, LeadFolder $f, array $extra = [])
    {
        return $this->actingAs($user)->post('/user/campaigns/folder-send', array_merge([
            'folder_id' => $f->id, 'provider' => 'gmail', 'interval' => 3, 'expected_ready' => 2, 'confirm' => '1',
        ], $extra));
    }

    public function test_analysed_leads_can_be_put_into_folders_and_filtered(): void
    {
        $user = $this->resendUser();
        $a = $this->analysed($user, 'Alpha Travel', 'a@alpha.example');
        $b = $this->analysed($user, 'Beta Tours', 'b@beta.example');
        $other = $this->resendUser([], ['email' => 'other@agency.example']);
        $foreign = $this->analysed($other, 'Not Mine', 'x@x.example');

        $this->actingAs($user)->post('/user/ai-outreach/folders/add', [
            'analysis_ids' => [$a->analysis_id, $foreign->analysis_id], 'new_folder' => 'Dubai travel',
        ])->assertSessionHas('success');

        $folder = LeadFolder::where('user_id', $user->id)->sole();
        $this->assertSame([$a->saved_lead_id], $folder->leads()->pluck('saved_leads.id')->all(), 'Only my own leads');

        $this->actingAs($user)->get('/user/ai-outreach?folder=' . $folder->id)->assertOk()
            ->assertSee('Alpha Travel')->assertDontSee('Beta Tours')->assertSee('Add to folder');
        $this->actingAs($user)->get('/user/campaigns/create')->assertOk()->assertSee('Dubai travel (1 leads · 1 AI emails ready)');
    }

    public function test_review_shows_each_leads_own_email_and_why_others_are_skipped(): void
    {
        $user = $this->resendUser();
        $this->connect($user);
        $ok = $this->analysed($user, 'Alpha Travel', 'a@alpha.example');
        $noEmail = $this->analysed($user, 'No Mail Co', null);
        $discarded = $this->analysed($user, 'Gone Ltd', 'g@gone.example', ['review_status' => 'discarded']);
        $f = $this->folderWith($user, [$ok, $noEmail, $discarded]);

        $this->actingAs($user)->get('/user/campaigns/folder-send?folder_id=' . $f->id . '&provider=gmail')->assertOk()
            ->assertSee('1 ready')->assertSee('2 skipped')->assertSee('Idea for Alpha Travel')
            ->assertSee('Skip: No email address')->assertSee('Skip: You discarded this email')
            ->assertSee('Approve & start sending 1 email(s)', false);
    }

    public function test_start_approves_drafts_and_sends_one_email_per_interval_through_gmail(): void
    {
        $user = $this->resendUser();
        $this->connect($user);
        $this->gmailAccepts('18c0ffee', '18c0ffee');
        $a = $this->analysed($user, 'Alpha Travel', 'a@alpha.example');
        $b = $this->analysed($user, 'Beta Tours', 'b@beta.example');
        $f = $this->folderWith($user, [$a, $b]);

        $this->start($user, $f)->assertRedirect()->assertSessionHas('success');
        $send = LeadFolderSend::sole();
        $this->assertSame([AiEmailDraft::REVIEW_APPROVED, AiEmailDraft::REVIEW_APPROVED], [$a->fresh()->review_status, $b->fresh()->review_status]);
        $this->assertSame(0, OutboundEmail::count(), 'Nothing is sent by the click itself');

        $this->artisan('outreach:folder-sends')->assertSuccessful();
        $this->artisan('outreach:folder-sends')->assertSuccessful(); // same minute: the 2nd is not due yet
        $this->assertSame(1, OutboundEmail::count());
        $this->assertSame(['a@alpha.example'], OutboundEmail::pluck('to_email')->all());
        $this->assertCount(1, $this->requestsTo(self::GMAIL_SEND));

        $this->travel(3)->minutes();
        $this->artisan('outreach:folder-sends')->assertSuccessful();
        $this->assertSame(2, OutboundEmail::where('provider', 'gmail')->where('status', 'sent')->count());
        $this->assertSame(LeadFolderSend::COMPLETED, $send->fresh()->status);
        $this->assertSame(2, $send->items()->where('status', LeadFolderSendItem::QUEUED)->count());

        $this->actingAs($user)->get('/user/campaigns/folder-sends/' . $send->id)->assertOk()->assertSee('Sent to queue');
    }

    public function test_nothing_starts_when_the_folder_changed_or_gmail_is_not_connected(): void
    {
        $user = $this->resendUser();
        $f = $this->folderWith($user, [$this->analysed($user, 'Alpha Travel', 'a@alpha.example'), $this->analysed($user, 'Beta Tours', 'b@beta.example')]);

        $this->start($user, $f)->assertSessionHasErrors('provider'); // Gmail not connected
        $this->connect($user);
        $this->start($user, $f, ['expected_ready' => 5])->assertSessionHasErrors('folder_id');
        $this->start($user, $f, ['confirm' => null])->assertSessionHasErrors('confirm');

        $this->assertSame(0, LeadFolderSend::count());
        $this->assertSame(0, AiEmailDraft::where('review_status', 'approved')->count(), 'Nothing approved');
    }

    public function test_stop_cancels_emails_not_sent_yet(): void
    {
        $user = $this->resendUser();
        $this->connect($user);
        $f = $this->folderWith($user, [$this->analysed($user, 'Alpha Travel', 'a@alpha.example'), $this->analysed($user, 'Beta Tours', 'b@beta.example')]);
        $this->start($user, $f);
        $send = LeadFolderSend::sole();

        $this->actingAs($user)->post("/user/campaigns/folder-sends/{$send->id}/cancel")->assertSessionHas('success');
        $this->artisan('outreach:folder-sends');

        $this->assertSame(LeadFolderSend::CANCELLED, $send->fresh()->status);
        $this->assertSame(2, $send->items()->where('status', LeadFolderSendItem::CANCELLED)->count());
        $this->assertSame(0, OutboundEmail::count());
    }

    public function test_an_address_suppressed_after_start_is_not_emailed(): void
    {
        $user = $this->resendUser();
        $this->connect($user);
        $this->gmailAccepts();
        $f = $this->folderWith($user, [$this->analysed($user, 'Alpha Travel', 'a@alpha.example'), $this->analysed($user, 'Beta Tours', 'b@beta.example')]);
        $this->start($user, $f);
        EmailSuppression::suppress($user->id, 'a@alpha.example', EmailSuppression::REASON_UNSUBSCRIBED);

        $this->artisan('outreach:folder-sends');

        $item = LeadFolderSendItem::where('recipient', 'a@alpha.example')->sole();
        $this->assertSame(LeadFolderSendItem::FAILED, $item->status);
        $this->assertStringContainsString('unsubscribed', strtolower($item->reason));
        $this->assertSame(0, OutboundEmail::where('to_email', 'a@alpha.example')->count());
    }

    public function test_resend_needs_lead_outreach_for_leads_that_did_not_opt_in(): void
    {
        $user = $this->resendUser();
        [, , $sender] = $this->connectResend($user);
        $this->respondWith(self::API . '/emails', fn () => Http::response(['id' => 're_folder_1']));
        $d = $this->analysed($user, 'Alpha Travel', 'a@alpha.example');
        $f = $this->folderWith($user, [$d]);
        $url = '/user/campaigns/folder-send?folder_id=' . $f->id . '&provider=resend&sender_id=' . $sender->id;

        $this->actingAs($user)->get($url)->assertOk()->assertSee('Skip: Not an opted-in contact and Resend emails to leads are off');

        $this->actingAs($user)->put('/user/email-sending/resend/lead-outreach', ['enabled' => '1', 'acknowledge' => '1']);
        $this->actingAs($user)->get($url)->assertOk()->assertSee('1 ready');
        $this->start($user, $f, ['provider' => 'resend', 'sender_id' => $sender->id, 'expected_ready' => 1])->assertSessionHas('success');
        $this->artisan('outreach:folder-sends');

        $email = OutboundEmail::sole();
        $this->assertSame(['resend', 'sent', 'a@alpha.example'], [$email->provider, $email->status, $email->to_email]);
        $this->assertCount(1, $this->resendCalls('POST', '/emails'));
    }

    public function test_other_accounts_folders_and_sends_are_not_reachable(): void
    {
        $user = $this->resendUser();
        $other = $this->resendUser([], ['email' => 'other@agency.example']);
        $f = $this->folderWith($other, [$this->analysed($other, 'Not Mine', 'x@x.example')]);
        $this->connect($other);
        $this->start($other, $f, ['expected_ready' => 1]);
        $send = LeadFolderSend::sole();

        $this->actingAs($user)->get('/user/campaigns/folder-send?folder_id=' . $f->id)->assertNotFound();
        $this->actingAs($user)->get('/user/campaigns/folder-sends/' . $send->id)->assertNotFound();
        $this->actingAs($user)->post("/user/campaigns/folder-sends/{$send->id}/cancel")->assertNotFound();
    }
}
