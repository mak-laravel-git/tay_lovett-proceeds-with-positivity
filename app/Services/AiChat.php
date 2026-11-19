<?php

namespace App\Services;

use App\Ai\Agents\ChatAgent;
use App\Models\AiGenerationLog;
use App\Models\AiModel;
use App\Models\AiProviderCredential;
use App\Models\AiSetting;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Throwable;

class AiChat
{
    public function __construct(
        private QuoteRouter $router,
        private AiFailureClassifier $classifier,
    ) {}

    /**
     * Answer one test-chat message.
     *
     * Without a credential the chat follows the live routing order, including fallbacks. With a credential it
     * makes exactly one call to that provider and model so the administrator sees that provider's real result.
     *
     * @param  list<array{role: string, content: string}>  $history
     * @return array{reply: string, provider: string, driver: string, model: string, latency_ms: int, input_tokens: int|null, output_tokens: int|null}
     */
    public function send(User $user, array $history, string $message, ?AiProviderCredential $credential, ?AiModel $model): array
    {
        $steps = $credential === null
            ? $this->router->candidates()->all()
            : [['credential' => $credential, 'model' => $model ?? $this->router->defaultModelFor($credential)]];

        $steps = array_values(array_filter($steps, fn (array $step): bool => $step['model'] !== null));

        if ($steps === []) {
            throw ValidationException::withMessages([
                'message' => $credential === null
                    ? 'No provider route is ready. Add an enabled provider and model first.'
                    : 'This provider has no enabled model. Add one in Models first.',
            ]);
        }

        $timeout = AiSetting::current()->timeout_seconds;
        $lastError = 'The call failed.';

        foreach ($steps as ['credential' => $step, 'model' => $stepModel]) {
            $started = microtime(true);

            try {
                $response = (new ChatAgent($history))->prompt(
                    $message,
                    provider: [$this->router->provider($step)],
                    model: $stepModel->name,
                    timeout: $timeout,
                );
            } catch (Throwable $exception) {
                $category = $this->classifier->classify($exception);
                $lastError = $step->label.': '.$this->classifier->describe($category);

                if ($category === 'unknown') {
                    $lastError .= ' '.$this->router->safeMessage($exception, $step);
                }

                $this->log($user, $step, $stepModel, null, null, $category);

                if (! $this->classifier->mayContinue($category)) {
                    break;
                }

                continue;
            }

            $inputTokens = $response->usage->inputTokens ?? null;
            $outputTokens = $response->usage->outputTokens ?? null;
            $this->log($user, $step, $stepModel, $inputTokens, $outputTokens, null);

            return [
                'reply' => trim($response->text),
                'provider' => $step->label,
                'driver' => $step->driver,
                'model' => $stepModel->name,
                'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
            ];
        }

        throw ValidationException::withMessages(['message' => $lastError]);
    }

    private function log(User $user, AiProviderCredential $credential, AiModel $model, ?int $input, ?int $output, ?string $error): void
    {
        AiGenerationLog::query()->create([
            'user_id' => $user->id,
            'driver' => $credential->driver,
            'model' => $model->name,
            'credential_label' => $credential->label.' (test chat)',
            'input_tokens' => $input,
            'output_tokens' => $output,
            'error_category' => $error,
        ]);
    }
}
