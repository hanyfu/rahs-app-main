<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserDepartment;
use Illuminate\Http\Request;

class UserDepartmentController extends Controller
{
    public function index()
    {
        return response()->json(UserDepartment::query()->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $dept = UserDepartment::create([
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'status' => $data['status'] ?? 'active',
            'color' => $data['color'] ?? '#3b82f6',
        ]);

        return response()->json($dept, 201);
    }

    public function update(Request $request, string $id)
    {
        $dept = UserDepartment::query()->findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $dept->update([
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'status' => $data['status'] ?? $dept->status,
            'color' => $data['color'] ?? $dept->color,
        ]);

        return response()->json($dept);
    }

    public function destroy(string $id)
    {
        UserDepartment::query()->findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
