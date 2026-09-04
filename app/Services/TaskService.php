<?php

namespace App\Services;

use App\Models\Atoll;
use App\Models\Island;
use App\Models\Profile;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class TaskService
{
    public function currentProfile(): ?Profile
    {
        $userId = auth()->id();

        return $userId ? Profile::query()->find($userId) : null;
    }

    public function applyTaskAccess(Builder $query, string $role, ?string $userId): Builder
    {
        if ($role === 'admin') {
            return $query;
        }

        if (in_array($role, ['coordinator', 'supervisor'], true)) {
            $assignmentColumn = $role === 'coordinator' ? 'coordinator_id' : 'supervisor_id';
            $atollIds = Atoll::query()->where($assignmentColumn, $userId)->pluck('id');
            $islandIds = Island::query()->whereIn('atoll_id', $atollIds)->pluck('id');

            return $query->where(function ($q) use ($userId, $islandIds) {
                $q->where('assigned_to', $userId)
                    ->orWhere('assigned_by', $userId)
                    ->orWhereIn('island_id', $islandIds);
            });
        }

        return $query->where(function ($q) use ($userId) {
            $q->where('assigned_to', $userId)
                ->orWhere('assigned_by', $userId);
        });
    }

    public function canUserAccessTask(string $role, ?string $userId, Task $task): bool
    {
        if ($role === 'admin') {
            return true;
        }

        if (in_array($role, ['coordinator', 'supervisor'], true)) {
            if ($task->assigned_to === $userId || $task->assigned_by === $userId) {
                return true;
            }

            if ($task->island_id) {
                $atollId = $task->island?->atoll_id;
                $assignmentColumn = $role === 'coordinator' ? 'coordinator_id' : 'supervisor_id';
                if ($atollId && Atoll::query()->where('id', $atollId)->where($assignmentColumn, $userId)->exists()) {
                    return true;
                }
            }

            return false;
        }

        return $task->assigned_to === $userId || $task->assigned_by === $userId;
    }

    /**
     * @throws ValidationException
     */
    public function validateTaskWriteRules(string $role, ?string $userId, array $candidate, bool $isCreate = false, ?Task $existingTask = null): void
    {
        if ($role === 'admin') {
            return;
        }

        $leaveService = new LeaveService;

        // Staff can only create or move tasks to their own assigned island.
        if ($role === 'staff') {
            $assignedIslandId = Island::query()
                ->where('assigned_staff_id', $userId)
                ->where('status', 'active')
                ->value('id');

            if ($isCreate) {
                if (! $assignedIslandId) {
                    throw ValidationException::withMessages(['island_id' => 'Staff must be assigned to an island before creating tasks']);
                }
                if (empty($candidate['island_id'])) {
                    throw ValidationException::withMessages(['island_id' => 'Staff can only create tasks in the name of their assigned island']);
                }
            }

            if (! empty($candidate['island_id'])) {
                $islandExists = Island::query()
                    ->where('id', $candidate['island_id'])
                    ->where('assigned_staff_id', $userId)
                    ->exists();
                if (! $islandExists) {
                    throw ValidationException::withMessages(['island_id' => 'Staff can only create or move tasks within their assigned island']);
                }
            }
        }

        // Coordinator can only create or move tasks within their assigned atolls.
        if ($role === 'coordinator') {
            if ($isCreate && empty($candidate['island_id'])) {
                throw ValidationException::withMessages(['island_id' => 'Coordinator can only create tasks within assigned atoll(s)']);
            }

            if (! empty($candidate['island_id'])) {
                $inScope = Island::query()
                    ->where('islands.id', $candidate['island_id'])
                    ->join('atolls', 'atolls.id', '=', 'islands.atoll_id')
                    ->where('atolls.coordinator_id', $userId)
                    ->exists();
                if (! $inScope) {
                    throw ValidationException::withMessages(['island_id' => 'Coordinator can only create or move tasks within assigned atoll(s)']);
                }
            }
        }

        // Supervisors can create or move tasks across every atoll assigned to them.
        if ($role === 'supervisor') {
            if ($isCreate && empty($candidate['island_id'])) {
                throw ValidationException::withMessages(['island_id' => 'Supervisor can only create tasks within assigned atoll(s)']);
            }

            if (! empty($candidate['island_id'])) {
                $inScope = Island::query()
                    ->where('islands.id', $candidate['island_id'])
                    ->join('atolls', 'atolls.id', '=', 'islands.atoll_id')
                    ->where('atolls.supervisor_id', $userId)
                    ->exists();
                if (! $inScope) {
                    throw ValidationException::withMessages(['island_id' => 'Supervisor can only create or move tasks within assigned atoll(s)']);
                }
            }
        }

        $assigneeId = $candidate['assigned_to'] ?? null;
        if (! $assigneeId) {
            return;
        }

        $assigneeRole = $leaveService->getUserRoleById($assigneeId);
        if (! $assigneeRole) {
            throw ValidationException::withMessages(['assigned_to' => 'Assigned user does not have a valid role']);
        }

        if ($role === 'staff') {
            $coordinatorIds = $leaveService->getStaffAssignedCoordinatorIds($userId);
            $canAssign = $assigneeId === $userId || in_array($assigneeId, $coordinatorIds, true);
            if (! $canAssign) {
                throw ValidationException::withMessages(['assigned_to' => 'Staff can only assign tasks to themselves or their assigned coordinator']);
            }
        }

        if ($role === 'coordinator') {
            if ($assigneeRole === 'supervisor') {
                $inScope = Atoll::query()
                    ->where('coordinator_id', $userId)
                    ->where('supervisor_id', $assigneeId)
                    ->exists();
                if (! $inScope) {
                    throw ValidationException::withMessages(['assigned_to' => 'Coordinator can only assign to their assigned supervisor']);
                }

                return;
            }

            if ($assigneeRole !== 'staff') {
                throw ValidationException::withMessages(['assigned_to' => 'Coordinator can only assign tasks to staff in assigned atoll(s) or assigned supervisor']);
            }

            $islandId = $candidate['island_id'] ?? $existingTask?->island_id ?? null;
            if (! $islandId) {
                throw ValidationException::withMessages(['island_id' => 'Task island is required when assigning to staff']);
            }

            $inScope = Island::query()
                ->where('islands.id', $islandId)
                ->where('islands.assigned_staff_id', $assigneeId)
                ->join('atolls', 'atolls.id', '=', 'islands.atoll_id')
                ->where('atolls.coordinator_id', $userId)
                ->exists();
            if (! $inScope) {
                throw ValidationException::withMessages(['assigned_to' => 'Coordinator can only assign tasks to staff from their assigned atoll(s)']);
            }
        }

        if (! $isCreate
            && ! in_array($role, ['supervisor', 'admin'], true)
            && ! empty($candidate['assigned_by'])
            && $existingTask
            && $candidate['assigned_by'] !== $existingTask->assigned_by) {
            throw ValidationException::withMessages(['assigned_by' => 'Only supervisors can change task creator assignment']);
        }
    }
}
