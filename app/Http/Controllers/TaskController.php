<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    public function index(): JsonResponse
    {
        $tasks = Task::with(['category', 'client', 'assignee'])->get();
        return response()->json($tasks);
    }

    public function tasksGroupedByCategory(): JsonResponse
    {
        $categories = TaskCategory::with([
            'tasks' => function ($query) {
                $query->with(['client', 'assignee', 'docs']);
            }
        ])->get();

        $users = User::where('status', 'active')->get();
        $clients = Client::where('status', 'active')->get();

        // Format response as category => tasks
        $response = $categories->map(function ($category) {
        return [
            'task_category_id' => $category->id,
            'category_name' => $category->name,
            'tasks' => $category->tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'assignee' => $task->assignee ? [
                        'id' => $task->assignee->id,
                        'name' => $task->assignee->name
                    ] : null,
                    'priority' => $task->priority,
                    'due_date' => $task->due_date,
                    'client' => $task->client ? [
                        'id' => $task->client->id,
                        'company_name' => $task->client->company_name
                    ] : null,

                    // ✅ NEW — Include docs
                    'docs' => $task->docs->map(function ($doc) {
                        return [
                            'id' => $doc->id,
                            'name' => $doc->name,
                            'path' => $doc->path,
                            'created_at' => $doc->created_at,
                        ];
                    }),

                    'created_at' => $task->created_at,
                    'updated_at' => $task->updated_at,
                ];
            })
        ];
        });

        return response()->json([
            'success' => true,
            'users' => $users,
            'clients' => $clients,
            'data' => $response
        ]);
    }


    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'task_category_id' => 'required|exists:task_categories,id',
            'client_id' => 'nullable|exists:clients,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|exists:users,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
        ]);

        $task = Task::create($validated);

        $task->load(['assignee', 'client']);

        return response()->json([
            'success' => true,
            'task' => $task
        ], 201);
    }

    public function show(Task $task): JsonResponse
    {
        $task->load(['category', 'client', 'assignee']);
        return response()->json($task);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'task_category_id' => 'required|exists:task_categories,id',
            'client_id' => 'nullable|exists:clients,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|exists:users,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
        ]);

        $task->update($validated);

        $task->load(['assignee', 'client']);

        return response()->json([
            'success' => true,
            'task' => $task
        ]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->delete();
        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully.'
        ]);
    }

    public function tasksByCategory($categoryId): JsonResponse
    {
        $tasks = Task::where('task_category_id', $categoryId)
                    ->with(['category', 'client', 'assignee'])
                    ->get();

        return response()->json($tasks);
    }
}
