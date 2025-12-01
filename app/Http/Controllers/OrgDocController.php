<?php

namespace App\Http\Controllers;

use App\Models\OrgDoc;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Support\Facades\Storage;

class OrgDocController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'document' => 'required|file',
        ]);

        $file = $request->file('document');
        $storedPath = $file->store('org_docs', 'public');

        $doc = OrgDoc::create([
            'name' => $file->getClientOriginalName(),
            'path' => $storedPath,
        ]);

        return response()->json([
            'success' => true,
            'document' => $doc,
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $doc = OrgDoc::findOrFail($id);

        // Delete file from storage
        if (Storage::disk('public')->exists($doc->path)) {
            Storage::disk('public')->delete($doc->path);
        }

        $doc->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.'
        ]);
    }

}
