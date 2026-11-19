<?php

namespace App\Console\Commands;

use App\Models\EmailAuditLog;
use App\Models\OutboundEmail;
use App\Services\Gmail\OutboundEmailService;
use Illuminate\Console\Command;

/**
 * Emails stuck in "sending" had their worker killed mid-request. Gmail may or may not
 * have accepted the message, so they are marked failed with an UNKNOWN outcome and are
 * never re-sent automatically (the user must check their Sent folder and confirm).
 * Emails left "pending" (job lost before it started) are safely re-dispatched.
 */
class RecoverStuckEmails extends Command
{
    protected $signature = 'email:recover-stuck {--minutes=10 : Minutes before a send is considered stuck}';

    protected $description = 'Resolve Gmail sends whose background job was lost';

    public function handle(): int
    {
        $cutoff = now()->subMinutes(max(1, (int) $this->option('minutes')));
        $unknown = 0;
        $requeued = 0;

        OutboundEmail::where('status', OutboundEmail::STATUS_SENDING)
            ->where('updated_at', '<', $cutoff)
            ->each(function (OutboundEmail $email) use (&$unknown) {
                $claimed = OutboundEmail::whereKey($email->id)
                    ->where('status', OutboundEmail::STATUS_SENDING)
                    ->update([
                        'status' => OutboundEmail::STATUS_FAILED,
                        'outcome_unknown' => true,
                        'error_code' => 'outcome_unknown',
                        'error_message' => 'Sending was interrupted. Check your Gmail "Sent" folder before retrying.',
                        'updated_at' => now(),
                    ]);
                if ($claimed) {
                    $unknown++;
                    EmailAuditLog::record($email->owner_user_id, null, 'send_interrupted', $email->id);
                    \App\Services\EmailTracking\EventRecorder::forEmail($email, \App\Models\EmailTrackingEvent::UNKNOWN,
                        \App\Models\EmailTrackingEvent::SOURCE_SYSTEM, \App\Services\EmailTracking\EventRecorder::failureKey($email),
                        null, ['code' => 'interrupted']);
                }
            });

        OutboundEmail::where('status', OutboundEmail::STATUS_PENDING)
            ->whereNull('campaign_id') // campaign emails are sent by Resend as one broadcast
            ->where('updated_at', '<', $cutoff)
            ->each(function (OutboundEmail $email) use (&$requeued) {
                $email->forceFill(['updated_at' => now()])->save();
                $email->provider === OutboundEmail::PROVIDER_RESEND
                    ? \App\Services\Resend\ResendSendService::dispatch($email)
                    : OutboundEmailService::dispatch($email);
                $requeued++;
            });

        $this->info("Marked {$unknown} interrupted send(s) as unknown outcome; re-queued {$requeued} pending send(s).");

        return self::SUCCESS;
    }
}
