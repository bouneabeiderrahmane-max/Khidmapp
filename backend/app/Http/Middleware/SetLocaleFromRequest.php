<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromRequest
{
    /**
     * Resolve the request locale from the Accept-Language header (or the
     * authenticated user's saved preference) and apply it for the duration
     * of the request. Falls back to config('app.fallback_locale').
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = explode(',', (string) config('app.supported_locales', 'fr,ar'));

        $locale = $request->user()?->locale
            ?? $request->getPreferredLanguage($supported)
            ?? config('app.fallback_locale');

        app()->setLocale($locale);

        return $next($request);
    }
}
