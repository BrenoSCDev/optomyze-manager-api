<?php

namespace App\Http\Controllers;

use App\Models\ProspectContact;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProspectContactController extends Controller
{
    /**
     * List all contact points for a client.
     */
    public function index($clientId): JsonResponse
    {
        $contacts = ProspectContact::where('client_id', $clientId)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'contacts' => $contacts
        ]);
    }

    /**
     * Create a new contact record.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id'   => 'required|exists:clients,id',
            'date'        => 'required|date',
            'description' => 'nullable|string'
        ]);

        $contact = ProspectContact::create($validated);

        return response()->json([
            'success' => true,
            'contact' => $contact
        ], 201);
    }

    /**
     * Show a single prospect contact.
     */
    public function show($id): JsonResponse
    {
        $contact = ProspectContact::find($id);

        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'contact' => $contact
        ]);
    }

    /**
     * Update a contact record.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $contact = ProspectContact::find($id);

        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found'
            ], 404);
        }

        $validated = $request->validate([
            'date'        => 'required|date',
            'description' => 'nullable|string'
        ]);

        $contact->update($validated);

        return response()->json([
            'success' => true,
            'contact' => $contact
        ]);
    }

    /**
     * Delete a contact record.
     */
    public function destroy($id): JsonResponse
    {
        $contact = ProspectContact::find($id);

        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found'
            ], 404);
        }

        $contact->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contact deleted successfully'
        ]);
    }
}
