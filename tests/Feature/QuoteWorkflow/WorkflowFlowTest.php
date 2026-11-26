<?php

use App\Enums\ActivityStatus;
use App\Enums\GatePurpose;
use App\Enums\QuoteStatus;
use App\Enums\RewardStatus;
use App\Enums\WorkflowState;
use App\Models\AdActivity;
use App\Models\AdProvider;
use App\Models\AdRewardRule;
use App\Models\AdSetting;
use App\Models\AiGenerationLog;
use App\Models\GoogleAdSetting;
use App\Models\LedgerEntry;
use App\Models\PointBalance;
use App\Models\Quote;
use App\Models\QuoteCategory;
use App\Models\QuoteWorkflow;
use App\Models\RewardedAdAttempt;
use App\Models\User;
use App\Models\WorkflowEvent;
use App\Services\Ads\AdGate;
use App\Services\QuoteModeration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\Support\TapResearchFixtures;
use Tests\Support\WorkflowFixtures;

beforeEach(function () {
    Http::preventStrayRequests();
    Cache::flush();

    $this->user = User::factory()->create();
    $this->category = QuoteCategory::factory()->create();
});

function startRun(): QuoteWorkflow
{
    $id = test()->actingAs(test()->user)
        ->postJson(route('studio.workflow.store'), ['quote_category_id' => test()->category->id])
        ->assertOk()
        ->json('workflow.id');

    return QuoteWorkflow::query()->findOrFail($id);
}

function issueAd(QuoteWorkflow $run): string
{
    return (string) test()->actingAs(test()->user)
        ->postJson(route('studio.workflow.ads.issue', $run))
        ->assertOk()
        ->json('ad.attempt_id');
}

function adEvent(QuoteWorkflow $run, string $attempt, string $event): TestResponse
{
    return test()->actingAs(test()->user)
        ->postJson(route('studio.workflow.ads.event', [$run, $attempt]), ['event' => $event]);
}

/** Watch the current step's ad to the end, the way the page reports it. */
function watchAd(QuoteWorkflow $run): void
{
    $attempt = issueAd($run);
    adEvent($run, $attempt, 'ready')->assertOk();
    adEvent($run, $attempt, 'shown')->assertOk();
    test()->travel(6)->seconds();
    adEvent($run, $attempt, 'granted')->assertOk();
    adEvent($run, $attempt, 'closed')->assertOk();
}

/** Drive the current ad step into one of the ways an ad can fail. */
function failAd(QuoteWorkflow $run, string $failure): TestResponse
{
    $attempt = issueAd($run);

    if ($failure === 'dismissed') {
        adEvent($run, $attempt, 'ready')->assertOk();
        adEvent($run, $attempt, 'shown')->assertOk();

        return adEvent($run, $attempt, 'closed')->assertOk();
    }

    return adEvent($run, $attempt, $failure)->assertOk();
}

function generateQuote(QuoteWorkflow $run): TestResponse
{
    return test()->actingAs(test()->user)->postJson(route('studio.workflow.generate', $run));
}

function surveyList(QuoteWorkflow $run): TestResponse
{
    return test()->actingAs(test()->user)->postJson(route('studio.workflow.offers', $run))->assertOk();
}

/*
|--------------------------------------------------------------------------
| The full flow
|--------------------------------------------------------------------------
*/

