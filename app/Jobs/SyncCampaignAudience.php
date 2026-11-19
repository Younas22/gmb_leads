<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailSuppression;
use App\Models\OutboundEmail;
use App\Models\UserSalesProfile;
use App\Services\Resend\CampaignRenderer;
use App\Services\Resend\CampaignService;
use App\Services\Resend\ResendConnectionService;
use App\Services\Resend\ResendException;
use App\Services\Resend\ResendQuota;
use App\Services\Resend\ScheduledCampaignGuard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Prepares one approved campaign in small steps (one job run = one bounded batch):
 *  1. create a Resend Segment for the campaign;
 *  2. add each recipient as a Resend Contact in that segment (eligibility re-checked
 *     for every contact right before it is added);
 *  3. create ONE Broadcast to the segment with send=true (optionally scheduled_at).
 * Resend skips contacts that unsubscribed in Resend. The broadcast request is made
 * once: if its outcome is unclear the campaign becomes "unknown" and is not retried.
 */
class SyncCampaignAudience implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;
    public int $timeout = 75;

    public function __construct(public readonly int $campaignId)
    {
    }

    public function backoff(): array
    {
        return [30, 120, 300, 600];
    }

    public function handle(CampaignService $campaigns, CampaignRenderer $renderer, ResendQuota $quota): void
    {
        $lock = Cache::lock('campaign:' . $this->campaignId, 120);
        if (!$lock->get()) {
            $this->release(20); // being cancelled or another run is active
            return;
        }

        try {
            $this->run($campaigns, $renderer, $quota);
        } finally {
            $lock->release();
        }
    }

    private function run(CampaignService $campaigns, CampaignRenderer $renderer, ResendQuota $quota): void
    {
        $campaign = EmailCampaign::with(['integration', 'sender.domain'])->find($this->campaignId);
        if (!$campaign || $campaign->status !== EmailCampaign::PREPARING) {
            return;
        }
        // A previous run reached the broadcast request but never recorded the result
        if ($campaign->last_error === 'broadcast_requested') {
            $campaign->forceFill(['status' => EmailCampaign::UNKNOWN, 'last_error' => 'Resend did not confirm the broadcast. Check Broadcasts in your Resend dashboard; it will not be re-created automatically.'])->save();
            return;
        }
        $integration = $campaign->integration;
        if (!$integration || !$integration->isUsable() || !$campaign->sender || !$campaign->sender->canSend()) {
            $campaigns->closeUnsent($campaign, EmailCampaign::FAILED, 'Resend connection or verified sender is no longer available.');
            return;
        }

        $client = ResendConnectionService::client($integration);
        $pause = (int) config('resend_user.requests_per_second', 4) > 0 ? (int) (1_000_000 / (int) config('resend_user.requests_per_second')) : 0;

        try {
            if (!$campaign->provider_segment_id) {
                $segment = $client->createSegment('CustomerNearMe campaign #' . $campaign->id);
                $campaign->forceFill(['provider_segment_id' => (string) $segment['id']])->save();
            }

            $batch = EmailCampaignRecipient::where('campaign_id', $campaign->id)->where('sync_status', 'pending')
                ->with('contact')->orderBy('id')->limit((int) config('resend_user.sync_batch', 40))->get();

            foreach ($batch as $recipient) {
                $contact = $recipient->contact;
                if (!$contact || !$contact->isSendable() || EmailSuppression::isSuppressed($campaign->owner_user_id, $recipient->email)) {
                    $this->skip($campaign, $recipient, $contact?->blockReason() ?? 'suppressed', $quota);
                    continue;
                }
                try {
                    $client->createContact(array_filter([
                        'email' => $recipient->email,
                        'first_name' => $contact->first_name,
                        'last_name' => $contact->last_name,
                        'segments' => [['id' => $campaign->provider_segment_id]],
                    ]));
                } catch (ResendException $e) {
                    if ($e->reason !== ResendException::VALIDATION && $e->reason !== ResendException::CONFLICT) {
                        throw $e;
                    }
                    // Contact already exists in Resend: only add it to the segment. Its own
                    // unsubscribe state in Resend is left untouched.
                    $client->addContactToSegment($recipient->email, $campaign->provider_segment_id);
                }
                $recipient->forceFill(['sync_status' => 'synced'])->save();
                if ($pause) {
                    usleep($pause);
                }
            }
        } catch (ResendException $e) {
            if ($e->reason === ResendException::INVALID_KEY) {
                app(ResendConnectionService::class)->markProblem($integration, $e);
                $campaigns->closeUnsent($campaign, EmailCampaign::FAILED, $e->getMessage());
                return;
            }
            throw $e; // retried with backoff; adding contacts again is harmless
        }

        if (EmailCampaignRecipient::where('campaign_id', $campaign->id)->where('sync_status', 'pending')->exists()) {
            CampaignService::dispatchSync($campaign->id, 1);
            return;
        }

        // Contacts added in earlier batches may have opted out since. Take them out of the
        // segment now, while no broadcast exists (Resend does not document whether segment
        // changes reach an already-created broadcast).
        try {
            foreach (app(ScheduledCampaignGuard::class)->blockedRecipients($campaign)->where('sync_status', 'synced') as $recipient) {
                try {
                    $client->removeContactFromSegment($recipient->email, $campaign->provider_segment_id);
                } catch (ResendException $e) {
                    if ($e->reason !== ResendException::NOT_FOUND) {
                        throw $e;
                    }
                }
                $this->skip($campaign, $recipient, 'suppressed_after_sync', $quota);
            }
        } catch (ResendException $e) {
            if ($e->reason === ResendException::INVALID_KEY) {
                app(ResendConnectionService::class)->markProblem($integration, $e);
                $campaigns->closeUnsent($campaign, EmailCampaign::FAILED, $e->getMessage());
                return;
            }
            throw $e; // retried; if it keeps failing, failed() closes the campaign (nothing sent)
        }

        if (!EmailCampaignRecipient::where('campaign_id', $campaign->id)->where('sync_status', 'synced')->exists()) {
            $campaigns->closeUnsent($campaign, EmailCampaign::FAILED, 'No recipient was still eligible when the campaign was prepared.');
            return;
        }

        // Final checks right before the broadcast: domain still verified at Resend?
        try {
            $domain = $client->getDomain($campaign->sender->domain->provider_domain_id);
        } catch (ResendException $e) {
            throw $e;
        }
        if (($domain['status'] ?? null) !== 'verified') {
            $campaigns->closeUnsent($campaign, EmailCampaign::FAILED, 'The sending domain is no longer verified in Resend.');
            return;
        }

        $profile = UserSalesProfile::where('owner_user_id', $campaign->owner_user_id)->first();
        $text = $renderer->broadcastText($campaign->body, $profile);
        $payload = array_filter([
            'segment_id' => $campaign->provider_segment_id,
            'from' => $campaign->sender->fromHeader(),
            'subject' => $renderer->broadcastSubject($campaign->subject),
            'text' => $text,
            'html' => $renderer->html($text),
            'reply_to' => $campaign->sender->reply_to ?: null,
            'name' => mb_substr('CNM #' . $campaign->id . ' ' . $campaign->name, 0, 100),
            'send' => true,
            'scheduled_at' => $campaign->scheduled_at?->toIso8601String(),
        ], fn ($v) => $v !== null);

        // The one non-repeatable step: mark the attempt first so a crash cannot re-create it
        $campaign->forceFill(['last_error' => 'broadcast_requested'])->save();
        try {
            $broadcast = $client->createBroadcast($payload);
        } catch (ResendException $e) {
            if ($e->isAmbiguous() || $e->reason === ResendException::CONFLICT) {
                $campaign->forceFill(['status' => EmailCampaign::UNKNOWN, 'last_error' => 'Resend did not confirm the broadcast. Check Broadcasts in your Resend dashboard; it will not be re-created automatically.'])->save();
                return;
            }
            $campaigns->closeUnsent($campaign, EmailCampaign::FAILED, $e->getMessage());
            return;
        } catch (Throwable) {
            $campaign->forceFill(['status' => EmailCampaign::UNKNOWN, 'last_error' => 'Resend did not confirm the broadcast. Check Broadcasts in your Resend dashboard; it will not be re-created automatically.'])->save();
            return;
        }

        $campaign->forceFill(['last_error' => null])->save();
        $campaigns->markSubmitted($campaign, (string) ($broadcast['id'] ?? ''), (bool) $campaign->scheduled_at);
    }

    private function skip(EmailCampaign $campaign, EmailCampaignRecipient $recipient, string $reason, ResendQuota $quota): void
    {
        $recipient->forceFill(['sync_status' => 'skipped', 'skip_reason' => mb_substr($reason, 0, 40)])->save();
        $cancelled = OutboundEmail::whereKey($recipient->outbound_email_id)->where('status', OutboundEmail::STATUS_PENDING)
            ->update(['status' => OutboundEmail::STATUS_CANCELLED, 'cancelled_at' => now(), 'error_code' => 'skipped_' . mb_substr($reason, 0, 30), 'updated_at' => now()]);
        if ($cancelled) {
            $quota->release($campaign->owner_user_id, ResendQuota::MONTHLY, 1);
            $campaign->decrement('reserved_quota');
        }
    }

    /** Ran out of retries while syncing contacts: no broadcast exists, so nothing was sent. */
    public function failed(?Throwable $e): void
    {
        $campaign = EmailCampaign::find($this->campaignId);
        if ($campaign && $campaign->status === EmailCampaign::PREPARING && $campaign->last_error !== 'broadcast_requested') {
            app(CampaignService::class)->closeUnsent($campaign, EmailCampaign::FAILED, 'Contacts could not be prepared in Resend. Nothing was sent.');
        } elseif ($campaign && $campaign->status === EmailCampaign::PREPARING) {
            $campaign->forceFill(['status' => EmailCampaign::UNKNOWN, 'last_error' => 'Check Broadcasts in your Resend dashboard; the campaign will not be re-created automatically.'])->save();
        }
    }
}
