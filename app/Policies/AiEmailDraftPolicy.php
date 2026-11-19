<?php

namespace App\Policies;

use App\Models\AiEmailDraft;
use App\Models\User;

/** Drafts belong to an account; the owner and its team members may review them. */
class AiEmailDraftPolicy
{
    public function view(User $user, AiEmailDraft $draft): bool
    {
        return (int) $draft->owner_user_id === (int) $user->getAccountOwner()?->id;
    }

    public function update(User $user, AiEmailDraft $draft): bool
    {
        return $this->view($user, $draft);
    }
}
