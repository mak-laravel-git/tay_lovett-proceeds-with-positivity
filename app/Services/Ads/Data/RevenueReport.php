<?php

namespace App\Services\Ads\Data;

/**
 * A provider's own report of what it earned the publisher on one day, as rows per user and country.
 */
final readonly class RevenueReport
{
    public const STATE_OK = 'ok';

    public const STATE_UNAVAILABLE = 'unavailable';

    public const STATE_FAILED = 'failed';

    /**
     * @param  list<array{user: string|null, country: string|null, micros: int}>  $rows
     */
    public function __construct(
        public string $state,
        public array $rows = [],
        public ?string $error = null,
        public ?int $httpStatus = null,
        public ?int $latencyMs = null,
    ) {}

    /**
     * @param  list<array{user: string|null, country: string|null, micros: int}>  $rows
     */
    public static function ok(array $rows, ?int $httpStatus = 200, ?int $latencyMs = null): self
    {
        return new self(self::STATE_OK, $rows, null, $httpStatus, $latencyMs);
    }

    public static function unavailable(string $reason, ?int $httpStatus = null): self
    {
        return new self(self::STATE_UNAVAILABLE, [], $reason, $httpStatus);
    }

    public static function failed(string $error, ?int $httpStatus = null, ?int $latencyMs = null): self
    {
        return new self(self::STATE_FAILED, [], $error, $httpStatus, $latencyMs);
    }
}
