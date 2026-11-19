<?php

namespace App\Services;

use App\Ai\Agents\QuoteAgent;
use App\Enums\QuoteStatus;
use App\Models\AiCredentialAudit;
use App\Models\AiGenerationLog;
use App\Models\AiModel;
use App\Models\AiProviderCredential;
use App\Models\AiRouteStep;
use App\Models\AiSetting;
use App\Models\Quote;
use App\Models\QuoteCategory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Ai;
use Laravel\Ai\Providers\Provider;
use Throwable;

class QuoteRouter
{
    public function __construct(
        private AiBudget $budget,
        private AiFailureClassifier $classifier,
        private QuoteGenerator $generator,
    ) {}

    /**
     * Generate an approved studio quote through one SDK call per eligible credential.
     */
    public function generate(User $user, QuoteCategory $category): Quote
    {
        $settings = AiSetting::current();

        if (! $settings->generation_enabled) {
            throw ValidationException::withMessages([
                'generation' => 'Quote generation is turned off.',
            ]);
        }

        $candidates = $this->candidates()->filter(fn (array $step): bool => $step['model']->max_request_cents !== null);

        if ($candidates->isEmpty()) {
            throw ValidationException::withMessages([
                'generation' => 'No AI provider route is ready. Add an enabled provider and model first.',
            ]);
        }

        $attempts = 0;

        foreach ($candidates as ['credential' => $credential, 'model' => $model]) {
            if ($attempts >= $settings->max_attempts) {
                break;
            }

            if ($this->coolingDown($credential)) {
                continue;
            }

            $reservation = $this->budget->reserve($model->max_request_cents);

            if ($reservation === null) {
                throw ValidationException::withMessages([
                    'generation' => 'The daily generation budget has been reached.',
                ]);
            }

            $attempts++;

            try {
                $provider = $this->provider($credential);

                $response = (new QuoteAgent($category->name))->prompt(
                    'Write a quote about '.$category->name.'.',
                    provider: [$provider],
                    model: $model->name,
                    timeout: $settings->timeout_seconds,
                );

                $inputTokens = $response->usage->inputTokens ?? null;
                $outputTokens = $response->usage->outputTokens ?? null;
                $this->budget->spend($reservation, $inputTokens, $outputTokens);
                $quote = $this->generator->store(
                    $category,
                    (string) ($response['text'] ?? ''),
                    $user->id,
                    QuoteStatus::Approved,
                );

                AiGenerationLog::query()->create([
                    'user_id' => $user->id,
                    'quote_id' => $quote->id,
                    'driver' => $credential->driver,
                    'model' => $model->name,
                    'credential_label' => $credential->label,
                    'input_tokens' => $inputTokens,
                    'output_tokens' => $outputTokens,
                    'reserved_cents' => $reservation->amount_cents,
                    'spent_cents' => $reservation->amount_cents,
                ]);

                return $quote;
            } catch (Throwable $exception) {
                $categoryName = $this->classifier->classify($exception);
                $this->budget->spend($reservation, null, null);
                $this->rememberFailure($credential, $categoryName, $settings->default_cooldown_seconds);

                AiGenerationLog::query()->create([
                    'user_id' => $user->id,
                    'driver' => $credential->driver,
                    'model' => $model->name,
                    'credential_label' => $credential->label,
                    'reserved_cents' => $reservation->amount_cents,
                    'spent_cents' => $reservation->amount_cents,
                    'error_category' => $categoryName,
                ]);

                if (! $this->classifier->mayContinue($categoryName)) {
                    break;
                }
            }
        }

        throw ValidationException::withMessages([
            'generation' => 'A quote could not be generated.',
        ]);
    }

    /**
     * The ordered provider and model pairs that generation will try, without calling anything.
     *
     * Priority mode walks enabled credentials by priority and uses the first enabled model of the same
     * driver. Manual mode follows the explicit route steps in position order.
     *
     * @return Collection<int, array{credential: AiProviderCredential, model: AiModel}>
     */
    public function candidates(): Collection
    {
        if (AiSetting::current()->routing_mode === 'manual') {
            return AiRouteStep::query()
                ->with(['credential', 'model'])
                ->orderBy('position')
                ->get()
                ->filter(fn (AiRouteStep $step): bool => $step->credential !== null
                    && ! $step->credential->trashed()
                    && $step->credential->is_enabled
                    && $step->model !== null
                    && $step->model->is_enabled)
                ->map(fn (AiRouteStep $step): array => ['credential' => $step->credential, 'model' => $step->model])
                ->values();
        }

        return $this->eligibleCredentials()
            ->map(fn (AiProviderCredential $credential): ?array => ($model = $this->defaultModelFor($credential))
                ? ['credential' => $credential, 'model' => $model]
                : null)
            ->filter()
            ->values();
    }

    /**
     * Describe the route without calling a provider.
     *
     * @return array<int, array{label: string, driver: string, model: string, priority: int}>
     */
    public function simulate(): array
    {
        return $this->candidates()
            ->map(fn (array $step): array => [
                'label' => $step['credential']->label,
                'driver' => $step['credential']->driver,
                'model' => $step['model']->name,
                'priority' => $step['credential']->priority,
            ])
            ->all();
    }

