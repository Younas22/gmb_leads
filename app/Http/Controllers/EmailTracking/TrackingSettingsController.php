<?php

namespace App\Http\Controllers\EmailTracking;

use App\Http\Controllers\Controller;
use App\Models\EmailAuditLog;
use App\Models\EmailTrackingSetting;
use App\Services\PlanFeatures;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** The sender's own link-tracking preference (opt-in, off by default). */
class TrackingSettingsController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();
        if (!PlanFeatures::enabled($user, 'email_tracking_enabled')) {
            return back()->with('error', 'Your plan does not include link tracking.');
        }

        $data = $request->validate([
            'link_tracking_enabled' => ['required', 'boolean'],
            'retention_days' => ['required', 'integer', Rule::in(EmailTrackingSetting::RETENTION_OPTIONS)],
            'accept_disclosure' => ['exclude_unless:link_tracking_enabled,1', 'accepted'],
        ], ['accept_disclosure.accepted' => 'Please confirm you have read how link tracking works before turning it on.']);

        $setting = EmailTrackingSetting::for($user);
        $enable = (bool) $data['link_tracking_enabled'];
        $setting->fill([
            'owner_user_id' => $user->getAccountOwner()->id,
            'link_tracking_enabled' => $enable,
            'retention_days' => (int) $data['retention_days'],
        ]);
        if ($enable && !$setting->disclosure_accepted_at) {
            $setting->disclosure_accepted_at = now();
        }
        $setting->save();

        EmailAuditLog::record($user->getAccountOwner()->id, $user->id, $enable ? 'link_tracking_enabled' : 'link_tracking_disabled', null, ['retention_days' => $setting->retention_days]);

        return back()->with('success', $enable ? 'Link tracking is on for emails you send from now on.' : 'Link tracking is off. New emails keep their original links.');
    }
}
