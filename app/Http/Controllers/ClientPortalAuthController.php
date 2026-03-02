<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\ClientPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClientPortalAuthController extends Controller
{
    public function __construct(private ClientPortalService $portalService) {}

    /**
     * POST /api/portal/{slug}/auth
     *
     * Authenticate a client against their portal using the plain client_key.
     * Returns a Sanctum token scoped to portal abilities.
     * Route is rate-limited to prevent brute force.
     */
    public function authenticate(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'client_key' => 'required|string',
        ]);

        $client = Client::where('portal_slug', $slug)->first();

        Log::info('Portal authentication attempt.', [
            'slug' => $slug,
            'ip'   => $request->ip(),
        ]);

        // Use a consistent response to avoid slug enumeration
        if (!$client || !$client->portal_enabled) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials.'], 401);
        }

        if (!$this->portalService->validateKey($client, $request->input('client_key'))) {
            Log::warning('Portal authentication failed — invalid key.', [
                'client_id' => $client->id,
                'ip'        => $request->ip(),
            ]);

            return response()->json(['success' => false, 'message' => 'Invalid credentials.'], 401);
        }

        $token = $this->portalService->createSessionToken($client);

        Log::info('Portal authentication successful.', ['client_id' => $client->id]);

        return response()->json([
            'success' => true,
            'token'   => $token,
            'client'  => [
                'id'           => $client->id,
                'company_name' => $client->company_name,
                'contact_name' => $client->contact_name,
                'email'        => $client->email,
            ],
        ]);
    }
}
