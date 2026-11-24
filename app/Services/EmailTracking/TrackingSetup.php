<?php

namespace App\Services\EmailTracking;

use App\Models\ConnectedEmailAccount;
use App\Models\EmailTrackingSetting;
use App\Models\OutboundEmail;
use App\Models\ResendDomain;
use App\Models\ResendSender;
use App\Models\User;
use App\Services\PlanFeatures;

/**
 * What is missing for opens, clicks and replies to be tracked for this account,
 * shown on Email Analytics with the action that fixes each item.
 */
class TrackingSetup
{
    /**
     * @return list<array{key: string, title: string, text: string, action?: array}>
     */
    public static function gaps(User $user): array
    {
        $owner = $user->getAccountOwner();
        $trackingPlan = PlanFeatures::enabled($user, 'email_tracking_enabled');
        $gaps = [];

        $usesResend = OutboundEmail::ownedBy($owner)->where('provider', OutboundEmail::PROVIDER_RESEND)->exists()
            || ResendDomain::where('owner_user_id', $owner->id)->exists();
        $usesGmail = ConnectedEmailAccount::where('owner_user_id', $owner->id)->exists();

        if ($trackingPlan) {
            foreach (ResendDomain::where('owner_user_id', $owner->id)->get()->filter->canSend() as $d) {
                $record = \App\Services\Resend\ResendDomainService::trackingRecord($d);
                if ($record && ($record['status'] ?? '') !== 'verified') {
                    // Tracking was asked for; Resend applies it once this DNS record is verified
                    $gaps[] = [
                        'key' => 'resend_tracking_dns_' . $d->id,
                        'title' => 'One DNS record left to finish open & click tracking for ' . $d->name,
                        'text' => 'Add this record at your DNS provider, then press "Check again": ' . strtoupper((string) $record['type']) . ' · ' . $record['name'] . ' → ' . $record['value'] . '. Opens and clicks are tracked for emails sent after it is verified.',
                        'action' => ['type' => 'resend_refresh', 'domain_id' => $d->id, 'label' => 'Check again'],
                    ];
                } elseif (!$d->open_tracking || !$d->click_tracking) {
                    $gaps[] = [
                        'key' => 'resend_tracking_' . $d->id,
                        'title' => 'Opens and clicks are off for ' . $d->name . ' (Resend)',
                        'text' => 'Resend only reports opens and clicks when tracking is on for the sending domain. It applies to emails sent after you turn it on.',
                        'action' => ['type' => 'resend_tracking', 'domain_id' => $d->id, 'label' => 'Turn on open & click tracking'],
                    ];
                }
            }
            if ($usesGmail && !EmailTrackingSetting::for($user)->link_tracking_enabled) {
                $gaps[] = [
                    'key' => 'gmail_tracking',
                    'title' => 'Open and click tracking is off for Gmail emails',
                    'text' => 'Turn on tracking in Email Sending settings (opens and link clicks).',
                    'action' => ['type' => 'link', 'url' => route('user.email-sending.index') . '#link-tracking', 'label' => 'Open settings'],
                ];
            }
        }

        // Replies to Resend emails: detected when they go to a Resend receiving address
        // (sender Reply-To) and the webhook sends "email.received".
        $readers = ConnectedEmailAccount::where('owner_user_id', $owner->id)->get()->filter->canReadReplies();
        $resendReplies = \App\Models\EmailReplyRecord::where('owner_user_id', $owner->id)->where('source', 'resend_inbound')->exists();
        if ($usesResend && $readers->isEmpty() && !$resendReplies) {
            $senders = ResendSender::where('owner_user_id', $owner->id)->get();
            $gaps[] = [
                'key' => 'resend_replies',
                'title' => 'Replies to Resend emails are not detected yet',
                'text' => 'Resend can receive the replies for you: 1) in Resend, turn on Receiving (Emails → Receiving gives you an address like anything@<id>.resend.app, or enable receiving on a subdomain such as reply.yourdomain.com); '
                    . '2) set "Replies go to" for ' . ($senders->pluck('from_email')->implode(', ') ?: 'your sender') . ' to that address; '
                    . '3) on your Resend webhook, tick the "email.received" event. Replies then show here automatically. Until then, use "Record reply" on an email page.',
                'action' => ['type' => 'link', 'url' => route('user.email-sending.resend.show'), 'label' => 'Change reply-to'],
            ];
        }

        // Replies to Gmail emails: found in a connected Gmail inbox with read-only access
        if ($usesGmail && $readers->isEmpty()) {
            $gaps[] = [
                'key' => 'replies',
                'title' => 'Replies are not detected automatically',
                'text' => config('gmail.reply_detection')
                    ? 'Connect (or reconnect) your Gmail and allow read access, so replies are found in your inbox. Until then, use "Record reply" on an email.'
                    : 'Automatic reply detection is not switched on for this server yet. Until then, use "Record reply" on an email page.',
                'action' => config('gmail.reply_detection')
                    ? ['type' => 'link', 'url' => route('user.email-sending.index'), 'label' => 'Connect Gmail']
                    : null,
            ];
        } elseif ($usesResend && $readers->isNotEmpty() && !$resendReplies) {
            // Alternative to Resend receiving: send Resend replies to the connected Gmail
            $gmail = $readers->pluck('email')->map(fn ($e) => mb_strtolower($e))->all();
            $senders = ResendSender::where('owner_user_id', $owner->id)->get()
                ->reject(fn ($s) => in_array(mb_strtolower((string) $s->reply_to), $gmail, true));
            if ($senders->isNotEmpty()) {
                $gaps[] = [
                    'key' => 'resend_reply_to',
                    'title' => 'Replies to Resend emails go to an inbox we cannot see',
                    'text' => 'Set "Replies go to" for ' . $senders->pluck('from_email')->implode(', ') . ' to your connected Gmail (' . implode(', ', $gmail) . ') so replies are found automatically.',
                    'action' => ['type' => 'link', 'url' => route('user.email-sending.resend.show'), 'label' => 'Change reply-to'],
                ];
            }
        }

        return array_values(array_filter($gaps));
    }
}
