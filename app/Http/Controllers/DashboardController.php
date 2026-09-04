<?php

namespace App\Http\Controllers;

use App\Models\Atoll;
use App\Models\Department;
use App\Models\HospitalContact;
use App\Models\HospitalProfile;
use App\Models\Island;
use App\Models\Profile;
use App\Models\RolePermission;
use App\Models\Task;
use App\Models\UserDepartment;
use App\Models\UserRole;
use App\Services\TaskService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $service = new TaskService;
        $user = auth()->user();
        $profile = $service->currentProfile();
        $role = $user->role;
        $managementPermissions = [
            'atolls' => 'manage_atolls', 'islands' => 'manage_islands',
            'users' => 'view_users', 'user-departments' => 'manage_departments',
            'departments' => 'manage_departments',
        ];
        $tab = $request->query('tab', 'overview');
        if (isset($managementPermissions[$tab]) && ! RolePermission::allows($managementPermissions[$tab], $user)) {
            abort(403, 'You do not have permission to access this management section.');
        }
        $canAdminTools = collect(array_unique($managementPermissions))->contains(fn ($key) => RolePermission::allows($key, $user));

        $tasksQuery = Task::query()->where('archived', false);
        $tasksQuery = $service->applyTaskAccess($tasksQuery, $role, $user->id);
        $tasks = $tasksQuery->with(['assignor', 'assignee', 'department', 'island.atoll'])->get();

        $stats = $this->computeStats($tasks);

        // Staff: assigned island + hospital profile
        $assignedIsland = null;
        $hospitalProfile = null;
        $hospitalContactId = null;
        $atollName = null;
        $hospitalName = null;

        if ($role === 'staff' && $profile) {
            $assignedIsland = Island::query()
                ->where('assigned_staff_id', $user->id)
                ->where('status', 'active')
                ->with('atoll')
                ->first();

            if ($assignedIsland) {
                $atollName = $assignedIsland->atoll?->name;
                $hospitalContact = HospitalContact::query()
                    ->where('island_id', $assignedIsland->id)
                    ->where('status', 'active')
                    ->latest('created_at')
                    ->first();
                $hospitalContactId = $hospitalContact?->id;
                $hospitalName = $hospitalContact?->hospital_name ?: "{$assignedIsland->name} Health Facility";
                // Profiles created by older versions may only be linked to the
                // island. Prefer the active contact link, then fall back to the
                // assigned island so staff always receive the complete record.
                $hospitalProfile = HospitalProfile::query()
                    ->where(function ($query) use ($hospitalContact, $assignedIsland) {
                        if ($hospitalContact) {
                            $query->where('hospital_contact_id', $hospitalContact->id)
                                ->orWhere('island_id', $assignedIsland->id);
                        } else {
                            $query->where('island_id', $assignedIsland->id);
                        }
                    })
                    ->orderByRaw('hospital_contact_id = ? desc', [$hospitalContact?->id ?? ''])
                    ->latest('updated_at')
                    ->first();

                $hospitalContactId ??= $hospitalProfile?->hospital_contact_id;
            } else {
                // Keep the dashboard informative, but do not attach or expose
                // an editable profile until the staff member is assigned.
                $hospitalName = 'Hospital Profile';
            }
        }

        $data = [
            'stats' => $stats,
            'role' => $role,
            'profile' => $profile,
            'assignedIsland' => $assignedIsland,
            'atollName' => $atollName,
            'hospitalProfile' => $hospitalProfile,
            'hospitalContactId' => $hospitalContactId,
            'hospitalName' => $hospitalName,
            'tab' => $tab,
            'permissionAccess' => RolePermission::query()->get()->mapWithKeys(fn ($permission) => [
                $permission->permission_key => RolePermission::allows($permission->permission_key, $user),
            ]),
        ];

        if ($canAdminTools || in_array($role, ['admin', 'supervisor'], true)) {
            $data += [
                'atolls' => Atoll::query()->with(['coordinator'])->orderBy('name')->get(),
                'islands' => Island::query()->with(['atoll', 'assignedStaff'])->orderBy('name')->get(),
                'departments' => Department::query()->orderBy('created_at', 'desc')->get(),
                'userDepartments' => UserDepartment::query()->orderBy('name')->get(),
                'profiles' => Profile::query()->with(['userRole', 'userDepartment', 'manager'])->orderBy('created_at', 'desc')->get(),
                'assignableStaff' => Profile::query()
                    ->with('userRole')
                    ->where('status', 'active')
                    ->whereHas('userRole', fn ($query) => $query->where('role', 'staff'))
                    ->orderBy('first_name')
                    ->orderBy('last_name')
                    ->get(),
                'userRoles' => UserRole::query()->pluck('role', 'user_id'),
                'coordinators' => $this->coordinatorDirectory(),
            ];
        }

        return view('dashboard.index', $data);
    }

    public function statistics()
    {
        $service = new TaskService;
        $user = auth()->user();

        $base = Task::query()->where('archived', false);
        $base = $service->applyTaskAccess($base, $user->role, $user->id);

        $today = now()->startOfDay()->toDateString();

        $row = (clone $base)
            ->selectRaw('count(*) as total')
            ->selectRaw("count(*) filter (where status = 'pending') as pending")
            ->selectRaw("count(*) filter (where status = 'in_progress') as in_progress")
            ->selectRaw("count(*) filter (where status = 'completed') as completed")
            ->selectRaw("count(*) filter (where status <> 'completed' and due_date ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}' and due_date::date < ?) as overdue", [$today])
            ->selectRaw('count(distinct assigned_to) filter (where assigned_to is not null) as assigned_users')
            ->first();

        $total = (int) $row->total;
        $completed = (int) $row->completed;

        return response()->json([
            'total' => $total,
            'pending' => (int) $row->pending,
            'inProgress' => (int) $row->in_progress,
            'completed' => $completed,
            'overdue' => (int) $row->overdue,
            'assignedUsers' => (int) $row->assigned_users,
            'efficiency' => $total > 0 ? round(($completed / $total) * 100) : 0,
        ]);
    }

    private function computeStats($tasks)
    {
        $today = now()->startOfDay();

        $total = $tasks->count();
        $pending = $tasks->where('status', 'pending')->count();
        $inProgress = $tasks->where('status', 'in_progress')->count();
        $completed = $tasks->where('status', 'completed')->count();
        $overdue = $tasks->filter(fn ($t) => $t->status !== 'completed' && $t->due_date && strtotime($t->due_date) < $today->timestamp)->count();
        $assignedUsers = $tasks->pluck('assigned_to')->filter()->unique()->count();
        $efficiency = $total > 0 ? round(($completed / $total) * 100) : 0;

        return compact('total', 'pending', 'inProgress', 'completed', 'overdue', 'assignedUsers', 'efficiency');
    }

    private function coordinatorDirectory()
    {
        $profiles = Profile::query()
            ->with('userRole')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $atolls = Atoll::query()->where('status', 'active')->orderBy('name')->get();

        $managers = [];

        foreach ($profiles as $profile) {
            $role = $profile->userRole?->role;
            if (! in_array($role, ['coordinator', 'supervisor'], true)) {
                continue;
            }

            $column = $role === 'coordinator' ? 'coordinator_id' : 'supervisor_id';
            $assignedAtolls = $atolls->where($column, $profile->id)->values();

            $managers[] = [
                'id' => $profile->id,
                'first_name' => $profile->first_name,
                'last_name' => $profile->last_name,
                'full_name' => $profile->full_name,
                'initials' => $profile->initials,
                'designation' => $profile->designation,
                'email' => $profile->email,
                'contact_no' => $profile->contact_no,
                'role' => $role,
                'status' => $profile->status,
                'assigned_atolls' => $assignedAtolls->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->values(),
            ];
        }

        return $managers;
    }
}
