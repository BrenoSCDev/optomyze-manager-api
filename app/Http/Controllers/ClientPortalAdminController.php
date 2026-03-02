<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\ClientPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientPortalAdminController extends Controller
{
    public function __construct(private ClientPortalService $portalService) {}

    /**
     * GET /api/clients/{client}/portal
     *
     * Returns current portal configuration for a client.
     */
    public function status(Client $client): JsonResponse
    {
        return response()->json([
            'success' => true,
            'portal'  => [
                'enabled' => $client->portal_enabled,
                'slug'    => $client->portal_slug,
                'has_key' => !is_null($client->portal_key),
                'url'     => $client->portal_slug
                    ? $this->portalService->portalUrl($client)
                    : null,
            ],
        ]);
    }

    /**
     * POST /api/clients/{client}/portal/enable
     *
     * Enable the portal. Generates slug and key if not already set.
     * Returns the plain key once — store and share with the client immediately.
     */
    public function enable(Client $client): JsonResponse
    {
        $result = $this->portalService->enable($client);

        return response()->json([
            'success' => true,
            'message' => 'Portal enabled.',
            'portal'  => [
                'enabled'   => true,
                'slug'      => $result['slug'],
                'url'       => $result['url'],
                'plain_key' => $result['plain_key'], // ⚠ shown once — copy and send to client
            ],
        ]);
    }

    /**
     * POST /api/clients/{client}/portal/disable
     *
     * Disable the portal and invalidate all active sessions.
     */
    public function disable(Client $client): JsonResponse
    {
        $this->portalService->disable($client);

        return response()->json([
            'success' => true,
            'message' => 'Portal disabled. All active sessions have been revoked.',
        ]);
    }

    /**
     * POST /api/clients/{client}/portal/regenerate-key
     *
     * Issue a new key and revoke all existing portal sessions.
     * Returns the new plain key once — share with the client immediately.
     */
    public function regenerateKey(Client $client): JsonResponse
    {
        if (!$client->portal_slug) {
            return response()->json([
                'success' => false,
                'message' => 'Portal has not been enabled for this client yet.',
            ], 422);
        }

        $result = $this->portalService->regenerateKey($client);

        return response()->json([
            'success'   => true,
            'message'   => 'Key regenerated. All previous sessions have been revoked.',
            'plain_key' => $result['plain_key'], // ⚠ shown once — copy and send to client
            'url'       => $result['url'],
        ]);
    }

    /**
     * PATCH /api/clients/{client}/portal/slug
     *
     * Update the portal slug. Existing sessions remain valid.
     */
    public function updateSlug(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('clients', 'portal_slug')->ignore($client->id),
            ],
        ]);

        $this->portalService->updateSlug($client, $validated['slug']);
        $client->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Portal slug updated.',
            'slug'    => $client->portal_slug,
            'url'     => $this->portalService->portalUrl($client),
        ]);
    }
}
