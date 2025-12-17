<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\OrgDoc;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(): JsonResponse
    {
        $users = User::with('department')->get();
        $departments = Department::withCount('users')->get();
        $orgDocs = OrgDoc::all();

        return response()->json([
            'success' => true,
            'meta' => [
                'total' => $users->count(),
                'users' => $users,
                'departments' => $departments,
                'org_docs' => $orgDocs,
            ],
        ]);
    }


    /**
     * Store a newly created user
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,agent',
            'status' => 'nullable|in:active,inactive,suspended',
            'title' => 'nullable|string|max:255',
            'phone_secondary' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:2',
            'start_date' => 'nullable|date',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status ?? 'active',
            'title' => $request->title,
            'department_id' => $request->department_id,
            'department' => $request->department,
            'phone_secondary' => $request->phone_secondary,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'start_date' => $request->start_date,
        ]);

        $user->load('department');

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user,
        ], 201);
    }

    /**
     * Display the specified user
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255'],
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'sometimes|required|in:admin,agent',
            'status' => 'nullable|in:active,inactive,suspended',
            'title' => 'nullable|string|max:255',
            'phone_secondary' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:2',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'department_id' => 'nullable|exists:departments,id'
        ]);

        $user->update($validated);

        $user->load('department');

        return response()->json([
            'success' => true,
            'message' => 'User updated usccessfully',
            'data' => $user,
        ]);
    }

    /**
     * Remove the specified user (soft delete)
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Restore a soft deleted user
     */
    public function restore(int $id): JsonResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return response()->json([
            'success' => true,
            'message' => 'User restored successfully',
            'data' => $user,
        ]);
    }

    /**
     * Get all active agents for assignment
     */
    public function agents(): JsonResponse
    {
        $agents = User::agents()->active()->get(['id', 'name', 'email', 'avatar', 'title']);

        return response()->json([
            'success' => true,
            'data' => $agents,
        ]);
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'preferences' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->update([
            'preferences' => array_merge($user->preferences ?? [], $request->preferences),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully',
            'data' => $user->preferences,
        ]);
    }
}