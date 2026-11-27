<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\AdGateController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\Ads\ActivityController as AdsActivityController;
use App\Http\Controllers\Admin\Ads\CallbackLogController as AdsCallbackLogController;
use App\Http\Controllers\Admin\Ads\OverviewController as AdsOverviewController;
use App\Http\Controllers\Admin\Ads\ProviderController as AdsProviderController;
use App\Http\Controllers\Admin\Ads\ReconciliationController as AdsReconciliationController;
use App\Http\Controllers\Admin\Ads\RequestLogController as AdsRequestLogController;
use App\Http\Controllers\Admin\Ads\RewardController as AdsRewardController;
use App\Http\Controllers\Admin\AiManagementController;
use App\Http\Controllers\Admin\AiPlaygroundController;
use App\Http\Controllers\Admin\ApplicationSettingsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GoogleAds\PlacementController;
use App\Http\Controllers\Admin\GoogleAds\SettingsController as GoogleAdsSettingsController;
use App\Http\Controllers\Admin\LegalDocumentController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\PeopleAccountController;
use App\Http\Controllers\Admin\QuoteModerationController;
use App\Http\Controllers\Admin\RevenueController;
use App\Http\Controllers\Admin\WithdrawalReviewController;
use App\Http\Controllers\Admin\WorkflowRunController;
use App\Http\Controllers\Admin\WorkflowSettingsController;
use App\Http\Controllers\AdsTxtController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\PublicLegalController;
use App\Http\Controllers\PublicQuoteController;
use App\Http\Controllers\Studio\WorkflowController;
use App\Http\Controllers\StudioController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = auth()->user();

    if ($user === null) {
        return redirect()->route('login');
    }

    return redirect()->route($user->isAdministrator() ? 'admin.home' : 'feed');
})->name('home');

Route::get('privacy', [PublicLegalController::class, 'show'])->defaults('slug', 'privacy')->name('privacy');
Route::get('terms', [PublicLegalController::class, 'show'])->defaults('slug', 'terms')->name('terms');
Route::inertia('how-to-use', 'help/how-to-use')->name('how-to-use');
Route::get('ads.txt', AdsTxtController::class)->name('ads.txt');
Route::get('og/default.png', [PublicQuoteController::class, 'siteImage'])->name('site.og');
Route::get('q/{quote}', [PublicQuoteController::class, 'show'])->name('quotes.public');
Route::get('q/{quote}/og.png', [PublicQuoteController::class, 'image'])->name('quotes.og');
// Where a provider sends the browser after a survey. Loaded inside our own in-app frame (or a popup), so it is
// reachable without the auth redirect; it only updates the user-facing experience and never awards anything.
Route::get('surveys/return/{provider}', [SurveyController::class, 'return'])->where('provider', '[a-z0-9_-]+')->name('surveys.return');

