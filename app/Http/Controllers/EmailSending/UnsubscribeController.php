<?php

namespace App\Http\Controllers\EmailSending;

use App\Http\Controllers\Controller;
use App\Models\EmailAuditLog;
use App\Models\EmailSuppression;
use App\Models\EmailTrackingEvent;
use App\Models\OutboundEmail;
use App\Services\EmailTracking\EventRecorder;
use App\Services\EmailTracking\FollowUpService;
use App\Services\EmailTracking\LeadSync;
use Illuminate\Http\Request;

/**
 * Public opt-out for recipients. Links are signed (APP_KEY), so they cannot be
 * forged for other addresses. GET only shows a confirmation; POST unsubscribes
 * (also used by mail clients' RFC 8058 one-click unsubscribe).
 */
class UnsubscribeController extends Controller
{
    public function show(int $email)
    {
        $model = OutboundEmail::findOrFail($email);

        return view('email-unsubscribe', [
            'email' => $model,
            'done' => EmailSuppression::isSuppressed($model->owner_user_id, $model->to_email),
            'masked' => self::mask($model->to_email),
        ]);
    }

    public function confirm(Request $request, int $email)
    {
        $model = OutboundEmail::findOrFail($email);

        $already = EmailSuppression::isSuppressed($model->owner_user_id, $model->to_email);
        EmailSuppression::suppress($model->owner_user_id, $model->to_email, EmailSuppression::REASON_UNSUBSCRIBED, $model->id);
        EmailAuditLog::record($model->owner_user_id, null, 'recipient_unsubscribed', $model->id, [
            'one_click' => $request->input('List-Unsubscribe') === 'One-Click',
        ]);

        // Phase 4: record the opt-out once and stop any planned follow-up with this lead
        if (!$already) {
            EventRecorder::forEmail($model, EmailTrackingEvent::OPTED_OUT, EmailTrackingEvent::SOURCE_UNSUBSCRIBE, 'opted_out:' . $model->id);
            app(FollowUpService::class)->cancelAllForLead(null, $model->lead, 'unsubscribed');
            LeadSync::applyOptOut($model->lead);
        }

        // One-click requests from mail providers expect a plain 200
        if ($request->input('List-Unsubscribe') === 'One-Click') {
            return response('Unsubscribed', 200);
        }

        return view('email-unsubscribe', ['email' => $model, 'done' => true, 'masked' => self::mask($model->to_email)]);
    }

    private static function mask(string $address): string
    {
        [$local, $domain] = array_pad(explode('@', $address, 2), 2, '');

        return mb_substr($local, 0, 1) . str_repeat('*', max(1, mb_strlen($local) - 1)) . '@' . $domain;
    }
}
