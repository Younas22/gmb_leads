<?php

namespace Tests\Feature\AiSalesProfile;

use App\Models\UserSalesProfile;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Security\SecurityTestCase;

/** AI Sales Profile: target industries / locations pickers use the Find Leads niches and locations. */
class TargetPickersTest extends SecurityTestCase
{
    private function owner()
    {
        return $this->makeSubscribedUser([], ['ai_audits_monthly' => '5', 'ai_email_drafts_monthly' => '5']);
    }

    public function test_profile_page_offers_find_leads_niches_and_countries(): void
    {
        DB::table('niches')->insert([['name' => 'Dentist'], ['name' => 'Limo service'], ['name' => 'Dentist ']]);
        DB::table('countries')->insert([['name' => 'United Arab Emirates'], ['name' => 'United States']]);

        $this->actingAs($this->owner())->get('/user/ai-sales-profile')->assertOk()
            ->assertSee('data-testid="industry-picker"', false)->assertSee('data-testid="location-picker"', false)
            ->assertSee('value="Limo service"', false)->assertSee('United Arab Emirates')
            ->assertSee('api\/states\/__ID__', false) // @json escapes slashes
            // Selected values are shown as removable chips; the (hidden) textarea is what is saved
            ->assertSee('data-testid="target_industries-chips"', false)
            ->assertSee('data-testid="target_locations-chips"', false)
            ->assertSee('id="target_industries-new"', false);
    }

    public function test_states_and_cities_load_for_the_picker(): void
    {
        $countryId = DB::table('countries')->insertGetId(['name' => 'United Arab Emirates']);
        $stateId = DB::table('states')->insertGetId(['country_id' => $countryId, 'name' => 'Dubai']);
        DB::table('cities')->insert(['state_id' => $stateId, 'country_id' => $countryId, 'name' => 'Jumeirah']);
        $user = $this->owner();

        $this->actingAs($user)->getJson("/user/api/states/{$countryId}")->assertOk()->assertJsonFragment(['name' => 'Dubai']);
        $this->actingAs($user)->getJson("/user/api/cities/{$stateId}")->assertOk()->assertJsonFragment(['name' => 'Jumeirah']);
    }

    public function test_picked_values_save_as_one_line_each(): void
    {
        $user = $this->owner();

        // What the pickers write into the boxes: one per line, no commas (the form also splits on commas)
        $this->actingAs($user)->put('/user/ai-sales-profile', [
            'company_name' => 'LimoSchedule', 'language' => 'en', 'tone' => 'professional',
            'target_industries' => "Limo service\nChauffeur service",
            'target_locations' => "Jumeirah (Dubai United Arab Emirates)\nTexas (United States)\nUnited Kingdom",
        ])->assertSessionDoesntHaveErrors(null, null, 'profile');

        $p = UserSalesProfile::where('owner_user_id', $user->id)->sole();
        $this->assertSame(['Limo service', 'Chauffeur service'], $p->target_industries);
        $this->assertSame(['Jumeirah (Dubai United Arab Emirates)', 'Texas (United States)', 'United Kingdom'], $p->target_locations);
    }
}
