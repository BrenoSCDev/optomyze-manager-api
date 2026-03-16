<?php

namespace App\Services;

use App\Models\DocFolder;
use App\Models\DocPage;
use Illuminate\Support\Facades\DB;

class DocService
{
    // ============================================================
    // PAGE OPERATIONS
    // ============================================================

    /**
     * Recursively archive a page and all its descendants.
     */
    public function archivePage(DocPage $page): void
    {
        DB::transaction(function () use ($page) {
            $this->archiveRecursive($page);
        });
    }

    /**
     * Restore a single page from the archive.
     * Does NOT automatically restore descendant pages.
     */
    public function restorePage(DocPage $page): DocPage
    {
        $page->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);

        return $page->fresh();
    }

    /**
     * Permanently delete a page and all its descendants.
     * Only callable on already-archived pages (trash → permanent delete).
     */
    public function deletePage(DocPage $page): void
    {
        DB::transaction(function () use ($page) {
            $this->deletePageRecursive($page);
        });
    }

    /**
     * Move a page to a different folder and/or parent page.
     *
     * Guards against:
     *  - Moving a page into itself
     *  - Moving a page into one of its own descendants (cycle)
     *
     * @throws \InvalidArgumentException
     */
    public function movePage(DocPage $page, ?int $newParentId, ?int $newFolderId): DocPage
    {
        if ($newParentId !== null) {
            if ($newParentId === $page->id) {
                throw new \InvalidArgumentException('A page cannot be moved into itself.');
            }

            if (in_array($newParentId, $page->descendantIds(), strict: true)) {
                throw new \InvalidArgumentException(
                    'A page cannot be moved into one of its own sub-pages.'
                );
            }
        }

        $page->update([
            'parent_id' => $newParentId,
            'folder_id' => $newFolderId,
        ]);

        return $page->fresh(['folder', 'parent', 'creator']);
    }

    /**
     * Toggle the favorite status of a page for a user.
     * Returns true if now favorited, false if unfavorited.
     */
    public function toggleFavorite(DocPage $page, int $userId): bool
    {
        $existing = DB::table('doc_favorites')
            ->where('user_id', $userId)
            ->where('page_id', $page->id)
            ->first();

        if ($existing) {
            DB::table('doc_favorites')
                ->where('user_id', $userId)
                ->where('page_id', $page->id)
                ->delete();

            return false;
        }

        DB::table('doc_favorites')->insert([
            'user_id'    => $userId,
            'page_id'    => $page->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    /**
     * Full-text search across page titles and content.
     * Returns non-archived pages whose title or content matches $query.
     */
    public function search(string $query): \Illuminate\Database\Eloquent\Collection
    {
        return DocPage::notArchived()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhereRaw("JSON_SEARCH(content, 'all', ?, null, '$.**.text') IS NOT NULL", ["%{$query}%"]);
            })
            ->with(['folder:id,name', 'creator:id,name'])
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();
    }

    // ============================================================
    // FOLDER OPERATIONS
    // ============================================================

    /**
     * Recursively delete a folder, all its sub-folders, and all their pages.
     */
    public function deleteFolder(DocFolder $folder): void
    {
        DB::transaction(function () use ($folder) {
            $this->deleteFolderRecursive($folder);
        });
    }

    /**
     * Move a folder to a new parent (or to root when $newParentId is null).
     *
     * @throws \InvalidArgumentException
     */
    public function moveFolder(DocFolder $folder, ?int $newParentId): DocFolder
    {
        if ($newParentId !== null) {
            if ($newParentId === $folder->id) {
                throw new \InvalidArgumentException('A folder cannot be moved into itself.');
            }

            if (in_array($newParentId, $folder->descendantIds(), strict: true)) {
                throw new \InvalidArgumentException(
                    'A folder cannot be moved into one of its own sub-folders.'
                );
            }
        }

        $folder->update(['parent_id' => $newParentId]);

        return $folder->fresh(['parent', 'creator']);
    }

    /**
     * Build the full sidebar tree: folders (nested) + root pages (no folder).
     */
    public function tree(): array
    {
        $folders = DocFolder::root()
            ->with([
                'recursiveSubfolders',
                'pages.recursiveChildren',
            ])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        $rootPages = DocPage::notArchived()
            ->whereNull('folder_id')
            ->whereNull('parent_id')
            ->with('recursiveChildren')
            ->orderBy('position')
            ->orderBy('title')
            ->get();

        return [
            'folders'    => $folders,
            'root_pages' => $rootPages,
        ];
    }

    // ============================================================
    // INTERNAL HELPERS
    // ============================================================

    protected function archiveRecursive(DocPage $page): void
    {
        $page->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        $page->children()->each(fn (DocPage $child) => $this->archiveRecursive($child));
    }

    protected function deletePageRecursive(DocPage $page): void
    {
        $page->children()->each(
            fn (DocPage $child) => $this->deletePageRecursive($child)
        );

        $page->delete();
    }

    protected function deleteFolderRecursive(DocFolder $folder): void
    {
        // Delete all pages inside this folder (recursively)
        $folder->pages()->each(fn (DocPage $page) => $this->deletePageRecursive($page));

        // Recurse into sub-folders
        $folder->subfolders()->each(fn (DocFolder $sub) => $this->deleteFolderRecursive($sub));

        $folder->delete();
    }
}
