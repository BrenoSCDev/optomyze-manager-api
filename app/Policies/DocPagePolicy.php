<?php

namespace App\Policies;

use App\Models\DocPage;
use App\Models\User;

class DocPagePolicy
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

    public function view(User $user, DocPage $page): bool
    {
        return $user->isActive();
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, DocPage $page): bool
    {
        // Any active user can edit — expand to creator-only if needed
        return $user->isActive();
    }

    public function delete(User $user, DocPage $page): bool
    {
        return $user->isActive() && $page->created_by === $user->id;
    }

    public function move(User $user, DocPage $page): bool
    {
        return $user->isActive() && $page->created_by === $user->id;
    }

    public function archive(User $user, DocPage $page): bool
    {
        return $user->isActive() && $page->created_by === $user->id;
    }

    public function restore(User $user, DocPage $page): bool
    {
        return $user->isActive() && $page->created_by === $user->id;
    }
}
