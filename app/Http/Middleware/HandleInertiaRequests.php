<?php

namespace App\Http\Middleware;

use App\Services\AppSettings;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'site' => app(AppSettings::class)->site(),
            // Reaching this line at all while the site is down means this visitor already bypassed maintenance
            // mode (an administrator route, or a valid secret-link cookie), so it is safe and useful to tell them.
            'maintenanceActive' => app()->isDownForMaintenance(),
            'auth' => [
                'user' => $request->user(),
                'is_administrator' => $request->user()?->isAdministrator() ?? false,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
