<?php

namespace App\Http\Controllers;

use App\Http\Requests\Drive\MoveFolderRequest;
use App\Http\Requests\Drive\StoreFolderRequest;
use App\Http\Requests\Drive\UpdateFolderRequest;
use App\Models\DriveFile;
use App\Models\DriveFolder;
use App\Services\DriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriveFolderController extends Controller
{
    public function __construct(protected DriveService $driveService)
    {
    }

    /**
     * GET /api/drive/folders
     *
     * Returns top-level folders by default.
     * Pass ?parent_id={id} to list subfolders of a specific folder.
     * Pass ?client_id={id} to filter folders shared with a client.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DriveFolder::class);

        $query = DriveFolder::with(['creator:id,name', 'client:id,company_name'])
            ->withCount(['subfolders', 'files']);

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->integer('parent_id'));
        } else {
            $query->whereNull('parent_id');
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->integer('client_id'));
        }

        // Multi-tenancy hook (uncomment when company_id is on the User model)
        // $query->forCompany($request->user()->company_id);

        $folders = $query->orderBy('name')->get();

        // Include loose files (no folder) only when listing the root level
        $looseFiles = null;
        if (!$request->filled('parent_id')) {
            $filesQuery = DriveFile::with('uploader:id,name')
                ->whereNull('folder_id');

            if ($request->filled('client_id')) {
                $filesQuery->where('client_id', $request->integer('client_id'));
            }

            $looseFiles = $filesQuery->orderBy('original_name')->get();
        }

        return response()->json([
            'success'     => true,
            'data'        => $folders,
            'loose_files' => $looseFiles,
        ]);
    }

    /**
     * POST /api/drive/folders
     *
     * Create a new folder (optionally nested inside a parent).
     */
    public function store(StoreFolderRequest $request): JsonResponse
    {
        $this->authorize('create', DriveFolder::class);

        $folder = DriveFolder::create([
            'company_id' => null, // set to $request->user()->company_id when available
            'client_id'  => $request->input('client_id'),
            'parent_id'  => $request->input('parent_id'),
            'name'       => $request->input('name'),
            'created_by' => $request->user()->id,
        ]);

        $folder->load(['creator:id,name', 'parent:id,name', 'client:id,company_name']);

        return response()->json([
            'success' => true,
            'message' => 'Folder created successfully.',
            'data'    => $folder,
        ], 201);
    }

    /**
     * GET /api/drive/folders/{id}
     *
     * Returns folder details, its immediate contents, and breadcrumb trail.
     */
    public function show(DriveFolder $folder): JsonResponse
    {
        $this->authorize('view', $folder);

        $folder->load([
            'creator:id,name',
            'client:id,company_name',
            'parent:id,name',
            'subfolders' => fn ($q) => $q->withCount(['subfolders', 'files'])->orderBy('name'),
            'files'      => fn ($q) => $q->with('uploader:id,name')->orderBy('original_name'),
        ]);

        return response()->json([
            'success'     => true,
            'data'        => $folder,
            'breadcrumbs' => $this->driveService->breadcrumbs($folder),
        ]);
    }

    /**
     * PUT /api/drive/folders/{id}
     *
     * Rename a folder.
     */
    public function update(UpdateFolderRequest $request, DriveFolder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $folder->update([
            'name'      => $request->input('name'),
            // `has` check lets client omit the field entirely without nulling it
            'client_id' => $request->has('client_id') ? $request->input('client_id') : $folder->client_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Folder updated successfully.',
            'data'    => $folder->fresh(['creator:id,name', 'parent:id,name', 'client:id,company_name']),
        ]);
    }

    /**
     * DELETE /api/drive/folders/{id}
     *
     * Recursively deletes the folder and all its contents (files + subfolders).
     */
    public function destroy(DriveFolder $folder): JsonResponse
    {
        $this->authorize('delete', $folder);

        $this->driveService->deleteFolder($folder);

        return response()->json([
            'success' => true,
            'message' => 'Folder and all its contents deleted successfully.',
        ]);
    }

    /**
     * PATCH /api/drive/folders/{id}/move
     *
     * Move a folder to a different parent.
     * Send { "parent_id": null } to move to root.
     */
    public function move(MoveFolderRequest $request, DriveFolder $folder): JsonResponse
    {
        $this->authorize('move', $folder);

        try {
            $updated = $this->driveService->moveFolder(
                $folder,
                $request->input('parent_id')
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Folder moved successfully.',
            'data'    => $updated,
        ]);
    }
}