Route::middleware(['auth', 'account.active', 'verified'])->group(function () {
    Route::get('feed', [FeedController::class, 'index'])->name('feed');
    Route::post('quotes/{quote}/save', [FeedController::class, 'save'])->name('quotes.save');
    Route::post('quotes/{quote}/ad', [AdController::class, 'present'])->name('ads.present');
    Route::get('ads/{ad}', [AdController::class, 'show'])->name('ads.show');
    Route::post('ads/{ad}/exit', [AdController::class, 'exit'])->name('ads.exit');
    Route::get('saved', [FeedController::class, 'saved'])->name('saved');
    Route::get('studio', [StudioController::class, 'index'])->name('studio');
    Route::post('studio', [StudioController::class, 'generate'])->middleware('throttle:10,1')->name('studio.generate');
    // The step-by-step "Create quote" flow. Each limit has its own prefix so the steps do not share one counter.
    Route::prefix('studio/workflow')->name('studio.workflow.')->group(function () {
        Route::post('/', [WorkflowController::class, 'store'])->middleware('throttle:20,1,workflow-start')->name('store');
        Route::get('{workflow}', [WorkflowController::class, 'show'])->middleware('throttle:120,1,workflow-show')->name('show');
        Route::post('{workflow}/ads/attempts', [WorkflowController::class, 'issueAd'])->middleware('throttle:20,1,workflow-ad')->name('ads.issue');
        Route::post('{workflow}/ads/attempts/{attempt}/events', [WorkflowController::class, 'adEvent'])->middleware('throttle:80,1,workflow-event')->name('ads.event');
        Route::post('{workflow}/survey/offers', [WorkflowController::class, 'offers'])->middleware('throttle:20,1,workflow-offers')->name('offers');
        Route::post('{workflow}/generate', [WorkflowController::class, 'generate'])->middleware('throttle:10,1,workflow-generate')->name('generate');
        Route::post('{workflow}/cancel', [WorkflowController::class, 'cancel'])->middleware('throttle:20,1,workflow-cancel')->name('cancel');
    });
    Route::get('ads/gate/{purpose}', [AdGateController::class, 'show'])->name('ads.gate');
    // Local development only: a provider cannot reach 127.0.0.1, so a survey is completed through the real
    // callback pipeline from here. The route does not exist in any other environment.
    if (app()->environment('local')) {
        Route::post('dev/ads/activities/{activity}/simulate', [AdGateController::class, 'simulate'])->name('dev.ads.simulate');
    }
    Route::post('studio/quotes/{quote}/publish', [StudioController::class, 'publish'])->name('studio.quotes.publish');
    Route::get('surveys', [SurveyController::class, 'index'])->name('surveys');
    Route::post('surveys/offers', [SurveyController::class, 'offers'])->middleware('throttle:20,1')->name('surveys.offers');
    Route::post('surveys/start', [SurveyController::class, 'start'])->middleware('throttle:30,1')->name('surveys.start');
    Route::get('surveys/activities/{activity}/status', [SurveyController::class, 'status'])->name('surveys.status');
    Route::get('earnings', [WalletController::class, 'earnings'])->name('earnings');
    Route::get('wallet', [WalletController::class, 'wallet'])->name('wallet');
    Route::get('withdrawals', [WalletController::class, 'withdrawals'])->name('withdrawals');
    Route::post('withdrawals', [WalletController::class, 'requestWithdrawal'])->name('withdrawals.store');
    Route::redirect('dashboard', '/feed')->name('dashboard');

    Route::middleware('administrator')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', DashboardController::class)->name('home');
        Route::get('quotes', [QuoteModerationController::class, 'index'])->name('quotes');
        Route::post('quotes', [QuoteModerationController::class, 'store'])->name('quotes.store');
        Route::post('quotes/{quote}/approve', [QuoteModerationController::class, 'approve'])->name('quotes.approve');
        Route::post('quotes/{quote}/reject', [QuoteModerationController::class, 'reject'])->name('quotes.reject');
        Route::post('quotes/{quote}/publish', [QuoteModerationController::class, 'publish'])->name('quotes.publish');
        Route::post('quotes/{quote}/unpublish', [QuoteModerationController::class, 'unpublish'])->name('quotes.unpublish');
        Route::get('ai/providers', [AiManagementController::class, 'providers'])->name('ai.providers');
        Route::post('ai/providers', [AiManagementController::class, 'storeProvider'])->name('ai.providers.store');
        Route::put('ai/providers/{credential}', [AiManagementController::class, 'updateProvider'])->name('ai.providers.update');
        Route::delete('ai/providers/{credential}', [AiManagementController::class, 'destroyProvider'])->name('ai.providers.destroy');
        Route::post('ai/providers/{credential}/test', [AiManagementController::class, 'testConnection'])->middleware('throttle:5,1')->name('ai.providers.test');
        Route::get('ai/models', [AiManagementController::class, 'models'])->name('ai.models');
        Route::post('ai/models', [AiManagementController::class, 'storeModel'])->name('ai.models.store');
        Route::put('ai/models/{model}', [AiManagementController::class, 'updateModel'])->name('ai.models.update');
        Route::delete('ai/models/{model}', [AiManagementController::class, 'destroyModel'])->name('ai.models.destroy');
        Route::post('ai/models/{model}/test', [AiManagementController::class, 'testModel'])->middleware('throttle:5,1')->name('ai.models.test');
        Route::get('ai/routing', [AiManagementController::class, 'routing'])->name('ai.routing');
        Route::put('ai/routing/mode', [AiManagementController::class, 'updateRoutingMode'])->name('ai.routing.mode');
        Route::post('ai/routing/steps', [AiManagementController::class, 'storeRouteStep'])->name('ai.routing.steps.store');
        Route::post('ai/routing/steps/{step}/move', [AiManagementController::class, 'moveRouteStep'])->name('ai.routing.steps.move');
        Route::delete('ai/routing/steps/{step}', [AiManagementController::class, 'destroyRouteStep'])->name('ai.routing.steps.destroy');
        Route::get('ai/playground', [AiPlaygroundController::class, 'index'])->name('ai.playground');
        Route::post('ai/playground', [AiPlaygroundController::class, 'send'])->middleware('throttle:20,1')->name('ai.playground.send');
        Route::get('ai/usage', [AiManagementController::class, 'usage'])->name('ai.usage');
        Route::get('ai/settings', [AiManagementController::class, 'settings'])->name('ai.settings');
        Route::put('ai/settings', [AiManagementController::class, 'updateSettings'])->name('ai.settings.update');
        Route::post('ai/settings/reset', [AiManagementController::class, 'resetSettings'])->name('ai.settings.reset');
        Route::get('withdrawals', [WithdrawalReviewController::class, 'index'])->name('withdrawals');
        Route::post('withdrawals/{withdrawal}/approve', [WithdrawalReviewController::class, 'approve'])->name('withdrawals.approve');
        Route::post('withdrawals/{withdrawal}/reject', [WithdrawalReviewController::class, 'reject'])->name('withdrawals.reject');
        Route::post('withdrawals/{withdrawal}/paid', [WithdrawalReviewController::class, 'paid'])->name('withdrawals.paid');
        Route::get('users', [PeopleAccountController::class, 'usersIndex'])->name('users');
        Route::post('users', [PeopleAccountController::class, 'storeUser'])->name('users.store');
        Route::get('users/{person}', [PeopleAccountController::class, 'showUser'])->name('users.show');
        Route::post('users/{person}', [PeopleAccountController::class, 'update'])->name('users.update');
        Route::delete('users/{person}', [PeopleAccountController::class, 'destroy'])->name('users.destroy');
        Route::post('users/{person}/restore', [PeopleAccountController::class, 'restore'])->name('users.restore');
        Route::post('users/{person}/ban', [PeopleAccountController::class, 'ban'])->name('users.ban');
        Route::post('users/{person}/unban', [PeopleAccountController::class, 'unban'])->name('users.unban');
        Route::post('users/{person}/suspend', [PeopleAccountController::class, 'suspend'])->name('users.suspend');
        Route::post('users/{person}/unsuspend', [PeopleAccountController::class, 'unsuspend'])->name('users.unsuspend');

        Route::get('administrators', [PeopleAccountController::class, 'administratorsIndex'])->name('administrators');
        Route::post('administrators', [PeopleAccountController::class, 'storeAdministrator'])->name('administrators.store');
        Route::get('administrators/{person}', [PeopleAccountController::class, 'showAdministrator'])->name('administrators.show');
        Route::post('administrators/{person}', [PeopleAccountController::class, 'update'])->name('administrators.update');
        Route::delete('administrators/{person}', [PeopleAccountController::class, 'destroy'])->name('administrators.destroy');
        Route::post('administrators/{person}/restore', [PeopleAccountController::class, 'restore'])->name('administrators.restore');

        Route::get('settings', [ApplicationSettingsController::class, 'index'])->name('settings');
        Route::get('settings/legal/{slug}', [LegalDocumentController::class, 'edit'])->whereIn('slug', ['privacy', 'terms'])->name('legal.edit');
        Route::put('settings/legal/{slug}', [LegalDocumentController::class, 'update'])->whereIn('slug', ['privacy', 'terms'])->name('legal.update');
        Route::post('settings/legal/{slug}/reset', [LegalDocumentController::class, 'reset'])->whereIn('slug', ['privacy', 'terms'])->name('legal.reset');
        Route::post('legal/images', [LegalDocumentController::class, 'uploadImage'])->middleware('throttle:20,1')->name('legal.images');
        Route::post('settings/smtp/test', [ApplicationSettingsController::class, 'testSmtp'])->middleware('throttle:3,1')->name('settings.smtp.test');
        Route::post('settings/maintenance/enable', [MaintenanceController::class, 'enable'])->name('maintenance.enable');
        Route::post('settings/maintenance/disable', [MaintenanceController::class, 'disable'])->name('maintenance.disable');
        Route::post('settings/{group}', [ApplicationSettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/{group}/reset', [ApplicationSettingsController::class, 'reset'])->name('settings.reset');

        // Quote workflow: rules and runs.
        Route::get('workflow', [WorkflowSettingsController::class, 'index'])->name('workflow.settings');
        Route::put('workflow', [WorkflowSettingsController::class, 'update'])->name('workflow.settings.update');
        Route::post('workflow/reset', [WorkflowSettingsController::class, 'reset'])->name('workflow.settings.reset');
        Route::get('workflow/runs', [WorkflowRunController::class, 'index'])->name('workflow.runs');
        Route::get('workflow/runs/{workflow}', [WorkflowRunController::class, 'show'])->name('workflow.runs.show');
        Route::post('workflow/runs/{workflow}/unlock', [WorkflowRunController::class, 'unlock'])->name('workflow.runs.unlock');
        Route::post('workflow/runs/{workflow}/cancel', [WorkflowRunController::class, 'cancel'])->name('workflow.runs.cancel');

        // Google ads: account settings and ad units (rewarded and regular).
        Route::get('google-ads', [GoogleAdsSettingsController::class, 'index'])->name('google-ads.settings');
        Route::put('google-ads', [GoogleAdsSettingsController::class, 'update'])->name('google-ads.settings.update');
        Route::get('google-ads/placements', [PlacementController::class, 'index'])->name('google-ads.placements');
        Route::post('google-ads/placements', [PlacementController::class, 'store'])->name('google-ads.placements.store');
        Route::put('google-ads/placements/{placement}', [PlacementController::class, 'update'])->name('google-ads.placements.update');
        Route::delete('google-ads/placements/{placement}', [PlacementController::class, 'destroy'])->name('google-ads.placements.destroy');

        Route::get('logs/ads', [ActivityLogController::class, 'ads'])->name('logs.ads');
        Route::get('logs/activity', [ActivityLogController::class, 'activity'])->name('logs.activity');

        // Ads & surveys: provider management, rewards, transaction log, monitoring and reconciliation.
        Route::prefix('ads')->name('ads.')->group(function () {
            Route::get('/', AdsOverviewController::class)->name('index');
            Route::get('providers', [AdsProviderController::class, 'index'])->name('providers');
            Route::put('providers/{provider}', [AdsProviderController::class, 'update'])->name('providers.update');
            Route::post('providers/{provider}/move', [AdsProviderController::class, 'move'])->name('providers.move');
            Route::post('providers/{provider}/test', [AdsProviderController::class, 'test'])->middleware('throttle:5,1')->name('providers.test');
            Route::delete('providers/{provider}/credentials', [AdsProviderController::class, 'clearCredentials'])->name('providers.credentials.destroy');
            Route::get('rewards', [AdsRewardController::class, 'index'])->name('rewards');
            Route::post('rewards', [AdsRewardController::class, 'store'])->name('rewards.store');
            Route::put('rewards/settings', [AdsRewardController::class, 'updateSettings'])->name('rewards.settings');
            Route::put('rewards/{rule}', [AdsRewardController::class, 'update'])->name('rewards.update');
            Route::delete('rewards/{rule}', [AdsRewardController::class, 'destroy'])->name('rewards.destroy');
            Route::get('activity', [AdsActivityController::class, 'index'])->name('activity');
            Route::get('activity/{activity}', [AdsActivityController::class, 'show'])->name('activity.show');
            Route::get('requests', [AdsRequestLogController::class, 'index'])->name('requests');
            Route::get('callbacks', [AdsCallbackLogController::class, 'index'])->name('callbacks');
            Route::get('reconciliation', [AdsReconciliationController::class, 'index'])->name('reconciliation');
            Route::post('reconciliation/run', [AdsReconciliationController::class, 'run'])->middleware('throttle:10,1')->name('reconciliation.run');
            Route::post('reconciliation/payouts', [AdsReconciliationController::class, 'storePayout'])->name('reconciliation.payouts');
            Route::get('reconciliation/{reconciliation}', [AdsReconciliationController::class, 'show'])->name('reconciliation.show');
        });
        // The old demonstration-only page is replaced by the ads overview.
        Route::redirect('advertisements', '/admin/ads')->name('advertisements');
        Route::get('revenue', [RevenueController::class, 'index'])->name('revenue');
    });
});

require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
