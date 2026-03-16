<?php

namespace App\Http\Controllers;

use App\Http\Requests\Docs\StoreFolderRequest;
use App\Http\Requests\Docs\UpdateFolderRequest;
use App\Models\DocFolder;
use App\Services\DocService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocFolderController extends Controller
{
    public function __construct(protected DocService $docService)
    {
    }

    /**
     * GET /api/docs/folders
     *
     * List root-level folders (no parent). Useful for a flat folder picker.
     * For the full nested sidebar tree use GET /api/docs/tree instead.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DocFolder::class);

        $folders = DocFolder::root()
            ->with(['subfolders', 'creator:id,name'])
            ->withCount('pages')
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $folders,
        ]);
    }

    /**
     * POST /api/docs/folders
     */
    public function store(StoreFolderRequest $request): JsonResponse
    {
        $this->authorize('create', DocFolder::class);

        $folder = DocFolder::create([
            'parent_id'  => $request->input('parent_id'),
            'name'       => $request->input('name'),
            'icon'       => $request->input('icon'),
            'position'   => $request->input('position', 0),
            'created_by' => $request->user()->id,
        ]);

        $folder->load('creator:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Folder created.',
            'data'    => $folder,
        ], 201);
    }

    /**
     * PUT /api/docs/folders/{folder}
     */
    public function update(UpdateFolderRequest $request, DocFolder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $folder->update($request->only(['name', 'icon', 'position']));

        return response()->json([
            'success' => true,
            'message' => 'Folder updated.',
            'data'    => $folder,
        ]);
    }

    /**
     * DELETE /api/docs/folders/{folder}
     *
     * Permanently deletes the folder and all pages inside it.
     */
    public function destroy(DocFolder $folder): JsonResponse
    {
        $this->authorize('delete', $folder);

        $this->docService->deleteFolder($folder);

        return response()->json([
            'success' => true,
            'message' => 'Folder and all its contents deleted.',
        ]);
    }

    /**
     * PATCH /api/docs/folders/{folder}/move
     *
     * Move a folder to a different parent (or to root with parent_id: null).
     */
    public function move(Request $request, DocFolder $folder): JsonResponse
    {
        $this->authorize('move', $folder);

        $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:doc_folders,id'],
        ]);

        try {
            $updated = $this->docService->moveFolder($folder, $request->input('parent_id'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Folder moved.',
            'data'    => $updated,
        ]);
    }
}
