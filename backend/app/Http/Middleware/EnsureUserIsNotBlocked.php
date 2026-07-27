<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un compte bloqué (CDC 8.9.2) perd immédiatement l'accès à l'API, même
 * avec un jeton JWT déjà émis et encore valide — pas seulement à la
 * prochaine connexion.
 */
class EnsureUserIsNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isBlocked()) {
            return response()->json(['message' => __('khidmapp.account_blocked')], 403);
        }

        return $next($request);
    }
}