test('ad, survey, quote, second ad and publishing run end to end', function () {
    WorkflowFixtures::aiRoute();
    WorkflowFixtures::rewardedAds();
    AdProvider::factory()->create();
    AdRewardRule::query()->create(['provider_key' => null, 'activity_type' => 'survey', 'points' => 25, 'is_enabled' => true]);
    Http::fake(['*/campaigns*' => Http::response(TapResearchFixtures::campaigns([TapResearchFixtures::survey('555001', 5)]))]);

    $run = startRun();
    expect($run->state)->toBe(WorkflowState::AdBeforeSurvey);

    watchAd($run);
    expect($run->refresh()->state)->toBe(WorkflowState::Survey)
        ->and($run->ad1_result)->toBe('completed');

    $list = surveyList($run)->assertJsonPath('provider', 'tapresearch')->assertJsonCount(1, 'offers')->json();
    $activityId = $this->actingAs($this->user)->postJson(route('surveys.start'), [
        'request_id' => $list['request_id'],
        'offer_identifier' => '555001',
        'purpose' => 'quote_generation',
    ])->assertOk()->json('activity_id');

    $activity = AdActivity::query()->findOrFail($activityId);
    expect($activity->quote_workflow_id)->toBe($run->id);

    // Nothing can be generated until the provider confirms.
    generateQuote($run)->assertUnprocessable();

    $this->get(TapResearchFixtures::postbackUrl(['uid' => $activity->provider_user_id, 'activity_id' => $activityId]))->assertOk();

    $this->actingAs($this->user)->getJson(route('studio.workflow.show', $run))
        ->assertJsonPath('workflow.state', 'generate');

    generateQuote($run)
        ->assertOk()
        ->assertJsonPath('workflow.state', 'ad_before_publish')
        ->assertJsonPath('workflow.quote', null)
        ->assertDontSee('Hope finds a way through.');

    $quote = Quote::query()->sole();
    expect($quote->status)->toBe(QuoteStatus::AwaitingUnlock)
        ->and($activity->refresh()->consumed_quote_id)->toBe($quote->id);

    // The locked quote is not readable anywhere.
    $this->actingAs($this->user)->get(route('studio'))->assertDontSee('Hope finds a way through.');
    $this->actingAs($this->user)->get(route('earnings'))
        ->assertDontSee('Hope finds a way through.')
        ->assertInertia(fn ($page) => $page->where('activities.data.0.used_for', null)->where('activities.data.0.unspent', false));
    $this->get(route('quotes.public', $quote))->assertNotFound();
    $this->actingAs($this->user)->post(route('studio.quotes.publish', $quote))->assertForbidden();

    watchAd($run);

    expect($run->refresh()->state)->toBe(WorkflowState::Completed)
        ->and($run->ad2_result)->toBe('completed')
        ->and($run->active_user_id)->toBeNull()
        ->and($quote->refresh()->status)->toBe(QuoteStatus::Published);

    $this->actingAs($this->user)->getJson(route('studio.workflow.show', $run))
        ->assertJsonPath('workflow.quote.text', 'Hope finds a way through.')
        ->assertJsonPath('workflow.quote.status', 'published');

    // Points are off by default, and the ads never touch money.
    expect($activity->refresh()->status)->toBe(ActivityStatus::Approved)
        ->and($activity->reward_status)->toBe(RewardStatus::NotEligible)
        ->and($activity->reward_note)->toBe('quote_workflow_no_points')
        ->and(PointBalance::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->count())->toBe(0)
        ->and(WorkflowEvent::query()->where('event', 'provider_approved')->count())->toBe(1)
        ->and(WorkflowEvent::query()->where('event', 'published')->count())->toBe(1);
});

test('manual publishing unlocks the quote and leaves the publish button to the user', function () {
    WorkflowFixtures::aiRoute();
    WorkflowFixtures::rules(['survey_enabled' => false, 'publish_mode' => 'manual']);

    $run = startRun();
    generateQuote($run)->assertOk()->assertJsonPath('workflow.state', 'completed');

    $quote = Quote::query()->sole();
    expect($quote->status)->toBe(QuoteStatus::Approved)->and($quote->published_at)->toBeNull();

    $this->actingAs($this->user)->post(route('studio.quotes.publish', $quote))->assertRedirect();
    expect($quote->refresh()->status)->toBe(QuoteStatus::Published);
});

test('with every step switched off the run goes straight to generating', function () {
    WorkflowFixtures::aiRoute();
    WorkflowFixtures::rewardedAds();
    WorkflowFixtures::rules(['ad1_enabled' => false, 'ad2_enabled' => false, 'survey_enabled' => false]);

    $run = startRun();
    expect($run->state)->toBe(WorkflowState::Generate)
        ->and($run->ad1_result)->toBe('disabled')
        ->and($run->survey_result)->toBe('disabled');

    generateQuote($run)->assertOk()->assertJsonPath('workflow.state', 'completed');
    expect(Quote::query()->sole()->status)->toBe(QuoteStatus::Published);
});

