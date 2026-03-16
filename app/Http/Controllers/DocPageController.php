<?php

namespace App\Http\Controllers;

use App\Http\Requests\Docs\MovePageRequest;
use App\Http\Requests\Docs\StorePageRequest;
use App\Http\Requests\Docs\UpdatePageRequest;
use App\Models\DocPage;
use App\Services\DocService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocPageController extends Controller
{
    public function __construct(protected DocService $docService)
    {
    }

    // ============================================================
    // DISCOVERY
    // ============================================================

    /**
     * GET /api/docs/tree
     *
     * Returns the full sidebar tree:
     *  - All folders (nested) with their root pages and sub-pages
     *  - Root pages (no folder, no parent)
     *
     * Only non-archived content is included.
     */
    public function tree(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DocPage::class);

        $tree = $this->docService->tree();

        return response()->json([
            'success' => true,
            'data'    => $tree,
        ]);
    }

    /**
     * GET /api/docs/search?q=...
     *
     * Full-text search across page titles and content.
     * Returns up to 50 non-archived results ordered by last updated.
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DocPage::class);

        $request->validate(['q' => ['required', 'string', 'min:1', 'max:255']]);

        $pages = $this->docService->search($request->input('q'));

        return response()->json([
            'success' => true,
            'data'    => $pages,
        ]);
    }

    /**
     * GET /api/docs/favorites
     *
     * Returns pages favorited by the authenticated user.
     */
    public function favorites(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DocPage::class);

        $pages = $request->user()
            ->belongsToMany(DocPage::class, 'doc_favorites', 'user_id', 'page_id')
            ->withTimestamps()
            ->notArchived()
            ->with(['folder:id,name', 'creator:id,name'])
            ->orderByDesc('doc_favorites.created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $pages,
        ]);
    }

    /**
     * GET /api/docs/archive
     *
     * Returns pages archived by the authenticated user.
     */
    public function archived(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DocPage::class);

        $pages = DocPage::archived()
            ->where('created_by', $request->user()->id)
            ->with(['folder:id,name', 'creator:id,name'])
            ->orderByDesc('archived_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $pages,
        ]);
    }

    // ============================================================
    // CRUD
    // ============================================================

    /**
     * POST /api/docs/pages
     *
     * Create a new page. Pass parent_id to create a sub-page, or folder_id
     * to place it inside a folder. Both can be null for a root-level page.
     *
     * Typically the editor creates a blank page instantly and then the user
     * fills in the title and content — so all fields except none are optional.
     */
    public function store(StorePageRequest $request): JsonResponse
    {
        $this->authorize('create', DocPage::class);

        $page = DocPage::create([
            'folder_id'  => $request->input('folder_id'),
            'parent_id'  => $request->input('parent_id'),
            'title'      => $request->input('title', 'Untitled'),
            'icon'       => $request->input('icon'),
            'cover_url'  => $request->input('cover_url'),
            'content'    => $request->input('content'),
            'position'   => $request->input('position', 0),
            'created_by' => $request->user()->id,
        ]);

        $page->load(['folder:id,name', 'parent:id,title', 'creator:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Page created.',
            'data'    => $page,
        ], 201);
    }

    /**
     * GET /api/docs/pages/{page}
     *
     * Returns the full page including:
     *  - All fields (content, icon, cover, etc.)
     *  - Direct child pages (title + icon only, no content)
     *  - Breadcrumb trail (root → … → this page)
     *  - Whether the requesting user has favorited this page
     */
    public function show(Request $request, DocPage $page): JsonResponse
    {
        $this->authorize('view', $page);

        $page->load([
            'folder:id,name,icon',
            'parent:id,title,icon',
            'children:id,parent_id,title,icon,position',
            'creator:id,name',
        ]);

        return response()->json([
            'success'      => true,
            'data'         => $page,
            'breadcrumbs'  => $page->breadcrumbs(),
            'is_favorited' => $page->isFavoritedBy($request->user()->id),
        ]);
    }

    /**
     * PUT /api/docs/pages/{page}
     *
     * Partial update — only the fields present in the request are updated.
     * The editor will call this frequently (autosave) with only `content`.
     */
    public function update(UpdatePageRequest $request, DocPage $page): JsonResponse
    {
        $this->authorize('update', $page);

        $page->update($request->only(['title', 'icon', 'cover_url', 'content', 'position']));

        return response()->json([
            'success' => true,
            'message' => 'Page saved.',
            'data'    => $page,
        ]);
    }

    /**
     * DELETE /api/docs/pages/{page}
     *
     * Two-stage delete:
     *  1. If the page is NOT archived → archive it (move to trash).
     *  2. If the page IS already archived → permanently delete it.
     *
     * This mirrors the Notion "move to trash → delete forever" pattern.
     */
    public function destroy(Request $request, DocPage $page): JsonResponse
    {
        $this->authorize('delete', $page);

        if (! $page->is_archived) {
            $this->docService->archivePage($page);

            return response()->json([
                'success' => true,
                'message' => 'Page moved to trash.',
            ]);
        }

        $this->docService->deletePage($page);

        return response()->json([
            'success' => true,
            'message' => 'Page permanently deleted.',
        ]);
    }

    /**
     * PATCH /api/docs/pages/{page}/move
     *
     * Move a page to a different parent page and/or folder.
     * Send null values to promote to root.
     */
    public function move(MovePageRequest $request, DocPage $page): JsonResponse
    {
        $this->authorize('move', $page);

        try {
            $updated = $this->docService->movePage(
                $page,
                $request->input('parent_id'),
                $request->input('folder_id'),
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Page moved.',
            'data'    => $updated,
        ]);
    }

    /**
     * PATCH /api/docs/pages/{page}/archive
     *
     * Explicitly archive a page (and all its sub-pages).
     */
    public function archive(DocPage $page): JsonResponse
    {
        $this->authorize('archive', $page);

        if ($page->is_archived) {
            return response()->json(['success' => false, 'message' => 'Page is already archived.'], 422);
        }

        $this->docService->archivePage($page);

        return response()->json([
            'success' => true,
            'message' => 'Page archived.',
        ]);
    }

    /**
     * PATCH /api/docs/pages/{page}/restore
     *
     * Restore a single archived page. Its sub-pages remain archived.
     */
    public function restore(DocPage $page): JsonResponse
    {
        $this->authorize('restore', $page);

        if (! $page->is_archived) {
            return response()->json(['success' => false, 'message' => 'Page is not archived.'], 422);
        }

        $restored = $this->docService->restorePage($page);

        return response()->json([
            'success' => true,
            'message' => 'Page restored.',
            'data'    => $restored,
        ]);
    }

    /**
     * PATCH /api/docs/pages/{page}/favorite
     *
     * Toggle the favorite status for the authenticated user.
     */
    public function toggleFavorite(Request $request, DocPage $page): JsonResponse
    {
        $this->authorize('view', $page);

        $favorited = $this->docService->toggleFavorite($page, $request->user()->id);

        return response()->json([
            'success'   => true,
            'favorited' => $favorited,
            'message'   => $favorited ? 'Added to favorites.' : 'Removed from favorites.',
        ]);
    }
}
