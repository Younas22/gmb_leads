<?php

namespace App\Services\Ai\Exceptions;

class AiQuotaExceededException extends AiException
{
    public function __construct(
        public readonly string $feature,
        public readonly int $limit,
        public readonly int $used,
    ) {
        parent::__construct($limit === 0
            ? 'Your current plan does not include this AI feature.'
            : "You have used all {$limit} AI requests for this feature this month.");
    }

    public function errorType(): string
    {
        return $this->limit === 0 ? 'feature_not_in_plan' : 'quota_exceeded';
    }
}