test('turning rewarded ads off globally skips both ad steps', function () {
    $run = startRun();

    expect($run->state)->toBe(WorkflowState::Survey)->and($run->ad1_result)->toBe('disabled');
});

test('a moderation match keeps an automatically published quote in review', function () {
    WorkflowFixtures::aiRoute();
    WorkflowFixtures::rules(['survey_enabled' => false]);
    config(['quotes.denylist' => ['finds a way']]);

    $run = startRun();
    generateQuote($run)->assertOk()->assertJsonPath('workflow.quote.status', 'pending_review');
});

/*
|--------------------------------------------------------------------------
| Fallback rules
|--------------------------------------------------------------------------
*/

dataset('ad failures', [
    'unavailable' => ['unavailable', 'unavailable'],
    'error' => ['error', 'error'],
    'dismissed' => ['dismissed', 'dismissed'],
]);

test('the first ad step follows its own continue or block rule', function (string $failure, string $rule) {
    WorkflowFixtures::rewardedAds();

    WorkflowFixtures::rules(["ad1_on_{$rule}" => 'block']);
    $run = startRun();
    expect(failAd($run, $failure)->json('message'))->not->toBeNull()
        ->and($run->refresh()->state)->toBe(WorkflowState::AdBeforeSurvey);

    $this->actingAs($this->user)->postJson(route('studio.workflow.cancel', $run))->assertOk();

    WorkflowFixtures::rules(["ad1_on_{$rule}" => 'continue']);
    $run = startRun();
    failAd($run, $failure)->assertJsonPath('message', null);

    expect($run->refresh()->state)->toBe(WorkflowState::Survey)
        ->and($run->ad1_result)->toBe("skipped_{$rule}");
})->with('ad failures');

test('the second ad step has independent rules', function (string $failure, string $rule) {
    WorkflowFixtures::aiRoute();
    WorkflowFixtures::rewardedAds();
    // The first step is lenient and the second strict, so a shared rule would show up here.
    WorkflowFixtures::rules(['ad1_enabled' => false, 'survey_enabled' => false, "ad1_on_{$rule}" => 'continue', "ad2_on_{$rule}" => 'block']);

    $run = startRun();
    generateQuote($run)->assertOk();
    expect(failAd($run, $failure)->json('message'))->not->toBeNull()
        ->and($run->refresh()->state)->toBe(WorkflowState::AdBeforePublish)
        ->and(Quote::query()->sole()->status)->toBe(QuoteStatus::AwaitingUnlock);

    // Settings are read when a run starts, so this run keeps its rule; the administrator can still release it.
    WorkflowFixtures::rules(["ad2_on_{$rule}" => 'continue']);
    expect(failAd($run, $failure)->json('message'))->not->toBeNull();

    app(QuoteModeration::class)->approve(Quote::query()->sole());
    $this->actingAs($this->user)->getJson(route('studio.workflow.show', $run))->assertJsonPath('workflow.state', 'completed');

    // A new day: on SQLite the existing AI budget counter cannot be reused for a second generation the same day.
    $this->travel(1)->days();
    $next = startRun();
    generateQuote($next)->assertOk();
    failAd($next, $failure)->assertJsonPath('workflow.state', 'completed');

    expect($next->refresh()->ad2_result)->toBe("skipped_{$rule}");
})->with('ad failures');

test('by default a missing first ad is skipped and a missing second ad keeps the quote locked', function () {
    WorkflowFixtures::aiRoute();
    WorkflowFixtures::rewardedAds();
    WorkflowFixtures::rules(['survey_enabled' => false]);

    $run = startRun();
    failAd($run, 'unavailable')->assertJsonPath('workflow.state', 'generate');
    generateQuote($run)->assertOk();
    failAd($run, 'unavailable')->assertJsonPath('workflow.state', 'ad_before_publish');
});

