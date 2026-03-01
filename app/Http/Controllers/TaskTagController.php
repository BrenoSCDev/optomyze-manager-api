<?php

namespace App\Http\Controllers;

use App\Models\TaskTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskTagController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => TaskTag::all()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $tag = TaskTag::create($validated);

        return response()->json([
            'success' => true,
            'data' => $tag
        ], 201);
    }

    public function show(TaskTag $taskTag): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $taskTag
        ]);
    }

    public function update(Request $request, TaskTag $taskTag): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $taskTag->update($validated);

        return response()->json([
            'success' => true,
            'data' => $taskTag
        ]);
    }

    public function destroy(TaskTag $taskTag): JsonResponse
    {
        $taskTag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Client tag deleted'
        ]);
    }
}