    /**
     * Checks an administrator can read to see why generation would or would not work.
     *
     * @return list<array{key: string, label: string, ok: bool, hint: string}>
     */
    public function readiness(): array
    {
        $settings = AiSetting::current();

        return [
            [
                'key' => 'generation',
                'label' => 'Quote generation is switched on',
                'ok' => $settings->generation_enabled,
                'hint' => 'Turn it on in AI settings. Until then Quote Studio refuses every request.',
            ],
            [
                'key' => 'budget',
                'label' => 'Daily budget is above $0',
                'ok' => $settings->daily_budget_cents > 0,
                'hint' => 'A budget of 0 blocks every request. Set a daily limit in AI settings.',
            ],
            [
                'key' => 'provider',
                'label' => 'At least one provider is enabled',
                'ok' => $this->eligibleCredentials()->isNotEmpty(),
                'hint' => 'Add a provider with an API key in Providers.',
            ],
            [
                'key' => 'model',
                'label' => 'A model is ready to use',
                'ok' => $this->candidates()->isNotEmpty(),
                'hint' => $settings->routing_mode === 'manual'
                    ? 'Manual routing needs at least one step with an enabled provider and model.'
                    : 'Add an enabled model for the same provider in Models.',
            ],
        ];
    }

    /**
     * Build an SDK provider for one stored credential.
     */
    public function provider(AiProviderCredential $credential): Provider
    {
        $config = [
            'name' => 'pwp-credential-'.$credential->id.'-'.str()->lower(str()->random(6)),
            'driver' => $credential->driver,
            'key' => $credential->payload['key'] ?? '',
        ];

        if (filled($credential->payload['url'] ?? null)) {
            $config['url'] = $credential->payload['url'];
        }

        return Ai::build($config);
    }

    /**
     * Run a short connection test with the same agent that real generation uses.
     *
     * @return array{ok: bool, category: string, message: string, model: string|null, latency_ms: int|null}
     */
    public function testConnection(User $actor, AiProviderCredential $credential, ?AiModel $model = null): array
    {
        $model ??= $this->defaultModelFor($credential);

        if ($model === null) {
            return [
                'ok' => false,
                'category' => 'no_model',
                'message' => 'Add an enabled model for this provider in Models before testing it.',
                'model' => null,
                'latency_ms' => null,
            ];
        }

        $started = microtime(true);
        $detail = null;

        try {
            (new QuoteAgent('Hope'))->prompt(
                'Write a short quote about hope.',
                provider: [$this->provider($credential)],
                model: $model->name,
                timeout: min(15, AiSetting::current()->timeout_seconds),
            );

            $category = 'healthy';
            $credential->update([
                'health' => 'healthy',
                'last_tested_at' => now(),
                'last_error_category' => null,
            ]);
            $model->update(['structured_output_tested' => true]);
        } catch (Throwable $exception) {
            $category = $this->classifier->classify($exception);
            $detail = $this->safeMessage($exception, $credential);
            $credential->update([
                'health' => $category === 'invalid_credentials' ? 'invalid' : 'unhealthy',
                'last_tested_at' => now(),
                'last_error_category' => $category,
            ]);
        }

        AiCredentialAudit::query()->create([
            'user_id' => $actor->id,
            'ai_provider_credential_id' => $credential->id,
            'action' => 'connection_test',
            'error_category' => $category === 'healthy' ? null : $category,
        ]);

        $message = $this->classifier->describe($category);

        return [
            'ok' => $category === 'healthy',
            'category' => $category,
            'message' => $category === 'unknown' && $detail !== null ? $message.' '.$detail : $message,
            'model' => $model->name,
            'latency_ms' => (int) round((microtime(true) - $started) * 1000),
        ];
    }

    public function defaultModelFor(AiProviderCredential $credential): ?AiModel
    {
        return AiModel::query()
            ->where('driver', $credential->driver)
            ->where('is_enabled', true)
            ->orderBy('id')
            ->first();
    }

    /**
     * Provider error text with the stored secret removed and the length capped.
     */
    public function safeMessage(Throwable $exception, AiProviderCredential $credential): string
    {
        $message = $exception->getMessage();
        $key = (string) ($credential->payload['key'] ?? '');

        if ($key !== '') {
            $message = str_replace($key, '[key]', $message);
        }

        return str($message)->limit(180)->toString();
    }

    /**
     * @return Collection<int, AiProviderCredential>
     */
    private function eligibleCredentials()
    {
        return AiProviderCredential::query()
            ->where('is_enabled', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();
    }

    private function coolingDown(AiProviderCredential $credential): bool
    {
        return Cache::has('ai:cooldown:credential:'.$credential->id);
    }

    private function rememberFailure(AiProviderCredential $credential, string $category, int $seconds): void
    {
        if ($category === 'invalid_credentials') {
            $credential->update(['health' => 'invalid', 'last_error_category' => $category]);
        }

        if (! in_array($category, ['rate_limit', 'transient_outage'], true)) {
            return;
        }

        Cache::put('ai:cooldown:credential:'.$credential->id, $category, now()->addSeconds($seconds));
    }
}
