<?php

namespace App\Http\Controllers;

use App\Models\ClientContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

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
