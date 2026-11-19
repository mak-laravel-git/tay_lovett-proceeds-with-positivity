<?php

namespace Database\Factories;

use App\Models\AdProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdProvider>
 */
class AdProviderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'tapresearch',
            'label' => 'TapResearch (Survey Feed API)',
            'is_enabled' => true,
            'priority' => 10,
            'health' => 'unknown',
            'settings' => [
                'api_token' => 'test-api-token',
                'api_secret' => 'test-api-secret',
            ],
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => ['is_enabled' => false]);
    }

    public function withReporting(): static
    {
        return $this->state(fn (array $attributes): array => [
            'settings' => [...$attributes['settings'], 'reporting_access_token' => 'test-reporting-token'],
        ]);
    }

    public function withoutCredentials(): static
    {
        return $this->state(fn (array $attributes): array => ['settings' => null]);
    }
}
