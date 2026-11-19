<?php

namespace App\Services\Ai\Outreach;

use RuntimeException;

/** The account has no active service or offer to recommend. No AI call is made. */
class ProfileIncompleteException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Add at least one active service or offer to your AI Sales Profile so the AI has something to recommend.');
    }
}
