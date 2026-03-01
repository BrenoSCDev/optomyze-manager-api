<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        return Department::with(['head', 'users'])->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'                 => 'required|string|max:255',
            'description'           => 'nullable|string',
            'head_of_department_id' => 'nullable|exists:users,id',
            'user_ids'              => 'nullable|array',
            'user_ids.*'            => 'integer|exists:users,id',
        ]);

        $userIds = $data['user_ids'] ?? [];
        unset($data['user_ids']);

        $department = Department::create($data);
        $department->users()->sync($userIds);

        return $department->load(['head', 'users']);
    }

    public function show(Department $department)
    {
        return $department->load(['head', 'users']);
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'title'                 => 'sometimes|string|max:255',
            'description'           => 'nullable|string',
            'head_of_department_id' => 'nullable|exists:users,id',
            'user_ids'              => 'nullable|array',
            'user_ids.*'            => 'integer|exists:users,id',
        ]);

        $userIds = $data['user_ids'] ?? null;
        unset($data['user_ids']);

        $department->update($data);

        if ($userIds !== null) {
            $department->users()->sync($userIds);
        }

        return $department->fresh(['head', 'users']);
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return response()->json(['message' => 'Department deleted successfully']);
    }
}