test('with no ad unit configured the unavailable rule decides, without using up the daily limit', function () {
    GoogleAdSetting::current()->update(['rewarded_enabled' => true]);

    WorkflowFixtures::rules(['ad1_on_unavailable' => 'block']);
    $run = startRun();
    $response = $this->actingAs($this->user)->postJson(route('studio.workflow.ads.issue', $run))->assertOk();
    expect($response->json('ad'))->toBeNull()
        ->and($response->json('message'))->not->toBeNull()
        ->and($run->refresh()->state)->toBe(WorkflowState::AdBeforeSurvey);

    $this->actingAs($this->user)->postJson(route('studio.workflow.cancel', $run))->assertOk();
    WorkflowFixtures::rules(['ad1_on_unavailable' => 'continue']);
    $run = startRun();
    $this->actingAs($this->user)->postJson(route('studio.workflow.ads.issue', $run))
        ->assertJsonPath('ad', null)
        ->assertJsonPath('workflow.state', 'survey');

    expect(WorkflowEvent::query()->where('event', 'fallback_skip')->count())->toBe(0);
});

test('test mode serves Googles sample unit even with no placement', function () {
    GoogleAdSetting::current()->update(['rewarded_enabled' => true, 'test_mode' => true]);

    $this->actingAs($this->user)->postJson(route('studio.workflow.ads.issue', startRun()))
        ->assertJsonPath('ad.ad_unit_path', GoogleAdSetting::TEST_REWARDED_UNIT);
});

test('a survey list that is empty or broken follows the matching rule', function (array $fake, string $rule, string $result) {
    AdProvider::factory()->create();
    Http::fake(['*/campaigns*' => Http::response(...$fake)]);

    WorkflowFixtures::rules([$rule => 'block']);
    $run = startRun();
    $response = surveyList($run)->assertJsonCount(0, 'offers');
    expect($response->json('message'))->not->toBeNull()
        ->and($run->refresh()->state)->toBe(WorkflowState::Survey);

    $this->actingAs($this->user)->postJson(route('studio.workflow.cancel', $run))->assertOk();

    WorkflowFixtures::rules([$rule => 'continue']);
    $run = startRun();
    surveyList($run)->assertJsonPath('workflow.state', 'generate');

    expect($run->refresh()->survey_result)->toBe($result);
})->with([
    'nothing to show' => [[['surveys' => [], 'player_eligibility' => ['is_eligible' => true]]], 'survey_on_no_surveys', 'skipped_no_surveys'],
    'user not eligible' => [[['surveys' => [], 'player_eligibility' => ['is_eligible' => false, 'reason' => 4]]], 'survey_on_no_surveys', 'skipped_no_surveys'],
    'provider error' => [[[], 503], 'survey_on_api_error', 'skipped_api_error'],
]);

test('having no usable provider counts as an API failure, not as no surveys', function () {
    WorkflowFixtures::rules(['survey_on_no_surveys' => 'continue', 'survey_on_api_error' => 'block']);

    $run = startRun();
    surveyList($run);
    expect($run->refresh()->state)->toBe(WorkflowState::Survey);

    $this->actingAs($this->user)->postJson(route('studio.workflow.cancel', $run))->assertOk();
    WorkflowFixtures::rules(['survey_on_api_error' => 'continue']);
    $run = startRun();
    surveyList($run)->assertJsonPath('workflow.state', 'generate');
});

test('an empty list does not skip the step while a survey is still waiting for confirmation', function () {
    AdProvider::factory()->create();
    Http::fake(['*/campaigns*' => Http::response(TapResearchFixtures::campaigns([]))]);
    WorkflowFixtures::rules(['survey_on_no_surveys' => 'continue']);
    $run = startRun();
    AdActivity::factory()->for($this->user)->create(['purpose' => GatePurpose::QuoteGeneration, 'quote_workflow_id' => $run->id]);

    surveyList($run)->assertJsonPath('workflow.state', 'survey')->assertJsonPath('workflow.survey.pending', true);
});

