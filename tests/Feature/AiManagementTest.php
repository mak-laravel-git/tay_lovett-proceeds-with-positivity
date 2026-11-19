<?php

use App\Ai\Agents\ChatAgent;
use App\Ai\Agents\QuoteAgent;
use App\Models\AiModel;
use App\Models\AiProviderCredential;
use App\Models\AiRouteStep;
use App\Models\AiSetting;
use App\Models\User;
use App\Services\AiFailureClassifier;
use App\Services\QuoteRouter;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response as ClientResponse;

function aiCredential(string $driver = 'openai', string $label = 'OpenAI A', int $priority = 1, bool $enabled = true): AiProviderCredential
{
    return AiProviderCredential::query()->create([
        'driver' => $driver,
        'label' => $label,
        'priority' => $priority,
        'is_enabled' => $enabled,
        'payload' => ['key' => 'secret-'.$label],
    ]);
}

function aiModel(string $driver = 'openai', string $name = 'gpt-test', bool $enabled = true): AiModel
{
    return AiModel::query()->create([
        'driver' => $driver,
        'name' => $name,
        'is_enabled' => $enabled,
        'max_request_cents' => 5,
    ]);
}

test('a provider can be edited without losing its key and a new key replaces it', function () {
    $admin = User::factory()->administrator()->create();
    $credential = aiCredential();

    $this->actingAs($admin)->put(route('admin.ai.providers.update', $credential), [
        'label' => 'Renamed',
        'priority' => 4,
        'is_enabled' => false,
    ])->assertRedirect();

    $credential->refresh();
    expect($credential->label)->toBe('Renamed')
        ->and($credential->priority)->toBe(4)
        ->and($credential->is_enabled)->toBeFalse()
        ->and($credential->payload['key'])->toBe('secret-OpenAI A');

    $this->actingAs($admin)->put(route('admin.ai.providers.update', $credential), [
        'label' => 'Renamed',
        'priority' => 4,
        'is_enabled' => true,
        'key' => 'rotated-key',
    ])->assertRedirect();

    expect($credential->refresh()->payload['key'])->toBe('rotated-key');
});

test('deleting a provider soft deletes it and removes its route steps', function () {
    $admin = User::factory()->administrator()->create();
    $credential = aiCredential();
    $model = aiModel();
    AiRouteStep::query()->create([
        'ai_provider_credential_id' => $credential->id,
        'ai_model_id' => $model->id,
        'position' => 1,
    ]);

    $this->actingAs($admin)->delete(route('admin.ai.providers.destroy', $credential))->assertRedirect();

    expect(AiProviderCredential::query()->count())->toBe(0)
        ->and(AiRouteStep::query()->count())->toBe(0);
});

test('the providers page shows readable driver names and only a key hint', function () {
    $admin = User::factory()->administrator()->create();
    aiCredential('anthropic', 'Claude');

    $this->actingAs($admin)->get(route('admin.ai.providers'))
        ->assertInertia(fn ($page) => $page
            ->where('credentials.0.driver_label', 'Anthropic (Claude)')
            ->where('credentials.0.key_hint', '••••aude')
            ->where('credentials.0.enabled_models', 0)
            ->where('drivers.0.label', 'OpenAI (GPT)'));
});

test('a model can be edited, disabled and deleted and duplicates are rejected', function () {
    $admin = User::factory()->administrator()->create();
    $model = aiModel();
    aiModel('openai', 'gpt-other');

    $this->actingAs($admin)->put(route('admin.ai.models.update', $model), [
        'name' => 'gpt-other',
        'max_request_cents' => 9,
        'is_enabled' => true,
    ])->assertSessionHasErrors('name');

    $this->actingAs($admin)->put(route('admin.ai.models.update', $model), [
        'name' => 'gpt-renamed',
        'max_request_cents' => 9,
        'is_enabled' => false,
    ])->assertRedirect();

    expect($model->refresh()->name)->toBe('gpt-renamed')
        ->and($model->is_enabled)->toBeFalse()
        ->and($model->max_request_cents)->toBe(9);

    $this->actingAs($admin)->delete(route('admin.ai.models.destroy', $model))->assertRedirect();
    expect(AiModel::query()->count())->toBe(1);
});

