<?php

namespace Tests\Feature\Security;

use App\Models\SavedLead;
use App\Models\SearchHistory;
use App\Models\User;

class LeadOwnershipTest extends SecurityTestCase
{
    private function makeTeamMember(User $company, array $attributes = []): User
    {
        return $this->makeUser(array_merge(['company_id' => $company->id], $attributes));
    }

    // ── Leads list (the user_id filter IDOR) ─────────────────────────────

    public function test_user_cannot_list_another_accounts_leads_via_user_id_filter(): void
    {
        $alice = $this->makeSubscribedUser();
        $bob = $this->makeSubscribedUser();
        $bobLead = $this->makeLead($bob, ['name' => 'Bob Secret Bakery']);

        $this->actingAs($alice)
            ->get('/user/leads?user_id=' . $bob->id)
            ->assertForbidden()
            ->assertDontSee('Bob Secret Bakery');
    }

    public function test_user_lead_list_shows_only_own_leads(): void
    {
        $alice = $this->makeSubscribedUser();
        $bob = $this->makeSubscribedUser();
        $this->makeLead($alice, ['name' => 'Alice Own Plumbing']);
        $this->makeLead($bob, ['name' => 'Bob Secret Bakery']);

        $this->actingAs($alice)
            ->get('/user/leads')
            ->assertOk()
            ->assertSee('Alice Own Plumbing')
            ->assertDontSee('Bob Secret Bakery');
    }

    public function test_company_can_filter_leads_by_its_own_team_member(): void
    {
        $company = $this->makeSubscribedUser(['user_type' => 'company']);
        $member = $this->makeTeamMember($company);
        $this->makeLead($member, ['name' => 'Member Found Dentist']);
        $this->makeLead($company, ['name' => 'Company Own Lead']);

        $this->actingAs($company)
            ->get('/user/leads?user_id=' . $member->id)
            ->assertOk()
            ->assertSee('Member Found Dentist')
            ->assertDontSee('Company Own Lead');

        $this->actingAs($company)
            ->get('/user/leads')
            ->assertOk()
            ->assertSee('Member Found Dentist')
            ->assertSee('Company Own Lead');
    }

    public function test_team_member_sees_company_leads_but_not_other_companies(): void
    {
        $company = $this->makeSubscribedUser(['user_type' => 'company']);
        $member = $this->makeTeamMember($company);
        $otherCompany = $this->makeSubscribedUser(['user_type' => 'company']);

        $this->makeLead($company, ['name' => 'Shared Team Lead']);
        $this->makeLead($otherCompany, ['name' => 'Other Company Lead']);

        $this->actingAs($member)
            ->get('/user/leads')
            ->assertOk()
            ->assertSee('Shared Team Lead')
            ->assertDontSee('Other Company Lead');

        $this->actingAs($member)
            ->get('/user/leads?user_id=' . $otherCompany->id)
            ->assertForbidden();
    }

    // ── Single-lead actions ──────────────────────────────────────────────

    public function test_single_lead_actions_are_blocked_for_other_accounts(): void
    {
        $alice = $this->makeSubscribedUser();
        $bob = $this->makeSubscribedUser();
        $bobLead = $this->makeLead($bob, ['notes' => 'original notes']);

        $this->actingAs($alice)->getJson("/user/leads/{$bobLead->id}")->assertNotFound();
        $this->actingAs($alice)->postJson("/user/leads/{$bobLead->id}/status", ['status' => 'contacted'])->assertNotFound();
        $this->actingAs($alice)->postJson("/user/leads/{$bobLead->id}/notes", ['notes' => 'hacked'])->assertNotFound();
        $this->actingAs($alice)->postJson("/user/leads/{$bobLead->id}/contact", ['channel' => 'email', 'value' => 'x@y.z'])->assertNotFound();
        $this->actingAs($alice)->postJson("/user/leads/{$bobLead->id}/check-seo")->assertNotFound();
        $this->actingAs($alice)->deleteJson("/user/leads/{$bobLead->id}")->assertNotFound();

        $fresh = $bobLead->fresh();
        $this->assertNotNull($fresh);
        $this->assertSame('not_contacted', $fresh->contact_status);
        $this->assertSame('original notes', $fresh->notes);
    }

    public function test_owner_can_view_and_update_own_lead(): void
    {
        $alice = $this->makeSubscribedUser();
        $lead = $this->makeLead($alice);

        $this->actingAs($alice)->getJson("/user/leads/{$lead->id}")->assertOk()->assertJsonPath('success', true);
        $this->actingAs($alice)->postJson("/user/leads/{$lead->id}/status", ['status' => 'contacted'])->assertOk();

        $this->assertSame('contacted', $lead->fresh()->contact_status);
    }

    public function test_bulk_actions_cannot_touch_other_accounts_leads(): void
    {
        $alice = $this->makeSubscribedUser();
        $bob = $this->makeSubscribedUser();
        $aliceLead = $this->makeLead($alice);
        $bobLead = $this->makeLead($bob);

        $this->actingAs($alice)->postJson('/user/leads/bulk', [
            'action' => 'delete',
            'lead_ids' => [$aliceLead->id, $bobLead->id],
        ])->assertForbidden();

        $this->assertNotNull($aliceLead->fresh());
        $this->assertNotNull($bobLead->fresh());

        $this->actingAs($alice)->postJson('/user/leads/bulk', [
            'action' => 'add_to_lead_center',
            'lead_ids' => [$bobLead->id],
        ])->assertNotFound();
    }

    public function test_csv_export_contains_only_own_leads(): void
    {
        $alice = $this->makeSubscribedUser();
        $bob = $this->makeSubscribedUser();
        $this->makeLead($alice, ['name' => 'Alice Export Lead']);
        $this->makeLead($bob, ['name' => 'Bob Export Lead']);

        $response = $this->actingAs($alice)->get('/user/leads/export');
        $response->assertOk();

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Alice Export Lead', $csv);
        $this->assertStringNotContainsString('Bob Export Lead', $csv);
    }

    // ── Search history (same user_id filter pattern) ─────────────────────

    public function test_user_cannot_view_another_accounts_search_history(): void
    {
        $alice = $this->makeSubscribedUser();
        $bob = $this->makeSubscribedUser();

        $this->actingAs($alice)
            ->get('/user/search-history?user_id=' . $bob->id)
            ->assertForbidden();

        $this->actingAs($alice)->get('/user/search-history')->assertOk();
    }

    public function test_company_can_view_team_member_search_history(): void
    {
        $company = $this->makeSubscribedUser(['user_type' => 'company']);
        $member = $this->makeTeamMember($company);

        $this->actingAs($company)
            ->get('/user/search-history?user_id=' . $member->id)
            ->assertOk();
    }
}
