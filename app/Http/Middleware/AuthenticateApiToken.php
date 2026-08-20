<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $apiToken = ApiToken::query()
            ->with('user.roles.permissions')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('revoked_at')
            ->first();

        if ($apiToken === null || $apiToken->isExpired()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $apiToken->forceFill(['last_used_at' => now()])->save();
        $request->setUserResolver(fn () => $apiToken->user);

        return $next($request);
    }
}