test('manual routing follows the saved step order and skips unusable steps', function () {
    $admin = User::factory()->administrator()->create();
    $first = aiCredential('openai', 'First', 1);
    $second = aiCredential('anthropic', 'Second', 2);
    $disabled = aiCredential('groq', 'Off', 3, false);
    $firstModel = aiModel('openai', 'gpt-a');
    $secondModel = aiModel('anthropic', 'claude-a');
    $disabledModel = aiModel('groq', 'llama-a');

    $this->actingAs($admin)->put(route('admin.ai.routing.mode'), ['routing_mode' => 'manual'])->assertRedirect();

    foreach ([[$second, $secondModel], [$first, $firstModel]] as [$credential, $model]) {
        $this->actingAs($admin)->post(route('admin.ai.routing.steps.store'), [
            'ai_provider_credential_id' => $credential->id,
            'ai_model_id' => $model->id,
        ])->assertRedirect();
    }

    AiRouteStep::query()->create([
        'ai_provider_credential_id' => $disabled->id,
        'ai_model_id' => $disabledModel->id,
        'position' => 3,
    ]);

    expect(collect(app(QuoteRouter::class)->simulate())->pluck('label')->all())->toBe(['Second', 'First']);

    $last = AiRouteStep::query()->where('ai_provider_credential_id', $first->id)->first();
    $this->actingAs($admin)->post(route('admin.ai.routing.steps.move', $last), ['direction' => 'up'])->assertRedirect();

    expect(collect(app(QuoteRouter::class)->simulate())->pluck('label')->all())->toBe(['First', 'Second']);
});

test('a route step must pair a provider with a model of the same driver', function () {
    $admin = User::factory()->administrator()->create();
    $credential = aiCredential('openai');
    $model = aiModel('anthropic', 'claude-a');

    $this->actingAs($admin)->post(route('admin.ai.routing.steps.store'), [
        'ai_provider_credential_id' => $credential->id,
        'ai_model_id' => $model->id,
    ])->assertSessionHasErrors('ai_model_id');

    expect(AiRouteStep::query()->count())->toBe(0);
});

test('priority routing uses credential priority with the first enabled model of the driver', function () {
    aiCredential('anthropic', 'Later', 5);
    aiCredential('openai', 'Sooner', 1);
    aiModel('openai', 'gpt-x', false);
    aiModel('openai', 'gpt-y');
    aiModel('anthropic', 'claude-x');

    $route = app(QuoteRouter::class)->simulate();

    expect(collect($route)->pluck('label')->all())->toBe(['Sooner', 'Later'])
        ->and($route[0]['model'])->toBe('gpt-y');
});

test('readiness lists exactly what blocks generation', function () {
    $checks = collect(app(QuoteRouter::class)->readiness())->pluck('ok', 'key')->all();
    expect($checks)->toBe(['generation' => false, 'budget' => false, 'provider' => false, 'model' => false]);

    aiCredential();
    aiModel();
    AiSetting::current()->update(['generation_enabled' => true, 'daily_budget_cents' => 100]);

    expect(collect(app(QuoteRouter::class)->readiness())->every(fn (array $check): bool => $check['ok']))->toBeTrue();
});

test('connection test flashes a result toast and marks the model verified', function () {
    QuoteAgent::fake([['text' => 'Test phrase', 'category' => 'Hope']]);
    $admin = User::factory()->administrator()->create();
    $credential = aiCredential();
    $model = aiModel();

    $this->actingAs($admin)->post(route('admin.ai.providers.test', $credential))->assertRedirect();

    expect($credential->refresh()->health)->toBe('healthy')
        ->and($model->refresh()->structured_output_tested)->toBeTrue();
});

test('connection test without a model explains what is missing', function () {
    $credential = aiCredential();
    $admin = User::factory()->administrator()->create();

    $result = app(QuoteRouter::class)->testConnection($admin, $credential);

    expect($result['ok'])->toBeFalse()->and($result['category'])->toBe('no_model');
});

test('http auth failures are classified as invalid credentials and may fail over', function () {
    $classifier = app(AiFailureClassifier::class);
    $response = new ClientResponse(new PsrResponse(401));

    expect($classifier->classify(new RequestException($response)))->toBe('invalid_credentials')
        ->and($classifier->mayContinue('invalid_credentials'))->toBeTrue()
        ->and($classifier->classify(new RuntimeException('boom')))->toBe('unknown');
});

