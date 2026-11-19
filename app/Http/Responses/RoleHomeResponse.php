<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class RoleHomeResponse implements LoginResponseContract, TwoFactorLoginResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false]);
        }

        return redirect()->intended(self::homeFor($request->user()));
    }

    /**
     * Where a signed-in user lands: the admin panel for administrators, the feed for everyone else.
     */
    public static function homeFor(?User $user): string
    {
        if ($user?->isAdministrator() === true) {
            return route('admin.home', absolute: false);
        }

        return route('feed', absolute: false);
    }
}