test('a disqualified survey is retried or skipped as configured', function () {
    WorkflowFixtures::rules(['survey_on_disqualified' => 'block']);
    $run = startRun();
    AdActivity::factory()->for($this->user)->create([
        'purpose' => GatePurpose::QuoteGeneration, 'quote_workflow_id' => $run->id, 'status' => ActivityStatus::Rejected,
    ]);

    $this->actingAs($this->user)->getJson(route('studio.workflow.show', $run))
        ->assertJsonPath('workflow.state', 'survey')
        ->assertJsonPath('workflow.notice', 'That survey was not completed. Choose another one to continue.');

    $this->actingAs($this->user)->postJson(route('studio.workflow.cancel', $run))->assertOk();
    WorkflowFixtures::rules(['survey_on_disqualified' => 'continue']);
    $run = startRun();
    AdActivity::factory()->for($this->user)->create([
        'purpose' => GatePurpose::QuoteGeneration, 'quote_workflow_id' => $run->id, 'status' => ActivityStatus::Rejected,
    ]);

    $this->actingAs($this->user)->getJson(route('studio.workflow.show', $run))->assertJsonPath('workflow.state', 'generate');
    expect($run->refresh()->survey_result)->toBe('skipped_disqualified');
});

test('the daily limit stops further skipping and resets the next day', function () {
    WorkflowFixtures::rewardedAds();
    WorkflowFixtures::rules(['ad1_on_error' => 'continue', 'max_fallback_skips_per_day' => 2]);

    foreach (range(1, 2) as $_) {
        $run = startRun();
        failAd($run, 'error')->assertJsonPath('workflow.state', 'survey');
        $this->actingAs($this->user)->postJson(route('studio.workflow.cancel', $run))->assertOk();
    }

    $run = startRun();
    expect(failAd($run, 'error')->json('message'))->toContain('limit')
        ->and($run->refresh()->state)->toBe(WorkflowState::AdBeforeSurvey);

    $this->travel(1)->days();
    $this->actingAs($this->user)->postJson(route('studio.workflow.cancel', $run));
    failAd(startRun(), 'error')->assertJsonPath('workflow.state', 'survey');
});

/*
|--------------------------------------------------------------------------
| What a hostile page cannot do
|--------------------------------------------------------------------------
*/

test('a reward cannot be claimed out of order, too early or twice', function () {
    WorkflowFixtures::rewardedAds();
    $run = startRun();
    $attempt = issueAd($run);

    adEvent($run, $attempt, 'granted')->assertUnprocessable();
    adEvent($run, $attempt, 'shown')->assertUnprocessable();
    adEvent($run, $attempt, 'ready')->assertOk();
    adEvent($run, $attempt, 'granted')->assertUnprocessable();
    adEvent($run, $attempt, 'shown')->assertOk();
    adEvent($run, $attempt, 'granted')->assertUnprocessable();

    expect($run->refresh()->state)->toBe(WorkflowState::AdBeforeSurvey);

    $this->travel(6)->seconds();
    adEvent($run, $attempt, 'granted')->assertOk()->assertJsonPath('workflow.state', 'survey');
    adEvent($run, $attempt, 'granted')->assertUnprocessable();
    adEvent($run, $attempt, 'made_up')->assertUnprocessable();
});

test('only the newest attempt counts and an old session expires', function () {
    WorkflowFixtures::rewardedAds();
    $run = startRun();
    $first = issueAd($run);
    $second = issueAd($run);

    adEvent($run, $first, 'ready')->assertUnprocessable();

    $this->travel(31)->minutes();
    adEvent($run, $second, 'ready')->assertUnprocessable();
    expect(RewardedAdAttempt::query()->find($first)->status->value)->toBe('superseded');
});

test('a user cannot see or drive another users run', function () {
    WorkflowFixtures::rewardedAds();
    $run = startRun();
    $attempt = issueAd($run);
    $intruder = User::factory()->create();

    $this->actingAs($intruder);
    $this->getJson(route('studio.workflow.show', $run))->assertNotFound();
    $this->postJson(route('studio.workflow.ads.issue', $run))->assertNotFound();
    $this->postJson(route('studio.workflow.ads.event', [$run, $attempt]), ['event' => 'ready'])->assertNotFound();
    $this->postJson(route('studio.workflow.generate', $run))->assertNotFound();
    $this->postJson(route('studio.workflow.cancel', $run))->assertNotFound();

    // Their own run cannot borrow an attempt from someone else's.
    $own = $this->postJson(route('studio.workflow.store'), ['quote_category_id' => $this->category->id])->json('workflow.id');
    $this->postJson(route('studio.workflow.ads.event', [$own, $attempt]), ['event' => 'ready'])->assertNotFound();
});

