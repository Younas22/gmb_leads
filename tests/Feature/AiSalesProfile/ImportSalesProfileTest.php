<?php

namespace Tests\Feature\AiSalesProfile;

use App\Models\User;
use App\Models\UserOffer;
use App\Models\UserSalesProfile;
use App\Services\Ai\SalesProfileContext;
use Tests\Feature\Security\SecurityTestCase;

/** sales-profile:import — the LimoSchedule profile file and the command's safety rules. */
class ImportSalesProfileTest extends SecurityTestCase
{
    private const FILE = 'database/data/sales-profiles/limoschedule.json';

    private function import(User $owner, array $options = [])
    {
        return $this->artisan('sales-profile:import', array_merge(['file' => self::FILE, '--owner' => $owner->email], $options));
    }

    public function test_dry_run_writes_nothing(): void
    {
        $owner = $this->makeSubscribedUser();

        $this->import($owner)->expectsOutputToContain('DRY RUN')->assertSuccessful();

        $this->assertSame(0, UserSalesProfile::count());
        $this->assertSame(0, UserOffer::count());
    }

    public function test_apply_imports_the_whole_profile_once_and_is_idempotent(): void
    {
        $owner = $this->makeSubscribedUser();
        $other = $this->makeSubscribedUser();
        $otherOffer = new UserOffer(['type' => 'service', 'title' => 'Complete Limo Booking Software', 'description' => 'theirs']);
        $otherOffer->owner_user_id = $other->id;
        $otherOffer->save();

        $this->import($owner, ['--apply' => true])->assertSuccessful();
        $this->import($owner, ['--apply' => true])->expectsOutputToContain('0 create, 0 update, 37 unchanged')->assertSuccessful();

        $p = UserSalesProfile::where('owner_user_id', $owner->id)->sole();
        $this->assertSame('LimoSchedule', $p->company_name);
        $this->assertSame('https://limoschedule.com', $p->website);
        $this->assertCount(20, $p->target_industries);
        $this->assertCount(20, $p->target_locations);
        $this->assertNull($p->sender_postal_address, 'No invented postal address');
        $this->assertSame(89, $p->completeness(UserOffer::ownedBy($owner)->active()->count()));
        foreach (['skill' => 20, 'service' => 12, 'offer' => 5] as $type => $n) {
            $this->assertSame($n, UserOffer::ownedBy($owner)->where('type', $type)->count(), "{$type} count");
        }
        $this->assertSame('theirs', $otherOffer->fresh()->description, 'Other accounts are never touched');
        $this->assertSame(0, UserSalesProfile::where('owner_user_id', $other->id)->count());

        // The UI form can save the imported profile again (limits respected, no comma-split overflow)
        $this->actingAs($owner)->put('/user/ai-sales-profile', [
            'company_name' => $p->company_name, 'description' => $p->description, 'website' => $p->website,
            'portfolio_urls' => implode("\n", $p->portfolio_urls), 'target_industries' => implode("\n", $p->target_industries),
            'target_locations' => implode("\n", $p->target_locations), 'language' => $p->language, 'tone' => $p->tone,
            'signature' => $p->signature, 'sender_name' => $p->sender_name, 'ai_instructions' => $p->ai_instructions,
        ])->assertRedirect()->assertSessionDoesntHaveErrors(null, null, 'profile');
        $this->assertCount(20, $p->fresh()->target_locations);

        // Why the import uses "Atlanta (Georgia USA)": the form also splits on commas, so the
        // original "Atlanta, Georgia, USA" style would exceed the 20-location limit on save.
        $withCommas = array_map(fn ($l) => str_replace([' (', ')'], [', ', ''], $l), $p->target_locations);
        $this->actingAs($owner)->put('/user/ai-sales-profile', [
            'language' => 'en', 'tone' => 'professional', 'target_locations' => implode("\n", $withCommas),
        ])->assertSessionHasErrorsIn('profile', ['target_locations']);

        // AI context sees the matching data
        $ctx = app(SalesProfileContext::class)->forUser($owner);
        $this->assertCount(20, $ctx['skills']);
        $this->assertSame('Websites with no visible fare estimator', collect($ctx['services'])->firstWhere('title', 'Online Fare Calculator')['ideal_for']);
        $this->assertSame('Starting at $1,999 - one-time license', $ctx['offers'][0]['price_hint']);
    }

    public function test_existing_filled_profile_is_not_overwritten_without_flag(): void
    {
        $owner = $this->makeSubscribedUser();
        $profile = new UserSalesProfile(['company_name' => 'Something Else', 'description' => 'Kept']);
        $profile->owner_user_id = $owner->id;
        $profile->save();

        $this->import($owner, ['--apply' => true])->expectsOutputToContain('Nothing was written')->assertFailed();
        $this->assertSame('Something Else', $profile->fresh()->company_name);
        $this->assertSame(0, UserOffer::count());

        $this->import($owner, ['--apply' => true, '--replace-profile' => true])->assertSuccessful();
        $this->assertSame('LimoSchedule', $profile->fresh()->company_name);
    }

    public function test_only_an_exact_profile_owner_account_can_be_targeted(): void
    {
        $this->artisan('sales-profile:import', ['file' => self::FILE])->expectsOutputToContain('--owner=EMAIL')->assertFailed();
        $this->artisan('sales-profile:import', ['file' => self::FILE, '--owner' => 'nobody@example.test', '--apply' => true])->assertFailed();
        $this->import($this->makeAdmin(), ['--apply' => true])->expectsOutputToContain('cannot own')->assertFailed();

        $company = $this->makeSubscribedUser(['user_type' => 'company']);
        $member = $this->makeUser(['company_id' => $company->id]);
        $this->import($member, ['--apply' => true])->expectsOutputToContain('cannot own')->assertFailed();

        $this->assertSame(0, UserSalesProfile::count());
    }

    public function test_invalid_data_is_rejected_with_the_ui_rules(): void
    {
        $owner = $this->makeSubscribedUser();
        $data = json_decode(file_get_contents(base_path(self::FILE)), true);
        $data['profile']['ai_instructions'] = str_repeat('x', 2001);
        $path = sys_get_temp_dir() . '/cnm-profile-' . uniqid() . '.json';
        file_put_contents($path, json_encode($data));

        $this->artisan('sales-profile:import', ['file' => $path, '--owner' => $owner->email, '--apply' => true])
            ->expectsOutputToContain('2000')->assertFailed();
        @unlink($path);
        $this->assertSame(0, UserSalesProfile::count());
    }
}
