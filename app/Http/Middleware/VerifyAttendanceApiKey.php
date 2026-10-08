<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyAttendanceApiKey
{
    public function handle(Request $request, Closure $next): mixed
    {
        $apiKey = $request->header('X-API-KEY');

        if (! $apiKey || ! hash_equals((string) config('services.attendance_api_key'), (string) $apiKey)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
