<?php

namespace App\Http\Controllers;

use App\Models\Atoll;
use App\Models\Department;
use App\Models\HospitalContact;
use App\Models\HospitalProfile;
use App\Models\Island;
use App\Models\Profile;
use App\Models\ScheduledReport;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $role = auth()->user()->role;
        $me = auth()->id();

        $tasks = (new TaskService)->applyTaskAccess(Task::query(), $role, $me)
            ->with(['assignor', 'assignee', 'department', 'island.atoll'])->get();
        $profiles = Profile::query()->with('userRole')->get();
        $departments = Department::query()->where('status', 'active')->get();
        $atolls = Atoll::query()->where('status', 'active')->get();
        $islands = Island::query()->where('status', 'active')->get();

        // Role scope
        if ($role === 'supervisor') {
            $coordinatorIds = $profiles->where('manager_id', $me)->pluck('id');
            $tasks = $tasks->filter(function ($t) use ($coordinatorIds, $me) {
                $involved = collect([$t->assigned_by, $t->assigned_to]);
                $involvedCoordinator = $coordinatorIds->intersect($involved)->isNotEmpty();
                $involvedSelf = $involved->contains($me);
                $inAtoll = $t->island && $coordinatorIds->contains($t->island->atoll?->coordinator_id);

                return $involvedSelf || $involvedCoordinator || $inAtoll;
            });
        } elseif ($role === 'coordinator') {
            $myAtollIds = $atolls->where('coordinator_id', $me)->pluck('id');
            $myIslandIds = $islands->whereIn('atoll_id', $myAtollIds)->pluck('id');
            $myStaff = $profiles->where('manager_id', $me)->pluck('id');
            $tasks = $tasks->filter(function ($t) use ($myIslandIds, $myStaff) {
                return $myIslandIds->contains($t->island_id) || $myStaff->contains($t->assigned_by) || $myStaff->contains($t->assigned_to);
            });
        }

        $scheduledReports = ScheduledReport::query()
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('reports.index', compact('tasks', 'profiles', 'departments', 'atolls', 'islands', 'role', 'scheduledReports'));
    }

    public function exportTasksCsv(Request $request)
    {
        $tasks = $this->filteredTasks($request);

        $csv = $this->tasksCsv($tasks);

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="tasks_'.now()->format('Y-m-d').'.csv"');
    }

    public function generate(Request $request, string $type)
    {
        $allowed = ['tasks', 'overdue', 'completed', 'workload', 'atoll-performance', 'department-performance'];
        validator(['type' => $type], ['type' => ['required', Rule::in($allowed)]])->validate();

        $tasks = $this->filteredTasks($request);
        $labels = [
            'tasks' => 'task_detail',
            'overdue' => 'overdue_tasks',
            'completed' => 'completed_tasks',
            'workload' => 'staff_workload',
            'atoll-performance' => 'atoll_performance',
            'department-performance' => 'department_performance',
        ];

        $csv = match ($type) {
            'overdue' => $this->tasksCsv($tasks->filter(fn ($task) => $task->isOverdue())),
            'completed' => $this->tasksCsv($tasks->where('status', 'completed')),
            'workload' => $this->groupedTasksCsv($tasks, fn ($task) => $task->assignee?->full_name ?: 'Unassigned', 'Staff member'),
            'atoll-performance' => $this->groupedTasksCsv($tasks, fn ($task) => $task->island?->atoll?->name ?: 'Unassigned', 'Atoll'),
            'department-performance' => $this->groupedTasksCsv($tasks, fn ($task) => $task->department?->name ?: 'Unassigned', 'Department'),
            default => $this->tasksCsv($tasks),
        };

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.$labels[$type].'_'.now()->format('Y-m-d').'.csv"');
    }

    public function exportHospitalContactsCsv()
    {
        $contacts = HospitalContact::query()->with('island.atoll')->where('status', 'active')->get();

        $rows = [['Hospital Name', 'Atoll', 'Island', 'Manager Name', 'Designation', 'Contact Number', 'Notes']];
        foreach ($contacts as $c) {
            $rows[] = [
                $this->csvEscape($c->hospital_name),
                $this->csvEscape($c->island?->atoll?->name ?? ''),
                $this->csvEscape($c->island?->name ?? ''),
                $this->csvEscape($c->manager_name),
                $this->csvEscape($c->contact_designation ?? ''),
                $this->csvEscape($c->contact_number),
                $this->csvEscape($c->notes ?? ''),
            ];
        }

        // Islands without a registered contact are still facilities in the
        // directory, so include them in the export too.
        $facilities = Island::query()
            ->with('atoll')
            ->where('status', 'active')
            ->whereNotIn('id', $contacts->pluck('island_id'))
            ->orderBy('name')
            ->get();
        foreach ($facilities as $f) {
            $rows[] = [
                $this->csvEscape($f->name.' Health Facility'),
                $this->csvEscape($f->atoll?->name ?? ''),
                $this->csvEscape($f->name),
                '', '', '', '',
            ];
        }

        $csv = "\xEF\xBB\xBF".implode("\n", array_map(fn ($r) => implode(',', $r), $rows));

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="hospital_contacts_'.now()->format('Y-m-d').'.csv"');
    }

    public function exportHospitalProfilesCsv()
    {
        $profiles = HospitalProfile::query()->with(['hospitalContact', 'island.atoll'])->get();

        $rows = [['Hospital', 'Island', 'Atoll', 'Beds', 'Grade', 'Population', 'Medical Staff', 'Nursing Staff', 'Admin Staff']];
        foreach ($profiles as $p) {
            $rows[] = [
                $this->csvEscape($p->hospitalContact?->hospital_name ?? ($p->island?->name.' Health Facility' ?? '')),
                $this->csvEscape($p->island?->name ?? ''),
                $this->csvEscape($p->island?->atoll?->name ?? ''),
                $p->no_of_beds,
                $this->csvEscape($p->grade ?? ''),
                $p->population,
                $p->medical_staff_total,
                $p->nursing_staff_total,
                $p->admin_staff_total,
            ];
        }

        $csv = "\xEF\xBB\xBF".implode("\n", array_map(fn ($r) => implode(',', $r), $rows));

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="hospital_profiles_'.now()->format('Y-m-d').'.csv"');
    }

    private function tasksCsv($tasks): string
    {
        $rows = [['Title', 'Description', 'Status', 'Priority', 'Due Date', 'Atoll', 'Island', 'Task Type', 'Assigned To', 'Assigned By', 'Created At']];
        foreach ($tasks as $t) {
            $rows[] = [
                $this->csvEscape($t->title),
                $this->csvEscape($t->creator_description ?? ''),
                $this->csvEscape($t->status),
                $this->csvEscape($t->priority),
                $t->due_date ?? '',
                $this->csvEscape($t->island?->atoll?->name ?? ''),
                $this->csvEscape($t->island?->name ?? ''),
                $this->csvEscape(implode(', ', $t->task_types ?? [])),
                $this->csvEscape($t->assignee?->full_name ?? ''),
                $this->csvEscape($t->assignor?->full_name ?? ''),
                $t->created_at?->format('Y-m-d H:i'),
            ];
        }

        return "\xEF\xBB\xBF".implode("\n", array_map(fn ($r) => implode(',', $r), $rows));
    }

    private function filteredTasks(Request $request): Collection
    {
        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'atoll_id' => ['nullable', 'uuid'],
            'island_id' => ['nullable', 'uuid'],
            'department_id' => ['nullable', 'uuid'],
            'assigned_to' => ['nullable', 'uuid'],
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
        ]);

        $query = (new TaskService)->applyTaskAccess(Task::query(), auth()->user()->role, auth()->id())
            ->with(['assignor', 'assignee', 'department', 'island.atoll']);
        $query->when($data['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date));
        $query->when($data['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
        $query->when($data['island_id'] ?? null, fn ($q, $id) => $q->where('island_id', $id));
        $query->when($data['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id));
        $query->when($data['assigned_to'] ?? null, fn ($q, $id) => $q->where('assigned_to', $id));
        $query->when($data['status'] ?? null, fn ($q, $value) => $q->where('status', $value));
        $query->when($data['priority'] ?? null, fn ($q, $value) => $q->where('priority', $value));
        $query->when($data['atoll_id'] ?? null, fn ($q, $id) => $q->whereHas('island', fn ($islands) => $islands->where('atoll_id', $id)));

        return $query->orderByDesc('created_at')->get();
    }

    private function groupedTasksCsv(Collection $tasks, callable $groupBy, string $label): string
    {
        $rows = [[$label, 'Total Tasks', 'Pending', 'In Progress', 'Completed', 'Overdue', 'Completion Rate']];
        foreach ($tasks->groupBy($groupBy)->sortKeys() as $name => $group) {
            $completed = $group->where('status', 'completed')->count();
            $overdue = $group->filter(fn ($task) => $task->isOverdue())->count();
            $rows[] = [
                $this->csvEscape((string) $name),
                $group->count(),
                $group->where('status', 'pending')->count(),
                $group->where('status', 'in_progress')->count(),
                $completed,
                $overdue,
                $group->count() ? round(($completed / $group->count()) * 100, 1).'%' : '0%',
            ];
        }

        return "\xEF\xBB\xBF".implode("\n", array_map(fn ($row) => implode(',', $row), $rows));
    }

    private function csvEscape(?string $value): string
    {
        $value = (string) $value;
        if (str_starts_with($value, '=') || str_starts_with($value, '+') || str_starts_with($value, '-') || str_starts_with($value, '@') || str_starts_with($value, "\t") || str_starts_with($value, "\r")) {
            $value = "'".$value;
        }
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}
