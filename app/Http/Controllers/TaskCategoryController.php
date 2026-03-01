<?php

namespace App\Http\Controllers;

use App\Models\TaskCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => TaskCategory::all()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string'
        ]);

        $category = TaskCategory::create($validated);

        return response()->json([
            'success' => true,
            'data' => $category
        ], 201);
    }

    public function show(TaskCategory $taskCategory): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $taskCategory
        ]);
    }

    public function update(Request $request, TaskCategory $taskCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string'
        ]);

        $taskCategory->update($validated);

        return response()->json([
            'success' => true,
            'data' => $taskCategory
        ]);
    }

    public function destroy(TaskCategory $taskCategory): JsonResponse
    {
        $taskCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task category deleted'
        ]);
    }
}
