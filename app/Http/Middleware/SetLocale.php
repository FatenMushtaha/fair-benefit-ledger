<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->preferredLocale() ?? Locale::fallback();

        app()->setLocale($locale->value);
        Carbon::setLocale($locale->value);

        return $next($request);
    }
}
