<?php

namespace App\Policies;

use App\Models\AiLeadAnalysis;
use App\Models\User;

/** Analyses belong to an account; the owner and its team members may use them. */
class AiLeadAnalysisPolicy
{
    public function view(User $user, AiLeadAnalysis $analysis): bool
    {
        return (int) $analysis->owner_user_id === (int) $user->getAccountOwner()?->id;
    }

    public function update(User $user, AiLeadAnalysis $analysis): bool
    {
        return $this->view($user, $analysis);
    }
}
