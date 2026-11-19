<?php

namespace Tests\Feature\AiOutreach;

use App\Models\AiAnalysisBatch;
use App\Models\AiEmailDraft;
use App\Models\AiLeadAnalysis;
use App\Models\AiUsageLog;
use App\Models\User;

class DraftReviewAndTenancyTest extends OutreachTestCase
{
    /** Runs a full analysis for one lead and returns [owner, analysis, draft]. */
    private function analysedLead(?User $owner = null, ?User $actor = null, string $leadName = 'Smile Dental'): array
    {
        $owner ??= $this->seller();
        $offer = $this->offerFor($owner);
        $lead = $this->leadFor($owner, ['name' => $leadName]);
        $this->fakeAi($this->happyAi($offer->id));

        $this->actingAs($actor ?? $owner)->postJson('/user/ai-outreach/analyze', ['lead_ids' => [$lead->id]])->assertOk();

        $analysis = AiLeadAnalysis::where('saved_lead_id', $lead->id)->sole();

        return [$owner, $analysis, AiEmailDraft::where('analysis_id', $analysis->id)->where('is_current', true)->sole()];
    }

    // ── Review workflow ──────────────────────────────────────────────────

    public function test_detail_page_shows_findings_offer_and_draft(): void
    {
        [$owner, $analysis, $draft] = $this->analysedLead();

        $this->actingAs($owner)->get("/user/ai-outreach/analyses/{$analysis->id}")
            ->assertOk()
            ->assertSee('Smile Dental')
            ->assertSee('No website URL is on file for this lead')
            ->assertSee('Online booking website')
            ->assertSee('The clinic relies on calls')
            ->assertSee($draft->subject)
            ->assertSee('Mark approved')
            ->assertSee('Approving does not send anything');
    }

    public function test_edit_approve_discard_restore(): void
    {
        [$owner, , $draft] = $this->analysedLead();

        $this->actingAs($owner)->put("/user/ai-outreach/drafts/{$draft->id}", [
            'subject' => 'Edited subject',
            'body' => "Hello,\r\nEdited body",
        ])->assertRedirect();
        $draft->refresh();
        $this->assertSame('Edited subject', $draft->subject);
        $this->assertSame("Hello,\nEdited body", $draft->body);
        $this->assertTrue($draft->user_edited);

        $this->actingAs($owner)->post("/user/ai-outreach/drafts/{$draft->id}/approve")->assertRedirect();
        $draft->refresh();
        $this->assertSame('approved', $draft->review_status);
        $this->assertSame($owner->id, $draft->approved_by_user_id);

        // Editing an approved draft requires approval again
        $this->actingAs($owner)->put("/user/ai-outreach/drafts/{$draft->id}", ['subject' => 'Changed again', 'body' => 'x'])->assertRedirect();
        $this->assertSame('draft', $draft->fresh()->review_status);

        $this->actingAs($owner)->post("/user/ai-outreach/drafts/{$draft->id}/discard")->assertRedirect();
        $this->assertSame('discarded', $draft->fresh()->review_status);

        $this->actingAs($owner)->put("/user/ai-outreach/drafts/{$draft->id}", ['subject' => 'No', 'body' => 'No'])
            ->assertSessionHas('error');
        $this->assertSame('Changed again', $draft->fresh()->subject);

        $this->actingAs($owner)->post("/user/ai-outreach/drafts/{$draft->id}/restore")->assertRedirect();
        $this->assertSame('draft', $draft->fresh()->review_status);
    }

    public function test_draft_edit_validation(): void
    {
        [$owner, , $draft] = $this->analysedLead();

        $this->actingAs($owner)->put("/user/ai-outreach/drafts/{$draft->id}", ['subject' => "Line\nBreak", 'body' => 'ok'])
            ->assertSessionHasErrors('subject');
        $this->actingAs($owner)->put("/user/ai-outreach/drafts/{$draft->id}", ['subject' => 'ok', 'body' => ''])
            ->assertSessionHasErrors('body');
        $this->actingAs($owner)->put("/user/ai-outreach/drafts/{$draft->id}", ['subject' => str_repeat('a', 201), 'body' => 'ok'])
            ->assertSessionHasErrors('subject');
    }

    public function test_draft_content_is_rendered_escaped(): void
    {
        [$owner, $analysis, $draft] = $this->analysedLead();
        $this->actingAs($owner)->put("/user/ai-outreach/drafts/{$draft->id}", [
            'subject' => '<script>alert(1)</script>',
            'body' => '</textarea><img src=x onerror=alert(2)>',
        ]);

        $this->actingAs($owner)->get("/user/ai-outreach/analyses/{$analysis->id}")
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('</textarea><img src=x onerror=alert(2)>', false);
    }

