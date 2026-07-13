<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TaskTag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class TaskController extends Controller
{
    public function index(): JsonResponse
    {
        $tasks = Task::with(['category', 'client', 'assignees'])->get();
        return response()->json($tasks);
    }

    public function tasksGroupedByCategory(Request $request): JsonResponse
    {
        // archived filter: 'active' (default) | 'archived' | 'all'
        $archived = $request->query('archived', 'active');

        $categories = TaskCategory::with([
            'tasks' => function ($query) use ($archived) {
                $query->with(['client', 'assignees', 'docs']);

                if ($archived === 'archived') {
                    $query->archived();
                } elseif ($archived !== 'all') {
                    $query->notArchived();
                }
            }
        ])->get();

        $users   = User::where('status', 'active')->get();
        $clients = Client::where('status', 'active')->get();
        $tags    = TaskTag::all();

        $response = $categories->map(function ($category) {
            return [
                'task_category_id' => $category->id,
                'category_name'    => $category->name,
                'tasks'            => $category->tasks->map(function ($task) {
                    return [
                        'id'          => $task->id,
                        'title'       => $task->title,
                        'description' => $task->description,
                        'tags'        => $task->tags,
                        'assignees'   => $task->assignees->map(fn ($u) => [
                            'id'     => $u->id,
                            'name'   => $u->name,
                            'avatar' => $u->avatar,
                            'title'  => $u->title,
                        ]),
                        'priority'    => $task->priority,
                        'due_date'    => $task->due_date,
                        'client'      => $task->client ? [
                            'id'           => $task->client->id,
                            'company_name' => $task->client->company_name,
                        ] : null,
                        'docs'        => $task->docs->map(fn ($doc) => [
                            'id'         => $doc->id,
                            'name'       => $doc->name,
                            'path'       => $doc->path,
                            'created_at' => $doc->created_at,
                        ]),
                        'archived_at' => $task->archived_at,
                        'created_at'  => $task->created_at,
                        'updated_at'  => $task->updated_at,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'users'   => $users,
            'clients' => $clients,
            'tags'    => $tags,
            'data'    => $response,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'task_category_id' => 'required|exists:task_categories,id',
            'client_id'        => 'nullable|exists:clients,id',
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'assignee_ids'     => 'nullable|array',
            'assignee_ids.*'   => 'integer|exists:users,id',
            'priority'         => 'required|in:low,medium,high,urgent',
            'due_date'         => 'nullable|date',
        ]);

        $task = Task::create([
            'task_category_id' => $validated['task_category_id'],
            'client_id'        => $validated['client_id'] ?? null,
            'title'            => $validated['title'],
            'description'      => $validated['description'] ?? null,
            'priority'         => $validated['priority'],
            'due_date'         => $validated['due_date'] ?? null,
        ]);

        $task->assignees()->sync($validated['assignee_ids'] ?? []);
        $task->load(['assignees', 'client']);

        // Fire webhook for each assignee
        foreach ($task->assignees as $assignee) {
            Http::post(
                'https://optomyze-n8n.kmfrpu.easypanel.host/webhook/c8c7a3fd-d623-4c95-94cd-08c8ed25a767',
                [
                    'event'    => 'task_created',
                    'task'     => [
                        'id'       => $task->id,
                        'title'    => $task->title,
                        'priority' => $task->priority,
                        'due_date' => $task->due_date,
                    ],
                    'assignee' => [
                        'id'    => $assignee->id,
                        'name'  => $assignee->name,
                        'email' => $assignee->email,
                        'phone' => $assignee->phone,
                    ],
                    'message'  => sprintf(
                        'Uma nova tarefa foi atribuída a você no Optomyze Manager: "%s". Acesse https://manager.optomyze.io/tasks para visualizar os detalhes.',
                        $task->title
                    ),
                ]
            );
        }

        return response()->json([
            'success' => true,
            'task'    => $task,
        ], 201);
    }

    public function show(Task $task): JsonResponse
    {
        $task->load(['category', 'client', 'assignees']);
        return response()->json($task);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'task_category_id' => 'required|exists:task_categories,id',
            'client_id'        => 'nullable|exists:clients,id',
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'assignee_ids'     => 'nullable|array',
            'assignee_ids.*'   => 'integer|exists:users,id',
            'priority'         => 'required|in:low,medium,high,urgent',
            'due_date'         => 'nullable|date',
        ]);

        $task->update([
            'task_category_id' => $validated['task_category_id'],
            'client_id'        => $validated['client_id'] ?? null,
            'title'            => $validated['title'],
            'description'      => $validated['description'] ?? null,
            'priority'         => $validated['priority'],
            'due_date'         => $validated['due_date'] ?? null,
        ]);

        $task->assignees()->sync($validated['assignee_ids'] ?? []);
        $task->load(['assignees', 'client']);

        // Fire webhook for each assignee
        foreach ($task->assignees as $assignee) {
            Http::post(
                'https://optomyze-n8n.kmfrpu.easypanel.host/webhook/c8c7a3fd-d623-4c95-94cd-08c8ed25a767',
                [
                    'event'    => 'task_updated',
                    'task'     => [
                        'id'       => $task->id,
                        'title'    => $task->title,
                        'priority' => $task->priority,
                        'due_date' => $task->due_date,
                    ],
                    'assignee' => [
                        'id'    => $assignee->id,
                        'name'  => $assignee->name,
                        'email' => $assignee->email,
                        'phone' => $assignee->phone,
                    ],
                    'message'  => sprintf(
                        'Sua tarefa foi atualizada no Optomyze Manager: "%s". Acesse https://manager.optomyze.io/tasks para visualizar os detalhes.',
                        $task->title
                    ),
                ]
            );
        }

        return response()->json([
            'success' => true,
            'task'    => $task,
        ]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->delete();
        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully.',
        ]);
    }

    public function moveCategory(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'task_category_id' => 'required|exists:task_categories,id',
        ]);

        $task->update(['task_category_id' => $validated['task_category_id']]);

        return response()->json([
            'success' => true,
            'task'    => $task->fresh(),
        ]);
    }

    public function archive(Task $task): JsonResponse
    {
        $task->update(['archived_at' => now()]);

        return response()->json([
            'success' => true,
            'task'    => $task->fresh(),
        ]);
    }

    public function unarchive(Task $task): JsonResponse
    {
        $task->update(['archived_at' => null]);

        return response()->json([
            'success' => true,
            'task'    => $task->fresh(),
        ]);
    }

    public function archiveCategory($categoryId): JsonResponse
    {
        $count = Task::where('task_category_id', $categoryId)
            ->notArchived()
            ->update(['archived_at' => now()]);

        return response()->json([
            'success' => true,
            'count'   => $count,
            'message' => 'Tasks archived successfully.',
        ]);
    }

    public function unarchiveCategory($categoryId): JsonResponse
    {
        $count = Task::where('task_category_id', $categoryId)
            ->archived()
            ->update(['archived_at' => null]);

        return response()->json([
            'success' => true,
            'count'   => $count,
            'message' => 'Tasks unarchived successfully.',
        ]);
    }

    public function tasksByCategory($categoryId): JsonResponse
    {
        $tasks = Task::where('task_category_id', $categoryId)
            ->with(['category', 'client', 'assignees'])
            ->get();

        return response()->json($tasks);
    }

    public function updateTags(Request $request, Task $task): JsonResponse
    {
        $data = $request->validate([
            'tags'   => 'nullable|array',
            'tags.*' => 'string|max:255',
        ]);

        $task->tags = empty($data['tags']) ? null : $data['tags'];
        $task->save();

        return response()->json([
            'message' => 'Tags updated successfully.',
            'task'    => $task,
        ]);
    }
}
