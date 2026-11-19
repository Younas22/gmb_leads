<?php

namespace App\Http\Controllers\AiSalesProfile;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiSalesProfile\UpdateSalesProfileRequest;
use App\Models\UserOffer;
use App\Models\UserSalesProfile;
use App\Services\Ai\AiService;
use App\Services\PlanFeatures;
use Illuminate\Http\Request;

/**
 * "My AI Sales Profile". The profile belongs to the account owner (the user,
 * or the company for team members). Team members can view it; only the
 * account owner can change it (Gate "manage-sales-profile").
 */
class SalesProfileController extends Controller
{
    public function index(Request $request, AiService $ai)
    {
        $user = $request->user();
        $owner = $user->getAccountOwner();

        $profile = UserSalesProfile::forOwner($owner);
        $offers = UserOffer::ownedBy($owner)->ordered()->get()->groupBy('type');
        $activeOfferCount = $offers->flatten()->where('is_active', true)->count();

        $quota = $ai->quota();
        $aiUsage = [];
        foreach (config('ai.quota_features') as $feature => $key) {
            $limit = PlanFeatures::limit($user, $key);
            $aiUsage[$feature] = [
                'limit' => $limit,
                'used' => $quota->used($owner, $feature),
            ];
        }

        return view('user.ai-sales-profile.index', [
            'profile' => $profile,
            'offers' => $offers,
            'offerTypes' => UserOffer::TYPES,
            'canEdit' => $user->can('manage-sales-profile'),
            'isTeamMember' => $user->isTeamMember(),
            'completeness' => $profile->completeness($activeOfferCount),
            'languages' => config('ai.languages'),
            'tones' => config('ai.tones'),
            'aiAvailable' => $ai->isAvailable(),
            'aiUsage' => $aiUsage,
            // Same niches and countries as Find Leads, for the target pickers (states/cities load on demand)
            'nicheOptions' => \App\Models\Niche::query()->distinct()->orderBy('name')->pluck('name'),
            'countryOptions' => \App\Models\Country::orderBy('name')->get(['id', 'name']),
            // Offers section: pick one of the 16 offer types (AI Offer Builder) right here
            'offerCategories' => $offerBuilderReady = \Illuminate\Support\Facades\Schema::hasTable('builder_offer_generations')
                ? \App\Models\OfferCategory::active()->with(['strategies' => fn ($q) => $q->where('is_active', true)])->get()
                : collect(),
            'offerLinks' => $offerBuilderReady ? \App\Services\OfferBuilder\ProfileOfferSync::linksFor($user) : [],
        ]);
    }

    public function update(UpdateSalesProfileRequest $request)
    {
        $user = $request->user();
        $owner = $user->getAccountOwner();

        $profile = UserSalesProfile::forOwner($owner);
        $profile->fill($request->profileData());
        $profile->owner_user_id = $owner->id;       // never from input
        $profile->updated_by_user_id = $user->id;
        $profile->save();

        return redirect()->route('user.ai-profile.index')->with('success', 'Your AI Sales Profile has been saved.');
    }
}
