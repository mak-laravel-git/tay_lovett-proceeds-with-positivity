<?php

namespace App\Http\Controllers\Admin\GoogleAds;

use App\Http\Controllers\Controller;
use App\Models\GoogleAdSetting;
use App\Services\QuoteWorkflow\WorkflowLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Account-wide Google ad settings: the two master switches, account identifiers, test mode and ads.txt.
 */
class SettingsController extends Controller
{
    public function index(): Response
    {
        $settings = GoogleAdSetting::current();

        return Inertia::render('admin/google-ads/settings', [
            'settings' => [
                'rewarded_enabled' => $settings->rewarded_enabled,
                'display_enabled' => $settings->display_enabled,
                'adsense_client_id' => $settings->adsense_client_id ?? '',
                'gam_network_code' => $settings->gam_network_code ?? '',
                'test_mode' => $settings->test_mode,
                'ads_txt' => $settings->ads_txt ?? '',
            ],
            'ads_txt_url' => url('/ads.txt'),
        ]);
    }

    public function update(Request $request, WorkflowLogger $logger): RedirectResponse
    {
        $validated = $request->validate([
            'rewarded_enabled' => ['required', 'boolean'],
            'display_enabled' => ['required', 'boolean'],
            'adsense_client_id' => ['nullable', 'string', 'regex:/^ca-pub-\d{10,20}$/'],
            'gam_network_code' => ['nullable', 'string', 'regex:/^\d{4,20}$/'],
            'test_mode' => ['required', 'boolean'],
            // Plain text only: this file is published at the site root.
            'ads_txt' => ['nullable', 'string', 'max:10000', 'not_regex:/[<>]/'],
        ], [
            'adsense_client_id.regex' => 'The AdSense client ID looks like ca-pub-1234567890123456.',
            'gam_network_code.regex' => 'The Ad Manager network code is digits only.',
            'ads_txt.not_regex' => 'ads.txt is a plain text file.',
        ]);

        foreach (['rewarded_enabled', 'display_enabled', 'test_mode'] as $field) {
            $validated[$field] = $request->boolean($field);
        }

        $settings = GoogleAdSetting::current()->fill($validated);
        $changed = array_keys($settings->getDirty());
        $settings->save();

        if ($changed !== []) {
            $logger->log(WorkflowLogger::ADMIN, 'google_ads_settings_updated', context: ['changed' => $changed], userId: $request->user()->id);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Google ad settings saved.']);

        return back();
    }
}
