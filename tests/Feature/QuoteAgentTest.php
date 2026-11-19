<?php

use App\Ai\Agents\QuoteAgent;
use App\Jobs\GenerateQuotePoolJob;
use App\Models\QuoteCategory;
use App\Services\QuoteGenerator;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Ai;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Gateway\StepResponse;
use Laravel\Ai\Providers\Provider;
use Laravel\Ai\Responses\Data\FinishReason;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Tests\Support\ScriptedStepGateway;

test('two same driver credentials stay distinct and the second succeeds after a rate limit', function () {
    $first = Ai::build([
        'name' => 'pwp-credential-a',
        'driver' => 'openai',
        'key' => 'fake-key-a',
    ]);
    $second = Ai::build([
        'name' => 'pwp-credential-b',
        'driver' => 'openai',
        'key' => 'fake-key-b',
    ]);

    $list = Provider::formatProviderAndModelList([$first, $second]);

    expect(array_keys($list))->toBe(['pwp-credential-a', 'pwp-credential-b']);

    $first->useTextGateway(new ScriptedStepGateway(function (TextProvider $provider): StepResponse {
        throw RateLimitedException::forProvider($provider->name());
    }));
    $second->useTextGateway(new ScriptedStepGateway(function (TextProvider $provider): StepResponse {
        return new StepResponse(
            'Keep going.',
            [],
            FinishReason::Stop,
            new TextUsage(10, 8),
            new Meta($provider->name(), 'gpt-test'),
            ['text' => 'Keep going.', 'category' => 'Hope'],
        );
    }));

    $response = (new QuoteAgent('Hope'))->prompt(
        'Write a quote.',
        provider: [$first, $second],
        model: 'gpt-test',
    );

    expect($response['text'])->toBe('Keep going.')
        ->and($response->meta->provider)->toBe('pwp-credential-b');
});

test('a faked prompt stores a quote for review and a job carries only a category id', function () {
    QuoteAgent::fake([
        ['text' => 'A small kindness can change a day.', 'category' => 'Kindness'],
    ])->preventStrayPrompts();

    $category = QuoteCategory::factory()->create(['name' => 'Kindness']);
    $quote = app(QuoteGenerator::class)->generate($category);

    expect($quote->status->value)->toBe('pending_review')
        ->and($quote->text)->toBe('A small kindness can change a day.');

    Queue::fake();
    GenerateQuotePoolJob::dispatch($category->id);
    Queue::assertPushed(GenerateQuotePoolJob::class, function (GenerateQuotePoolJob $job) use ($category) {
        $encoded = serialize($job);

        return $job->categoryId === $category->id
            && ! str_contains($encoded, 'fake-key');
    });
});

test('an empty structured response is rejected', function () {
    QuoteAgent::fake([
        ['category' => 'Hope'],
    ]);

    $category = QuoteCategory::factory()->create();

    expect(fn () => app(QuoteGenerator::class)->generate($category))
        ->toThrow(ValidationException::class);
});
