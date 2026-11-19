<?php

namespace App\Services\Ads\Data;

/**
 * One survey (or other activity) a provider can offer a user. The entry URL stays on the server: the browser
 * only ever receives it from the "start" endpoint, which also records the attempt.
 */
final readonly class Offer
{
    public function __construct(
        public string $identifier,
        public string $entryUrl,
        public ?int $lengthMinutes = null,
        public ?string $category = null,
        public ?string $supportUrl = null,
        public bool $isHot = false,
    ) {}

    /**
     * The shape sent to the frontend: no entry URL.
     *
     * @return array{identifier: string, length_minutes: int|null, category: string|null, support_url: string|null, is_hot: bool}
     */
    public function toPublicArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'length_minutes' => $this->lengthMinutes,
            'category' => $this->category,
            'support_url' => $this->supportUrl,
            'is_hot' => $this->isHot,
        ];
    }
}
