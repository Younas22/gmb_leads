<?php

namespace App\Http\Controllers\Concerns;

use App\Models\LeadCenterAccessGrant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Centralizes "whose Lead Center am I looking at" for every Lead Center controller.
 *
 * Normally that's the logged-in user's own account (or their company, for team members —
 * matching the existing My Leads / folders convention). But a user can also be granted
 * access to work on someone else's Lead Center (see LeadCenterAccessController); while
 * they've switched into that view, leadCenterOwner() resolves to the granting account
 * instead — and ONLY within Lead Center. Every other page (profile, subscription, My
 * Leads, etc.) is untouched, since nothing outside Lead Center's own controllers calls this.
 */
trait ResolvesLeadCenterOwner
{
    /**
     * The real account behind the logged-in user, ignoring any "acting as" delegation.
     * Always use this (never leadCenterOwner()) for anything about the user's OWN
     * identity — e.g. who is granting access to whom.
     */
    protected function baseOwnerUser(): User
    {
        $user = Auth::user();
        return $user->isTeamMember() ? $user->company : $user;
    }

    /**
     * The Lead Center owner whose data this request should show/edit.
     */
    protected function leadCenterOwner(): User
    {
        $actingAsId = session('lc_acting_as');

        if ($actingAsId) {
            $stillValid = LeadCenterAccessGrant::where('owner_user_id', $actingAsId)
                ->where('grantee_user_id', Auth::id())
                ->where('status', LeadCenterAccessGrant::STATUS_ACCEPTED)
                ->exists();

            if ($stillValid) {
                $owner = User::find($actingAsId);
                if ($owner) {
                    return $owner;
                }
            }

            // Grant was revoked/declined/deleted since switching — fall back silently.
            session()->forget('lc_acting_as');
        }

        return $this->baseOwnerUser();
    }

    /**
     * Data every Lead Center page needs to render the access/sharing UI consistently:
     * the "you're viewing X's Lead Center" bar, incoming share requests, and the
     * account switcher. Merge this into every view() call in Lead Center controllers.
     */
    protected function leadCenterAccessContext(): array
    {
        $me = Auth::id();
        $actingAsId = session('lc_acting_as');

        $actingAsOwner = null;
        if ($actingAsId) {
            $valid = LeadCenterAccessGrant::where('owner_user_id', $actingAsId)
                ->where('grantee_user_id', $me)
                ->where('status', LeadCenterAccessGrant::STATUS_ACCEPTED)
                ->exists();
            $actingAsOwner = $valid ? User::find($actingAsId) : null;
        }

        $pendingGrantsForMe = LeadCenterAccessGrant::with('owner')
            ->where('grantee_user_id', $me)
            ->where('status', LeadCenterAccessGrant::STATUS_PENDING)
            ->latest()
            ->get();

        $myAcceptedGrants = LeadCenterAccessGrant::with('owner')
            ->where('grantee_user_id', $me)
            ->where('status', LeadCenterAccessGrant::STATUS_ACCEPTED)
            ->get();

        // People I (the real logged-in account, not whoever I'm currently viewing) have
        // shared my own Lead Center with — for the "Manage Sharing" list.
        $mySharedGrants = LeadCenterAccessGrant::with('grantee')
            ->where('owner_user_id', $this->baseOwnerUser()->id)
            ->whereIn('status', [LeadCenterAccessGrant::STATUS_PENDING, LeadCenterAccessGrant::STATUS_ACCEPTED, LeadCenterAccessGrant::STATUS_DECLINED])
            ->latest()
            ->get();

        return compact('actingAsOwner', 'pendingGrantsForMe', 'myAcceptedGrants', 'mySharedGrants');
    }
}
