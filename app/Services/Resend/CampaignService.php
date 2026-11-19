<?php

namespace App\Services\Resend;

use App\Jobs\SyncCampaignAudience;
use App\Models\EmailAuditLog;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailSuppression;
use App\Models\MarketingContact;
use App\Models\OutboundEmail;
use App\Models\ResendIntegration;
use App\Models\User;
use App\Models\UserSalesProfile;
use App\Services\EmailTracking\EventRecorder;
use App\Services\PlanFeatures;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Permission-based campaigns: opted-in contacts only, explicit approval, sent as ONE
 * Resend Broadcast to a per-campaign Resend Segment (no per-recipient send jobs).
 *
 * draft --approve--> preparing --(contacts synced, broadcast created)--> scheduled | submitted
 *   any of draft/preparing --cancel--> cancelled; scheduled --cancel (DELETE broadcast)--> cancelled
 *   broadcast request with unclear outcome --> unknown (never re-created automatically)
 */
class CampaignService
{
    public function __construct(private readonly ResendQuota $quota, private readonly CampaignRenderer $renderer)
    {
    }

    /** Opted-in, not suppressed, matching the list and segment filters. */
    public function eligible(EmailCampaign $campaign): Builder
    {
        $filters = (array) ($campaign->segment_filters ?? []);

        return MarketingContact::where('owner_user_id', $campaign->owner_user_id)->sendable()
            ->when($campaign->list_id, fn ($q) => $q->whereIn('id', DB::table('marketing_contact_list_members')->where('list_id', $campaign->list_id)->select('contact_id')))
            ->when(!empty($filters['tag']), fn ($q) => $q->where('tags', 'like', '%"' . str_replace(['%', '_', '"'], '', $filters['tag']) . '"%'))
            ->when(!empty($filters['organization']), fn ($q) => $q->where('organization', 'like', '%' . str_replace(['%', '_'], '', $filters['organization']) . '%'))
            ->when(!empty($filters['consent_after']), fn ($q) => $q->where('consent_at', '>=', Carbon::parse($filters['consent_after'])->startOfDay()))
            ->whereNotIn('email_hash', EmailSuppression::where('owner_user_id', $campaign->owner_user_id)->select('email_hash'));
    }

    /** Everything the review screen needs, plus blocking problems. */
    public function review(User $user, EmailCampaign $campaign): array
    {
        $problems = [];
        $integration = ResendIntegration::forOwner($user->getAccountOwner());
        $sender = $campaign->sender()->with('domain')->first();
        $eligible = $this->eligible($campaign)->count();
        $profile = UserSalesProfile::where('owner_user_id', $campaign->owner_user_id)->first();

        if (!PlanFeatures::enabled($user, 'resend_campaigns_enabled')) {
            $problems[] = 'Your plan does not include Resend campaigns.';
        }
        if (!$integration || !$integration->isUsable()) {
            $problems[] = 'Connect a working Resend account first.';
        }
        if (!$sender || (int) $sender->owner_user_id !== (int) $campaign->owner_user_id) {
            $problems[] = 'Choose a sender.';
        } elseif (!$sender->canSend()) {
            $problems[] = 'The sender\'s domain is not verified in Resend.';
        }
        if ($eligible === 0) {
            $problems[] = 'No opted-in, non-suppressed contacts match this campaign.';
        }
        if (!$profile || blank($profile->sender_name) || blank($profile->sender_postal_address)) {
            $problems[] = 'Add your sender name and business postal address in your AI Sales Profile (required in every campaign).';
        }
        if (blank($campaign->subject) || blank($campaign->body)) {
            $problems[] = 'Add a subject and a message.';
        }

        $sample = $this->eligible($campaign)->orderBy('id')->first();

        return [
            'eligible' => $eligible,
            'excluded_suppressed' => MarketingContact::where('owner_user_id', $campaign->owner_user_id)->sendable()
                ->whereIn('email_hash', EmailSuppression::where('owner_user_id', $campaign->owner_user_id)->select('email_hash'))->count(),
            'problems' => $problems,
            'sender' => $sender,
            'subject' => $this->renderer->personalize($campaign->subject, $sample),
            'body' => $this->renderer->personalize($campaign->body, $sample) . "\n\n" . $this->renderer->footer($profile, '[Resend unsubscribe link]'),
            'sample' => $sample,
            'tracking' => ['open' => (bool) $sender?->domain?->open_tracking, 'click' => (bool) $sender?->domain?->click_tracking],
        ];
    }

