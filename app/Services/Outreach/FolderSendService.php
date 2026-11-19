<?php

namespace App\Services\Outreach;

use App\Models\AiEmailDraft;
use App\Models\EmailAuditLog;
use App\Models\EmailSuppression;
use App\Models\LeadFolder;
use App\Models\LeadFolderSend;
use App\Models\LeadFolderSendItem;
use App\Models\MarketingContact;
use App\Models\OutboundEmail;
use App\Models\ResendIntegration;
use App\Models\ResendSender;
use App\Models\User;
use App\Models\UserSalesProfile;
use App\Services\Gmail\OutboundEmailService;
use App\Services\PlanFeatures;
use App\Services\Resend\ResendSendService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * "Send to a lead folder": each lead in a folder receives ITS OWN AI email draft, one at a
 * time with a pause in between, through Gmail or (when allowed) Resend.
 *
 * The run only schedules; every email still goes through the normal single-send pipeline
 * (OutboundEmailService / ResendSendService::queue) when its turn comes, so all existing
 * checks apply at that moment: do-not-email list, plan limits, footer + unsubscribe link,
 * one email per draft.
 */
class FolderSendService
{
    public const INTERVALS = [1, 2, 3, 5, 10, 15]; // minutes between two emails
    public const MAX_LEADS = 500;

    public function __construct(private readonly OutboundEmailService $gmail, private readonly ResendSendService $resend)
    {
    }

    /** Folders of the account, with how many leads have an AI draft ready. */
    public function folders(User $user): Collection
    {
        $owner = $user->getAccountOwner();

        return LeadFolder::where('user_id', $owner->id)->withCount('leads')->orderBy('name')->get()
            ->each(function (LeadFolder $f) use ($owner) {
                $f->ready_count = AiEmailDraft::where('owner_user_id', $owner->id)->where('is_current', true)
                    ->where('generation_status', AiEmailDraft::GENERATION_GENERATED)
                    ->where('review_status', '!=', AiEmailDraft::REVIEW_DISCARDED)
                    ->whereIn('saved_lead_id', DB::table('lead_folder_items')->where('folder_id', $f->id)->select('lead_id'))
                    ->count();
            });
    }

    public function folder(User $user, int $folderId): LeadFolder
    {
        return LeadFolder::where('user_id', $user->getAccountOwner()->id)->findOrFail($folderId);
    }

    /** Problems that stop the whole run for this provider (empty = ready). */
    public function providerProblems(User $user, string $provider, ?int $senderId): array
    {
        $owner = $user->getAccountOwner();
        $profile = UserSalesProfile::where('owner_user_id', $owner->id)->first();
        $problems = [];
        if (!$profile || blank($profile->sender_name) || blank($profile->sender_postal_address)) {
            $problems[] = 'Add your sender name and business postal address in your AI Sales Profile (required in every email).';
        }
        if ($provider === 'gmail') {
            if (!PlanFeatures::enabled($user, 'gmail_sending_enabled')) {
                $problems[] = 'Your plan does not include Gmail sending.';
            }
            if ($this->gmail->account($user)?->status !== 'active') {
                $problems[] = 'Connect your Gmail account first (Email Sending → Gmail).';
            }
        } elseif ($provider === 'resend') {
            $integration = ResendIntegration::forOwner($owner);
            $sender = $senderId ? ResendSender::where('owner_user_id', $owner->id)->with('domain')->find($senderId) : null;
            if (!PlanFeatures::enabled($user, 'resend_single_sending_enabled')) {
                $problems[] = 'Your plan does not include sending through Resend.';
            }
            if (!$integration || !$integration->isUsable()) {
                $problems[] = 'Connect a working Resend account first.';
            }
            if (!$sender || !$sender->canSend()) {
                $problems[] = 'Choose a sender on a verified Resend domain.';
            }
        } else {
            $problems[] = 'Choose Gmail or Resend.';
        }

        return $problems;
    }

