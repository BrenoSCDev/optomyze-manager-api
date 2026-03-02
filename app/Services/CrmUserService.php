<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CrmUserService
{
    private string $baseUrl;
    private string $managerKey;

    public function __construct()
    {
        $this->baseUrl    = rtrim(config('services.crm.url'), '/');
        $this->managerKey = config('app.manager_key');
    }

    private function client()
    {
        return Http::withHeaders([
            'X-Manager-Key' => $this->managerKey,
            'Accept'        => 'application/json',
        ])->baseUrl($this->baseUrl);
    }

    public function all(int $companyId, ?bool $active = null, ?string $role = null): array
    {
        $query = ['company_id' => $companyId];

        if ($active !== null) {
            $query['active'] = $active;
        }

        if ($role !== null) {
            $query['role'] = $role;
        }

        return $this->client()->get('/api/manager/users', $query)->json();
    }

    public function find(int $id): array
    {
        return $this->client()->get("/api/manager/users/{$id}")->json();
    }

    public function create(array $data): array
    {
        return $this->client()->post('/api/manager/users', $data)->json();
    }

    public function update(int $id, array $data): array
    {
        return $this->client()->put("/api/manager/users/{$id}", $data)->json();
    }

    public function deactivate(int $id): array
    {
        return $this->client()->patch("/api/manager/users/{$id}/deactivate")->json();
    }

    public function delete(int $id): array
    {
        return $this->client()->delete("/api/manager/users/{$id}")->json();
    }
}