    public function test_regenerate_creates_new_current_draft_and_keeps_history(): void
    {
        [$owner, $analysis, $first] = $this->analysedLead();

        $this->actingAs($owner)->post("/user/ai-outreach/analyses/{$analysis->id}/regenerate")->assertRedirect();

        $this->assertFalse($first->fresh()->is_current);
        $this->assertSame(2, AiEmailDraft::where('analysis_id', $analysis->id)->count());
        $this->assertSame(1, AiEmailDraft::where('analysis_id', $analysis->id)->where('is_current', true)->count());
        $this->assertSame('completed', $analysis->fresh()->status);
        // Regenerating costs one draft, not another analysis
        $this->assertSame(1, AiUsageLog::where('feature', 'website_audit')->count());
        $this->assertSame(2, AiUsageLog::where('feature', 'email_draft')->count());

        $this->actingAs($owner)->get("/user/ai-outreach/analyses/{$analysis->id}")->assertOk()->assertSee('Draft history');
    }

    public function test_regenerate_requires_a_completed_analysis(): void
    {
        [$owner, $analysis] = $this->analysedLead();
        $analysis->forceFill(['status' => 'failed'])->save();

        $this->actingAs($owner)->post("/user/ai-outreach/analyses/{$analysis->id}/regenerate")->assertSessionHas('error');
        $this->assertSame(1, AiEmailDraft::count());
    }

    // ── Pages ────────────────────────────────────────────────────────────

    public function test_index_batch_and_usage_pages(): void
    {
        [$owner, $analysis] = $this->analysedLead();
        $batch = AiAnalysisBatch::sole();

        $this->actingAs($owner)->get('/user/ai-outreach')
            ->assertOk()
            ->assertSee('AI Outreach')
            ->assertSee('Smile Dental')
            ->assertSee('Selected offer');

        $this->actingAs($owner)->get('/user/ai-outreach?status=failed')->assertOk()->assertDontSee('Smile Dental');
        $this->actingAs($owner)->get('/user/ai-outreach?review=draft&q=Smile')->assertOk()->assertSee('Smile Dental');
        $this->actingAs($owner)->get('/user/ai-outreach?status=nonsense')->assertSessionHasErrors('status');

        $this->actingAs($owner)->get("/user/ai-outreach/batches/{$batch->id}")->assertOk()->assertSee('1 of 1 finished');
        $this->actingAs($owner)->getJson("/user/ai-outreach/batches/{$batch->id}/status")
            ->assertOk()
            ->assertJsonPath('progress.completed', 1)
            ->assertJsonPath('items.0.status', 'completed');

        $this->actingAs($owner)->getJson('/user/ai-outreach/usage')
            ->assertOk()
            ->assertJsonPath('features.website_audit.used', 1)
            ->assertJsonPath('features.email_draft.used', 1)
            ->assertJsonPath('features.email_draft.input_tokens', 1000);
    }

    public function test_leads_page_keeps_selection_ui_and_adds_the_ai_action(): void
    {
        $owner = $this->seller();
        $this->leadFor($owner);

        $this->actingAs($owner)->get('/user/leads')
            ->assertOk()
            ->assertSee('id="selectAll"', false)
            ->assertSee('Add to Lead Center')
            ->assertSee('Analyze with AI')
            ->assertSee('cnmAnalyzeModal', false);
    }

    public function test_feature_disabled_by_plan_hides_and_blocks_ai_outreach(): void
    {
        $owner = $this->seller(['ai_sales_profile_enabled' => 'false']);
        $lead = $this->leadFor($owner);

        $this->actingAs($owner)->get('/user/leads')->assertOk()->assertDontSee('cnmAnalyzeModal', false)->assertDontSee('data-testid="analyze-with-ai"', false);
        $this->actingAs($owner)->postJson('/user/ai-outreach/analyze', ['lead_ids' => [$lead->id]])->assertForbidden();
        $this->actingAs($owner)->get('/user/ai-outreach')->assertRedirect(route('user.subscription'));
    }

    // ── Tenant isolation ─────────────────────────────────────────────────

