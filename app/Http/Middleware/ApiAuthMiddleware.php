<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class ApiAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        if (! Auth::guard('api')->check()) {
            return response()->json(['message' => 'You must login first.'], 401);
        }

        return $next($request);
    }
}
