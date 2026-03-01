<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CrmCompanyService
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

    public function all(?bool $active = null): array
    {
        $query = $active !== null ? ['active' => $active] : [];
        return $this->client()->get('/api/manager/companies', $query)->json();
    }

    public function find(int $id): array
    {
        return $this->client()->get("/api/manager/companies/{$id}")->json();
    }

    public function create(array $data): array
    {
        return $this->client()->post('/api/manager/companies', $data)->json();
    }

    public function update(int $id, array $data): array
    {
        return $this->client()->put("/api/manager/companies/{$id}", $data)->json();
    }

    public function delete(int $id): array
    {
        return $this->client()->delete("/api/manager/companies/{$id}")->json();
    }

    public function restore(int $id): array
    {
        return $this->client()->post("/api/manager/companies/{$id}/restore")->json();
    }
}
