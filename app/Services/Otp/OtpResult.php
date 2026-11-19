<?php

namespace App\Services\Otp;

use App\Enums\OtpStatus;

final class OtpResult
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly OtpStatus $status,
        public readonly array $meta = [],
    ) {}

    public function verified(): bool
    {
        return $this->status === OtpStatus::Verified;
    }
}
