<?php

namespace App\Services\EmailTracking;

use App\Models\AiEmailDraft;
use App\Models\EmailFollowUp;
use App\Models\EmailReplyRecord;
use App\Models\EmailTrackingEvent;
use App\Models\OutboundEmail;
use App\Models\SavedLead;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Chronological activity for a lead or one email, built from recorded events
 * (each with its source) plus draft approvals and upcoming follow-ups.
 * Always scoped to the viewer's account.
 */
class ActivityTimeline
{
    public const LIMIT = 300;

    /** @return Collection<int, array{at: \Carbon\Carbon, title: string, detail: ?string, source: string, user: ?string, upcoming: bool}> */
    public function forLead(User $owner, SavedLead $lead): Collection
    {
        $events = EmailTrackingEvent::ownedBy($owner)->where('saved_lead_id', $lead->id)
            ->with(['user:id,name', 'link:id,link_position', 'email:id,subject'])
            ->latest('occurred_at')->latest('id')->limit(self::LIMIT)->get();

        $drafts = AiEmailDraft::ownedBy($owner)->where('saved_lead_id', $lead->id)->whereNotNull('approved_at')
            ->with('createdBy:id,name')->latest('approved_at')->limit(50)->get();

        $upcoming = EmailFollowUp::ownedBy($owner)->open()->where('saved_lead_id', $lead->id)->get();

        return $this->merge($events, $drafts, $upcoming);
    }

    public function forEmail(User $owner, OutboundEmail $email): Collection
    {
        $events = EmailTrackingEvent::ownedBy($owner)->where('outbound_email_id', $email->id)
            ->with(['user:id,name', 'link:id,link_position'])
            ->latest('occurred_at')->latest('id')->limit(self::LIMIT)->get();

        $drafts = $email->draft_id
            ? AiEmailDraft::ownedBy($owner)->whereKey($email->draft_id)->whereNotNull('approved_at')->with('createdBy:id,name')->get()
            : collect();

        $upcoming = EmailFollowUp::ownedBy($owner)->open()->where('outbound_email_id', $email->id)->get();

        return $this->merge($events, $drafts, $upcoming);
    }

    private function merge(Collection $events, Collection $drafts, Collection $upcoming): Collection
    {
        $items = collect();

        foreach ($drafts as $draft) {
            $items->push([
                'at' => $draft->approved_at,
                'title' => 'AI email draft approved',
                'detail' => $draft->subject,
                'source' => EmailTrackingEvent::SOURCE_LABELS[EmailTrackingEvent::SOURCE_USER],
                'user' => $draft->createdBy?->name,
                'upcoming' => false,
            ]);
        }

        // Fetched newest-first (to keep the most recent when limited); oldest-first here so
        // events in the same second keep the order they happened in
        foreach ($events->sortBy([['occurred_at', 'asc'], ['id', 'asc']]) as $event) {
            $items->push([
                'at' => $event->occurred_at,
                'title' => $event->typeLabel(),
                'detail' => $this->detail($event),
                'source' => $event->sourceLabel(),
                'user' => $event->user?->name,
                'upcoming' => false,
            ]);
        }

        foreach ($upcoming as $followUp) {
            $items->push([
                'at' => $followUp->due_date->copy()->startOfDay(),
                'title' => 'Follow-up due',
                'detail' => $followUp->notes,
                'source' => EmailTrackingEvent::SOURCE_LABELS[EmailTrackingEvent::SOURCE_USER],
                'user' => null,
                'upcoming' => true,
            ]);
        }

        return $items->sortBy(fn ($i) => $i['at']->getTimestamp())->values();
    }

    private function detail(EmailTrackingEvent $event): ?string
    {
        $meta = $event->metadata ?? [];

        return match ($event->event_type) {
            EmailTrackingEvent::LINK_CLICK => trim('Link #' . ($event->link?->link_position ?? '?') . ' · '
                . (EmailTrackingEvent::CLASSIFICATION_LABELS[$event->classification] ?? 'Unclassified')),
            EmailTrackingEvent::REPLY_RECORDED, EmailTrackingEvent::REPLY_UPDATED => (EmailReplyRecord::STATUSES[$meta['reply_status'] ?? ''] ?? null)
                . (isset($meta['previous_status']) ? ' (was ' . (EmailReplyRecord::STATUSES[$meta['previous_status']] ?? $meta['previous_status']) . ')' : ''),
            EmailTrackingEvent::REPLY_REMOVED => isset($meta['previous_status']) ? 'Was: ' . (EmailReplyRecord::STATUSES[$meta['previous_status']] ?? $meta['previous_status']) : null,
            EmailTrackingEvent::FOLLOW_UP_SCHEDULED => isset($meta['due_date']) ? 'Due ' . \Illuminate\Support\Carbon::parse($meta['due_date'])->format('D, M j') : null,
            EmailTrackingEvent::FOLLOW_UP_CANCELLED => isset($meta['reason']) ? 'Reason: ' . str_replace('_', ' ', $meta['reason']) : null,
            EmailTrackingEvent::FAILED, EmailTrackingEvent::UNKNOWN => isset($meta['code']) ? 'Code: ' . $meta['code'] : null,
            EmailTrackingEvent::QUEUED => !empty($meta['retry']) ? 'Retry' : (!empty($meta['link_tracking']) ? 'With link tracking' : null),
            default => $event->email?->subject,
        };
    }
}
