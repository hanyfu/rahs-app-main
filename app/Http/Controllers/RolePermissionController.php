<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RolePermissionController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();

        $permissions = RolePermission::query()->orderBy('category')->orderBy('permission_name')->get();

        return view('role-permissions.index', compact('permissions'));
    }

    public function data()
    {
        $this->authorizeAdmin();

        return response()->json(RolePermission::query()->orderBy('category')->orderBy('permission_name')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'permission_name' => ['required', 'string', 'max:255'],
            'permission_description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:255'],
            'supervisor_access' => ['nullable', 'boolean'],
            'coordinator_access' => ['nullable', 'boolean'],
            'staff_access' => ['nullable', 'boolean'],
            'user_access' => ['nullable', 'boolean'],
        ]);

        $key = $this->generateKey($data['permission_name']);

        if (RolePermission::query()->where('permission_key', $key)->exists()) {
            throw ValidationException::withMessages(['permission_name' => 'Permission key already exists']);
        }

        $permission = RolePermission::create([
            'permission_name' => $data['permission_name'],
            'permission_key' => $key,
            'permission_description' => $data['permission_description'] ?? null,
            'category' => $data['category'],
            'admin_access' => true,
            'supervisor_access' => $data['supervisor_access'] ?? false,
            'coordinator_access' => $data['coordinator_access'] ?? false,
            'staff_access' => $data['staff_access'] ?? false,
            'user_access' => $data['user_access'] ?? false,
        ]);

        return response()->json($permission, 201);
    }

    public function update(Request $request, string $id)
    {
        $permission = RolePermission::query()->findOrFail($id);

        $data = $request->validate([
            'permission_name' => ['nullable', 'string', 'max:255'],
            'permission_description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'admin_access' => ['nullable', 'boolean'],
            'supervisor_access' => ['nullable', 'boolean'],
            'coordinator_access' => ['nullable', 'boolean'],
            'staff_access' => ['nullable', 'boolean'],
            'user_access' => ['nullable', 'boolean'],
        ]);

        // Administrator access is the recovery path for the access-control
        // matrix and cannot be disabled by a client request.
        unset($data['admin_access']);

        $permission->update($data);

        return response()->json($permission);
    }

    public function destroy(string $id)
    {
        RolePermission::query()->findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    private function authorizeAdmin(): void
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Access Denied');
        }
    }

    private function generateKey(string $name): string
    {
        $key = strtolower($name);
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);
        $key = trim($key, '_');

        return $key ?: 'permission_'.Str::uuid();
    }
}
