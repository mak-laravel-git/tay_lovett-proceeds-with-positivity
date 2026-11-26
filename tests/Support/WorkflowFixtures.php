<?php

namespace Tests\Support;

use App\Ai\Agents\QuoteAgent;
use App\Enums\GatePurpose;
use App\Models\AdActivity;
use App\Models\AdPlacement;
use App\Models\AiModel;
use App\Models\AiProviderCredential;
use App\Models\AiSetting;
use App\Models\GoogleAdSetting;
use App\Models\QuoteWorkflow;
use App\Models\QuoteWorkflowSetting;
use App\Models\User;

/**
 * Setup shared by the quote workflow tests.
 */
final class WorkflowFixtures
{
    /**
     * A working AI route, so a failure can only come from the workflow itself.
     */
    public static function aiRoute(): void
    {
        QuoteAgent::fake([
            ['text' => 'Hope finds a way through.', 'category' => 'Hope'],
            ['text' => 'Courage is a quiet daily choice.', 'category' => 'Hope'],
            ['text' => 'Small steps still move you forward.', 'category' => 'Hope'],
        ]);

        AiSetting::current()->update(['generation_enabled' => true, 'daily_budget_cents' => 1000, 'max_attempts' => 2]);
        AiProviderCredential::query()->create([
            'driver' => 'openai', 'label' => 'OpenAI A', 'priority' => 1, 'is_enabled' => true, 'payload' => ['key' => 'fake-key'],
        ]);
        AiModel::query()->create(['driver' => 'openai', 'name' => 'gpt-test', 'is_enabled' => true, 'max_request_cents' => 25]);
    }

    /**
     * Rewarded ads switched on with one configured Ad Manager unit.
     */
    public static function rewardedAds(): AdPlacement
    {
        GoogleAdSetting::current()->update(['rewarded_enabled' => true]);

        return AdPlacement::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    public static function rules(array $rules): void
    {
        QuoteWorkflowSetting::current()->update($rules);
    }

    /**
     * A provider-confirmed survey that belongs to the run, as the callback pipeline would leave it.
     *
     * @param  array<string, mixed>  $state
     */
    public static function approvedSurvey(User $user, ?QuoteWorkflow $workflow = null, array $state = []): AdActivity
    {
        return AdActivity::factory()->for($user)->approved()->create([
            'purpose' => GatePurpose::QuoteGeneration,
            'quote_workflow_id' => $workflow?->id,
            ...$state,
        ]);
    }
}
