<?php

namespace App\Services\EmailTracking;

use App\Models\OutboundEmail;

/**
 * Extension point for a FUTURE automatic reply sync.
 *
 * Not implemented in Phase 4: detecting replies needs Gmail read access
 * (gmail.readonly or gmail.metadata), which are restricted scopes requiring
 * separate user consent, Google verification and possibly a security assessment.
 * Until such an integration is authorised, only ManualReplySource exists and
 * the UI states that automatic reply detection is unavailable.
 */
interface ReplySource
{
    /** Short identifier stored in email_reply_records.source */
    public function key(): string;

    public function supportsAutomaticDetection(): bool;

    /**
     * Replies found for an email, newest first.
     *
     * @return list<array{received_at: \DateTimeInterface, thread_id: ?string}>
     */
    public function repliesFor(OutboundEmail $email): array;
}
