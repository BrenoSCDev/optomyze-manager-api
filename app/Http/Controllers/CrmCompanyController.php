<?php

namespace App\Http\Controllers;

use App\Services\CrmCompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmCompanyController extends Controller
{
    public function __construct(private CrmCompanyService $crm) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $active = match ($request->query('active')) {
                'true'  => true,
                'false' => false,
                default => null,
            };

            $companies = $this->crm->all($active);

            return response()->json([
                'success'   => true,
                'companies' => $companies,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch companies.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $company = $this->crm->find($id);

            return response()->json([
                'success' => true,
                'company' => $company,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch company.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'              => 'required|string|max:255',
                'email'             => 'required|email|max:255',
                'subscription_plan' => 'required|in:basic,premium,enterprise',
                'phone'             => 'nullable|string|max:50',
                'website'           => 'nullable|string|max:255',
                'product_module'    => 'nullable|in:product,ERP',
                'settings'          => 'nullable|array',
            ]);

            $company = $this->crm->create($validated);

            return response()->json([
                'success' => true,
                'company' => $company,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create company.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'              => 'sometimes|required|string|max:255',
                'email'             => 'sometimes|required|email|max:255',
                'subscription_plan' => 'sometimes|required|in:basic,premium,enterprise',
                'phone'             => 'nullable|string|max:50',
                'website'           => 'nullable|string|max:255',
                'product_module'    => 'nullable|in:product,ERP',
                'is_active'         => 'sometimes|boolean',
                'settings'          => 'nullable|array',
            ]);

            $company = $this->crm->update($id, $validated);

            return response()->json([
                'success' => true,
                'company' => $company,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update company.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->crm->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Company deleted successfully.',
                'data'    => $result,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete company.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function restore(int $id): JsonResponse
    {
        try {
            $company = $this->crm->restore($id);

            return response()->json([
                'success' => true,
                'message' => 'Company restored successfully.',
                'company' => $company,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore company.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
