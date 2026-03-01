<?php

namespace App\Policies;

use App\Models\DriveFile;
use App\Models\User;

/**
 * Authorization policy for DriveFile.
 *
 * Follows the same upgrade path as DriveFolderPolicy.
 * See DriveFolderPolicy for detailed upgrade notes.
 */
class DriveFilePolicy
{
    /**
     * Admins bypass every policy check automatically.
     */
    public function before(User $user): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Any active user can list files.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Any active user can view file metadata.
     */
    public function view(User $user, DriveFile $file): bool
    {
        return $user->isActive();
    }

    /**
     * Any active user can upload files.
     */
    public function create(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Only the uploader (or admin via before()) can delete a file.
     */
    public function delete(User $user, DriveFile $file): bool
    {
        return $user->isActive() && $user->id === $file->uploaded_by;
    }

    /**
     * Only the uploader (or admin via before()) can move a file.
     */
    public function move(User $user, DriveFile $file): bool
    {
        return $user->isActive() && $user->id === $file->uploaded_by;
    }

    /**
     * Any active user can download a file they have access to.
     * Upgrade: restrict to company or client scope when multi-tenancy is added.
     */
    public function download(User $user, DriveFile $file): bool
    {
        return $user->isActive();
    }
}
