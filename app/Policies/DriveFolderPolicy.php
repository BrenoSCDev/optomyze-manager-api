<?php

namespace App\Policies;

use App\Models\DriveFolder;
use App\Models\User;

/**
 * Authorization policy for DriveFolder.
 *
 * Current behavior (single-tenant):
 *  - Any authenticated internal user (admin or agent) can perform all operations.
 *  - Admins additionally bypass all checks via the `before` hook.
 *
 * Multi-tenancy upgrade path:
 *  - Add `$user->company_id === $folder->company_id` checks when company scoping
 *    is introduced on the User model.
 *
 * Client portal upgrade path:
 *  - Guard a separate client auth with a different ability check and limit
 *    access to folders where `$folder->client_id === $client->id`.
 */
class DriveFolderPolicy
{
    /**
     * Admins bypass every policy check automatically.
     */
    public function before(User $user): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null; // Defer to individual methods for non-admins
    }

    /**
     * Any authenticated agent can list folders.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Any authenticated agent can view a specific folder.
     *
     * Upgrade: Add `&& $user->company_id === $folder->company_id`
     */
    public function view(User $user, DriveFolder $folder): bool
    {
        return $user->isActive() && $folder->exists;
    }

    /**
     * Any active user can create folders.
     */
    public function create(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Only the creator (or admin via before()) can rename a folder.
     */
    public function update(User $user, DriveFolder $folder): bool
    {
        return $user->isActive() && $user->id === $folder->created_by;
    }

    /**
     * Only the creator (or admin via before()) can delete a folder.
     */
    public function delete(User $user, DriveFolder $folder): bool
    {
        return $user->isActive() && $user->id === $folder->created_by;
    }

    /**
     * Only the creator (or admin via before()) can move a folder.
     */
    public function move(User $user, DriveFolder $folder): bool
    {
        return $user->isActive() && $user->id === $folder->created_by;
    }
}
