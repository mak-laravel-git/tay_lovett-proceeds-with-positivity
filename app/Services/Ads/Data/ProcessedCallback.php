<?php

namespace App\Services\Ads\Data;

use App\Enums\CallbackResult;

final readonly class ProcessedCallback
{
    public function __construct(
        public int $httpStatus,
        public CallbackResult $result,
        public ?string $activityId = null,
    ) {}
}
