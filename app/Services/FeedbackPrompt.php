<?php

namespace App\Services;

use App\Models\ExportHistory;
use App\Models\LeadCenterLead;
use App\Models\SavedLead;
use App\Models\SearchHistory;
use App\Models\User;
use App\Models\UserFeedback;

/**
 * The post-login "Share Your Feedback" prompt.
 *
 * Shown on every page until the user either submits feedback, or — only when they
 * genuinely have no activity in the tool — closes it. Whether someone "has used the
 * tool" is decided from their real records here, never from what they answer, so
 * answering "No" while having leads/searches/exports does not unlock the close button.
 */
class FeedbackPrompt
{
    /** Counts of what this user has actually done in the tool. */
    public static function activity(User $user): array
    {
        return [
            'saved_leads'       => SavedLead::where('user_id', $user->id)->count(),
            'searches'          => SearchHistory::where('user_id', $user->id)->count(),
            'exports'           => ExportHistory::where('user_id', $user->id)->count(),
            'lead_center_leads' => LeadCenterLead::where('user_id', $user->id)->count(),
        ];
    }

    public static function hasActivity(array $activity): bool
    {
        return array_sum($activity) > 0;
    }

    public static function hasGivenFeedback(User $user): bool
    {
        return UserFeedback::where('user_id', $user->id)->exists();
    }

    /**
     * Prompt data for the layout, or null when the prompt shouldn't open.
     * A new user who closed it is asked again once they start using the tool.
     */
    public static function state(?User $user): ?array
    {
        if (!$user || $user->isAdmin() || self::hasGivenFeedback($user)) {
            return null;
        }

        $activity = self::activity($user);
        $hasActivity = self::hasActivity($activity);

        if ($user->feedback_prompt_dismissed_at && !$hasActivity) {
            return null;
        }

        return [
            'activity'     => $activity,
            'has_activity' => $hasActivity,
            'member_since' => $user->created_at,
        ];
    }
}