    /**
     * Every lead of the folder with its own draft and whether it can be sent.
     * @return list<array{lead: \App\Models\SavedLead, draft: ?AiEmailDraft, recipient: string, ready: bool, reason: ?string, needs_approval: bool}>
     */
    public function plan(User $user, LeadFolder $folder, string $provider): array
    {
        $owner = $user->getAccountOwner();
        $allowedUserIds = $owner->isCompany() ? array_merge([$owner->id], $owner->teamMembers()->pluck('id')->all()) : [$owner->id];
        $leads = $folder->leads()->whereIn('saved_leads.user_id', $allowedUserIds)->orderBy('saved_leads.id')->limit(self::MAX_LEADS)->get();
        $drafts = AiEmailDraft::where('owner_user_id', $owner->id)->where('is_current', true)
            ->whereIn('saved_lead_id', $leads->pluck('id'))->get()->keyBy('saved_lead_id');
        $integration = ResendIntegration::forOwner($owner);

        $rows = [];
        foreach ($leads as $lead) {
            $draft = $drafts->get($lead->id);
            $recipient = EmailSuppression::normalize((string) ($lead->email ?? ''));
            $reason = match (true) {
                !OutboundEmailService::validRecipient($recipient) => $recipient === '' ? 'No email address' : 'Email address is not valid',
                !$draft => 'No AI email yet (analyse this lead in AI Outreach)',
                $draft->generation_status !== AiEmailDraft::GENERATION_GENERATED => 'AI email could not be written',
                $draft->review_status === AiEmailDraft::REVIEW_DISCARDED => 'You discarded this email',
                OutboundEmail::where('draft_lock', (string) $draft->id)->exists()
                    || OutboundEmail::where('draft_id', $draft->id)->whereIn('status', [OutboundEmail::STATUS_SENT, OutboundEmail::STATUS_PENDING])->exists() => 'Already sent',
                EmailSuppression::isSuppressed($owner->id, $recipient) => 'On your do-not-email list',
                $provider === 'resend' && !$this->resendAllows($owner, $integration, $recipient) => 'Not an opted-in contact and Resend emails to leads are off',
                default => null,
            };
            $rows[] = [
                'lead' => $lead, 'draft' => $draft, 'recipient' => $recipient, 'ready' => $reason === null, 'reason' => $reason,
                'needs_approval' => $reason === null && $draft->review_status !== AiEmailDraft::REVIEW_APPROVED,
            ];
        }

        return $rows;
    }

    private function resendAllows(User $owner, ?ResendIntegration $integration, string $recipient): bool
    {
        $contact = MarketingContact::where('owner_user_id', $owner->id)->where('email_hash', MarketingContact::hashFor($recipient))->first();

        return $contact ? $contact->isSendable() : (bool) $integration?->allowsLeadOutreach();
    }

    /** Approve the ready drafts (the user confirmed them) and schedule one email per interval. */
    public function start(User $user, LeadFolder $folder, string $provider, ?int $senderId, int $intervalMinutes): LeadFolderSend
    {
        $owner = $user->getAccountOwner();
        $rows = $this->plan($user, $folder, $provider);

        return DB::transaction(function () use ($user, $owner, $folder, $provider, $senderId, $intervalMinutes, $rows) {
            $send = LeadFolderSend::create([
                'owner_user_id' => $owner->id, 'user_id' => $user->id, 'folder_id' => $folder->id, 'folder_name' => mb_substr($folder->name, 0, 150),
                'provider' => $provider, 'resend_sender_id' => $provider === 'resend' ? $senderId : null,
                'interval_seconds' => $intervalMinutes * 60, 'status' => LeadFolderSend::RUNNING, 'total' => count($rows),
            ]);

            $slot = 0;
            foreach ($rows as $row) {
                if ($row['ready'] && $row['needs_approval']) {
                    $row['draft']->forceFill(['review_status' => AiEmailDraft::REVIEW_APPROVED, 'approved_at' => now(), 'approved_by_user_id' => $user->id])->save();
                }
                LeadFolderSendItem::create([
                    'send_id' => $send->id, 'saved_lead_id' => $row['lead']->id, 'draft_id' => $row['draft']?->id,
                    'lead_name' => mb_substr((string) $row['lead']->name, 0, 255), 'recipient' => $row['recipient'] ?: null,
                    'status' => $row['ready'] ? LeadFolderSendItem::PENDING : LeadFolderSendItem::SKIPPED,
                    'reason' => $row['reason'],
                    'due_at' => $row['ready'] ? now()->addMinutes($intervalMinutes * $slot++) : null,
                ]);
            }
            if ($slot === 0) {
                $send->forceFill(['status' => LeadFolderSend::COMPLETED, 'finished_at' => now()])->save();
            }
            EmailAuditLog::record($owner->id, $user->id, 'folder_send_started', null, [
                'send_id' => $send->id, 'folder_id' => $folder->id, 'provider' => $provider, 'ready' => $slot, 'interval_minutes' => $intervalMinutes,
            ]);

            return $send;
        });
    }

