<?php

use App\Enums\AdLocation;
use App\Enums\QuoteStatus;
use App\Enums\WorkflowState;
use App\Models\AdPlacement;
use App\Models\AdProvider;
use App\Models\AdProviderCall;
use App\Models\GoogleAdSetting;
use App\Models\Quote;
use App\Models\QuoteCategory;
use App\Models\QuoteWorkflow;
use App\Models\QuoteWorkflowSetting;
use App\Models\User;
use App\Models\WorkflowEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\TapResearchFixtures;
use Tests\Support\WorkflowFixtures;

beforeEach(function () {
    Http::preventStrayRequests();
    Cache::flush();

    $this->admin = User::factory()->administrator()->create();
});

/**
 * @param  array<string, mixed>  $changes
 * @return array<string, mixed>
 */
function workflowForm(array $changes = []): array
{
    return [...QuoteWorkflowSetting::defaults(), ...$changes];
}

/**
 * @param  array<string, mixed>  $changes
 * @return array<string, mixed>
 */
function placementForm(array $changes = []): array
{
    return [
        'key' => 'quote-rewarded', 'name' => 'Quote rewarded', 'kind' => 'rewarded', 'network' => 'ad_manager',
        'location' => null, 'ad_unit_path' => '/1234567/rewarded', 'adsense_slot_id' => null, 'sizes' => null,
        'devices' => 'all', 'min_height' => 0, 'every_n' => null, 'load_timeout_seconds' => 10,
        'min_watch_seconds' => 5, 'is_enabled' => true, 'sort_order' => 10,
        ...$changes,
    ];
}

test('only administrators reach the workflow, ad and log pages', function () {
    $placement = AdPlacement::factory()->create();
    $user = User::factory()->create();
    $this->actingAs($user);

    foreach (['admin.workflow.settings', 'admin.workflow.runs', 'admin.google-ads.settings', 'admin.google-ads.placements', 'admin.logs.ads', 'admin.logs.activity'] as $route) {
        $this->get(route($route))->assertForbidden();
    }

    $this->put(route('admin.workflow.settings.update'), workflowForm(['enabled' => false]))->assertForbidden();
    $this->put(route('admin.google-ads.settings.update'), [])->assertForbidden();
    $this->post(route('admin.google-ads.placements.store'), placementForm())->assertForbidden();
    $this->delete(route('admin.google-ads.placements.destroy', $placement))->assertForbidden();

    expect(QuoteWorkflowSetting::current()->enabled)->toBeTrue()
        ->and(AdPlacement::query()->count())->toBe(1);
});

test('every admin page renders', function () {
    WorkflowFixtures::rewardedAds();
    $category = QuoteCategory::factory()->create();
    $user = User::factory()->create();
    $run = $this->actingAs($user)->postJson(route('studio.workflow.store'), ['quote_category_id' => $category->id])->json('workflow.id');
    $this->actingAs($user)->postJson(route('studio.workflow.ads.issue', $run))->assertOk();
    $this->actingAs($this->admin);

    $this->get(route('admin.workflow.settings'))->assertOk()->assertInertia(fn ($page) => $page
        ->component('admin/workflow/settings')->where('settings.publish_mode', 'auto')->has('providers', 3)->has('placements', 1));
    $this->get(route('admin.workflow.runs', ['state' => 'ad_before_survey', 'user' => $user->email]))->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/workflow/runs')->has('runs.data', 1));
    $this->get(route('admin.workflow.runs.show', $run))->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/workflow/run-show')->has('attempts', 1)->has('events'));
    $this->get(route('admin.google-ads.settings'))->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/google-ads/settings'));
    $this->get(route('admin.google-ads.placements'))->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/google-ads/placements')->has('placements', 1));
    $this->get(route('admin.logs.ads', ['status' => 'issued', 'step' => 'before_survey']))->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/logs/ads')->has('attempts.data', 1)->where('summary.0.total', 1));
    $this->get(route('admin.logs.activity', ['category' => 'ad', 'from' => now()->toDateString()]))->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/logs/activity')->has('events.data', 1));
});

test('workflow settings are saved, validated, audited and can be reset', function () {
    $this->actingAs($this->admin);
    $this->get(route('admin.workflow.settings'))->assertOk();

    $this->put(route('admin.workflow.settings.update'), workflowForm([
        'ad2_on_unavailable' => 'continue', 'publish_mode' => 'manual', 'survey_provider_keys' => ['tapresearch'],
    ]))->assertSessionHasNoErrors();

    $settings = QuoteWorkflowSetting::current();
    expect($settings->ad2_on_unavailable)->toBe('continue')
        ->and($settings->ad1_on_unavailable)->toBe('continue')
        ->and($settings->ad2_on_error)->toBe('block')
        ->and($settings->publish_mode)->toBe('manual')
        ->and($settings->survey_provider_keys)->toBe(['tapresearch'])
        ->and(WorkflowEvent::query()->where('event', 'workflow_settings_updated')->sole()->user_id)->toBe($this->admin->id);

    $this->put(route('admin.workflow.settings.update'), workflowForm([
        'ad1_on_error' => 'sometimes', 'survey_provider_keys' => ['google_adsense'], 'max_fallback_skips_per_day' => -1,
    ]))->assertSessionHasErrors(['ad1_on_error', 'survey_provider_keys.0', 'max_fallback_skips_per_day']);

    $this->post(route('admin.workflow.settings.reset'))->assertSessionHasNoErrors();
    expect(QuoteWorkflowSetting::current()->publish_mode)->toBe('auto')
        ->and(QuoteWorkflowSetting::current()->ad2_on_unavailable)->toBe('block');
});

