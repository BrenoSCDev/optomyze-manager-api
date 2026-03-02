<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ClientPortalService
{
    /**
     * Enable the portal for a client.
     * Generates a slug (if missing) and a fresh key.
     * Returns the plain key — shown once and never again.
     */
    public function enable(Client $client): array
    {
        if (!$client->portal_slug) {
            $client->portal_slug = $this->uniqueSlug($client->company_name);
        }

        $plainKey = $this->issueKey($client);
        $client->portal_enabled = true;
        $client->save();

        Log::info('Client portal enabled.', ['client_id' => $client->id, 'slug' => $client->portal_slug]);

        return [
            'slug'      => $client->portal_slug,
            'plain_key' => $plainKey,
            'url'       => $this->portalUrl($client),
        ];
    }

    /**
     * Disable the portal and revoke all active portal sessions.
     */
    public function disable(Client $client): void
    {
        $client->update(['portal_enabled' => false]);
        $client->tokens()->where('name', 'portal')->delete();

        Log::info('Client portal disabled.', ['client_id' => $client->id]);
    }

    /**
     * Issue a new key, revoking all existing portal sessions.
     * Returns the plain key — shown once and never again.
     */
    public function regenerateKey(Client $client): array
    {
        $client->tokens()->where('name', 'portal')->delete();

        $plainKey = $this->issueKey($client);

        Log::info('Client portal key regenerated.', ['client_id' => $client->id]);

        return [
            'plain_key' => $plainKey,
            'url'       => $this->portalUrl($client),
        ];
    }

    /**
     * Update the portal slug (validates uniqueness before calling).
     */
    public function updateSlug(Client $client, string $slug): void
    {
        $client->update(['portal_slug' => Str::slug($slug)]);
    }

    /**
     * Check a plain key against the stored hash.
     */
    public function validateKey(Client $client, string $key): bool
    {
        if (!$client->portal_key || !$client->portal_enabled) {
            return false;
        }

        return Hash::check($key, $client->portal_key);
    }

    /**
     * Create a Sanctum session token scoped to portal abilities.
     */
    public function createSessionToken(Client $client): string
    {
        return $client->createToken('portal', ['portal'])->plainTextToken;
    }

    /**
     * Build the frontend portal URL for a client.
     */
    public function portalUrl(Client $client): string
    {
        // $base = rtrim(config('app.frontend_url', config('app.url')), '/');
        $base = 'http://localhost:8080'; // For local development; adjust as needed

        return "{$base}/portal/{$client->portal_slug}";
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Internals
    // ──────────────────────────────────────────────────────────────────────────

    private function issueKey(Client $client): string
    {
        $plain = Str::random(48);
        $client->portal_key = Hash::make($plain);
        $client->save();

        return $plain;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'client';
        $slug = $base;
        $i    = 2;

        while (Client::where('portal_slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