    /** Controlled test: only to the signed-in user's own address. */
    public function sendTest(User $user, EmailCampaign $campaign): ?string
    {
        if ($campaign->test_sends >= 5) {
            return 'This campaign already had 5 test emails.';
        }
        $review = $this->review($user, $campaign);
        $blocking = array_filter($review['problems'], fn ($p) => !str_starts_with($p, 'No opted-in'));
        if ($blocking) {
            return implode(' ', $blocking);
        }
        if ($problem = $this->quota->reserve($user, [ResendQuota::DAILY => 1, ResendQuota::MONTHLY => 1])) {
            return $problem;
        }

        $profile = UserSalesProfile::where('owner_user_id', $campaign->owner_user_id)->first();
        try {
            ResendConnectionService::client($campaign->integration ?? ResendIntegration::forOwner($user->getAccountOwner()))->sendEmail([
                'from' => $review['sender']->fromHeader(),
                'to' => [$user->email],
                'subject' => '[TEST] ' . $review['subject'],
                'text' => $this->renderer->personalize($campaign->body, $review['sample']) . "\n\n" . $this->renderer->footer($profile, '(unsubscribe link appears here in the real campaign)'),
                'tags' => [['name' => 'cnm_campaign_test', 'value' => (string) $campaign->id]],
            ], 'cnm-test-' . $campaign->id . '-' . Str::random(16));
        } catch (ResendException $e) {
            if (!$e->isAmbiguous()) {
                $this->quota->release($campaign->owner_user_id, ResendQuota::DAILY, 1);
                $this->quota->release($campaign->owner_user_id, ResendQuota::MONTHLY, 1);
            }

            return $e->getMessage();
        }

        $campaign->increment('test_sends');
        EmailAuditLog::record($campaign->owner_user_id, $user->id, 'campaign_test_sent', null, ['campaign_id' => $campaign->id]);

        return null;
    }

    /**
     * Explicit approval: snapshot recipients, reserve quota, start the audience sync.
     * Only one request can move a campaign out of "draft".
     */
    public function approve(User $user, EmailCampaign $campaign, ?Carbon $scheduleAt): ?string
    {
        if ($scheduleAt) {
            $min = now()->addMinutes((int) config('resend_user.schedule_min_minutes', 10));
            $max = now()->addDays((int) config('resend_user.schedule_max_days', 30));
            if ($scheduleAt->lt($min) || $scheduleAt->gt($max)) {
                return 'Choose a send time between ' . config('resend_user.schedule_min_minutes') . ' minutes and ' . config('resend_user.schedule_max_days') . ' days from now.';
            }
        }

        $problem = Cache::lock('campaign:' . $campaign->id, 30)->block(10, function () use ($user, $campaign, $scheduleAt) {
            $campaign->refresh();
            if ($campaign->status !== EmailCampaign::DRAFT) {
                return 'This campaign has already been approved.';
            }
            $review = $this->review($user, $campaign);
            if ($review['problems'] !== []) {
                return implode(' ', $review['problems']);
            }

            $contacts = $this->eligible($campaign)->orderBy('id')->get();
            if ($problem = $this->quota->reserve($user, [ResendQuota::CAMPAIGNS => 1, ResendQuota::MONTHLY => $contacts->count()])) {
                return $problem;
            }

            $owner = $user->getAccountOwner();
            $profile = UserSalesProfile::where('owner_user_id', $owner->id)->first();

            DB::transaction(function () use ($user, $owner, $campaign, $contacts, $scheduleAt, $profile, $review) {
                $claimed = EmailCampaign::whereKey($campaign->id)->where('status', EmailCampaign::DRAFT)->update([
                    'status' => EmailCampaign::PREPARING,
                    'approved_at' => now(),
                    'approved_by_user_id' => $user->id,
                    'scheduled_at' => $scheduleAt,
                    'resend_integration_id' => ResendIntegration::forOwner($owner)->id,
                    'recipient_count' => $contacts->count(),
                    'reserved_quota' => $contacts->count(),
                    'last_error' => null,
                    'updated_at' => now(),
                ]);
                if (!$claimed) {
                    throw new \RuntimeException('campaign already approved');
                }

                foreach ($contacts as $contact) {
                    $email = OutboundEmail::create([
                        'owner_user_id' => $owner->id,
                        'provider' => OutboundEmail::PROVIDER_RESEND,
                        'user_id' => $user->id,
                        'resend_integration_id' => ResendIntegration::forOwner($owner)->id,
                        'resend_sender_id' => $review['sender']->id,
                        'saved_lead_id' => $contact->saved_lead_id,
                        'campaign_id' => $campaign->id,
                        'marketing_contact_id' => $contact->id,
                        'idempotency_key' => 'campaign-' . $campaign->id . '-' . $contact->id,
                        'from_email' => $review['sender']->from_email,
                        'to_email' => $contact->email,
                        'subject' => mb_substr($this->renderer->personalize($campaign->subject, $contact), 0, 200),
                        'body' => $this->renderer->personalize($campaign->body, $contact) . "\n\n" . $this->renderer->footer($profile, '[Resend unsubscribe link]'),
                        'link_tracking' => false,
                        'status' => OutboundEmail::STATUS_PENDING,
                        'queued_at' => now(),
                    ]);
                    EmailCampaignRecipient::create([
                        'campaign_id' => $campaign->id, 'contact_id' => $contact->id, 'outbound_email_id' => $email->id, 'email' => $contact->email,
                    ]);
                }
            });

            EmailAuditLog::record($owner->id, $user->id, 'campaign_approved', null, ['campaign_id' => $campaign->id, 'recipients' => $contacts->count(), 'scheduled' => (bool) $scheduleAt]);

            return null;
        });

        // Started only after the approval lock is released (the job takes the same lock)
        if ($problem === null) {
            self::dispatchSync($campaign->id);
        }

        return $problem;
    }

