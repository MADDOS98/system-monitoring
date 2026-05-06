<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SetUserTimezone
{
    public function handle(Request $request, Closure $next)
    {
        $tz = $request->cookie('tz');

        if ($tz && in_array($tz, timezone_identifiers_list())) {
            config(['app.timezone' => $tz]);
            date_default_timezone_set($tz);
        }

        return $next($request);
    }
}
