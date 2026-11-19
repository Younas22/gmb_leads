<?php

namespace App\Http\Controllers\AiSalesProfile;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiSalesProfile\SaveUserOfferRequest;
use App\Models\UserOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Skills / services / offers of the account owner. Every lookup is scoped to
 * the owner, so another account's item is simply "not found" (404).
 */
class SalesOfferController extends Controller
{
    public function store(SaveUserOfferRequest $request)
    {
        $owner = $request->user()->getAccountOwner();
        $data = $request->validated();

        $count = UserOffer::ownedBy($owner)->where('type', $data['type'])->count();
        $max = UserOffer::limits($data['type'])['max'];
        if ($count >= $max) {
            $plural = strtolower(UserOffer::TYPES[$data['type']]) . 's';
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => "You can add up to {$max} {$plural}. Remove one first."], 422);
            }
            return $this->back($data['type'])
                ->withErrors(['title' => "You can add up to {$max} {$plural}. Remove one first."], 'offer')
                ->withInput();
        }

        $offer = new UserOffer($data);
        $offer->owner_user_id = $owner->id;   // never from input
        $offer->sort_order = (int) UserOffer::ownedBy($owner)->where('type', $data['type'])->max('sort_order') + 1;
        $offer->save();

        if ($request->expectsJson()) {
            // Used by "+ Add a new service" inside the offer form
            return response()->json(['ok' => true, 'id' => $offer->id, 'title' => $offer->title, 'price_hint' => $offer->price_hint, 'description' => $offer->description]);
        }

        return $this->back($offer->type)->with('success', UserOffer::TYPES[$offer->type] . ' added.');
    }

    public function update(SaveUserOfferRequest $request, int $offer)
    {
        $item = $this->findOwned($request, $offer);
        $item->fill($request->validated())->save();

        return $this->back($item->type)->with('success', UserOffer::TYPES[$item->type] . ' updated.');
    }

    public function toggle(Request $request, int $offer)
    {
        Gate::authorize('manage-sales-profile');
        $item = $this->findOwned($request, $offer);

        if ($item->is_active && $this->wouldDropBelowMin($item, activeOnly: true)) {
            return $this->minError($request, $item);
        }

        $item->is_active = !$item->is_active;
        $item->save();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'is_active' => $item->is_active]);
        }

        return $this->back($item->type)->with('success', UserOffer::TYPES[$item->type] . ($item->is_active ? ' enabled.' : ' disabled.'));
    }

    public function destroy(Request $request, int $offer)
    {
        Gate::authorize('manage-sales-profile');
        $item = $this->findOwned($request, $offer);
        $type = $item->type;

        if ($this->wouldDropBelowMin($item, activeOnly: false)) {
            return $this->minError($request, $item);
        }

        $item->delete();
        if ($type === UserOffer::TYPE_OFFER && \Illuminate\Support\Facades\Schema::hasTable('builder_offers')) {
            // Its AI messages / saved answers go with it
            \App\Services\OfferBuilder\ProfileOfferSync::forgetProfileOffer($request->user()->getAccountOwner(), (int) $item->id);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return $this->back($type)->with('success', UserOffer::TYPES[$type] . ' removed.');
    }

    /** An account keeps at least UserOffer::limits()['min'] items of a type (active ones when disabling). */
    private function wouldDropBelowMin(UserOffer $item, bool $activeOnly): bool
    {
        $min = UserOffer::limits($item->type)['min'];
        if ($min < 1) {
            return false;
        }
        $count = UserOffer::where('owner_user_id', $item->owner_user_id)
            ->where('type', $item->type)
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->count();

        return $count <= $min;
    }

    private function minError(Request $request, UserOffer $item)
    {
        $min = UserOffer::limits($item->type)['min'];
        $noun = strtolower(UserOffer::TYPES[$item->type]) . ($min === 1 ? '' : 's');
        $message = "You need at least {$min} active {$noun}. Add another " . strtolower(UserOffer::TYPES[$item->type]) . ' first.';
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return $this->back($item->type)->with('error', $message);
    }

    private function findOwned(Request $request, int $id): UserOffer
    {
        return UserOffer::ownedBy($request->user()->getAccountOwner())->findOrFail($id);
    }

    private function back(string $type)
    {
        return redirect()->to(route('user.ai-profile.index') . '#' . $type . 's');
    }
}
