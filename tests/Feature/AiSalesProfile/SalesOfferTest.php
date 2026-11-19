<?php

namespace Tests\Feature\AiSalesProfile;

use App\Models\UserOffer;
use Tests\Feature\Security\SecurityTestCase;

class SalesOfferTest extends SecurityTestCase
{
    private function addOffer($user, array $data = [])
    {
        return $this->actingAs($user)->post('/user/ai-sales-profile/offers', array_merge([
            'type' => 'service',
            'title' => 'Website redesign',
            'description' => 'Modern, fast, mobile-first.',
            'price_hint' => 'From $499',
            'ideal_for' => 'Businesses with outdated sites',
            'is_active' => '1',
        ], $data));
    }

    private function makeOffer($owner, array $attrs = []): UserOffer
    {
        $offer = new UserOffer(array_merge(['type' => 'service', 'title' => 'Owned service', 'is_active' => true], $attrs));
        $offer->owner_user_id = $owner->id;
        $offer->save();

        return $offer;
    }

    // ── CRUD ─────────────────────────────────────────────────────────────

    public function test_owner_can_add_skills_services_and_offers(): void
    {
        $user = $this->makeSubscribedUser();

        $this->addOffer($user, ['type' => 'skill', 'main_skill' => 'Web Development', 'title' => 'Laravel Development', 'price_hint' => null, 'ideal_for' => null])->assertRedirect();
        $this->addOffer($user, ['type' => 'service', 'title' => 'Local SEO'])->assertRedirect();
        $this->addOffer($user, ['type' => 'service', 'title' => 'Google Ads'])->assertRedirect();
        $this->addOffer($user, ['type' => 'offer', 'title' => 'Free website audit', 'price_hint' => 'Free'])->assertRedirect();

        $this->assertSame(4, UserOffer::where('owner_user_id', $user->id)->count());
        $services = UserOffer::where('owner_user_id', $user->id)->where('type', 'service')->orderBy('sort_order')->pluck('title')->all();
        $this->assertSame(['Local SEO', 'Google Ads'], $services);
        $this->assertSame([1, 2], UserOffer::where('type', 'service')->orderBy('sort_order')->pluck('sort_order')->all());

        $this->actingAs($user)->get('/user/ai-sales-profile')
            ->assertOk()
            ->assertSee('Laravel Development')
            ->assertSee('Local SEO')
            ->assertSee('Free website audit')
            ->assertDontSee('class="cnm-empty p-8 text-center "', false); // every empty state is hidden
    }

    public function test_owner_can_edit_toggle_and_delete(): void
    {
        $user = $this->makeSubscribedUser();
        $offer = $this->makeOffer($user, ['title' => 'Old title']);
        $this->makeOffer($user, ['title' => 'Second service']); // services keep a minimum of 1

        $this->actingAs($user)->put("/user/ai-sales-profile/offers/{$offer->id}", [
            'type' => 'service', 'title' => 'New title', 'description' => 'Updated', 'is_active' => '1',
        ])->assertRedirect();
        $this->assertSame('New title', $offer->fresh()->title);

        $this->actingAs($user)->patch("/user/ai-sales-profile/offers/{$offer->id}/toggle")->assertRedirect();
        $this->assertFalse($offer->fresh()->is_active);

        $this->actingAs($user)->patchJson("/user/ai-sales-profile/offers/{$offer->id}/toggle")
            ->assertOk()->assertJson(['success' => true, 'is_active' => true]);

        $this->actingAs($user)->delete("/user/ai-sales-profile/offers/{$offer->id}")->assertRedirect();
        $this->assertNull($offer->fresh());
    }

    public function test_disabled_items_are_shown_as_disabled(): void
    {
        $user = $this->makeSubscribedUser();
        $this->makeOffer($user, ['title' => 'Paused service', 'is_active' => false]);

        $this->actingAs($user)->get('/user/ai-sales-profile')
            ->assertOk()
            ->assertSee('Paused service')
            ->assertSee('Disabled')
            ->assertSee('Enable');
    }

    // ── Validation ───────────────────────────────────────────────────────

