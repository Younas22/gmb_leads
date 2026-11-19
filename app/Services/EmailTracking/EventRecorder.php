<?php

namespace App\Services\EmailTracking;

use App\Models\EmailTrackingEvent;
use App\Models\OutboundEmail;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Writes activity events. Recording is best-effort: a failure here is logged
 * and never interrupts sending (so it can never cause a retry or a duplicate send).
 * A repeated dedupe_key is silently ignored.
 */
class EventRecorder
{
    public static function record(array $attributes): ?EmailTrackingEvent
    {
        $attributes['occurred_at'] ??= now();

        try {
            return EmailTrackingEvent::create($attributes);
        } catch (UniqueConstraintViolationException) {
            return null; // already recorded
        } catch (Throwable $e) {
            Log::warning('Email event not recorded', ['type' => $attributes['event_type'] ?? null, 'exception' => get_class($e)]);
            return null;
        }
    }

    public static function forEmail(OutboundEmail $email, string $type, string $source, ?string $dedupeKey = null, ?int $userId = null, array $meta = [], $occurredAt = null): ?EmailTrackingEvent
    {
        return self::record([
            'owner_user_id' => $email->owner_user_id,
            'outbound_email_id' => $email->id,
            'saved_lead_id' => $email->saved_lead_id,
            // Phase 5
            'provider' => $email->provider ?? OutboundEmail::PROVIDER_GMAIL,
            'campaign_id' => $email->campaign_id,
            'marketing_contact_id' => $email->marketing_contact_id,
            'user_id' => $userId,
            'event_type' => $type,
            'event_source' => $source,
            'dedupe_key' => $dedupeKey,
            'metadata' => $meta ?: null,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    /**
     * Queued (or re-queued after a failure). Each confirmed send or retry is its own
     * user action (duplicates are already stopped by the idempotency key), so no dedupe key.
     */
    public static function queued(OutboundEmail $email, ?int $userId, bool $retry = false): void
    {
        self::forEmail($email, EmailTrackingEvent::QUEUED, EmailTrackingEvent::SOURCE_SYSTEM, null, $userId,
            array_filter(['retry' => $retry ?: null, 'link_tracking' => $email->link_tracking ?: null]));
    }

    /** Dedupe key for the outcome of the current queue cycle (one failure per cycle). */
    public static function failureKey(OutboundEmail $email): string
    {
        $cycle = EmailTrackingEvent::where('outbound_email_id', $email->id)->where('event_type', EmailTrackingEvent::QUEUED)->count();

        return 'failed:' . $email->id . ':' . $cycle;
    }
}
