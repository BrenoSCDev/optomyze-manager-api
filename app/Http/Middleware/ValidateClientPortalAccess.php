<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class ValidateClientPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();

        if (!$bearerToken) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $accessToken = PersonalAccessToken::findToken($bearerToken);

        if (
            !$accessToken ||
            $accessToken->tokenable_type !== Client::class ||
            !$accessToken->can('portal')
        ) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        /** @var Client $client */
        $client = $accessToken->tokenable;

        if (!$client || !$client->portal_enabled) {
            return response()->json(['success' => false, 'message' => 'Portal access is disabled.'], 403);
        }

        $accessToken->forceFill(['last_used_at' => now()])->save();

        $request->attributes->set('portal_client', $client);

        return $next($request);
    }
}