    public function test_other_accounts_cannot_use_or_see_anything(): void
    {
        [$owner, $analysis, $draft] = $this->analysedLead();
        $batch = AiAnalysisBatch::sole();
        $intruder = $this->seller();
        $this->offerFor($intruder);

        // Submitting someone else's lead ids queues nothing
        $this->actingAs($intruder)->postJson('/user/ai-outreach/estimate', ['lead_ids' => [$analysis->saved_lead_id]])
            ->assertOk()->assertJsonPath('valid', 0)->assertJsonPath('can_start', false);
        $this->actingAs($intruder)->postJson('/user/ai-outreach/analyze', ['lead_ids' => [$analysis->saved_lead_id]])->assertStatus(422);

        $this->actingAs($intruder)->get("/user/ai-outreach/analyses/{$analysis->id}")->assertNotFound();
        $this->actingAs($intruder)->post("/user/ai-outreach/analyses/{$analysis->id}/regenerate")->assertNotFound();
        $this->actingAs($intruder)->post("/user/ai-outreach/analyses/{$analysis->id}/retry")->assertNotFound();
        $this->actingAs($intruder)->get("/user/ai-outreach/batches/{$batch->id}")->assertNotFound();
        $this->actingAs($intruder)->getJson("/user/ai-outreach/batches/{$batch->id}/status")->assertNotFound();
        $this->actingAs($intruder)->put("/user/ai-outreach/drafts/{$draft->id}", ['subject' => 'Hacked', 'body' => 'Hacked'])->assertNotFound();
        $this->actingAs($intruder)->post("/user/ai-outreach/drafts/{$draft->id}/approve")->assertNotFound();
        $this->actingAs($intruder)->post("/user/ai-outreach/drafts/{$draft->id}/discard")->assertNotFound();

        $this->actingAs($intruder)->get('/user/ai-outreach')->assertOk()->assertDontSee('Smile Dental');
        $this->actingAs($intruder)->getJson('/user/ai-outreach/usage')->assertJsonPath('features.email_draft.used', 0);

        $this->assertSame('draft', $draft->fresh()->review_status);
        $this->assertNotSame('Hacked', $draft->fresh()->subject);
    }

    public function test_mixed_selection_only_uses_own_leads(): void
    {
        $owner = $this->seller();
        $offer = $this->offerFor($owner);
        $mine = $this->leadFor($owner, ['name' => 'Mine']);
        $theirs = $this->leadFor($this->seller(), ['name' => 'Theirs']);
        $this->fakeAi($this->happyAi($offer->id));

        $this->actingAs($owner)->postJson('/user/ai-outreach/analyze', ['lead_ids' => [$mine->id, $theirs->id, 999999]])
            ->assertOk()->assertJsonPath('queued', 1);

        $this->assertSame([$mine->id], AiLeadAnalysis::pluck('saved_lead_id')->all());
    }

    // ── Teams ────────────────────────────────────────────────────────────

    public function test_team_member_analyses_company_leads_with_shared_quota(): void
    {
        $company = $this->seller(['ai_audits_monthly' => '1', 'ai_email_drafts_monthly' => '1'], ['user_type' => 'company']);
        $member = $this->makeUser(['company_id' => $company->id]);

        [, $analysis, $draft] = $this->analysedLead($company, $member);

        $this->assertSame($company->id, $analysis->owner_user_id);
        $this->assertSame($member->id, $analysis->created_by_user_id);
        $this->assertSame($member->id, AiUsageLog::where('feature', 'email_draft')->sole()->user_id);
        $this->assertSame($company->id, AiUsageLog::where('feature', 'email_draft')->sole()->owner_user_id);

        // Company owner sees and reviews the member's draft
        $this->actingAs($company)->get("/user/ai-outreach/analyses/{$analysis->id}")->assertOk();
        $this->actingAs($company)->post("/user/ai-outreach/drafts/{$draft->id}/approve")->assertRedirect();

        // Shared quota is now used up for everyone in the company
        $another = $this->leadFor($company, ['name' => 'Second']);
        $this->actingAs($company)->postJson('/user/ai-outreach/estimate', ['lead_ids' => [$another->id]])
            ->assertJsonPath('can_start', false)
            ->assertJsonPath('quota.website_audit.remaining', 0);

        // Team members still cannot edit the AI Sales Profile
        $this->actingAs($member)->put('/user/ai-sales-profile', ['company_name' => 'X', 'language' => 'en', 'tone' => 'friendly'])->assertForbidden();
    }

    public function test_starting_is_rate_limited(): void
    {
        $owner = $this->seller();
        $this->offerFor($owner);
        $lead = $this->leadFor($owner);
        \Illuminate\Support\Facades\Queue::fake();

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($owner)->postJson('/user/ai-outreach/analyze', ['lead_ids' => [$lead->id]]);
        }

        $this->actingAs($owner)->postJson('/user/ai-outreach/analyze', ['lead_ids' => [$lead->id]])->assertStatus(429);
    }
}