    /** @return string|null problem */
    public function cancel(User $user, EmailCampaign $campaign): ?string
    {
        return Cache::lock('campaign:' . $campaign->id, 60)->block(15, function () use ($user, $campaign) {
            $campaign->refresh();
            if (!$campaign->isCancellable()) {
                return 'This campaign can no longer be cancelled.';
            }

            if ($campaign->status === EmailCampaign::SCHEDULED && $campaign->provider_broadcast_id) {
                try {
                    ResendConnectionService::client($campaign->integration)->deleteBroadcast($campaign->provider_broadcast_id);
                } catch (ResendException $e) {
                    return 'Resend could not cancel the broadcast (it may already be sending): ' . $e->getMessage();
                }
            }

            $this->closeUnsent($campaign, EmailCampaign::CANCELLED, null);
            EmailAuditLog::record($campaign->owner_user_id, $user->id, 'campaign_cancelled', null, ['campaign_id' => $campaign->id]);

            return null;
        });
    }

    /** Cancel every not-yet-sent recipient row and give back the unused quota. */
    public function closeUnsent(EmailCampaign $campaign, string $status, ?string $error): void
    {
        DB::transaction(function () use ($campaign, $status, $error) {
            $unsent = OutboundEmail::where('campaign_id', $campaign->id)->where('status', OutboundEmail::STATUS_PENDING)
                ->update(['status' => OutboundEmail::STATUS_CANCELLED, 'cancelled_at' => now(), 'updated_at' => now()]);
            $campaign->forceFill([
                'status' => $status,
                'cancelled_at' => $status === EmailCampaign::CANCELLED ? now() : $campaign->cancelled_at,
                'last_error' => $error ? mb_substr($error, 0, 255) : $campaign->last_error,
                'reserved_quota' => max(0, $campaign->reserved_quota - $unsent),
            ])->save();
            $this->quota->release($campaign->owner_user_id, ResendQuota::MONTHLY, $unsent);
            if ($campaign->provider_broadcast_id === null) {
                $this->quota->release($campaign->owner_user_id, ResendQuota::CAMPAIGNS, 1);
            }
        });
    }

    public static function dispatchSync(int $campaignId, int $delaySeconds = 0): void
    {
        $job = dispatch(new SyncCampaignAudience($campaignId))
            ->onConnection(config('resend_user.queue.connection'))
            ->onQueue(config('resend_user.queue.send'));
        if ($delaySeconds > 0) {
            $job->delay($delaySeconds);
        }
    }

    /** Record the provider acceptance of a broadcast for every recipient row. */
    public function markSubmitted(EmailCampaign $campaign, string $broadcastId, bool $scheduled): void
    {
        DB::transaction(function () use ($campaign, $broadcastId, $scheduled) {
            $campaign->forceFill([
                'provider_broadcast_id' => $broadcastId,
                'status' => $scheduled ? EmailCampaign::SCHEDULED : EmailCampaign::SUBMITTED,
                'submitted_at' => now(),
            ])->save();

            if (!$scheduled) {
                // Accepted by Resend for sending; delivery comes from webhooks per recipient
                OutboundEmail::where('campaign_id', $campaign->id)->where('status', OutboundEmail::STATUS_PENDING)
                    ->update(['status' => OutboundEmail::STATUS_SENT, 'sent_at' => now(), 'updated_at' => now()]);
            }
        });

        foreach (OutboundEmail::where('campaign_id', $campaign->id)->whereIn('status', [OutboundEmail::STATUS_SENT, OutboundEmail::STATUS_PENDING])->cursor() as $email) {
            EventRecorder::forEmail($email, \App\Models\EmailTrackingEvent::ACCEPTED, \App\Models\EmailTrackingEvent::SOURCE_RESEND_API,
                'accepted:' . $email->id, $campaign->approved_by_user_id, ['broadcast' => true, 'scheduled' => $scheduled ?: null]);
        }
    }
}
