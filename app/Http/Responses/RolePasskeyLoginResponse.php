<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * After a passkey sign-in, send administrators to the admin panel and everyone else to the feed.
 */
class RolePasskeyLoginResponse implements PasskeyLoginResponseContract
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        $target = redirect()->intended(RoleHomeResponse::homeFor($request->user()));

        if ($request->wantsJson()) {
            return new JsonResponse(['redirect' => $target->getTargetUrl()]);
        }

        return $target;
    }
}
