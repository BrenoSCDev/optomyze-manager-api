<?php

namespace App\Http\Controllers;

use App\Models\ProspectFolder;
use App\Models\ProspectTag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProspectFolderController extends Controller
{
    /**
     * List all folders WITH number of clients in each.
     */
    public function index(): JsonResponse
    {
        $folders = ProspectFolder::withCount('clients')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'folders' => $folders
        ]);
    }

    /**
     * Create folder.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $folder = ProspectFolder::create($validated);

        return response()->json([
            'success' => true,
            'folder' => $folder
        ], 201);
    }

    /**
     * Show a single folder WITH all clients inside it.
     */
    public function show(ProspectFolder $prospectFolder): JsonResponse
    {
        $prospectFolder->load([
            'clients.prospectContacts'
        ]);

        return response()->json([
            'success' => true,
            'folder' => $prospectFolder,
            'tags' => ProspectTag::all(),
        ]);
    }


    /**
     * Update a folder.
     */
    public function update(Request $request, ProspectFolder $prospectFolder): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $prospectFolder->update($validated);

        return response()->json([
            'success' => true,
            'folder' => $prospectFolder
        ]);
    }

    /**
     * Delete a folder.
     */
    public function destroy(ProspectFolder $prospectFolder): JsonResponse
    {
        $prospectFolder->delete();

        return response()->json([
            'success' => true,
            'message' => 'Folder deleted'
        ]);
    }
}
