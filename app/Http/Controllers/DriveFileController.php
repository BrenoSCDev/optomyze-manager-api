<?php

namespace App\Http\Controllers;

use App\Http\Requests\Drive\MoveFileRequest;
use App\Http\Requests\Drive\StoreFileRequest;
use App\Models\DriveFile;
use App\Models\DriveFolder;
use App\Services\DriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DriveFileController extends Controller
{
    public function __construct(protected DriveService $driveService)
    {
    }

    /**
     * POST /api/drive/files
     *
     * Upload a file into the Drive.
     * Optionally associate with a folder and/or a client.
     *
     * Large file handling note:
     *  - For files > 50 MB consider multipart / chunked uploads via a dedicated
     *    endpoint and reassemble on the server, or use direct S3 presigned POSTs.
     *  - Increase `upload_max_filesize` and `post_max_size` in php.ini accordingly.
     *  - Tune the `max` rule in StoreFileRequest to match your php.ini limits.
     */
    public function store(StoreFileRequest $request): JsonResponse
    {
        $this->authorize('create', DriveFile::class);

        $file = $this->driveService->storeFile(
            upload:     $request->file('file'),
            folderId:   $request->input('folder_id'),
            companyId:  null, // set to $request->user()->company_id when available
            clientId:   $request->input('client_id'),
            uploadedBy: $request->user()->id,
        );

        $file->load(['folder:id,name', 'uploader:id,name', 'client:id,company_name']);
        $file->append('human_size');

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully.',
            'data'    => $file,
        ], 201);
    }

    /**
     * GET /api/drive/files/{id}
     *
     * Return file metadata. Physical path (stored_name) is always hidden.
     */
    public function show(DriveFile $file): JsonResponse
    {
        $this->authorize('view', $file);

        $file->load(['folder:id,name', 'uploader:id,name', 'client:id,company_name']);
        $file->append('human_size');

        return response()->json([
            'success' => true,
            'data'    => $file,
        ]);
    }

    /**
     * GET /api/drive/files/{id}/download
     *
     * Stream the file to the client with its original filename.
     * Route is named "drive.files.download" for signed URL generation.
     *
     * The file is never directly accessible via public URL — all downloads
     * pass through this policy-checked endpoint.
     */
    public function download(DriveFile $file): StreamedResponse
    {
        $this->authorize('download', $file);

        if (! Storage::disk($file->disk)->exists($file->stored_name)) {
            abort(404, 'File not found on storage.');
        }

        return Storage::disk($file->disk)->download(
            $file->stored_name,
            $file->original_name,
            ['Content-Type' => $file->mime_type]
        );
    }

    /**
     * DELETE /api/drive/files/{id}
     *
     * Remove the file from storage and delete the database record.
     */
    public function destroy(DriveFile $file): JsonResponse
    {
        $this->authorize('delete', $file);

        $this->driveService->deleteFile($file);

        return response()->json([
            'success' => true,
            'message' => 'File deleted successfully.',
        ]);
    }

    /**
     * PATCH /api/drive/files/{id}/move
     *
     * Move a file to a different folder.
     * Send { "folder_id": null } to move to root (no folder).
     */
    public function move(MoveFileRequest $request, DriveFile $file): JsonResponse
    {
        $this->authorize('move', $file);

        $updated = $this->driveService->moveFile($file, $request->input('folder_id'));
        $updated->append('human_size');

        return response()->json([
            'success' => true,
            'message' => 'File moved successfully.',
            'data'    => $updated,
        ]);
    }

    /**
     * GET /api/drive/files/{id}/url
     *
     * Generate a time-limited signed download URL.
     * Useful for sharing with clients without exposing the download endpoint session.
     *
     * On local disk: returns a signed route URL (valid for 30 minutes).
     * On S3: returns a native presigned URL.
     */
    public function temporaryUrl(DriveFile $file): JsonResponse
    {
        $this->authorize('download', $file);

        $url = $this->driveService->temporaryUrl($file, minutes: 30);

        return response()->json([
            'success'    => true,
            'url'        => $url,
            'expires_in' => 30, // minutes
        ]);
    }
}
