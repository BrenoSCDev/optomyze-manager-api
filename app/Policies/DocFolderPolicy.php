<?php

namespace App\Policies;

use App\Models\DocFolder;
use App\Models\User;

class DocFolderPolicy
{
    /** Admins bypass all checks. */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin() && $user->isActive()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, DocFolder $folder): bool
    {
        return $user->isActive();
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, DocFolder $folder): bool
    {
        return $user->isActive() && $folder->created_by === $user->id;
    }

    public function delete(User $user, DocFolder $folder): bool
    {
        return $user->isActive() && $folder->created_by === $user->id;
    }

    public function move(User $user, DocFolder $folder): bool
    {
        return $user->isActive() && $folder->created_by === $user->id;
    }
}
