<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesLeadCenterOwner;
use App\Models\LeadCenterAccessGrant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets a user grant another registered account access to work on their Lead Center
 * (view + edit, same as the owner) without exposing anything else about their account.
 */
class LeadCenterAccessController extends Controller
{
    use ResolvesLeadCenterOwner;

    /**
     * Share access to MY OWN Lead Center with another user, by email. Always acts as
     * the real logged-in account — never the one currently being "acted as".
     */
    public function share(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $me = $this->baseOwnerUser();
        $target = User::where('email', trim($request->email))->first();

        if (!$target) {
            return response()->json(['success' => false, 'message' => 'No account found with that email.'], 404);
        }

        if ($target->id === $me->id) {
            return response()->json(['success' => false, 'message' => 'You cannot share access with yourself.'], 422);
        }

        $grant = LeadCenterAccessGrant::updateOrCreate(
            ['owner_user_id' => $me->id, 'grantee_user_id' => $target->id],
            ['status' => LeadCenterAccessGrant::STATUS_PENDING]
        );

        return response()->json([
            'success' => true,
            'message' => "Access request sent to {$target->email}. They'll see it next time they open Lead Center.",
            'grant' => [
                'id' => $grant->id,
                'status' => $grant->status,
                'grantee' => ['name' => $target->full_name, 'email' => $target->email],
            ],
        ]);
    }

    /**
     * Accept or decline an access request sent to me.
     */
    public function respond(Request $request, $id)
    {
        $request->validate(['action' => 'required|in:accept,decline']);

        $grant = LeadCenterAccessGrant::where('grantee_user_id', Auth::id())
            ->where('status', LeadCenterAccessGrant::STATUS_PENDING)
            ->findOrFail($id);

        $grant->update([
            'status' => $request->action === 'accept' ? LeadCenterAccessGrant::STATUS_ACCEPTED : LeadCenterAccessGrant::STATUS_DECLINED,
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->action === 'accept' ? 'Access accepted.' : 'Access declined.',
        ]);
    }

    /**
     * End a sharing relationship — either side (the owner who granted it, or the
     * grantee who received it) can do this.
     */
    public function revoke($id)
    {
        $me = $this->baseOwnerUser();

        $grant = LeadCenterAccessGrant::where('id', $id)
            ->where(function ($q) use ($me) {
                $q->where('owner_user_id', $me->id)->orWhere('grantee_user_id', Auth::id());
            })
            ->firstOrFail();

        $grant->update(['status' => LeadCenterAccessGrant::STATUS_REVOKED]);

        // If the grantee currently has this exact account switched in, kick them back to their own.
        if ((int) session('lc_acting_as') === (int) $grant->owner_user_id && Auth::id() === $grant->grantee_user_id) {
            session()->forget('lc_acting_as');
        }

        return response()->json(['success' => true, 'message' => 'Access removed.']);
    }

    /**
     * Switch which Lead Center I'm viewing — either into an account that granted me
     * access (owner_id given), or back to my own (owner_id omitted/null).
     */
    public function switchTo(Request $request)
    {
        $request->validate(['owner_id' => 'nullable|integer']);

        if (!$request->owner_id) {
            session()->forget('lc_acting_as');
            return response()->json(['success' => true, 'message' => 'Switched back to your own Lead Center.']);
        }

        $grant = LeadCenterAccessGrant::where('owner_user_id', $request->owner_id)
            ->where('grantee_user_id', Auth::id())
            ->where('status', LeadCenterAccessGrant::STATUS_ACCEPTED)
            ->with('owner')
            ->first();

        if (!$grant) {
            return response()->json(['success' => false, 'message' => 'You do not have accepted access to that account.'], 403);
        }

        session(['lc_acting_as' => (int) $request->owner_id]);

        return response()->json([
            'success' => true,
            'message' => "Now viewing {$grant->owner->full_name}'s Lead Center.",
        ]);
    }
}
