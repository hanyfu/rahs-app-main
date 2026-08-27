<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthUser;
use App\Models\Profile;
use App\Models\UserDepartment;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        $profiles = Profile::query()
            ->with(['userRole', 'userDepartment', 'manager'])
            ->orderBy('created_at', 'desc')
            ->get();

        $userDepartments = UserDepartment::query()->where('status', 'active')->orderBy('name')->get();
        $roles = UserRole::query()->pluck('role', 'user_id');

        return response()->json([
            'profiles' => $profiles,
            'user_departments' => $userDepartments,
            'roles' => $roles,
        ]);
    }

    public function createUser(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'min:8', 'max:72', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
            'firstName' => ['required', 'string', 'max:100'],
            'lastName' => ['required', 'string', 'max:100'],
            'departmentId' => ['nullable', 'string'],
            'userDepartmentId' => ['nullable', 'string'],
            'managerId' => ['nullable', 'string'],
            'contactNo' => ['nullable', 'string'],
            'role' => ['nullable', 'in:admin,supervisor,coordinator,staff'],
        ]);

        $role = $data['role'] ?? 'staff';

        if (auth()->user()->role !== 'admin' && $role === 'admin') {
            throw ValidationException::withMessages(['role' => 'Only administrators can create admin users']);
        }

        if (AuthUser::where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages(['email' => 'User already exists']);
        }

        $id = (string) Str::uuid();

        DB::transaction(function () use ($id, $data, $role) {
            AuthUser::create(['id' => $id, 'email' => $data['email'], 'password' => $data['password']]);
            Profile::create([
                'id' => $id,
                'email' => $data['email'],
                'first_name' => $data['firstName'],
                'last_name' => $data['lastName'],
                'department_id' => $data['departmentId'] ?: null,
                'user_department_id' => $data['userDepartmentId'] ?: null,
                'manager_id' => $data['managerId'] ?: null,
                'contact_no' => $data['contactNo'] ?: null,
            ]);
            UserRole::create(['user_id' => $id, 'role' => $role]);
        });

        return response()->json(['success' => true, 'user' => ['id' => $id, 'email' => $data['email']]], 201);
    }

    public function update(Request $request, string $id)
    {
        $profile = Profile::query()->findOrFail($id);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'contact_no' => ['nullable', 'string', 'max:20'],
            'user_department_id' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
            'manager_id' => ['nullable', 'string'],
        ]);

        $profile->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'contact_no' => $data['contact_no'] ?: null,
            'user_department_id' => $data['user_department_id'] ?: null,
            'status' => $data['status'],
            'manager_id' => $data['manager_id'] ?: null,
        ]);

        return response()->json(['success' => true]);
    }

    public function updateRole(Request $request, string $id)
    {
        $data = $request->validate(['role' => ['required', 'in:admin,supervisor,coordinator,staff']]);

        if (auth()->user()->role !== 'admin' && $data['role'] === 'admin') {
            throw ValidationException::withMessages(['role' => 'Only administrators can grant the admin role']);
        }

        if ($id === auth()->id() && auth()->user()->role === 'admin' && $data['role'] !== 'admin') {
            throw ValidationException::withMessages(['role' => 'Cannot remove your own admin rights']);
        }

        UserRole::query()->where('user_id', $id)->delete();
        UserRole::create(['user_id' => $id, 'role' => $data['role']]);

        return response()->json(['success' => true]);
    }

    public function deleteUser(Request $request)
    {
        $data = $request->validate(['userId' => ['required', 'string']]);
        $userId = $data['userId'];

        if ($userId === auth()->id()) {
            throw ValidationException::withMessages(['userId' => 'Cannot delete yourself']);
        }

        DB::transaction(function () use ($userId) {
            DB::table('profiles')->where('manager_id', $userId)->update(['manager_id' => null]);
            DB::table('atolls')->where('coordinator_id', $userId)->update(['coordinator_id' => null]);
            DB::table('atolls')->where('supervisor_id', $userId)->update(['supervisor_id' => null]);
            DB::table('islands')->where('assigned_staff_id', $userId)->update(['assigned_staff_id' => null]);
            DB::table('tasks')->where('assigned_by', $userId)->update(['assigned_by' => null]);
            DB::table('tasks')->where('assigned_to', $userId)->update(['assigned_to' => null]);
            DB::table('task_comments')->where('user_id', $userId)->update(['user_id' => null]);
            DB::table('task_activities')->where('user_id', $userId)->update(['user_id' => null]);
            DB::table('call_logs')->where('user_id', $userId)->update(['user_id' => null]);
            DB::table('scheduled_reports')->where('user_id', $userId)->update(['user_id' => null]);
            DB::table('critical_staff_leaves')->where('created_by', $userId)->update(['created_by' => null]);
            DB::table('critical_staff_leaves')->where('assigned_coordinator', $userId)->update(['assigned_coordinator' => null]);
            DB::table('critical_staff_leaves')->where('direct_supervisor', $userId)->update(['direct_supervisor' => null]);
            DB::table('critical_staff_leaves')->where('reviewed_by', $userId)->update(['reviewed_by' => null]);

            UserRole::query()->where('user_id', $userId)->delete();
            Profile::query()->where('id', $userId)->delete();
            AuthUser::query()->where('id', $userId)->delete();
        });

        return response()->json(['success' => true]);
    }
}
