<?php

namespace Tests\Feature\AiOutreach;

use App\Models\AiLeadAnalysis;
use App\Models\SavedLead;
use App\Models\User;

/**
 * My Leads: leads analysed with AI get a coloured row + "AI analysed" badge, and the
 * Analyze modal learns which selected leads were analysed before (to ask: again or skip?).
 */
class AnalyzedLeadsMarkingTest extends OutreachTestCase
{
    private function analysis(User $owner, SavedLead $lead, string $status): AiLeadAnalysis
    {
        return AiLeadAnalysis::create([
            'owner_user_id' => $owner->id,
            'created_by_user_id' => $owner->id,
            'saved_lead_id' => $lead->id,
            'status' => $status,
            'stage' => $status === 'completed' ? 'done' : 'audit',
            'active_key' => in_array($status, AiLeadAnalysis::OPEN_STATUSES, true) ? AiLeadAnalysis::activeKey($owner->id, $lead->id) : null,
        ]);
    }

    public function test_leads_page_marks_analysed_and_running_leads(): void
    {
        $user = $this->seller();
        $done = $this->leadFor($user, ['name' => 'Done Dental']);
        $running = $this->leadFor($user, ['name' => 'Running Dental']);
        $failed = $this->leadFor($user, ['name' => 'Failed Dental']);
        $this->analysis($user, $done, 'completed');
        $this->analysis($user, $running, 'analyzing');
        $this->analysis($user, $failed, 'failed');

        $html = $this->actingAs($user)->get(route('user.leads'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/data-lead-id="' . $done->id . '" data-ai-state="completed"/', $html);
        $this->assertMatchesRegularExpression('/data-lead-id="' . $running->id . '" data-ai-state="open"/', $html);
        $this->assertMatchesRegularExpression('/data-lead-id="' . $failed->id . '" data-ai-state=""/', $html);
        $this->assertSame(1, substr_count($html, 'data-testid="ai-analyzed-badge"'));
    }

    public function test_estimate_lists_already_analysed_leads_and_other_accounts_do_not_leak(): void
    {
        $user = $this->seller();
        $this->offerFor($user);
        $done = $this->leadFor($user);
        $fresh = $this->leadFor($user);
        $this->analysis($user, $done, 'completed');

        $this->actingAs($user)->postJson('/user/ai-outreach/estimate', ['lead_ids' => [$done->id, $fresh->id]])
            ->assertOk()
            ->assertJsonPath('already_analyzed', 1)
            ->assertJsonPath('analyzed_ids', [$done->id])
            ->assertJsonPath('to_queue', 2); // "Analyse again" is still allowed

        // Another account's analysis of a lead with the same id range is not counted
        $other = $this->seller();
        $this->offerFor($other);
        $otherLead = $this->leadFor($other);
        $this->actingAs($other)->postJson('/user/ai-outreach/estimate', ['lead_ids' => [$otherLead->id]])
            ->assertJsonPath('already_analyzed', 0);
    }
}
