<?php

namespace App\Services\EmailTracking;

use App\Models\EmailTrackingEvent;
use App\Models\EmailTrackingLink;
use App\Models\EmailTrackingSetting;
use App\Models\OutboundEmail;
use App\Models\User;
use App\Services\PlanFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Records a request to a tracked link.
 *
 * A request is NOT proof that a person clicked: mail security scanners open links
 * automatically. Each request is classified (probable_human / suspected_scanner /
 * internal) and analytics only count probable_human requests as estimated clicks.
 *
 * Privacy: no IP address or user-agent is stored. client_hash is an HMAC (APP_KEY)
 * of the IP network prefix + user agent + date, cut to 16 hex characters. It changes
 * every day and is used only to estimate unique clicks and to drop rapid repeats.
 */
class ClickRecorder
{
    public function record(EmailTrackingLink $link, Request $request): ?EmailTrackingEvent
    {
        $email = $link->email;
        if (!$email || !$this->shouldRecord($email)) {
            return null;
        }

        // Bounded storage per link (the redirect itself keeps working)
        $key = 'track-link:' . $link->id;
        $max = (int) config('email_tracking.max_events_per_link_per_hour', 30);
        if (RateLimiter::tooManyAttempts($key, $max)) {
            return null;
        }
        RateLimiter::hit($key, 3600);

        [$classification, $reason] = $this->classify($email, $request);
        $clientHash = $this->clientHash($request);

        return EventRecorder::record([
            'owner_user_id' => $email->owner_user_id,
            'outbound_email_id' => $email->id,
            'saved_lead_id' => $email->saved_lead_id,
            'tracking_link_id' => $link->id,
            'event_type' => EmailTrackingEvent::LINK_CLICK,
            'event_source' => EmailTrackingEvent::SOURCE_REDIRECT,
            'classification' => $classification,
            'client_hash' => $clientHash,
            // The same client hitting the same link within a minute counts once
            'dedupe_key' => sprintf('click:%d:%s:%d', $link->id, $clientHash, intdiv(now()->getTimestamp(), 60)),
            'metadata' => ['reason' => $reason, 'method' => $request->getMethod()],
        ]);
    }

    private function shouldRecord(OutboundEmail $email): bool
    {
        // Only emails that went out (or may have gone out)
        if ($email->status !== OutboundEmail::STATUS_SENT && !$email->isUnknown()) {
            return false;
        }

        $sender = User::find($email->user_id);
        if (!$sender || !PlanFeatures::enabled($sender, 'email_tracking_enabled')) {
            return false;
        }

        // Stop collecting after the sender's retention period
        $since = $email->sent_at ?? $email->queued_at;
        $days = EmailTrackingSetting::for($sender)->retentionDays();

        return $since === null || $since->gt(now()->subDays($days));
    }

    /** @return array{0: string, 1: string} [classification, reason] */
    public function classify(OutboundEmail $email, Request $request): array
    {
        $viewer = $request->user();
        if ($viewer && (int) $viewer->getAccountOwner()?->id === (int) $email->owner_user_id) {
            return [EmailTrackingEvent::INTERNAL, 'signed_in_team_member'];
        }

        if ($request->isMethod('HEAD')) {
            return [EmailTrackingEvent::SCANNER, 'head_request'];
        }

        $agent = strtolower((string) $request->userAgent());
        if ($agent === '' || !str_contains($agent, 'mozilla/')) {
            return [EmailTrackingEvent::SCANNER, 'non_browser_agent'];
        }
        foreach ((array) config('email_tracking.scanner_user_agents', []) as $needle) {
            if (str_contains($agent, $needle)) {
                return [EmailTrackingEvent::SCANNER, 'scanner_agent'];
            }
        }

        $sentAt = $email->sent_at;
        if ($sentAt && $sentAt->diffInSeconds(now(), true) < (int) config('email_tracking.scanner_window_seconds', 60)) {
            return [EmailTrackingEvent::SCANNER, 'too_soon_after_send'];
        }

        return [EmailTrackingEvent::HUMAN, 'browser'];
    }

    public function clientHash(Request $request): string
    {
        $ip = (string) $request->ip();
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $prefix = implode('.', array_slice(explode('.', $ip), 0, 3));
        } else {
            $prefix = implode(':', array_slice(explode(':', $ip), 0, 4));
        }

        return substr(hash_hmac('sha256', $prefix . '|' . (string) $request->userAgent() . '|' . now()->toDateString(), (string) config('app.key')), 0, 16);
    }
}
