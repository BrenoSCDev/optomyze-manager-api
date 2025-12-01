<?php

namespace App\Http\Controllers;

use App\Models\ClientTag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClientTagController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ClientTag::all()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $tag = ClientTag::create($validated);

        return response()->json([
            'success' => true,
            'data' => $tag
        ], 201);
    }

    public function show(ClientTag $clientTag): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $clientTag
        ]);
    }

    public function update(Request $request, ClientTag $clientTag): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $clientTag->update($validated);

        return response()->json([
            'success' => true,
            'data' => $clientTag
        ]);
    }

    public function destroy(ClientTag $clientTag): JsonResponse
    {
        $clientTag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Client tag deleted'
        ]);
    }
}
