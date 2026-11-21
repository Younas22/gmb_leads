<?php

namespace App\Http\Controllers\EmailTracking;

use App\Http\Controllers\Controller;
use App\Models\EmailTrackingEvent;
use App\Models\OutboundEmail;
use App\Models\User;
use App\Services\EmailTracking\EventRecorder;
use App\Services\PlanFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * GET /o/{email}.gif (signed): the 1x1 image in Gmail emails sent with tracking on.
 * Loading it = the email was opened (or images were loaded automatically / by a
 * proxy; reported as an estimate). Always answers with the same transparent GIF,
 * so the response reveals nothing about the email, lead or account.
 */
class OpenPixelController extends Controller
{
    /** Transparent 1x1 GIF */
    private const GIF = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public static function urlFor(OutboundEmail $email): string
    {
        return URL::signedRoute('email.open', ['email' => $email->id]);
    }

    public function __invoke(Request $request, int $email)
    {
        try {
            if ($request->hasValidSignature()) {
                $this->record($request, $email);
            }
        } catch (Throwable $e) {
            Log::warning('Email open not recorded', ['outbound_email_id' => $email, 'exception' => get_class($e)]);
        }

        return response(base64_decode(self::GIF), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    private function record(Request $request, int $id): void
    {
        $email = OutboundEmail::find($id);
        if (!$email || $email->status !== OutboundEmail::STATUS_SENT || !($email->open_tracking || $email->link_tracking)) {
            return;
        }
        $sender = User::find($email->user_id);
        if (!$sender || !PlanFeatures::enabled($sender, 'email_tracking_enabled')) {
            return;
        }

        $ua = mb_strtolower((string) $request->userAgent());
        $via = match (true) {
            str_contains($ua, 'googleimageproxy') => 'gmail',
            str_contains($ua, 'yahoomailproxy') => 'yahoo',
            str_contains($ua, 'outlook') || str_contains($ua, 'microsoft') => 'outlook',
            default => 'other',
        };
        // Loaded right after sending = almost always a scanner or preview, not a person
        $seconds = $email->sent_at ? $email->sent_at->diffInSeconds(now()) : PHP_INT_MAX;
        $classification = $seconds < (int) config('email_tracking.open_scanner_window_seconds', 10)
            ? EmailTrackingEvent::SCANNER
            : EmailTrackingEvent::HUMAN;

        // One open per email per 30 minutes (Gmail reloads the image when the email is viewed again)
        $bucket = intdiv(now()->timestamp, 1800);

        EventRecorder::record([
            'owner_user_id' => $email->owner_user_id,
            'outbound_email_id' => $email->id,
            'saved_lead_id' => $email->saved_lead_id,
            'provider' => $email->provider ?? OutboundEmail::PROVIDER_GMAIL,
            'campaign_id' => $email->campaign_id,
            'marketing_contact_id' => $email->marketing_contact_id,
            'event_type' => EmailTrackingEvent::OPENED,
            'event_source' => EmailTrackingEvent::SOURCE_OPEN_PIXEL,
            'classification' => $classification,
            'dedupe_key' => 'opened:' . $email->id . ':' . $bucket,
            'metadata' => ['via' => $via],
            'occurred_at' => now(),
        ]);
    }
}
