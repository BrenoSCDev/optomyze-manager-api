<?php

namespace App\Http\Controllers;

use App\Models\ClientContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Filesystem\FilesystemAdapter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientContractController extends Controller
{
    /**
     * Store a new client contract.
     */
    public function store(Request $request): JsonResponse
    {
        // Validate input
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name'      => 'required|string|max:255',
            'file'      => 'required|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240', // 10MB
        ]);

        // Store the file (storage/app/contracts/)
        $path = $request->file('file')->store('contracts', 'public');

        // Create record
        $contract = ClientContract::create([
            'client_id' => $validated['client_id'],
            'name'      => $validated['name'],
            'path'      => $path,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contract uploaded successfully.',
            'contract' => $contract
        ], 201);
    }

    /**
     * Preview a client contract inline (PDF / image).
     */
    public function preview(int $id): StreamedResponse|JsonResponse
    {
        $contract = ClientContract::find($id);

        if (!$contract) {
            return response()->json(['success' => false, 'message' => 'Contract not found.'], 404);
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if (!$disk->exists($contract->path)) {
            return response()->json(['success' => false, 'message' => 'File not found on storage.'], 404);
        }

        return $disk->response(
            $contract->path,
            $contract->name,
            ['Content-Type' => $disk->mimeType($contract->path)]
        );
    }

    /**
     * Download a client contract.
     */
    public function download(int $id): StreamedResponse|JsonResponse
    {
        $contract = ClientContract::find($id);

        if (!$contract) {
            return response()->json(['success' => false, 'message' => 'Contract not found.'], 404);
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if (!$disk->exists($contract->path)) {
            return response()->json(['success' => false, 'message' => 'File not found on storage.'], 404);
        }

        return $disk->download($contract->path, $contract->name);
    }

    /**
     * Delete a client contract.
     */
    public function destroy($id): JsonResponse
    {
        $contract = ClientContract::find($id);

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found.'
            ], 404);
        }

        // Delete file from storage
        if ($contract->path && Storage::exists($contract->path)) {
            Storage::delete($contract->path);
        }

        // Delete database record
        $contract->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contract deleted successfully.'
        ]);
    }
}