test('test chat in auto mode answers through the live route', function () {
    ChatAgent::fake(['Hello from the model']);
    $admin = User::factory()->administrator()->create();
    aiCredential();
    aiModel();

    $this->actingAs($admin)->postJson(route('admin.ai.playground.send'), [
        'message' => 'Hi',
        'history' => [['role' => 'user', 'content' => 'Earlier'], ['role' => 'assistant', 'content' => 'Reply']],
    ])
        ->assertOk()
        ->assertJsonPath('reply', 'Hello from the model')
        ->assertJsonPath('provider', 'OpenAI A')
        ->assertJsonPath('model', 'gpt-test');
});

test('test chat can target a chosen provider and model', function () {
    ChatAgent::fake(['From Claude']);
    $admin = User::factory()->administrator()->create();
    aiCredential('openai', 'OpenAI A', 1);
    $claude = aiCredential('anthropic', 'Claude', 2);
    aiModel('openai', 'gpt-test');
    $claudeModel = aiModel('anthropic', 'claude-test');

    $this->actingAs($admin)->postJson(route('admin.ai.playground.send'), [
        'message' => 'Hi',
        'credential_id' => $claude->id,
        'model_id' => $claudeModel->id,
    ])
        ->assertOk()
        ->assertJsonPath('provider', 'Claude')
        ->assertJsonPath('model', 'claude-test');
});

test('test chat rejects a model from another provider and an empty route', function () {
    $admin = User::factory()->administrator()->create();
    $openai = aiCredential('openai');
    aiModel('openai', 'gpt-test');
    $claudeModel = aiModel('anthropic', 'claude-test');

    $this->actingAs($admin)->postJson(route('admin.ai.playground.send'), [
        'message' => 'Hi',
        'credential_id' => $openai->id,
        'model_id' => $claudeModel->id,
    ])->assertStatus(422);

    AiModel::query()->delete();

    $this->actingAs($admin)->postJson(route('admin.ai.playground.send'), ['message' => 'Hi'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('message');
});

test('only administrators can reach the test chat and ai management', function () {
    $user = User::factory()->create();
    $credential = aiCredential();

    $this->actingAs($user)->get(route('admin.ai.playground'))->assertForbidden();
    $this->actingAs($user)->postJson(route('admin.ai.playground.send'), ['message' => 'Hi'])->assertForbidden();
    $this->actingAs($user)->delete(route('admin.ai.providers.destroy', $credential))->assertForbidden();
});

test('the test chat page renders for an administrator', function () {
    $admin = User::factory()->administrator()->create();
    aiCredential();
    aiModel();

    $this->actingAs($admin)->get(route('admin.ai.playground'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/ai/playground')
            ->has('credentials', 1)
            ->has('route', 1));
});

test('resetting ai settings restores the defaults and keeps providers and models', function () {
    $admin = User::factory()->administrator()->create();
    aiCredential();
    aiModel();
    AiSetting::current()->update([
        'generation_enabled' => true,
        'daily_budget_cents' => 900,
        'timeout_seconds' => 55,
        'max_attempts' => 5,
        'default_cooldown_seconds' => 999,
        'routing_mode' => 'manual',
    ]);

    $this->actingAs($admin)->post(route('admin.ai.settings.reset'))->assertRedirect();

    $settings = AiSetting::current()->refresh();
    expect($settings->only(array_keys(AiSetting::defaults())))->toEqual(AiSetting::defaults())
        ->and(AiProviderCredential::query()->count())->toBe(1)
        ->and(AiModel::query()->count())->toBe(1);

    $this->actingAs(User::factory()->create())->post(route('admin.ai.settings.reset'))->assertForbidden();
});

test('a model can be tested directly through its first enabled provider', function () {
    QuoteAgent::fake([['text' => 'Test phrase', 'category' => 'Hope']]);
    $admin = User::factory()->administrator()->create();
    aiCredential('openai', 'OpenAI A', 1);
    $model = aiModel('openai', 'gpt-test');

    $this->actingAs($admin)->post(route('admin.ai.models.test', $model))->assertRedirect();

    expect($model->refresh()->structured_output_tested)->toBeTrue();
});

test('testing a model with no enabled provider of its driver explains the problem and does not crash', function () {
    $admin = User::factory()->administrator()->create();
    $model = aiModel('anthropic', 'claude-test');

    $this->actingAs($admin)->post(route('admin.ai.models.test', $model))
        ->assertRedirect();

    expect($model->refresh()->structured_output_tested)->toBeFalse();
});

test('only administrators can test a model', function () {
    $model = aiModel();

    $this->actingAs(User::factory()->create())->post(route('admin.ai.models.test', $model))->assertForbidden();
});