    public function test_offer_validation(): void
    {
        $user = $this->makeSubscribedUser();

        $this->addOffer($user, ['title' => ''])->assertSessionHasErrorsIn('offer', 'title');
        $this->addOffer($user, ['type' => 'secret'])->assertSessionHasErrorsIn('offer', 'type');
        $this->addOffer($user, ['title' => str_repeat('x', 151)])->assertSessionHasErrorsIn('offer', 'title');
        $this->addOffer($user, ['description' => str_repeat('x', 1001)])->assertSessionHasErrorsIn('offer', 'description');
        $this->addOffer($user, ['price_hint' => str_repeat('x', 101)])->assertSessionHasErrorsIn('offer', 'price_hint');

        $this->assertSame(0, UserOffer::count());
    }

    public function test_items_per_type_are_limited(): void
    {
        config(['ai.max_offers_per_type' => 2]);
        $user = $this->makeSubscribedUser();

        $this->addOffer($user, ['type' => 'offer', 'title' => 'One']);
        $this->addOffer($user, ['type' => 'offer', 'title' => 'Two']);
        $this->addOffer($user, ['type' => 'offer', 'title' => 'Three'])->assertSessionHasErrorsIn('offer', 'title');
        $this->addOffer($user, ['type' => 'skill', 'main_skill' => 'SEO', 'title' => 'Local SEO'])->assertSessionDoesntHaveErrors();

        $this->assertSame(2, UserOffer::where('type', 'offer')->count());
    }