test('an ad unit can be created, edited and deleted', function () {
    $this->actingAs($this->admin);

    $this->post(route('admin.google-ads.placements.store'), placementForm())->assertSessionHasNoErrors();
    $placement = AdPlacement::query()->sole();
    QuoteWorkflowSetting::current()->update(['ad1_placement_id' => $placement->id]);

    $this->put(route('admin.google-ads.placements.update', $placement), placementForm([
        'kind' => 'display', 'network' => 'adsense', 'location' => 'feed_between', 'adsense_slot_id' => '1234567890', 'every_n' => 3,
    ]))->assertSessionHasNoErrors();

    $placement->refresh();
    expect($placement->location)->toBe(AdLocation::FeedBetween)
        ->and($placement->ad_unit_path)->toBeNull()
        ->and($placement->every_n)->toBe(3);

    $this->delete(route('admin.google-ads.placements.destroy', $placement))->assertSessionHasNoErrors();

    expect(AdPlacement::query()->count())->toBe(0)
        ->and(QuoteWorkflowSetting::current()->ad1_placement_id)->toBeNull()
        ->and(WorkflowEvent::query()->where('category', 'admin')->count())->toBe(3);
});

test('ad unit rules are enforced', function (array $changes, string $field) {
    $this->actingAs($this->admin)
        ->post(route('admin.google-ads.placements.store'), placementForm($changes))
        ->assertSessionHasErrors($field);

    expect(AdPlacement::query()->count())->toBe(0);
})->with([
    'rewarded must be Ad Manager' => [['network' => 'adsense', 'adsense_slot_id' => '1234567890'], 'network'],
    'unit path format' => [['ad_unit_path' => 'rewarded'], 'ad_unit_path'],
    'display needs a location' => [['kind' => 'display'], 'location'],
    'AdSense needs a slot' => [['kind' => 'display', 'network' => 'adsense', 'location' => 'feed_top'], 'adsense_slot_id'],
    'size format' => [['kind' => 'display', 'location' => 'feed_top', 'sizes' => 'large'], 'sizes'],
    'key characters' => [['key' => 'has spaces'], 'key'],
]);

