<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->isRestricted()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $user->isBanned()
                ? 'This account has been banned.'
                : 'This account is temporarily suspended.';

            return redirect()
                ->route('login')
                ->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
