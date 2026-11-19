<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\People\BanPersonRequest;
use App\Http\Requests\Admin\People\StorePersonRequest;
use App\Http\Requests\Admin\People\SuspendPersonRequest;
use App\Http\Requests\Admin\People\UpdatePersonRequest;
use App\Models\User;
use App\Services\PeopleAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PeopleAccountController extends Controller
{
    public function __construct(private PeopleAccountService $people) {}

    public function usersIndex(Request $request): Response
    {
        return $this->index($request, UserRole::User, 'admin/users/index', 'Users');
    }

    public function administratorsIndex(Request $request): Response
    {
        return $this->index($request, UserRole::Administrator, 'admin/administrators/index', 'Administrators');
    }

    public function showUser(Request $request, int $person): Response
    {
        return $this->show(
            $request,
            $person,
            UserRole::User,
            'admin/users/show',
        );
    }

    public function showAdministrator(Request $request, int $person): Response
    {
        return $this->show(
            $request,
            $person,
            UserRole::Administrator,
            'admin/administrators/show',
        );
    }

    public function storeUser(StorePersonRequest $request): RedirectResponse
    {
        $user = $this->people->create(UserRole::User, $request->personData());

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User created.');
    }

    public function storeAdministrator(StorePersonRequest $request): RedirectResponse
    {
        $user = $this->people->create(UserRole::Administrator, $request->personData());

        return redirect()
            ->route('admin.administrators.show', $user)
            ->with('success', 'Administrator created.');
    }

    public function update(UpdatePersonRequest $request, User $person): RedirectResponse
    {
        $this->assertRouteRoleMatches($request, $person);
        $this->people->update($person, $request->personData());

        return back()->with('success', 'Account updated.');
    }

    public function destroy(Request $request, User $person): RedirectResponse
    {
        $this->assertRouteRoleMatches($request, $person);
        /** @var User $actor */
        $actor = $request->user();
        $this->people->softDelete($actor, $person);

        $index = $person->role === UserRole::Administrator
            ? 'admin.administrators'
            : 'admin.users';

        return redirect()
            ->route($index, ['status' => 'deleted'])
            ->with('success', 'Account moved to trash.');
    }

    public function restore(Request $request, int $person): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($person);
        $this->assertRouteRoleMatches($request, $user);
        $this->people->restore($user);

        $show = $user->role === UserRole::Administrator
            ? 'admin.administrators.show'
            : 'admin.users.show';

        return redirect()
            ->route($show, $user)
            ->with('success', 'Account restored.');
    }

    public function ban(BanPersonRequest $request, User $person): RedirectResponse
    {
        $this->assertRouteRoleMatches($request, $person);
        $this->assertUserRoleOnly($person);
        /** @var User $actor */
        $actor = $request->user();
        $this->people->ban($actor, $person, $request->validated('reason'));

        return back()->with('success', 'User banned.');
    }

    public function unban(Request $request, User $person): RedirectResponse
    {
        $this->assertRouteRoleMatches($request, $person);
        $this->assertUserRoleOnly($person);
        /** @var User $actor */
        $actor = $request->user();
        $this->people->unban($actor, $person);

        return back()->with('success', 'Ban lifted.');
    }

    public function suspend(SuspendPersonRequest $request, User $person): RedirectResponse
    {
        $this->assertRouteRoleMatches($request, $person);
        $this->assertUserRoleOnly($person);
        /** @var User $actor */
        $actor = $request->user();
        $this->people->suspend(
            $actor,
            $person,
            (int) $request->validated('days'),
            $request->validated('reason'),
        );

        return back()->with('success', 'User suspended.');
    }

    public function unsuspend(Request $request, User $person): RedirectResponse
    {
        $this->assertRouteRoleMatches($request, $person);
        $this->assertUserRoleOnly($person);
        /** @var User $actor */
        $actor = $request->user();
        $this->people->unsuspend($actor, $person);

        return back()->with('success', 'Suspension cleared.');
    }

    private function show(Request $request, int $personId, UserRole $role, string $component): Response
    {
        $person = User::withTrashed()->findOrFail($personId);

        if ($person->role !== $role) {
            abort(404);
        }

        $month = $request->string('month')->toString() ?: null;

        return Inertia::render($component, [
            'account' => $this->people->serialize(
                $person,
                withWallet: $role === UserRole::User,
                month: $month,
            ),
            'capabilities' => [
                'can_moderate' => $role === UserRole::User,
                'show_wallet' => $role === UserRole::User,
            ],
            'routes' => $this->routesFor($person),
            'filters' => [
                'month' => $month ?? now()->format('Y-m'),
            ],
        ]);
    }

    private function index(Request $request, UserRole $role, string $component, string $title): Response
    {
        return Inertia::render($component, $this->directoryProps($request, $role, $title));
    }

    /**
     * @return array<string, mixed>
     */
    private function directoryProps(Request $request, UserRole $role, string $title): array
    {
        $status = $request->string('status', 'active')->toString();
        $search = $request->string('search')->trim()->toString();

        $query = User::query()->where('role', $role);

        if ($status === 'deleted') {
            $query->onlyTrashed();
        } else {
            $query->whereNull('deleted_at');

            match ($status) {
                'banned' => $query->whereNotNull('banned_at'),
                'suspended' => $query
                    ->whereNull('banned_at')
                    ->whereNotNull('suspended_until')
                    ->where('suspended_until', '>', now()),
                default => $query
                    ->whereNull('banned_at')
                    ->where(function ($builder): void {
                        $builder->whereNull('suspended_until')
                            ->orWhere('suspended_until', '<=', now());
                    }),
            };
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $accounts = $query
            ->latest()
            ->get()
            ->map(fn (User $user): array => $this->people->serialize($user))
            ->values()
            ->all();

        return [
            'title' => $title,
            'role' => $role->value,
            'status' => $status,
            'search' => $search,
            'accounts' => $accounts,
            'capabilities' => [
                'can_moderate' => $role === UserRole::User,
                'show_wallet' => $role === UserRole::User,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function routesFor(User $person): array
    {
        $prefix = $person->role === UserRole::Administrator
            ? 'administrators'
            : 'users';

        $routes = [
            'index' => "/admin/{$prefix}",
            'show' => "/admin/{$prefix}/{$person->id}",
            'update' => "/admin/{$prefix}/{$person->id}",
            'destroy' => "/admin/{$prefix}/{$person->id}",
            'restore' => "/admin/{$prefix}/{$person->id}/restore",
        ];

        if ($person->role === UserRole::User) {
            $routes['ban'] = "/admin/users/{$person->id}/ban";
            $routes['unban'] = "/admin/users/{$person->id}/unban";
            $routes['suspend'] = "/admin/users/{$person->id}/suspend";
            $routes['unsuspend'] = "/admin/users/{$person->id}/unsuspend";
        }

        return $routes;
    }

    private function assertRouteRoleMatches(Request $request, User $person): void
    {
        $path = $request->path();
        $expected = str_contains($path, 'administrators')
            ? UserRole::Administrator
            : UserRole::User;

        if ($person->role !== $expected) {
            abort(404);
        }
    }

    private function assertUserRoleOnly(User $person): void
    {
        if ($person->role !== UserRole::User) {
            throw ValidationException::withMessages([
                'user' => 'Only platform users can be banned or suspended.',
            ]);
        }
    }
}
