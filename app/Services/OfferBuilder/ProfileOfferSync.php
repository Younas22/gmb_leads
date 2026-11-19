<?php

namespace App\Services\OfferBuilder;

use App\Models\BuilderOffer;
use App\Models\User;
use App\Models\UserOffer;

/**
 * Keeps a generated Offer Builder offer in the AI Sales Profile "Offers" list, so
 * AI Outreach can suggest it without an extra step. The link is stored in
 * builder_offers.generation_metadata.profile_offer_id. Only the account owner's
 * profile is changed (team members cannot edit the company profile).
 */
class ProfileOfferSync
{
    /** Create or update the profile offer for a builder offer. Null = not possible (team member / limit). */
    public function sync(BuilderOffer $offer, bool $create = true): ?UserOffer
    {
        if (!$create && !data_get($offer->generation_metadata, 'profile_offer_id')) {
            return null; // update only: never re-add an offer the user removed from the profile
        }

        $content = $offer->generated_content;
        $user = $offer->user ?? User::find($offer->user_id);
        if (!$content || !$user || $user->isTeamMember() || $user->isAdmin()) {
            return null;
        }

        $owner = $user->getAccountOwner();
        $values = self::values($content);

        $linkedId = (int) data_get($offer->generation_metadata, 'profile_offer_id', 0);
        $profileOffer = $linkedId ? UserOffer::ownedBy($owner)->whereKey($linkedId)->first() : null;
        if (!$profileOffer && !$create) {
            return null;
        }
        $profileOffer ??= UserOffer::ownedBy($owner)->where('type', UserOffer::TYPE_OFFER)->where('title', $values['title'])->first();

        if (!$profileOffer) {
            $max = UserOffer::limits(UserOffer::TYPE_OFFER)['max'];
            if (UserOffer::ownedBy($owner)->where('type', UserOffer::TYPE_OFFER)->count() >= $max) {
                return null;
            }
            $profileOffer = new UserOffer(['type' => UserOffer::TYPE_OFFER, 'is_active' => true]);
            $profileOffer->owner_user_id = $owner->id;
            $profileOffer->sort_order = (int) UserOffer::ownedBy($owner)->where('type', UserOffer::TYPE_OFFER)->max('sort_order') + 1;
        }

        $profileOffer->fill($values)->save();

        $offer->forceFill(['generation_metadata' => array_merge((array) $offer->generation_metadata, ['profile_offer_id' => $profileOffer->id])])->saveQuietly();

        return $profileOffer;
    }

    /** @return array{title: string, description: ?string, price_hint: ?string, ideal_for: ?string} */
    public static function values(array $c): array
    {
        $price = trim((string) strtok((string) ($c['pricing_structure'] ?? ''), "\n"));

        return [
            'title' => mb_substr(trim((string) ($c['offer_name'] ?? 'Offer')), 0, 150),
            'description' => mb_substr(trim(($c['core_offer'] ?? '') . "\n\n" . ($c['value_proposition'] ?? '')), 0, 1000) ?: null,
            'price_hint' => $price !== '' ? mb_substr($price, 0, 100) : null,
            'ideal_for' => mb_substr(trim((string) ($c['target_audience'] ?? '')), 0, 255) ?: null,
        ];
    }

    /**
     * For the profile Offers list: profile offer id => the offer record behind it
     * (the user's inputs as coloured chips and the offer type).
     *
     * @return array<int, array{id: int, chips: list<array>, strategy: ?string}>
     */
    public static function linksFor(User $user): array
    {
        $map = [];
        BuilderOffer::ownedBy($user)->with('strategy')->latest('id')
            ->get(['id', 'offer_strategy_id', 'business_inputs', 'generation_metadata'])
            ->each(function (BuilderOffer $o) use (&$map) {
                $pid = (int) data_get($o->generation_metadata, 'profile_offer_id', 0);
                if ($pid && !isset($map[$pid])) {
                    $map[$pid] = [
                        'id' => $o->id,
                        'chips' => InputHighlighter::chips($o->strategy, (array) $o->business_inputs),
                        'strategy' => $o->strategy?->name,
                    ];
                }
            });

        return $map;
    }

    /** Remove the offer records behind a profile offer that the user deleted. */
    public static function forgetProfileOffer(User $owner, int $profileOfferId): void
    {
        BuilderOffer::ownedBy($owner)->get(['id', 'generation_metadata'])
            ->filter(fn (BuilderOffer $o) => (int) data_get($o->generation_metadata, 'profile_offer_id', 0) === $profileOfferId)
            ->each->delete();
    }
}
