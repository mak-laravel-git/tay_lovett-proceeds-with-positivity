<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AppSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * Real Laravel maintenance mode (the same thing `php artisan down`/`up` do), with a themed prerendered page
 * and a one-time secret bypass link. Administrators keep full access to /admin and to signing in, through the
 * exceptions registered in AppServiceProvider, so this never locks an administrator out of turning it off again.
 */
class MaintenanceController extends Controller
{
    public function enable(Request $request, AppSettings $settings): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:500'],
            'expected_back_at' => ['nullable', 'date'],
        ]);

        $settings->setMany([
            'maintenance.title' => $validated['title'],
            'maintenance.message' => $validated['message'] ?? '',
            'maintenance.expected_back_at' => $validated['expected_back_at'] ?? '',
        ], $request->user()->id);

        // The down command only forwards `retryAfter` to the prerendered view, so the rest of the page's data
        // (title, message, expected-back date, current branding) is read by the view itself from config and
        // from AppSettings, both of which are available right now because we are still inside this request.
        config([
            'maintenance_page.title' => $validated['title'],
            'maintenance_page.message' => $validated['message'] ?? '',
            'maintenance_page.expected_back_at' => $validated['expected_back_at'] ?? null,
        ]);

        $options = [
            '--secret' => Str::random(40),
            '--render' => 'maintenance-mode',
        ];

        if (filled($validated['expected_back_at'] ?? null)) {
            $options['--retry'] = Carbon::parse($validated['expected_back_at'])->toRfc7231String();
        }

        Artisan::call('down', $options);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Maintenance mode is on. Only you can browse the site normally.']);

        return back();
    }

    public function disable(): RedirectResponse
    {
        Artisan::call('up');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Maintenance mode is off. The site is reachable again.']);

        return back();
    }
}
