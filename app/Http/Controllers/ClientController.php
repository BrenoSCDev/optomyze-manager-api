<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;


class ClientController extends Controller
{
    public function index(): JsonResponse
    {
        $clients = Client::ActiveClients()->get();

        return response()->json([
            'success' => true,
            'clients' => $clients,
        ]);
    }

    public function prospects(): JsonResponse
    {
        $clients = Client::Prospects()->get();

        return response()->json([
            'success' => true,
            'prospects' => $clients,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            // 1) Validate incoming request
            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'legal_name' => 'nullable|string|max:255',
                'industry' => 'nullable|string|max:255',
                'employees' => 'nullable|integer|min:0',
                'tax_id' => 'nullable|string|max:255',

                'contact_name' => 'nullable|string|max:255',
                'position' => 'nullable|string|max:255',

                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:50',
                'secondary_phone' => 'nullable|string|max:50',
                'website' => 'nullable|string|max:255',

                'instagram' => 'nullable|string|max:255',
                'linkedin' => 'nullable|string|max:255',
                'facebook' => 'nullable|string|max:255',
                'twitter_x' => 'nullable|string|max:255',
                'youtube' => 'nullable|string|max:255',
                'tiktok' => 'nullable|string|max:255',

                'country' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
                'zip_code' => 'nullable|string|max:50',

                'value' => 'nullable|numeric|min:0',
                'status' => 'required|in:lead,prospect,active,inactive',
                'source' => 'nullable|string|max:255',
                'priority' => 'nullable|in:low,medium,high',

                'tags' => 'nullable|string',
                'notes' => 'nullable|string',

                'crm_active' => 'boolean',

                'closed_at' => 'nullable|date',
            ]);

            // 2) Create client
            $client = Client::create($validated);

            // 3) Return success response
            return response()->json([
                'success' => true,
                'client' => $client,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            // Unknown or DB errors
            return response()->json([
                'success' => false,
                'message' => 'Unexpected error.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeProspects(Request $request): JsonResponse
    {
        try {
            // 1) Validate only the allowed fields
            $validated = $request->validate([
                'prospect_folder_id' => 'required|integer|exists:prospect_folders,id',

                'company_name' => 'required|string|max:255',
                'industry' => 'nullable|string|max:255',

                'contact_name' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:50',
                'website' => 'nullable|string|max:255',

                'instagram' => 'nullable|string|max:255',
                'linkedin' => 'nullable|string|max:255',
                'facebook' => 'nullable|string|max:255',
                'twitter_x' => 'nullable|string|max:255',
                'youtube' => 'nullable|string|max:255',
                'tiktok' => 'nullable|string|max:255',

                'address' => 'nullable|string|max:255',

                'priority' => 'nullable|in:low,medium,high',

                'notes' => 'nullable|string',
            ]);

            $validated['status'] = 'prospect';

            $client = Client::create($validated);

            return response()->json([
                'success' => true,
                'client' => $client,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unexpected error.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function show(Client $client): JsonResponse
    {
        $client->load(['contracts', 'payments']);
        return response()->json([
            'success' => true,
            'client' => $client,
        ]);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        try {
            // 1) Validate data (same rules as store)
            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'legal_name' => 'nullable|string|max:255',
                'industry' => 'nullable|string|max:255',
                'employees' => 'nullable|integer|min:0',
                'tax_id' => 'nullable|string|max:255',

                'contact_name' => 'nullable|string|max:255',
                'position' => 'nullable|string|max:255',

                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:50',
                'secondary_phone' => 'nullable|string|max:50',
                'website' => 'nullable|string|max:255',

                'instagram' => 'nullable|string|max:255',
                'linkedin' => 'nullable|string|max:255',
                'facebook' => 'nullable|string|max:255',
                'twitter_x' => 'nullable|string|max:255',
                'youtube' => 'nullable|string|max:255',
                'tiktok' => 'nullable|string|max:255',

                'country' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
                'zip_code' => 'nullable|string|max:50',

                'value' => 'nullable|numeric|min:0',
                'status' => 'nullable|in:lead,prospect,active,inactive',
                'source' => 'nullable|string|max:255',
                'priority' => 'nullable|in:low,medium,high',

                'tags' => 'nullable|string',
                'notes' => 'nullable|string',

                'crm_active' => 'boolean',

                'closed_at' => 'nullable|date',
            ]);

            // 2) Update client
            $client->update($validated);

            // 3) Return success response
            return response()->json([
                'success' => true,
                'client' => $client,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unexpected error.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json([
            'success' => true,
            'message' => 'Client deleted successfully',
        ]);
    }

    public function updateTags(Request $request, $clientId, $type)
    {
        // Validate inputs
        $validated = $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'string|max:255',
        ]);

        // Determine column dynamically
        if (!in_array($type, ['prospect', 'client'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid tag type. Must be "prospect" or "client".',
            ], 400);
        }

        $column = $type === 'prospect' ? 'prospect_tags' : 'client_tags';

        // Fetch client
        $client = Client::find($clientId);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found.',
            ], 404);
        }

        // Update tags
        $client->$column = $validated['tags'];
        $client->save();

        return response()->json([
            'success' => true,
            'message' => ucfirst($type) . ' tags updated successfully.',
            'client' => $client
        ]);
    }

    public function convertProspect(Client $client): JsonResponse
    {
        $client->status = 'active';
        $client->prospect_folder_id = null;
        $client->save();

        return response()->json([
            'success' => true,
            'message' => 'Prospect converted to active client successfully.',
            'client' => $client
        ]);
    }

    public function importProspects(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'folder_id' => 'required|exists:prospect_folders,id',
        ]);

        $folderId = $request->folder_id;

        // Load the file into an array
        $rows = Excel::toArray([], $request->file('file'))[0];

        // Remove header row
        $header = array_shift($rows);

        // Normalize header names
        $mappedHeader = array_map(function ($h) {
            return strtolower(trim($h));
        }, $header);

        foreach ($rows as $row) {
            // Map columns by header position
            $data = array_combine($mappedHeader, $row);

            Client::create([
                'company_name'        => $data['business name'] ?? null,
                'phone'               => $data['phone'] ?? null,
                'address'             => $data['address'] ?? null,
                'website'             => $data['website'] ?? null,
                'status'              => 'prospect',
                'prospect_folder_id'  => $folderId,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Prospects imported successfully.',
        ]);
    }
}
