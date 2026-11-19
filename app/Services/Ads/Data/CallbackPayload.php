<?php

namespace App\Services\Ads\Data;

/**
 * A provider callback after the adapter has parsed and verified it. The processor never looks at raw
 * provider parameters, so any provider can plug into the same pipeline by producing one of these.
 */
final readonly class CallbackPayload
{
    public const KIND_COMPLETION = 'completion';

    public const KIND_REJECTED = 'rejected';

    public const KIND_OTHER = 'other';

    /**
     * @param  array<string, mixed>  $sanitized  Parameters safe to store and show (no signature or token).
     */
    public function __construct(
        public bool $signatureValid,
        public bool $tokenValid,
        public ?string $transactionId,
        public ?string $externalUserId,
        public ?string $activityId,
        public ?string $offerIdentifier,
        public ?int $payoutAmount,
        public ?string $payoutCurrency,
        public ?int $payoutType,
        public ?int $outcome,
        public int $revenueMicros,
        public string $kind,
        public array $sanitized,
    ) {}

    public function isAuthentic(): bool
    {
        return $this->signatureValid && $this->tokenValid;
    }
}