test('Google settings are validated and ads.txt is served from them', function () {
    $this->get('/ads.txt')->assertNotFound();
    $this->actingAs($this->admin);

    $form = [
        'rewarded_enabled' => true, 'display_enabled' => true, 'test_mode' => false,
        'adsense_client_id' => 'ca-pub-1234567890123456', 'gam_network_code' => '1234567',
        'ads_txt' => 'google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0',
    ];

    $this->put(route('admin.google-ads.settings.update'), [...$form, 'adsense_client_id' => 'pub-1', 'ads_txt' => '<script>'])
        ->assertSessionHasErrors(['adsense_client_id', 'ads_txt']);
    $this->put(route('admin.google-ads.settings.update'), $form)->assertSessionHasNoErrors();

    auth()->logout();
    $this->get('/ads.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('f08c47fec0942fa0');
});

test('regular ads reach public pages only, and only when switched on and complete', function () {
    $user = User::factory()->create();
    AdPlacement::factory()->display(AdLocation::FeedTop)->create();
    // Incomplete: no slot id, so it is left out.
    AdPlacement::factory()->display(AdLocation::SavedTop)->create(['adsense_slot_id' => null]);
    GoogleAdSetting::current()->update(['adsense_client_id' => 'ca-pub-1234567890123456']);

    $this->actingAs($user)->get(route('feed'))->assertInertia(fn ($page) => $page->where('displayAds', null));

    GoogleAdSetting::current()->update(['display_enabled' => true]);

    $this->actingAs($user)->get(route('feed'))->assertInertia(fn ($page) => $page
        ->has('displayAds.placements', 1)
        ->where('displayAds.placements.0.location', 'feed_top')
        ->where('displayAds.client', 'ca-pub-1234567890123456'));
    $this->actingAs($user)->get('/settings/profile')->assertInertia(fn ($page) => $page->where('displayAds', null));
    $this->actingAs($this->admin)->get(route('admin.home'))->assertInertia(fn ($page) => $page->where('displayAds', null));
});

test('regular ads work without the quote workflow and without rewarded ads', function () {
    AdPlacement::factory()->display()->create();
    GoogleAdSetting::current()->update(['display_enabled' => true, 'rewarded_enabled' => false, 'adsense_client_id' => 'ca-pub-1234567890123456']);
    QuoteWorkflowSetting::current()->update(['enabled' => false]);

    $this->actingAs(User::factory()->create())->get(route('feed'))
        ->assertInertia(fn ($page) => $page->has('displayAds.placements', 1));
});

test('an administrator can release a waiting quote', function () {
    WorkflowFixtures::aiRoute();
    WorkflowFixtures::rewardedAds();
    WorkflowFixtures::rules(['ad1_enabled' => false, 'survey_enabled' => false]);
    $user = User::factory()->create();
    $category = QuoteCategory::factory()->create();
    $run = $this->actingAs($user)->postJson(route('studio.workflow.store'), ['quote_category_id' => $category->id])->json('workflow.id');
    $this->actingAs($user)->postJson(route('studio.workflow.generate', $run))->assertOk();

    $this->actingAs($user)->post(route('admin.workflow.runs.unlock', $run))->assertForbidden();
    $this->actingAs($this->admin)->post(route('admin.workflow.runs.cancel', $run))->assertSessionHasErrors('workflow');
    $this->actingAs($this->admin)->post(route('admin.workflow.runs.unlock', $run))->assertSessionHasNoErrors();

    expect(Quote::query()->sole()->status)->toBe(QuoteStatus::Published)
        ->and(QuoteWorkflow::query()->sole()->state)->toBe(WorkflowState::Completed);

    $this->actingAs($this->admin)->post(route('admin.workflow.runs.unlock', $run))->assertSessionHasErrors('workflow');
});

test('the TapResearch address and timeout can be changed, but only to TapResearch', function () {
    $provider = AdProvider::factory()->create();
    $this->actingAs($this->admin);

    foreach (['http://api.tapresearch.com/x', 'https://evil.example.com/x', 'https://tapresearch.com.evil.example/x', 'https://user@api.tapresearch.com/x', 'https://api.tapresearch.com/x?a=1'] as $url) {
        $this->put(route('admin.ads.providers.update', $provider), ['base_url' => $url])->assertSessionHasErrors('base_url');
    }

    $this->put(route('admin.ads.providers.update', $provider), [
        'base_url' => 'https://eu.api.tapresearch.com/supply_api/v4', 'timeout_seconds' => 12,
    ])->assertSessionHasNoErrors();

    Http::fake(['eu.api.tapresearch.com/*' => Http::response(TapResearchFixtures::campaigns([TapResearchFixtures::survey()]))]);
    $this->actingAs(User::factory()->create())->postJson(route('surveys.offers'))->assertJsonPath('provider', 'tapresearch');

    $this->actingAs($this->admin)->put(route('admin.ads.providers.update', $provider), ['base_url' => '', 'timeout_seconds' => ''])
        ->assertSessionHasNoErrors();
    expect($provider->fresh()->config ?? [])->not->toHaveKeys(['base_url', 'timeout_seconds']);

    $other = AdProvider::factory()->create(['key' => 'wannads', 'label' => 'Wannads']);
    $this->put(route('admin.ads.providers.update', $other), ['base_url' => 'https://api.tapresearch.com/x'])
        ->assertSessionHasErrors('base_url');
});

test('an address stored some other way never receives the token', function () {
    AdProvider::factory()->create(['config' => ['base_url' => 'https://evil.example.com']]);
    Http::fake(['api.tapresearch.com/*' => Http::response(TapResearchFixtures::campaigns([TapResearchFixtures::survey()]))]);

    $this->actingAs(User::factory()->create())->postJson(route('surveys.offers'))->assertJsonPath('provider', 'tapresearch');
});

test('deleting credentials switches the provider off', function () {
    $provider = AdProvider::factory()->create();

    $this->actingAs($this->admin)->delete(route('admin.ads.providers.credentials.destroy', $provider))->assertSessionHasNoErrors();

    expect($provider->fresh()->settings)->toBeNull()->and($provider->fresh()->is_enabled)->toBeFalse();
});

test('stored API responses and logs never contain secrets', function () {
    AdProvider::factory()->create();
    Http::fake(['*/campaigns*' => Http::response([
        'error' => 'bad token test-api-token',
        'api_token' => 'something-else',
        'help' => 'https://support.example.test/entry?sig=abc123',
    ], 401)]);

    $this->actingAs(User::factory()->create())->postJson(route('surveys.offers'))->assertOk();

    $excerpt = AdProviderCall::query()->sole()->response_excerpt;
    expect($excerpt)->toContain('bad token [redacted]')
        ->not->toContain('test-api-token')
        ->not->toContain('something-else')
        ->not->toContain('abc123');

    $this->actingAs($this->admin)->get(route('admin.ads.requests'))
        ->assertInertia(fn ($page) => $page->where('calls.data.0.response', $excerpt));
});
