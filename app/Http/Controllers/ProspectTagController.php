<?php

namespace App\Http\Controllers;

use App\Models\ProspectTag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProspectTagController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ProspectTag::all()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $tag = ProspectTag::create($validated);

        return response()->json([
            'success' => true,
            'data' => $tag
        ], 201);
    }

    public function show(ProspectTag $prospectTag): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $prospectTag
        ]);
    }

    public function update(Request $request, ProspectTag $prospectTag): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $prospectTag->update($validated);

        return response()->json([
            'success' => true,
            'data' => $prospectTag
        ]);
    }

    public function destroy(ProspectTag $prospectTag): JsonResponse
    {
        $prospectTag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Prospect tag deleted'
        ]);
    }
}
