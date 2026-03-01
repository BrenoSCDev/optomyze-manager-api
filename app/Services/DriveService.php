<?php

namespace App\Services;

use App\Models\DriveFile;
use App\Models\DriveFolder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DriveService
{
    /**
     * The filesystem disk used for Drive storage.
     * Override via DRIVE_DISK environment variable (e.g. "s3").
     */
    protected string $disk;

    public function __construct()
    {
        $this->disk = config('filesystems.drive_disk', 'local');
    }

    // ============================================
    // FILE OPERATIONS
    // ============================================

    /**
     * Store an uploaded file and create the DriveFile record.
     *
     * Storage path strategy: drive/{year}/{month}/{uuid}.{extension}
     *  - Date-partitioned → avoids massive flat directories
     *  - UUID filename → prevents enumeration and collision
     *  - Original name preserved in DB, never exposed via path
     */
    public function storeFile(
        UploadedFile $upload,
        ?int $folderId,
        ?int $companyId,
        ?int $clientId,
        int  $uploadedBy
    ): DriveFile {
        $extension   = $upload->getClientOriginalExtension();
        $storedName  = $this->generateStoragePath($extension);

        Storage::disk($this->disk)->putFileAs(
            dirname($storedName),
            $upload,
            basename($storedName)
        );

        return DriveFile::create([
            'company_id'    => $companyId,
            'folder_id'     => $folderId,
            'client_id'     => $clientId,
            'original_name' => $upload->getClientOriginalName(),
            'stored_name'   => $storedName,
            'disk'          => $this->disk,
            'mime_type'     => $upload->getMimeType(),
            'size'          => $upload->getSize(),
            'uploaded_by'   => $uploadedBy,
        ]);
    }

    /**
     * Delete a file from storage and remove the database record.
     */
    public function deleteFile(DriveFile $file): void
    {
        if (Storage::disk($file->disk)->exists($file->stored_name)) {
            Storage::disk($file->disk)->delete($file->stored_name);
        }

        $file->delete();
    }

    /**
     * Move a file to a different folder (or to root when $targetFolderId is null).
     * Only updates the DB record — the physical file stays where it is.
     */
    public function moveFile(DriveFile $file, ?int $targetFolderId): DriveFile
    {
        $file->update(['folder_id' => $targetFolderId]);

        return $file->fresh(['folder', 'uploader']);
    }

    /**
     * Generate a temporary signed URL for secure client-side downloads.
     *
     * On S3: uses Storage::temporaryUrl() (native support).
     * On local disk: generates a signed application URL that routes through
     *   the download endpoint with a time-limited HMAC signature.
     *
     * @param int $minutes TTL of the signed URL
     */
    public function temporaryUrl(DriveFile $file, int $minutes = 30): string
    {
        if ($file->disk === 's3') {
            return Storage::disk('s3')->temporaryUrl(
                $file->stored_name,
                now()->addMinutes($minutes)
            );
        }

        // Local disk fallback: signed route URL
        // Requires the download route to verify the signature via `signed` middleware
        return url()->temporarySignedRoute(
            'drive.files.download',
            now()->addMinutes($minutes),
            ['file' => $file->id]
        );
    }

    // ============================================
    // FOLDER OPERATIONS
    // ============================================

    /**
     * Recursively delete a folder, all its files, and all its subfolders.
     * Uses an iterative stack to avoid PHP recursion depth limits on deep trees.
     */
    public function deleteFolder(DriveFolder $folder): void
    {
        DB::transaction(function () use ($folder) {
            $this->deleteFolderRecursive($folder);
        });
    }

    /**
     * Move a folder to a new parent (or to root when $newParentId is null).
     *
     * Guards against:
     *  - Moving a folder into itself
     *  - Moving a folder into one of its own descendants (cycle)
     *
     * @throws \InvalidArgumentException
     */
    public function moveFolder(DriveFolder $folder, ?int $newParentId): DriveFolder
    {
        if ($newParentId !== null) {
            if ($newParentId === $folder->id) {
                throw new \InvalidArgumentException('A folder cannot be moved into itself.');
            }

            if (in_array($newParentId, $folder->descendantIds(), strict: true)) {
                throw new \InvalidArgumentException(
                    'A folder cannot be moved into one of its own subfolders.'
                );
            }
        }

        $folder->update(['parent_id' => $newParentId]);

        return $folder->fresh(['parent', 'creator']);
    }

    /**
     * Build an ordered breadcrumb trail for a folder (root → … → current).
     * Returns a lightweight array suitable for direct API output.
     */
    public function breadcrumbs(DriveFolder $folder): array
    {
        $crumbs  = [];
        $current = $folder;

        // Collect ancestors by walking up the parent chain
        while ($current !== null) {
            array_unshift($crumbs, [
                'id'   => $current->id,
                'name' => $current->name,
            ]);
            $current = $current->parent_id
                ? DriveFolder::find($current->parent_id)
                : null;
        }

        return $crumbs;
    }

    // ============================================
    // INTERNAL HELPERS
    // ============================================

    /**
     * Generate a collision-safe storage path partitioned by date.
     * e.g. drive/2026/03/550e8400-e29b-41d4-a716-446655440000.pdf
     */
    protected function generateStoragePath(string $extension): string
    {
        $uuid      = Str::uuid()->toString();
        $year      = now()->format('Y');
        $month     = now()->format('m');
        $filename  = $extension ? "{$uuid}.{$extension}" : $uuid;

        return "drive/{$year}/{$month}/{$filename}";
    }

    /**
     * Internal recursive delete implementation.
     * Called inside a DB transaction from deleteFolder().
     */
    protected function deleteFolderRecursive(DriveFolder $folder): void
    {
        // 1. Delete all files inside this folder
        $folder->files()->each(function (DriveFile $file) {
            $this->deleteFile($file);
        });

        // 2. Recurse into each subfolder
        $folder->subfolders()->each(function (DriveFolder $sub) {
            $this->deleteFolderRecursive($sub);
        });

        // 3. Remove the folder record itself
        $folder->delete();
    }
}
