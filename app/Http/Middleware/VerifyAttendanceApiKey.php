<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyAttendanceApiKey
{
    public function handle(Request $request, Closure $next): mixed
    {
        $apiKey = $request->header('X-API-KEY');

        if (! $apiKey || $apiKey !== env('ATTENDANCE_API_KEY')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
