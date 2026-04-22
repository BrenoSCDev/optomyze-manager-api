<?php

namespace App\Http\Controllers;

use App\Models\TaskDoc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskDocController extends Controller
{
    /**
     * Store a new task document.
     */
    public function store(Request $request): JsonResponse
    {
        // Validate input
        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'name'    => 'required|string|max:255',
            'file'    => 'required|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240', // 10MB
        ]);

        // Store the file (storage/app/task_docs/)
        $path = $request->file('file')->store('task_docs', 'public');

        // Create record
        $doc = TaskDoc::create([
            'task_id' => $validated['task_id'],
            'name'    => $validated['name'],
            'path'    => $path,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully.',
            'doc'     => $doc
        ], 201);
    }

    /**
     * Download a task document.
     */
    public function download($id): StreamedResponse|JsonResponse
    {
        $doc = TaskDoc::find($id);

        if (!$doc) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found.'
            ], 404);
        }

        if (!Storage::disk('public')->exists($doc->path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found on storage.'
            ], 404);
        }

        return Storage::disk('public')->download($doc->path, $doc->name);
    }

    /**
     * Delete a task document.
     */
    public function destroy($id): JsonResponse
    {
        $doc = TaskDoc::find($id);

        if (!$doc) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found.'
            ], 404);
        }

        // Delete file from storage
        if ($doc->path && Storage::exists($doc->path)) {
            Storage::delete($doc->path);
        }

        // Delete database record
        $doc->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.'
        ]);
    }
}
