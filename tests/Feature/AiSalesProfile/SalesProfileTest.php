<?php

namespace Tests\Feature\AiSalesProfile;

use App\Models\User;
use App\Models\UserSalesProfile;
use Tests\Feature\Security\SecurityTestCase;

class SalesProfileTest extends SecurityTestCase
{
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Pixel Growth Studio',
            'description' => 'We build fast websites for local businesses.',
            'website' => 'pixelgrowth.example',
            'portfolio_urls' => "https://pixelgrowth.example/work\nhttps://dribbble.com/pixel",
            'target_industries' => "Dentists\nRestaurants, Real estate",
            'target_locations' => "Lahore\nDubai",
            'language' => 'en',
            'tone' => 'friendly',
            'signature' => "Ayesha\nFounder",
            'sender_name' => 'Ayesha Khan',
            'sender_postal_address' => '1 Main Road, Lahore, Pakistan',
            'ai_instructions' => 'Keep emails under 120 words.',
        ], $overrides);
    }

    // ── Access ───────────────────────────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/user/ai-sales-profile')->assertRedirect();
        $this->put('/user/ai-sales-profile', $this->validPayload())->assertRedirect();
        $this->assertSame(0, UserSalesProfile::count());
    }

    public function test_user_without_subscription_is_sent_to_subscription_page(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get('/user/ai-sales-profile')->assertRedirect(route('user.subscription'));
    }

    public function test_empty_profile_page_renders_with_empty_states(): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)->get('/user/ai-sales-profile')
            ->assertOk()
            ->assertSee('My AI Sales Profile')
            ->assertSee('No skills yet')
            ->assertSee('No services yet')
            ->assertSee('No offers yet')
            ->assertSee('AI not configured yet')
            ->assertSee('Save profile');
    }

    public function test_sidebar_shows_the_menu_item(): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)->get('/user/leads')
            ->assertOk()
            ->assertSee(route('user.ai-profile.index'), false)
            ->assertSee('AI Sales Profile');
    }

    // ── Create / update ──────────────────────────────────────────────────

    public function test_owner_can_create_and_update_profile(): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)->put('/user/ai-sales-profile', $this->validPayload())
            ->assertRedirect(route('user.ai-profile.index'))
            ->assertSessionHas('success');

        $profile = UserSalesProfile::where('owner_user_id', $user->id)->firstOrFail();
        $this->assertSame('Pixel Growth Studio', $profile->company_name);
        $this->assertSame('https://pixelgrowth.example', $profile->website);
        $this->assertSame(['https://pixelgrowth.example/work', 'https://dribbble.com/pixel'], $profile->portfolio_urls);
        $this->assertSame(['Dentists', 'Restaurants', 'Real estate'], $profile->target_industries);
        $this->assertSame(['Lahore', 'Dubai'], $profile->target_locations);
        $this->assertSame('friendly', $profile->tone);
        $this->assertSame($user->id, $profile->updated_by_user_id);

        // Second save updates the same row (one profile per account)
        $this->actingAs($user)->put('/user/ai-sales-profile', $this->validPayload(['company_name' => 'Renamed Studio', 'portfolio_urls' => '']))
            ->assertRedirect();

        $this->assertSame(1, UserSalesProfile::where('owner_user_id', $user->id)->count());
        $profile->refresh();
        $this->assertSame('Renamed Studio', $profile->company_name);
        $this->assertSame([], $profile->portfolio_urls);

        $this->actingAs($user)->get('/user/ai-sales-profile')
            ->assertOk()
            ->assertSee('Renamed Studio')
            ->assertSee('Dentists');
    }

    public function test_duplicate_list_entries_are_removed(): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)->put('/user/ai-sales-profile', $this->validPayload([
            'target_industries' => "Dentists\ndentists\n  Dentists  \nCafes",
        ]))->assertRedirect();

        $this->assertSame(['Dentists', 'Cafes'], UserSalesProfile::firstOrFail()->target_industries);
    }

    // ── Validation ───────────────────────────────────────────────────────

    public function test_unsafe_or_invalid_urls_are_rejected(): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)->put('/user/ai-sales-profile', $this->validPayload(['website' => 'javascript:alert(1)']))
            ->assertSessionHasErrorsIn('profile', 'website');

        $this->actingAs($user)->put('/user/ai-sales-profile', $this->validPayload(['portfolio_urls' => "https://ok.example\ndata:text/html,hi"]))
            ->assertSessionHasErrorsIn('profile', 'portfolio_urls.1');

        $this->assertSame(0, UserSalesProfile::count());
    }

    public function test_list_and_length_limits_are_enforced(): void
    {
        $user = $this->makeSubscribedUser();

        $tooManyUrls = implode("\n", array_map(fn ($i) => "https://site{$i}.example", range(1, 11)));
        $this->actingAs($user)->put('/user/ai-sales-profile', $this->validPayload(['portfolio_urls' => $tooManyUrls]))
            ->assertSessionHasErrorsIn('profile', 'portfolio_urls');

        $tooManyIndustries = implode("\n", array_map(fn ($i) => "Industry {$i}", range(1, 21)));
        $this->actingAs($user)->put('/user/ai-sales-profile', $this->validPayload(['target_industries' => $tooManyIndustries]))
            ->assertSessionHasErrorsIn('profile', 'target_industries');

        $this->actingAs($user)->put('/user/ai-sales-profile', $this->validPayload(['description' => str_repeat('a', 2001)]))
            ->assertSessionHasErrorsIn('profile', 'description');

        $this->actingAs($user)->put('/user/ai-sales-profile', $this->validPayload(['ai_instructions' => str_repeat('a', 2001)]))
            ->assertSessionHasErrorsIn('profile', 'ai_instructions');

        $this->assertSame(0, UserSalesProfile::count());
    }

    public function test_language_and_tone_must_be_known_values(): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)->put('/user/ai-sales-profile', $this->validPayload(['language' => 'xx', 'tone' => 'aggressive']))
            ->assertSessionHasErrorsIn('profile', ['language', 'tone']);
    }

    public function test_owner_cannot_be_mass_assigned(): void
    {
        $user = $this->makeSubscribedUser();
        $victim = $this->makeSubscribedUser();

        $this->actingAs($user)->put('/user/ai-sales-profile', $this->validPayload([
            'owner_user_id' => $victim->id,
            'updated_by_user_id' => $victim->id,
            'id' => 999,
        ]))->assertRedirect();

        $this->assertNull(UserSalesProfile::where('owner_user_id', $victim->id)->first());
        $profile = UserSalesProfile::where('owner_user_id', $user->id)->firstOrFail();
        $this->assertSame($user->id, $profile->updated_by_user_id);
        $this->assertNotSame(999, $profile->id);
    }

    public function test_profile_values_are_rendered_escaped(): void
    {
        $user = $this->makeSubscribedUser();
        $this->actingAs($user)->put('/user/ai-sales-profile', $this->validPayload([
            'company_name' => '<script>alert("x")</script>',
            'description' => '<img src=x onerror=alert(1)>',
        ]))->assertRedirect();

        $this->actingAs($user)->get('/user/ai-sales-profile')
            ->assertOk()
            ->assertDontSee('<script>alert("x")</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    // ── Ownership and teams ──────────────────────────────────────────────

    public function test_accounts_cannot_see_each_others_profiles(): void
    {
        $alice = $this->makeSubscribedUser();
        $bob = $this->makeSubscribedUser();

        $this->actingAs($alice)->put('/user/ai-sales-profile', $this->validPayload(['company_name' => 'Alice Secret Agency']));

        $this->actingAs($bob)->get('/user/ai-sales-profile')
            ->assertOk()
            ->assertDontSee('Alice Secret Agency');

        // Bob saving creates his own profile, Alice's is untouched
        $this->actingAs($bob)->put('/user/ai-sales-profile', $this->validPayload(['company_name' => 'Bob Co']));
        $this->assertSame('Alice Secret Agency', UserSalesProfile::where('owner_user_id', $alice->id)->value('company_name'));
        $this->assertSame(2, UserSalesProfile::count());
    }

    public function test_company_owner_edits_and_team_member_reads_company_profile(): void
    {
        $company = $this->makeSubscribedUser(['user_type' => 'company']);
        $member = $this->makeUser(['company_id' => $company->id]);

        $this->actingAs($company)->put('/user/ai-sales-profile', $this->validPayload(['company_name' => 'Company Brand']))
            ->assertRedirect();

        $this->actingAs($member)->get('/user/ai-sales-profile')
            ->assertOk()
            ->assertSee('Company Brand')
            ->assertSee('Only the company account owner can change it')
            ->assertDontSee('Save profile');

        $this->actingAs($member)->put('/user/ai-sales-profile', $this->validPayload(['company_name' => 'Hijacked']))
            ->assertForbidden();

        $this->assertSame('Company Brand', UserSalesProfile::where('owner_user_id', $company->id)->value('company_name'));
        $this->assertNull(UserSalesProfile::where('owner_user_id', $member->id)->first());
    }

    // ── Plan features ────────────────────────────────────────────────────

    public function test_feature_is_on_by_default_when_package_does_not_define_it(): void
    {
        $user = $this->makeSubscribedUser(); // package has no ai_* keys

        $this->actingAs($user)->get('/user/ai-sales-profile')->assertOk();
    }

    public function test_package_can_disable_the_feature(): void
    {
        $user = $this->makeSubscribedUser([], ['ai_sales_profile_enabled' => 'false']);

        $this->actingAs($user)->get('/user/ai-sales-profile')->assertRedirect(route('user.subscription'));
        $this->actingAs($user)->put('/user/ai-sales-profile', $this->validPayload())->assertRedirect(route('user.subscription'));
        $this->actingAs($user)->postJson('/user/ai-sales-profile/offers', ['type' => 'skill', 'title' => 'X'])->assertForbidden();

        $this->actingAs($user)->get('/user/leads')->assertOk()->assertDontSee(route('user.ai-profile.index'), false);
        $this->assertSame(0, UserSalesProfile::count());
    }

    public function test_team_member_follows_company_plan(): void
    {
        $company = $this->makeSubscribedUser(['user_type' => 'company'], ['ai_sales_profile_enabled' => 'false']);
        $member = $this->makeUser(['company_id' => $company->id]);

        $this->actingAs($member)->get('/user/ai-sales-profile')->assertRedirect(route('user.subscription'));
    }

    public function test_quota_usage_is_shown_from_plan(): void
    {
        $user = $this->makeSubscribedUser([], ['ai_audits_monthly' => '25', 'ai_email_drafts_monthly' => 'unlimited']);

        $this->actingAs($user)->get('/user/ai-sales-profile')
            ->assertOk()
            ->assertSee('/ 25')
            ->assertSee('/ unlimited');
    }
}
