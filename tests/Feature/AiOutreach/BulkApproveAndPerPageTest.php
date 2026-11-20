<?php

namespace Tests\Feature\AiOutreach;

use App\Models\AiEmailDraft;
use App\Models\AiLeadAnalysis;
use App\Models\User;

/** AI Outreach list: "Approve emails" for selected leads, and rows per page. */
class BulkApproveAndPerPageTest extends OutreachTestCase
{
    private function analysisWithDraft(User $owner, ?string $review = 'draft', string $name = 'Smile Dental'): AiLeadAnalysis
    {
        $lead = $this->leadFor($owner, ['name' => $name]);
        $analysis = AiLeadAnalysis::create([
            'owner_user_id' => $owner->id, 'created_by_user_id' => $owner->id,
            'saved_lead_id' => $lead->id, 'status' => 'completed', 'stage' => 'done',
        ]);
        if ($review !== null) {
            $draft = new AiEmailDraft(['subject' => 'Hi', 'body' => 'Hello']);
            $draft->forceFill([
                'owner_user_id' => $owner->id, 'saved_lead_id' => $lead->id, 'analysis_id' => $analysis->id,
                'review_status' => $review, 'is_current' => true,
            ])->save();
        }

        return $analysis;
    }

    private function reviewOf(AiLeadAnalysis $a): ?string
    {
        return AiEmailDraft::where('analysis_id', $a->id)->where('is_current', true)->value('review_status');
    }

    public function test_bulk_approve_approves_only_waiting_drafts(): void
    {
        $user = $this->seller();
        $waiting = $this->analysisWithDraft($user);
        $waiting2 = $this->analysisWithDraft($user);
        $discarded = $this->analysisWithDraft($user, 'discarded');
        $noDraft = $this->analysisWithDraft($user, null);

        $this->actingAs($user)->from(route('user.ai-outreach.index'))
            ->post(route('user.ai-outreach.drafts.bulk-approve'), ['analysis_ids' => [$waiting->id, $waiting2->id, $discarded->id, $noDraft->id]])
            ->assertRedirect(route('user.ai-outreach.index'))
            ->assertSessionHas('success', fn ($m) => str_contains($m, '2 email draft(s) approved') && str_contains($m, '2 skipped'));

        $this->assertSame('approved', $this->reviewOf($waiting));
        $this->assertSame('approved', $this->reviewOf($waiting2));
        $this->assertSame('discarded', $this->reviewOf($discarded));
        $this->assertNotNull(AiEmailDraft::where('analysis_id', $waiting->id)->value('approved_at'));
    }

    public function test_bulk_approve_cannot_touch_another_account(): void
    {
        $alice = $this->seller();
        $bob = $this->seller();
        $aliceAnalysis = $this->analysisWithDraft($alice);

        $this->actingAs($bob)->postJson(route('user.ai-outreach.drafts.bulk-approve'), ['analysis_ids' => [$aliceAnalysis->id]])
            ->assertOk()->assertJsonPath('approved', 0);
        $this->assertSame('draft', $this->reviewOf($aliceAnalysis));

        $this->actingAs($bob)->postJson(route('user.ai-outreach.drafts.bulk-approve'), ['analysis_ids' => []])->assertStatus(422);
    }

    public function test_per_page_selector(): void
    {
        $user = $this->seller();
        foreach (range(1, 12) as $i) {
            $this->analysisWithDraft($user, 'draft', "Biz {$i}");
        }

        $this->actingAs($user)->get(route('user.ai-outreach.index'))->assertOk()
            ->assertSee('data-testid="per-page"', false)
            ->assertSee('Approve emails')
            ->assertSee('of <strong class="text-gray-900">12</strong> results', false);

        $page = $this->actingAs($user)->get(route('user.ai-outreach.index', ['per_page' => 10]))->assertOk();
        $this->assertSame(10, substr_count($page->getContent(), 'class="cnm-row-check'));

        $all = $this->actingAs($user)->get(route('user.ai-outreach.index', ['per_page' => 'all']))->assertOk();
        $this->assertSame(12, substr_count($all->getContent(), 'class="cnm-row-check'));

        $this->actingAs($user)->get(route('user.ai-outreach.index', ['per_page' => 7]))->assertRedirect();
    }
}
