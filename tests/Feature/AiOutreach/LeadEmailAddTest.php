<?php

namespace Tests\Feature\AiOutreach;

use App\Models\AiLeadAnalysis;

/** Missing email: shown as "No email" + "Add email" in My Leads and AI Outreach; one shared lead record. */
class LeadEmailAddTest extends OutreachTestCase
{
    public function test_add_email_from_either_page_shows_on_both(): void
    {
        $user = $this->seller();
        $lead = $this->leadFor($user, ['name' => 'No Mail Dental', 'email' => null]);
        $withMail = $this->leadFor($user, ['name' => 'Mail Dental', 'email' => 'hello@mail.example']);
        foreach ([$lead, $withMail] as $l) {
            AiLeadAnalysis::create(['owner_user_id' => $user->id, 'saved_lead_id' => $l->id, 'status' => 'completed', 'stage' => 'done']);
        }

        $this->actingAs($user)->get(route('user.leads'))->assertOk()
            ->assertSee('data-testid="add-email"', false)
            ->assertSee('hello@mail.example');
        $this->actingAs($user)->get(route('user.ai-outreach.index'))->assertOk()
            ->assertSee('data-testid="no-email-badge"', false)
            ->assertSee('hello@mail.example');

        // Missing / has filters in AI Outreach
        $this->actingAs($user)->get(route('user.ai-outreach.index', ['email' => 'missing']))
            ->assertSee('No Mail Dental')->assertDontSee('hello@mail.example');
        $this->actingAs($user)->get(route('user.ai-outreach.index', ['email' => 'has']))
            ->assertSee('hello@mail.example')->assertDontSee('No Mail Dental');

        // Add it (same endpoint from both pages)
        $this->actingAs($user)->postJson(route('user.leads.contact', $lead->id), ['channel' => 'email', 'value' => ' Owner@NoMail.Example '])
            ->assertOk()->assertJsonPath('email', 'owner@nomail.example');

        $this->actingAs($user)->get(route('user.ai-outreach.index', ['email' => 'has']))->assertSee('owner@nomail.example');
        $this->actingAs($user)->get(route('user.leads'))->assertSee('owner@nomail.example');
    }

    public function test_invalid_email_and_other_accounts_are_refused(): void
    {
        $user = $this->seller();
        $lead = $this->leadFor($user, ['email' => null]);

        $this->actingAs($user)->postJson(route('user.leads.contact', $lead->id), ['channel' => 'email', 'value' => 'not-an-email'])
            ->assertStatus(422);
        $this->assertNull($lead->fresh()->email);

        $other = $this->seller();
        $this->actingAs($other)->postJson(route('user.leads.contact', $lead->id), ['channel' => 'email', 'value' => 'x@y.example'])
            ->assertNotFound();
        $this->assertNull($lead->fresh()->email);
    }
}
