<?php

namespace App\Services\EmailTracking;

use App\Models\OutboundEmail;

/** Phase 4 reply source: the user records replies by hand. Never reads Gmail. */
class ManualReplySource implements ReplySource
{
    public function key(): string
    {
        return 'manual';
    }

    public function supportsAutomaticDetection(): bool
    {
        return false;
    }

    public function repliesFor(OutboundEmail $email): array
    {
        return [];
    }
}
