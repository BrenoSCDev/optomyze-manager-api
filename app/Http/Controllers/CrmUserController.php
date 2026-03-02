<?php

namespace App\Http\Controllers;

use App\Services\CrmUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmUserController extends Controller
{
    public function __construct(private CrmUserService $crm) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'company_id' => 'required|integer',
                'active'     => 'sometimes|in:true,false',
                'role'       => 'sometimes|in:admin,manager,agent',
            ]);

            $active = match ($request->query('active')) {
                'true'  => true,
                'false' => false,
                default => null,
            };

            $users = $this->crm->all(
                (int) $request->query('company_id'),
                $active,
                $request->query('role'),
            );

            return response()->json([
                'success' => true,
                'users'   => $users,
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
                'message' => 'Failed to fetch users.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->crm->find($id);

            return response()->json([
                'success' => true,
                'user'    => $user,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'company_id' => 'required|integer',
                'name'       => 'required|string|max:255',
                'email'      => 'required|email|max:255',
                'phone'      => 'nullable|string|max:20',
                'role'       => 'required|in:admin,manager,agent',
                'is_active'  => 'sometimes|boolean',
                'password'   => 'sometimes|string|min:8',
            ]);

            $user = $this->crm->create($validated);

            return response()->json([
                'success' => true,
                'user'    => $user,
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
                'message' => 'Failed to create user.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'      => 'sometimes|string|max:255',
                'email'     => 'sometimes|email|max:255',
                'phone'     => 'nullable|string|max:20',
                'role'      => 'sometimes|in:admin,manager,agent',
                'is_active' => 'sometimes|boolean',
                'password'  => 'sometimes|string|min:8',
            ]);

            $user = $this->crm->update($id, $validated);

            return response()->json([
                'success' => true,
                'user'    => $user,
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
                'message' => 'Failed to update user.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function deactivate(int $id): JsonResponse
    {
        try {
            $result = $this->crm->deactivate($id);

            return response()->json([
                'success' => true,
                'message' => 'User deactivated successfully.',
                'data'    => $result,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate user.',
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
                'message' => 'User deleted successfully.',
                'data'    => $result,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
