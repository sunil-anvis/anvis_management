<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\UserToken;
use Illuminate\Support\Carbon;

class CustomAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized. No token provided.'], 401);
        }

        $userToken = UserToken::with('user')->where('token', hash('sha256', $token))->first();

        if (!$userToken || $userToken->is_revoked) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized. Invalid or revoked token.'], 401);
        }

        if (Carbon::now()->greaterThan($userToken->expiry_time)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized. Token has expired.'], 401);
        }

        // Authenticate the user for the current request
        auth()->login($userToken->user);

        return $next($request);
    }
}
