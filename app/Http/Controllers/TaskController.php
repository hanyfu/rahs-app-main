<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Atoll;
use App\Models\Department;
use App\Models\Island;
use App\Models\Profile;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Services\LeaveService;
use App\Services\NotificationService;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    private TaskService $taskService;

    private NotificationService $notifications;

    public function __construct()
    {
        $this->taskService = new TaskService;
        $this->notifications = new NotificationService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $role = $user->role;
        $me = $user->id;

        $query = Task::query()->with(['assignor', 'assignee', 'department', 'island.atoll']);

        if ($role !== 'admin') {
            $query = $this->taskService->applyTaskAccess($query, $role, $me);
        }

        if (! in_array($role, ['admin', 'supervisor'], true)) {
            $query->where('archived', false);
        }

        $query->orderBy('created_at', 'desc')->limit(100);

        $tasks = $query->get();

        $nextCursor = null;
        if ($tasks->count() === 100) {
            $last = $tasks->last();
            $nextCursor = $last->created_at->format('Y-m-d H:i:s.u').'_'.$last->id;
        }

        // Archived counts for admin/supervisor/coordinator
        $archivedCounts = ['completed' => 0, 'cancelled' => 0];
        if (! in_array($role, ['staff'], true)) {
            $archivedQuery = Task::query()->where('archived', true)->whereIn('status', ['completed', 'cancelled']);
            $archivedQuery = $this->taskService->applyTaskAccess($archivedQuery, $role, $me);
            $archivedTasks = $archivedQuery->get(['status']);
            $archivedCounts = [
                'completed' => $archivedTasks->where('status', 'completed')->count(),
                'cancelled' => $archivedTasks->where('status', 'cancelled')->count(),
            ];
        }

        $atolls = Atoll::query()->where('status', 'active')->orderBy('name')->get();
        $islands = Island::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'atoll_id', 'assigned_staff_id']);
        $profiles = Profile::query()->where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'avatar_url', 'designation']);
        $departments = Department::query()->where('status', 'active')->orderBy('name')->get();

        // Assignee dropdown options, scoped per role. `profiles` stays full so
        // task cards can still resolve any assignee's name.
        $assignableProfiles = $profiles;

        if ($role === 'staff') {
            // Staff only create tasks in the name of their assigned island.
            $myIslandIds = Island::query()->where('assigned_staff_id', $me)->pluck('id');
            $myAtollIds = Island::query()->whereIn('id', $myIslandIds)->pluck('atoll_id')->unique();
            $atolls = $atolls->whereIn('id', $myAtollIds)->values();
            $islands = $islands->whereIn('id', $myIslandIds)->values();

            // Staff may only assign to themselves or their assigned coordinator(s).
            $coordinatorIds = (new LeaveService)->getStaffAssignedCoordinatorIds($me);
            $assignableProfiles = $profiles->whereIn('id', collect([$me])->merge($coordinatorIds)->unique())->values();
        } elseif ($role === 'coordinator') {
            // Coordinators only create tasks within their assigned atolls.
            $myAtollIds = Atoll::query()->where('coordinator_id', $me)->pluck('id');
            $atolls = $atolls->whereIn('id', $myAtollIds)->values();
            $islands = $islands->whereIn('atoll_id', $myAtollIds)->values();

            // Coordinators may assign to staff in their atolls, their assigned
            // supervisors, or themselves.
            $islandIds = Island::query()->whereIn('atoll_id', $myAtollIds)->pluck('id');
            $staffIds = Island::query()->whereIn('id', $islandIds)->whereNotNull('assigned_staff_id')->pluck('assigned_staff_id');
            $supervisorIds = Atoll::query()->whereIn('id', $myAtollIds)->whereNotNull('supervisor_id')->pluck('supervisor_id');
            $assignableIds = $staffIds->merge($supervisorIds)->push($me)->unique();
            $assignableProfiles = $profiles->whereIn('id', $assignableIds)->values();
        } elseif ($role === 'supervisor') {
            // Supervisors may cover multiple atolls and all hospitals within them.
            $myAtollIds = Atoll::query()->where('supervisor_id', $me)->pluck('id');
            $atolls = $atolls->whereIn('id', $myAtollIds)->values();
            $islands = $islands->whereIn('atoll_id', $myAtollIds)->values();

            $staffIds = Island::query()->whereIn('atoll_id', $myAtollIds)->whereNotNull('assigned_staff_id')->pluck('assigned_staff_id');
            $coordinatorIds = Atoll::query()->whereIn('id', $myAtollIds)->whereNotNull('coordinator_id')->pluck('coordinator_id');
            $assignableIds = $staffIds->merge($coordinatorIds)->push($me)->unique();
            $assignableProfiles = $profiles->whereIn('id', $assignableIds)->values();
        }

        $viewMode = $request->query('view', 'list');

        return view('tasks.index', compact('tasks', 'nextCursor', 'archivedCounts', 'atolls', 'islands', 'profiles', 'assignableProfiles', 'departments', 'role', 'viewMode'));
    }

    public function apiIndex(Request $request)
    {
        $user = auth()->user();
        $role = $user->role;
        $me = $user->id;

        $query = Task::query()->with(['assignor', 'assignee', 'department', 'island.atoll']);

        if ($role !== 'admin') {
            $query = $this->taskService->applyTaskAccess($query, $role, $me);
        }

        if (! in_array($role, ['admin', 'supervisor'], true)) {
            $query->where('archived', false);
        }

        $limit = min(max((int) $request->query('limit', 100), 1), 200);

        $cursor = $request->query('cursor');
        if (is_string($cursor) && $cursor !== '') {
            [$cursorCreatedAt, $cursorId] = array_pad(explode('_', $cursor, 2), 2, null);
            if ($cursorCreatedAt && $cursorId) {
                $query->where(function ($q) use ($cursorCreatedAt, $cursorId) {
                    $q->where('created_at', '<', $cursorCreatedAt)
                        ->orWhere(function ($q2) use ($cursorCreatedAt, $cursorId) {
                            $q2->where('created_at', '=', $cursorCreatedAt)->where('id', '<', $cursorId);
                        });
                });
            }
        }

        $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');

        $tasks = $query->limit($limit + 1)->get();
        $hasMore = $tasks->count() > $limit;
        $pageTasks = $tasks->take($limit)->values();

        $nextCursor = null;
        if ($hasMore) {
            $last = $pageTasks->last();
            $nextCursor = $last->created_at->format('Y-m-d H:i:s.u').'_'.$last->id;
        }

        return response()->json([
            'tasks' => $pageTasks,
            'next_cursor' => $nextCursor,
        ]);
    }

    public function store(StoreTaskRequest $request)
    {
        $this->requirePermission('create_tasks');
        $data = $request->validated();

        $user = auth()->user();
        $data['assigned_by'] = $request->input('assigned_by') ?: $user->id;
        // Staff and coordinators always create tasks in their own name.
        if (! in_array($user->role, ['admin', 'supervisor'], true)) {
            $data['assigned_by'] = $user->id;
        }
        $data['status'] = $data['status'] ?? 'pending';
        $data['task_types'] = $data['task_types'] ?? [];

        $this->taskService->validateTaskWriteRules($user->role, $user->id, $data, true, null);

        $task = Task::create($data);

        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'action' => 'created',
        ]);

        $this->notifications->sendTaskNotification('task_created', $task->id, $user->id);

        return response()->json($task->load(['assignor', 'assignee', 'department', 'island.atoll']), 201);
    }

    public function update(UpdateTaskRequest $request, string $id)
    {
        $this->requirePermission('edit_tasks');
        $task = Task::query()->findOrFail($id);

        if (! $this->taskService->canUserAccessTask(auth()->user()->role, auth()->id(), $task)) {
            throw ValidationException::withMessages(['task' => 'Forbidden: You cannot update this task']);
        }

        if ($task->archived && ! in_array(auth()->user()->role, ['admin', 'supervisor'], true)) {
            throw ValidationException::withMessages(['task' => 'Archived tasks cannot be modified']);
        }

        $data = $request->validated();

        $candidate = array_merge($task->only([
            'title', 'creator_description', 'completion_description', 'status', 'priority',
            'assigned_by', 'assigned_to', 'department_id', 'island_id', 'due_date', 'archived', 'task_types',
        ]), array_filter($data, fn ($v) => $v !== null));

        $oldStatus = $task->status;
        $oldAssignee = $task->assigned_to;

        $this->taskService->validateTaskWriteRules(auth()->user()->role, auth()->id(), $candidate, false, $task);

        // Never write nulls: fields omitted or nulled by the client must keep
        // their existing value rather than being cleared (validation only ever
        // saw the non-null candidate above).
        $patch = array_filter($data, fn ($v) => $v !== null);

        $changed = $this->diff($task, $patch);

        $task->update($patch);
        $task->refresh();

        // Log activities
        foreach ($changed as $field => $values) {
            if ($field === 'archived') {
                TaskActivity::create([
                    'task_id' => $task->id,
                    'user_id' => auth()->id(),
                    'action' => 'updated',
                    'field_name' => 'archived',
                    'old_value' => $values['old'] ? 'true' : 'false',
                    'new_value' => $values['new'] ? 'true' : 'false',
                ]);
            } else {
                TaskActivity::create([
                    'task_id' => $task->id,
                    'user_id' => auth()->id(),
                    'action' => 'updated',
                    'field_name' => $field,
                    'old_value' => $values['old'],
                    'new_value' => $values['new'],
                ]);
            }
        }

        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            $this->notifications->sendTaskNotification('status_change', $task->id, auth()->id(), $oldStatus, $data['status']);
        }

        if (isset($data['assigned_to']) && $data['assigned_to'] !== $oldAssignee) {
            $this->notifications->sendTaskNotification('reassigned', $task->id, auth()->id());
        }

        return response()->json($task->load(['assignor', 'assignee', 'department', 'island.atoll']));
    }

    public function destroy(Request $request, string $id)
    {
        $this->requirePermission('delete_tasks');
        $task = Task::query()->findOrFail($id);

        if (! in_array(auth()->user()->role, ['admin', 'supervisor'], true)) {
            throw ValidationException::withMessages(['task' => 'Only administrators can delete tasks']);
        }

        // Soft delete: the task (and its comments/history) is kept for audit
        // purposes and only permanently removed by the Housekeeping purge.
        $task->delete();

        return response()->json(['success' => true]);
    }

    private function diff(Task $task, array $data): array
    {
        $changed = [];
        foreach ($data as $key => $value) {
            $old = $task->getOriginal($key);
            $new = $value;
            if (is_array($old)) {
                $old = json_encode($old);
            }
            if (is_array($new)) {
                $new = json_encode($new);
            }
            if ((string) $old !== (string) $new) {
                $changed[$key] = ['old' => $old, 'new' => $new];
            }
        }

        return $changed;
    }
}
