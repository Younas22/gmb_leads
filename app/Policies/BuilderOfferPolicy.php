<?php

namespace App\Policies;

use App\Models\BuilderOffer;
use App\Models\User;

/** AI Offer Builder: an offer is private to the user who created it. */
class BuilderOfferPolicy
{
    public function view(User $user, BuilderOffer $offer): bool
    {
        return (int) $offer->user_id === (int) $user->id;
    }

    public function update(User $user, BuilderOffer $offer): bool
    {
        return $this->view($user, $offer);
    }

    public function delete(User $user, BuilderOffer $offer): bool
    {
        return $this->view($user, $offer);
    }
}