    public function cancel(User $user, LeadFolderSend $send): int
    {
        $n = $send->items()->where('status', LeadFolderSendItem::PENDING)
            ->update(['status' => LeadFolderSendItem::CANCELLED, 'reason' => 'Stopped by ' . $user->name, 'processed_at' => now(), 'updated_at' => now()]);
        if ($send->status === LeadFolderSend::RUNNING) {
            $send->forceFill(['status' => LeadFolderSend::CANCELLED, 'finished_at' => now()])->save();
        }
        EmailAuditLog::record($send->owner_user_id, $user->id, 'folder_send_cancelled', null, ['send_id' => $send->id, 'stopped' => $n]);

        return $n;
    }

    /**
     * Called every minute: hands at most ONE due email per run to the send pipeline, so
     * emails stay spaced even if the scheduler was late.
     * @return int emails handed over
     */
    public function processDue(): int
    {
        $handled = 0;
        foreach (LeadFolderSend::where('status', LeadFolderSend::RUNNING)->get() as $send) {
            $item = $send->items()->where('status', LeadFolderSendItem::PENDING)->where('due_at', '<=', now())->orderBy('due_at')->orderBy('id')->first();
            if ($item) {
                $this->sendItem($send, $item);
                $handled++;
            }
            if (!$send->items()->where('status', LeadFolderSendItem::PENDING)->exists()) {
                $send->forceFill(['status' => LeadFolderSend::COMPLETED, 'finished_at' => now()])->save();
            }
        }

        return $handled;
    }

    private function sendItem(LeadFolderSend $send, LeadFolderSendItem $item): void
    {
        // Claim first so two workers can never send the same item
        if (!LeadFolderSendItem::whereKey($item->id)->where('status', LeadFolderSendItem::PENDING)->update(['status' => LeadFolderSendItem::QUEUED, 'processed_at' => now(), 'updated_at' => now()])) {
            return;
        }

        $user = User::find($send->user_id);
        $draft = $item->draft_id ? AiEmailDraft::with('lead')->where('owner_user_id', $send->owner_user_id)->find($item->draft_id) : null;
        $problems = [];
        $email = null;
        if (!$user || !$draft || !$item->recipient) {
            $problems[] = 'The user, AI email or address no longer exists.';
        } else {
            try {
                $key = 'folder-' . $send->id . '-' . $item->id;
                if ($send->provider === 'gmail') {
                    $preview = $this->gmail->preview($user, $draft, $item->recipient);
                    $result = $this->gmail->queue($user, $draft, $item->recipient, $key, (bool) ($preview['tracking']['enabled'] ?? false));
                } else {
                    $result = $this->resend->queue($user, $draft, $send->resend_sender_id, $item->recipient, $key);
                }
                $problems = $result['problems'];
                $email = $result['email'];
            } catch (Throwable $e) {
                report($e);
                $problems[] = 'Could not be queued (unexpected error).';
            }
        }

        $item->forceFill($email && $problems === []
            ? ['status' => LeadFolderSendItem::QUEUED, 'outbound_email_id' => $email->id, 'reason' => null]
            : ['status' => LeadFolderSendItem::FAILED, 'reason' => mb_substr(implode(' ', $problems) ?: 'Not sent.', 0, 255)])->save();
    }
}
