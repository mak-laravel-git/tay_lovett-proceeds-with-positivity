<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Throwable;

class AiFailureClassifier
{
    /**
     * Classify a provider failure. Unknown failures stay closed.
     */
    public function classify(Throwable $exception): string
    {
        $status = $this->httpStatus($exception);

        return match (true) {
            $exception instanceof RateLimitedException => 'rate_limit',
            $exception instanceof ProviderOverloadedException, $exception instanceof ProviderConnectionException => 'transient_outage',
            $exception instanceof InsufficientCreditsException => 'insufficient_credits',
            in_array($status, [401, 403], true) => 'invalid_credentials',
            $status === 404 => 'model_not_found',
            default => 'unknown',
        };
    }

    /**
     * Categories that may try the next pre-approved credential.
     */
    public function mayContinue(string $category): bool
    {
        return in_array($category, [
            'rate_limit',
            'transient_outage',
            'insufficient_credits',
            'invalid_credentials',
            'model_not_found',
        ], true);
    }

    /**
     * Plain-language explanation for administrators.
     */
    public function describe(string $category): string
    {
        return match ($category) {
            'healthy' => 'The provider answered correctly.',
            'rate_limit' => 'The provider is rate limiting this key. Try again shortly.',
            'transient_outage' => 'The provider could not be reached or is overloaded.',
            'insufficient_credits' => 'The account has no credit left. Add billing with the provider.',
            'invalid_credentials' => 'The provider rejected the API key. Check or replace the key.',
            'model_not_found' => 'The provider does not know this model name. Check the model id.',
            default => 'The call failed for an unexpected reason.',
        };
    }

    private function httpStatus(Throwable $exception): ?int
    {
        $current = $exception;

        while ($current !== null) {
            if ($current instanceof RequestException) {
                return $current->response->status();
            }

            $current = $current->getPrevious();
        }

        return null;
    }
}