    public function test_services_are_between_one_and_five(): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)->get('/user/ai-sales-profile')->assertOk()
            ->assertSee('Add at least 1 active service', false);

        foreach (range(1, 5) as $i) {
            $this->addOffer($user, ['title' => "Service {$i}"])->assertSessionDoesntHaveErrors();
        }
        $this->addOffer($user, ['title' => 'Service 6'])->assertSessionHasErrorsIn('offer', 'title');
        $this->assertSame(5, UserOffer::where('type', 'service')->count());

        $this->actingAs($user)->get('/user/ai-sales-profile')->assertOk()
            ->assertSee('<span class="cnm-count">5</span> / 5', false)
            ->assertSee('maximum of 5 services')
            ->assertSee('cnm-add border-t border-gray-100 group hidden', false); // add form hidden

        // Down to the last one: it can be neither deleted nor disabled
        $services = UserOffer::where('type', 'service')->orderBy('id')->get();
        foreach ($services->take(4) as $s) {
            $this->actingAs($user)->delete("/user/ai-sales-profile/offers/{$s->id}")->assertSessionMissing('error');
        }
        $last = $services->last();
        $this->actingAs($user)->delete("/user/ai-sales-profile/offers/{$last->id}")->assertSessionHas('error');
        $this->actingAs($user)->patchJson("/user/ai-sales-profile/offers/{$last->id}/toggle")->assertStatus(422);
        $this->assertTrue($last->fresh()->is_active);
    }

    // ── Skills: main → sub skill catalog, 3..10 ─────────────────────────

    private function addSkill($user, string $main, string $sub)
    {
        return $this->addOffer($user, ['type' => 'skill', 'main_skill' => $main, 'title' => $sub, 'price_hint' => null, 'ideal_for' => null]);
    }

    /** @return list<array{0:string,1:string}> */
    private function catalogPairs(int $n): array
    {
        $pairs = [];
        foreach (config('skills.catalog') as $main => $subs) {
            foreach ($subs as $sub) {
                $pairs[] = [$main, $sub];
                if (count($pairs) === $n) {
                    return $pairs;
                }
            }
        }

        return $pairs;
    }

    public function test_skill_catalog_has_unique_sub_skills_and_many_categories(): void
    {
        $catalog = config('skills.catalog');
        $this->assertGreaterThanOrEqual(25, count($catalog));
        $all = array_map('strtolower', array_merge(...array_values($catalog)));
        $this->assertSame(count($all), count(array_unique($all)), 'Sub skill names must be unique');
        foreach ($catalog as $subs) {
            $this->assertNotEmpty($subs);
        }
    }

    public function test_skill_must_come_from_the_catalog_and_match_its_main_skill(): void
    {
        $user = $this->makeSubscribedUser();

        $this->addSkill($user, 'Web Development', 'Made up skill')->assertSessionHasErrorsIn('offer', 'title');
        $this->addSkill($user, 'SEO', 'Laravel Development')->assertSessionHasErrorsIn('offer', 'title');
        $this->addSkill($user, 'Not a category', 'Laravel Development')->assertSessionHasErrorsIn('offer', 'main_skill');
        $this->addOffer($user, ['type' => 'skill', 'title' => 'Laravel Development'])->assertSessionHasErrorsIn('offer', 'main_skill');
        $this->assertSame(0, UserOffer::count());

        $this->addSkill($user, 'Web Development', 'Laravel Development')->assertSessionDoesntHaveErrors();
        $this->addSkill($user, 'Web Development', 'Laravel Development')->assertSessionHasErrorsIn('offer', 'title'); // duplicate
        $this->assertSame(1, UserOffer::count());

        $this->actingAs($user)->get('/user/ai-sales-profile')->assertOk()
            ->assertSee('Web Development')
            ->assertSee('Add at least 3 active skills', false);
    }

    public function test_at_most_ten_skills(): void
    {
        $user = $this->makeSubscribedUser();
        $pairs = $this->catalogPairs(11);

        foreach (array_slice($pairs, 0, 10) as [$main, $sub]) {
            $this->addSkill($user, $main, $sub)->assertSessionDoesntHaveErrors();
        }
        $this->addSkill($user, ...$pairs[10])->assertSessionHasErrorsIn('offer', 'title');
        $this->assertSame(10, UserOffer::where('type', 'skill')->count());

        $this->actingAs($user)->get('/user/ai-sales-profile')->assertOk()
            ->assertSee('<span class="cnm-count">10</span> / 10', false)
            ->assertSee('maximum of 10 skills')
            ->assertSee('cnm-add border-t border-gray-100 group hidden', false); // add form hidden
    }

    public function test_skills_cannot_go_below_three(): void
    {
        $user = $this->makeSubscribedUser();
        foreach ($this->catalogPairs(4) as [$main, $sub]) {
            $this->addSkill($user, $main, $sub);
        }
        $skills = UserOffer::where('type', 'skill')->orderBy('id')->get();

        // 4 → 3 is fine
        $this->actingAs($user)->delete("/user/ai-sales-profile/offers/{$skills[0]->id}")->assertRedirect();
        $this->assertNull($skills[0]->fresh());

        // 3 → 2 is blocked, for delete and for disabling
        $this->actingAs($user)->delete("/user/ai-sales-profile/offers/{$skills[1]->id}")->assertSessionHas('error');
        $this->assertNotNull($skills[1]->fresh());
        $this->actingAs($user)->patchJson("/user/ai-sales-profile/offers/{$skills[1]->id}/toggle")->assertStatus(422);
        $this->assertTrue($skills[1]->fresh()->is_active);

        // Offers have no minimum
        $special = $this->makeOffer($user, ['type' => 'offer']);
        $this->actingAs($user)->delete("/user/ai-sales-profile/offers/{$special->id}")->assertRedirect();
        $this->assertNull($special->fresh());
    }

    public function test_delete_works_over_ajax_without_reload(): void
    {
        $user = $this->makeSubscribedUser();
        $a = $this->makeOffer($user, ['title' => 'Service A']);
        $b = $this->makeOffer($user, ['title' => 'Service B']);

        $page = $this->actingAs($user)->get('/user/ai-sales-profile')->assertOk();
        $page->assertSee('class="cnm-del"', false)->assertSee('data-min="1"', false)->assertSee('data-max="5"', false);

        $this->actingAs($user)->deleteJson("/user/ai-sales-profile/offers/{$a->id}")->assertOk()->assertJson(['success' => true]);
        $this->assertNull($a->fresh());

        // The last service: refused with a message the page can show
        $this->actingAs($user)->deleteJson("/user/ai-sales-profile/offers/{$b->id}")
            ->assertStatus(422)->assertJson(['success' => false])->assertJsonPath('message', 'You need at least 1 active service. Add another service first.');
        $this->assertNotNull($b->fresh());
    }

    public function test_skill_can_be_changed_to_another_sub_skill(): void
    {
        $user = $this->makeSubscribedUser();
        $this->addSkill($user, 'Web Development', 'Laravel Development');
        $this->addSkill($user, 'SEO', 'Local SEO');
        $skill = UserOffer::where('title', 'Laravel Development')->first();

        $this->actingAs($user)->put("/user/ai-sales-profile/offers/{$skill->id}", [
            'type' => 'skill', 'main_skill' => 'SEO', 'title' => 'Local SEO', 'is_active' => '1',
        ])->assertSessionHasErrorsIn('offer', 'title'); // already have it

        $this->actingAs($user)->put("/user/ai-sales-profile/offers/{$skill->id}", [
            'type' => 'skill', 'main_skill' => 'Design & Creative', 'title' => 'Logo Design', 'is_active' => '1',
        ])->assertSessionDoesntHaveErrors();
        $this->assertSame('Logo Design', $skill->fresh()->title);

        // Saving it unchanged is not a duplicate of itself
        $this->actingAs($user)->put("/user/ai-sales-profile/offers/{$skill->id}", [
            'type' => 'skill', 'main_skill' => 'Design & Creative', 'title' => 'Logo Design', 'is_active' => '1',
        ])->assertSessionDoesntHaveErrors();
    }

    public function test_owner_and_sort_order_cannot_be_mass_assigned_to_another_account(): void
    {
        $user = $this->makeSubscribedUser();
        $victim = $this->makeSubscribedUser();

        $this->addOffer($user, ['owner_user_id' => $victim->id])->assertRedirect();

        $this->assertSame(0, UserOffer::where('owner_user_id', $victim->id)->count());
        $this->assertSame(1, UserOffer::where('owner_user_id', $user->id)->count());
    }

    public function test_offer_text_is_rendered_escaped(): void
    {
        $user = $this->makeSubscribedUser();
        $this->addOffer($user, ['title' => '<script>alert(1)</script>', 'ideal_for' => '"><img src=x onerror=alert(2)>']);

        $this->actingAs($user)->get('/user/ai-sales-profile')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(2)>', false);
    }

    // ── Ownership and teams ──────────────────────────────────────────────

    public function test_other_accounts_items_cannot_be_changed(): void
    {
        $alice = $this->makeSubscribedUser();
        $bob = $this->makeSubscribedUser();
        $bobOffer = $this->makeOffer($bob, ['title' => 'Bob service']);

        $this->actingAs($alice)->put("/user/ai-sales-profile/offers/{$bobOffer->id}", [
            'type' => 'service', 'title' => 'Hacked',
        ])->assertNotFound();
        $this->actingAs($alice)->patch("/user/ai-sales-profile/offers/{$bobOffer->id}/toggle")->assertNotFound();
        $this->actingAs($alice)->delete("/user/ai-sales-profile/offers/{$bobOffer->id}")->assertNotFound();

        $fresh = $bobOffer->fresh();
        $this->assertSame('Bob service', $fresh->title);
        $this->assertTrue($fresh->is_active);

        $this->actingAs($alice)->get('/user/ai-sales-profile')->assertOk()->assertDontSee('Bob service');
    }

    public function test_team_member_sees_company_items_but_cannot_change_them(): void
    {
        $company = $this->makeSubscribedUser(['user_type' => 'company']);
        $member = $this->makeUser(['company_id' => $company->id]);
        $offer = $this->makeOffer($company, ['title' => 'Company service']);

        $this->actingAs($member)->get('/user/ai-sales-profile')
            ->assertOk()
            ->assertSee('Company service')
            ->assertDontSee('Add service');

        $this->addOffer($member)->assertForbidden();
        $this->actingAs($member)->put("/user/ai-sales-profile/offers/{$offer->id}", ['type' => 'service', 'title' => 'X'])->assertForbidden();
        $this->actingAs($member)->patch("/user/ai-sales-profile/offers/{$offer->id}/toggle")->assertForbidden();
        $this->actingAs($member)->delete("/user/ai-sales-profile/offers/{$offer->id}")->assertForbidden();

        $this->assertSame(1, UserOffer::count());
        $this->assertTrue($offer->fresh()->is_active);
    }

    public function test_deleting_an_account_removes_its_profile_data(): void
    {
        $user = $this->makeSubscribedUser();
        $this->makeOffer($user);
        $this->actingAs($user)->put('/user/ai-sales-profile', [
            'company_name' => 'Gone Co', 'language' => 'en', 'tone' => 'professional',
        ]);

        $user->delete();

        $this->assertSame(0, UserOffer::count());
        $this->assertSame(0, \App\Models\UserSalesProfile::count());
    }
}