test('steps cannot be jumped', function () {
    WorkflowFixtures::aiRoute();
    WorkflowFixtures::rewardedAds();
    $run = startRun();

    generateQuote($run)->assertUnprocessable();
    $this->actingAs($this->user)->postJson(route('studio.workflow.offers', $run))->assertJsonPath('workflow.state', 'ad_before_survey');
    $this->actingAs($this->user)->post(route('studio.generate'), ['quote_category_id' => $this->category->id])
        ->assertSessionHasErrors('workflow');

    expect(Quote::query()->count())->toBe(0)->and(AiGenerationLog::query()->count())->toBe(0);
});

test('a quote is generated only once per run', function () {
    WorkflowFixtures::aiRoute();
    WorkflowFixtures::rewardedAds();
    WorkflowFixtures::rules(['ad1_enabled' => false, 'survey_enabled' => false]);

    $run = startRun();
    generateQuote($run)->assertOk();
    generateQuote($run)->assertUnprocessable();

    expect(Quote::query()->count())->toBe(1);
});

test('a failed generation gives the survey back and can be retried', function () {
    // No AI route is configured yet.
    $run = startRun();
    $unlock = WorkflowFixtures::approvedSurvey($this->user, $run);

    generateQuote($run)->assertUnprocessable()->assertJsonValidationErrors('generation');

    expect($run->refresh()->state)->toBe(WorkflowState::Generate)
        ->and($unlock->refresh()->consumed_at)->toBeNull()
        ->and(app(AdGate::class)->available($this->user, GatePurpose::QuoteGeneration))->toBe(1);

    WorkflowFixtures::aiRoute();
    generateQuote($run)->assertOk();
    expect($unlock->refresh()->consumed_quote_id)->not->toBeNull();
});

test('a survey started for a quote needs a run on the survey step and an allowed provider', function () {
    AdProvider::factory()->create();
    Http::fake(['*/campaigns*' => Http::response(TapResearchFixtures::campaigns([TapResearchFixtures::survey('555001')]))]);
    $list = $this->actingAs($this->user)->postJson(route('surveys.offers'))->json();
    $body = ['request_id' => $list['request_id'], 'offer_identifier' => '555001', 'purpose' => 'quote_generation'];

    $this->actingAs($this->user)->postJson(route('surveys.start'), $body)->assertStatus(409);

    WorkflowFixtures::rules(['survey_provider_keys' => ['theoremreach']]);
    startRun();
    $this->actingAs($this->user)->postJson(route('surveys.start'), $body)->assertForbidden();

    expect(AdActivity::query()->count())->toBe(0);
});

test('switching the flow off does not change the rules of a run in progress', function () {
    AdProvider::factory()->create();
    Http::fake(['*/campaigns*' => Http::response(TapResearchFixtures::campaigns([TapResearchFixtures::survey('555001')]))]);
    $run = startRun();
    $list = surveyList($run)->json();
    WorkflowFixtures::rules(['enabled' => false]);

    $id = $this->actingAs($this->user)->postJson(route('surveys.start'), [
        'request_id' => $list['request_id'], 'offer_identifier' => '555001', 'purpose' => 'quote_generation',
    ])->assertOk()->json('activity_id');

    expect(AdActivity::query()->findOrFail($id)->quote_workflow_id)->toBe($run->id);
});

test('only providers chosen for the flow are asked', function () {
    AdProvider::factory()->create();
    Http::fake();
    WorkflowFixtures::rules(['survey_provider_keys' => ['theoremreach']]);

    surveyList(startRun())->assertJsonCount(0, 'offers');

    Http::assertNothingSent();
});

/*
|--------------------------------------------------------------------------
| Points, runs and housekeeping
|--------------------------------------------------------------------------
*/

