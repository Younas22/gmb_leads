<?php

namespace App\Services\Resend;

use App\Jobs\GuardScheduledCampaign;
use App\Models\EmailAuditLog;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailSuppression;
use App\Models\MarketingContact;
use App\Models\OutboundEmail;
use Illuminate\Support\Collection;

/**
 * Keeps approved campaigns from reaching anyone who opted out (or was suppressed)
 * AFTER approval.
 *
 * Resend documents that deleting a `scheduled` broadcast cancels its delivery, but it
 * does NOT document when a broadcast resolves its segment members, so removing a
 * contact from the segment (or flagging it in Resend) cannot be proven to affect an
 * already-scheduled broadcast. Therefore:
 *   - preparing (no broadcast yet): the contact is removed from the segment before the
 *     broadcast is created (SyncCampaignAudience);
 *   - scheduled (broadcast exists): the whole broadcast is deleted at Resend and the
 *     campaign is cancelled. Nothing is sent; the user can create a new campaign.
 * If Resend refuses the cancellation the campaign becomes "unknown" with an urgent note.
 */
class ScheduledCampaignGuard
{
    public const AUTO_CANCELLED = 'Automatically cancelled: %d recipient(s) unsubscribed or were added to your do-not-email list after approval. Resend cannot guarantee removing them from an already scheduled broadcast, so the broadcast was deleted and nothing was sent. Create a new campaign to send to the remaining contacts.';
    public const NOT_CANCELLED = 'A recipient was suppressed after approval, but Resend did not accept the cancellation (%s); it may already be sending. Open Broadcasts in your Resend dashboard now and delete it if it is still scheduled.';

    /** Campaigns (preparing or scheduled) that still plan to email this address. */
    public static function campaignsFor(int $ownerId, string $email): Collection
    {
        return EmailCampaignRecipient::query()
            ->join('email_campaigns', 'email_campaigns.id', '=', 'email_campaign_recipients.campaign_id')
            ->where('email_campaigns.owner_user_id', $ownerId)
            ->whereIn('email_campaigns.status', [EmailCampaign::PREPARING, EmailCampaign::SCHEDULED])
            ->where('email_campaign_recipients.email', EmailSuppression::normalize($email))
            ->where('email_campaign_recipients.sync_status', '!=', 'skipped')
            ->distinct()->pluck('email_campaigns.id');
    }

    /** Called for every new suppression. Scheduled campaigns are protected right away. */
    public static function addressSuppressed(int $ownerId, string $email): void
    {
        foreach (self::campaignsFor($ownerId, $email) as $campaignId) {
            self::dispatch((int) $campaignId);
        }
    }

    public static function dispatch(int $campaignId): void
    {
        dispatch(new GuardScheduledCampaign($campaignId))
            ->onConnection(config('resend_user.queue.connection'))
            ->onQueue(config('resend_user.queue.send'))
            ->afterCommit();
    }

    /**
     * Recipients (not yet skipped) who may no longer be emailed: suppressed for this
     * account, or the contact is gone / withdrew consent / unsubscribed / bounced / complained.
     */
    public function blockedRecipients(EmailCampaign $campaign): Collection
    {
        $active = EmailCampaignRecipient::where('campaign_id', $campaign->id)->where('sync_status', '!=', 'skipped')->with('contact')->get();
        if ($active->isEmpty()) {
            return $active;
        }
        $suppressed = EmailSuppression::where('owner_user_id', $campaign->owner_user_id)
            ->whereIn('email_hash', $active->map(fn ($r) => EmailSuppression::hashFor($r->email))->all())
            ->pluck('email_hash')->flip();

        return $active->filter(fn (EmailCampaignRecipient $r) => !$r->contact instanceof MarketingContact
            || (int) $r->contact->owner_user_id !== (int) $campaign->owner_user_id
            || !$r->contact->isSendable()
            || $suppressed->has(EmailSuppression::hashFor($r->email)))->values();
    }

    /** Must run while holding the campaign lock. */
    public function protect(EmailCampaign $campaign, CampaignService $campaigns): void
    {
        if ($campaign->status !== EmailCampaign::SCHEDULED || !$campaign->provider_broadcast_id) {
            return; // preparing: handled by the sync job before the broadcast exists
        }
        $blocked = $this->blockedRecipients($campaign);
        if ($blocked->isEmpty()) {
            return;
        }

        try {
            ResendConnectionService::client($campaign->integration)->deleteBroadcast($campaign->provider_broadcast_id);
        } catch (ResendException $e) {
            if ($e->isAmbiguous() || $e->isRetryable()) {
                throw $e; // retried by the job; a repeated DELETE is harmless
            }
            if ($e->reason === ResendException::INVALID_KEY && $campaign->integration) {
                app(ResendConnectionService::class)->markProblem($campaign->integration, $e);
            }
            $this->markUnconfirmed($campaign, $e->reason);

            return;
        }

        foreach ($blocked as $recipient) {
            $recipient->forceFill(['sync_status' => 'skipped', 'skip_reason' => 'suppressed_after_approval'])->save();
            OutboundEmail::whereKey($recipient->outbound_email_id)->where('status', OutboundEmail::STATUS_PENDING)
                ->update(['error_code' => 'skipped_suppressed', 'updated_at' => now()]);
        }
        $campaigns->closeUnsent($campaign, EmailCampaign::CANCELLED, sprintf(self::AUTO_CANCELLED, $blocked->count()));
        // The broadcast never went out: give the monthly campaign allowance back
        app(ResendQuota::class)->release($campaign->owner_user_id, ResendQuota::CAMPAIGNS, 1);
        EmailAuditLog::record($campaign->owner_user_id, null, 'campaign_auto_cancelled', null, [
            'campaign_id' => $campaign->id, 'blocked_recipients' => $blocked->count(),
        ]);
    }

    public function markUnconfirmed(EmailCampaign $campaign, string $reason): void
    {
        $campaign->forceFill(['status' => EmailCampaign::UNKNOWN, 'last_error' => mb_substr(sprintf(self::NOT_CANCELLED, $reason), 0, 255)])->save();
        EmailAuditLog::record($campaign->owner_user_id, null, 'campaign_auto_cancel_failed', null, ['campaign_id' => $campaign->id, 'reason' => $reason]);
    }
}
