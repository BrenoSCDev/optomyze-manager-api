<?php

namespace App\Http\Controllers;

use App\Models\ClientContract;
use App\Models\DriveFile;
use App\Models\DriveFolder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientPortalController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function client(Request $request)
    {
        return $request->attributes->get('portal_client');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Endpoints
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/portal/me
     *
     * Return the authenticated client's profile.
     */
    public function me(Request $request): JsonResponse
    {
        $client = $this->client($request);

        return response()->json([
            'success' => true,
            'data'    => $client->makeHidden(['portal_key', 'portal_slug', 'portal_enabled']),
        ]);
    }

    /**
     * GET /api/portal/tasks
     *
     * All tasks linked to this client, scoped strictly by client_id.
     */
    public function tasks(Request $request): JsonResponse
    {
        $client = $this->client($request);

        $tasks = $client->tasks()
            ->with(['category:id,name', 'assignees:id,name,avatar'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($task) => [
                'id'          => $task->id,
                'title'       => $task->title,
                'description' => $task->description,
                'priority'    => $task->priority,
                'due_date'    => $task->due_date,
                'category'    => $task->category?->name,
                'assignees'   => $task->assignees->map(fn ($u) => [
                    'name'   => $u->name,
                    'avatar' => $u->avatar,
                ]),
                'created_at'  => $task->created_at,
            ]);

        return response()->json(['success' => true, 'data' => $tasks]);
    }

    /**
     * GET /api/portal/payments
     *
     * Payment history for this client.
     */
    public function payments(Request $request): JsonResponse
    {
        $client = $this->client($request);

        $payments = $client->payments()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $payments]);
    }

    /**
     * GET /api/portal/drive
     *
     * Root-level folders and loose files belonging to this client.
     */
    public function drive(Request $request): JsonResponse
    {
        $client = $this->client($request);

        $folders = DriveFolder::with(['creator:id,name'])
            ->withCount(['subfolders', 'files'])
            ->where('client_id', $client->id)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $looseFiles = DriveFile::with('uploader:id,name')
            ->where('client_id', $client->id)
            ->whereNull('folder_id')
            ->orderBy('original_name')
            ->get();

        return response()->json([
            'success'     => true,
            'folders'     => $folders,
            'loose_files' => $looseFiles,
        ]);
    }

    /**
     * GET /api/portal/drive/folders/{id}
     *
     * Contents of a specific folder — verified to belong to this client.
     */
    public function folder(Request $request, int $id): JsonResponse
    {
        $client = $this->client($request);

        $folder = DriveFolder::where('id', $id)
            ->where('client_id', $client->id)
            ->firstOrFail();

        $folder->load([
            'subfolders' => fn ($q) => $q->withCount(['subfolders', 'files'])->orderBy('name'),
            'files'      => fn ($q) => $q->with('uploader:id,name')->orderBy('original_name'),
        ]);

        return response()->json(['success' => true, 'data' => $folder]);
    }

    /**
     * GET /api/portal/drive/files/{id}/preview
     *
     * Stream a file inline (for image / PDF preview) — verified to belong to this client.
     */
    public function previewFile(Request $request, int $id): StreamedResponse|JsonResponse
    {
        $client = $this->client($request);

        $file = DriveFile::where('id', $id)
            ->where('client_id', $client->id)
            ->firstOrFail();

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($file->disk);

        if (!$disk->exists($file->stored_name)) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        return $disk->response(
            $file->stored_name,
            $file->original_name,
            ['Content-Type' => $file->mime_type]
        );
    }

    /**
     * GET /api/portal/drive/files/{id}/download
     *
     * Force-download a file — verified to belong to this client.
     */
    public function downloadFile(Request $request, int $id): StreamedResponse|JsonResponse
    {
        $client = $this->client($request);

        $file = DriveFile::where('id', $id)
            ->where('client_id', $client->id)
            ->firstOrFail();

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($file->disk);

        if (!$disk->exists($file->stored_name)) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        return $disk->download(
            $file->stored_name,
            $file->original_name,
            ['Content-Type' => $file->mime_type]
        );
    }

    /**
     * GET /api/portal/contracts
     *
     * All contracts belonging to this client.
     */
    public function contracts(Request $request): JsonResponse
    {
        $client = $this->client($request);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        $contracts = $client->contracts()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($contract) => [
                'id'         => $contract->id,
                'name'       => $contract->name,
                'url'        => $disk->url($contract->path),
                'created_at' => $contract->created_at,
            ]);

        return response()->json(['success' => true, 'data' => $contracts]);
    }

    /**
     * GET /api/portal/contracts/{id}/download
     *
     * Force-download a contract — verified to belong to this client.
     */
    public function downloadContract(Request $request, int $id): StreamedResponse|JsonResponse
    {
        $client = $this->client($request);

        $contract = ClientContract::where('id', $id)
            ->where('client_id', $client->id)
            ->firstOrFail();

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if (!$disk->exists($contract->path)) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        return $disk->download($contract->path, $contract->name);
    }
}