test('points and revenue share follow the rule of the run', function (bool $award, int $points, int $ledger) {
    AdProvider::factory()->create(['config' => ['reward_cashout_permission' => true]]);
    AdRewardRule::query()->create(['provider_key' => null, 'activity_type' => 'survey', 'points' => 25, 'is_enabled' => true]);
    AdSetting::current()->update(['user_revenue_share_enabled' => true]);
    WorkflowFixtures::rules(['award_survey_points' => $award]);
    $run = startRun();
    $activity = AdActivity::factory()->for($this->user)->create([
        'purpose' => GatePurpose::QuoteGeneration, 'quote_workflow_id' => $run->id, 'provider_user_id' => 'uworkflow',
    ]);

    $this->get(TapResearchFixtures::postbackUrl(['uid' => 'uworkflow', 'activity_id' => $activity->id, 'revenue' => '3.00']))->assertOk();

    expect($activity->refresh()->awarded_points)->toBe($points)
        ->and(LedgerEntry::query()->count())->toBe($ledger)
        ->and(app(AdGate::class)->available($this->user, GatePurpose::QuoteGeneration))->toBe(1);
})->with([
    'switched off' => [false, 0, 0],
    'switched on' => [true, 25, 1],
]);

test('a user has one run at a time and it resumes', function () {
    $first = startRun();
    $second = startRun();

    expect($second->id)->toBe($first->id)->and(QuoteWorkflow::query()->count())->toBe(1);

    $this->actingAs($this->user)->get(route('studio'))
        ->assertInertia(fn ($page) => $page->where('workflow.active.id', $first->id));
});

test('an unspent survey from an earlier run satisfies the survey step', function () {
    $unlock = WorkflowFixtures::approvedSurvey($this->user);

    expect(startRun()->state)->toBe(WorkflowState::Generate)
        ->and($unlock->refresh()->consumed_at)->toBeNull();
});

test('an offerwall completion unlocks too', function () {
    WorkflowFixtures::approvedSurvey($this->user, null, ['activity_type' => 'offerwall', 'provider_key' => 'wannads']);

    expect(app(AdGate::class)->available($this->user, GatePurpose::QuoteGeneration))->toBe(1);
});

test('changing the settings does not change a run already in progress', function () {
    WorkflowFixtures::rewardedAds();
    $run = startRun();

    WorkflowFixtures::rules(['ad1_enabled' => false, 'survey_enabled' => false]);

    $this->actingAs($this->user)->getJson(route('studio.workflow.show', $run))
        ->assertJsonPath('workflow.state', 'ad_before_survey');
});

test('the flow cannot start while switched off, and one-step creation works instead', function () {
    WorkflowFixtures::aiRoute();
    WorkflowFixtures::rules(['enabled' => false]);

    $this->actingAs($this->user)->postJson(route('studio.workflow.store'), ['quote_category_id' => $this->category->id])
        ->assertUnprocessable();
    $this->actingAs($this->user)->post(route('studio.generate'), ['quote_category_id' => $this->category->id])
        ->assertSessionHasNoErrors();

    expect(Quote::query()->sole()->status)->toBe(QuoteStatus::Approved);
});

test('abandoned runs expire but a run holding a quote never does', function () {
    WorkflowFixtures::aiRoute();
    WorkflowFixtures::rewardedAds();
    WorkflowFixtures::rules(['ad1_enabled' => false, 'survey_enabled' => false]);
    $holding = startRun();
    generateQuote($holding)->assertOk();

    $other = User::factory()->create();
    $abandoned = $this->actingAs($other)->postJson(route('studio.workflow.store'), ['quote_category_id' => $this->category->id])->json('workflow.id');

    $this->travel(3)->days();
    $this->artisan('workflow:maintain')->assertSuccessful();

    expect(QuoteWorkflow::query()->find($abandoned)->state)->toBe(WorkflowState::Expired)
        ->and($holding->refresh()->state)->toBe(WorkflowState::AdBeforePublish);
    $this->actingAs($this->user)->postJson(route('studio.workflow.cancel', $holding))->assertUnprocessable();
});

test('old logs are pruned after the retention period', function () {
    startRun();
    expect(WorkflowEvent::query()->count())->toBeGreaterThan(0);

    $this->travel(100)->days();
    $this->artisan('workflow:maintain')->assertSuccessful();

    // Only the line written while expiring the old run remains.
    expect(WorkflowEvent::query()->where('event', 'started')->count())->toBe(0);
});
