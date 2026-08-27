<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        return response()->json(Department::query()->orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $department = Department::create([
            'name' => $data['name'],
            'status' => $data['status'] ?? 'active',
            'color' => $data['color'] ?? '#3b82f6',
        ]);

        return response()->json($department, 201);
    }

    public function update(Request $request, string $id)
    {
        $department = Department::query()->findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $department->update([
            'name' => $data['name'],
            'status' => $data['status'] ?? $department->status,
            'color' => $data['color'] ?? $department->color,
        ]);

        return response()->json($department);
    }

    public function destroy(string $id)
    {
        Department::query()->findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
