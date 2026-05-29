<?php

namespace App\Http\Controllers;

use App\Models\ClientContract;
use App\Models\DriveFile;
use App\Models\DriveFolder;
use App\Services\DriveService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
            ->with(['category:id,name,type', 'assignees:id,name,avatar'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($task) => [
                'id'            => $task->id,
                'title'         => $task->title,
                'description'   => $task->description,
                'priority'      => $task->priority,
                'due_date'      => $task->due_date,
                'category'      => $task->category?->name,
                'category_type' => $task->category?->type,
                'assignees'     => $task->assignees->map(fn ($u) => [
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
            ->where(function ($q) use ($client) {
                $q->where('client_id', $client->id)
                  ->orWhereHas('folder', fn($fq) => $fq->where('client_id', $client->id));
            })
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
            ->where(function ($q) use ($client) {
                $q->where('client_id', $client->id)
                  ->orWhereHas('folder', fn($fq) => $fq->where('client_id', $client->id));
            })
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
     * POST /api/portal/drive/files/bulk/download
     *
     * Download multiple files as a zip — all verified to belong to this client.
     */
    public function bulkDownloadFiles(Request $request): BinaryFileResponse|JsonResponse
    {
        $client = $this->client($request);

        $fileIds = $request->input('file_ids', []);

        $files = DriveFile::whereIn('id', $fileIds)
            ->where(function ($q) use ($client) {
                $q->where('client_id', $client->id)
                  ->orWhereHas('folder', fn($fq) => $fq->where('client_id', $client->id));
            })
            ->get();

        if ($files->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No accessible files found.'], 404);
        }

        $zipPath = app(DriveService::class)->bulkDownloadZip($files);

        return response()
            ->download($zipPath, 'files.zip', ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    /**
     * GET /api/portal/contracts
     *
     * All contracts belonging to this client.
     */
    public function contracts(Request $request): JsonResponse
    {
        $client = $this->client($request);

        $contracts = $client->contracts()
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'created_at']);

        return response()->json(['success' => true, 'data' => $contracts]);
    }

    /**
     * GET /api/portal/events
     *
     * Calendar events linked to this client.
     */
    public function events(Request $request): JsonResponse
    {
        $client = $this->client($request);

        $events = \App\Models\CalendarEvent::where('client_id', $client->id)
            ->orderBy('start_datetime')
            ->get(['id', 'title', 'description', 'location', 'start_datetime', 'end_datetime', 'is_all_day', 'color']);

        return response()->json(['success' => true, 'data' => $events]);
    }

    /**
     * GET /api/portal/payments/{id}/file/preview
     *
     * Stream a payment transaction file inline — verified to belong to this client.
     */
    public function previewPaymentFile(Request $request, int $id): StreamedResponse|JsonResponse
    {
        $client = $this->client($request);

        $payment = $client->payments()->findOrFail($id);

        if (!$payment->transaction_file) {
            return response()->json(['success' => false, 'message' => 'No file attached to this payment.'], 404);
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        if (!$disk->exists($payment->transaction_file)) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        return $disk->response(
            $payment->transaction_file,
            basename($payment->transaction_file),
            ['Content-Type' => $disk->mimeType($payment->transaction_file)]
        );
    }

    /**
     * GET /api/portal/payments/{id}/file/download
     *
     * Force-download a payment transaction file — verified to belong to this client.
     */
    public function downloadPaymentFile(Request $request, int $id): StreamedResponse|JsonResponse
    {
        $client = $this->client($request);

        $payment = $client->payments()->findOrFail($id);

        if (!$payment->transaction_file) {
            return response()->json(['success' => false, 'message' => 'No file attached to this payment.'], 404);
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        if (!$disk->exists($payment->transaction_file)) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        return $disk->download($payment->transaction_file, basename($payment->transaction_file));
    }

    /**
     * GET /api/portal/contracts/{id}/preview
     *
     * Stream a contract inline (for PDF / image preview) — verified to belong to this client.
     */
    public function previewContract(Request $request, int $id): StreamedResponse|JsonResponse
    {
        $client = $this->client($request);

        $contract = ClientContract::where('id', $id)
            ->where('client_id', $client->id)
            ->firstOrFail();

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        if (!$disk->exists($contract->path)) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        return $disk->response(
            $contract->path,
            $contract->name,
            ['Content-Type' => $disk->mimeType($contract->path)]
        );
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
        $disk = Storage::disk('local');

        if (!$disk->exists($contract->path)) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        return $disk->download($contract->path, $contract->name);
    }
}
