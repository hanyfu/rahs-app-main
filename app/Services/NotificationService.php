<?php

namespace App\Services;

use App\Jobs\CreateNotification;
use App\Models\Atoll;
use App\Models\CriticalStaffLeave;
use App\Models\Island;
use App\Models\Task;
use App\Models\UserRole;

class NotificationService
{
    public function insertNotification(?string $userId, string $title, string $message, ?string $taskId = null): void
    {
        CreateNotification::dispatch($userId, $title, $message, $taskId);
    }

    public function notifyUsers(array $userIds, string $title, string $message, ?string $taskId = null): void
    {
        $ids = array_unique(array_filter($userIds));
        foreach ($ids as $uid) {
            $this->insertNotification($uid, $title, $message, $taskId);
        }
    }

    public function getRoleUserIds(array $roles = []): array
    {
        if (empty($roles)) {
            return [];
        }

        return UserRole::query()->whereIn('role', $roles)->pluck('user_id')->all();
    }

    public function sendTaskNotification(
        string $type,
        string $taskId,
        ?string $actorId,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?string $commentContent = null
    ): int {
        $task = Task::query()->find($taskId);
        if (! $task) {
            return 0;
        }

        $actorId = $actorId ?: auth()->id();
        $recipients = collect([$task->assigned_by, $task->assigned_to])
            ->filter()
            ->unique()
            ->reject(fn ($id) => $id === $actorId)
            ->values()
            ->all();

        if (empty($recipients)) {
            return 0;
        }

        $title = 'Task Update';
        $message = "Task \"{$task->title}\" was updated.";

        switch ($type) {
            case 'task_created':
                $title = 'New Task Assigned';
                $message = "A new task \"{$task->title}\" was created and assigned.";
                break;
            case 'reassigned':
                $title = 'Task Reassigned';
                $message = "Task \"{$task->title}\" was reassigned.";
                break;
            case 'status_change':
                $title = 'Task Status Updated';
                $message = "Status changed from \"{$oldStatus}\" to \"{$newStatus}\" for \"{$task->title}\".";
                break;
            case 'comment':
                $title = 'New Task Comment';
                $snippet = $commentContent ? mb_substr($commentContent, 0, 120) : '';
                $message = $snippet
                    ? "New comment on \"{$task->title}\": {$snippet}"
                    : "A new comment was added on \"{$task->title}\".";
                break;
        }

        $created = 0;
        foreach ($recipients as $uid) {
            $this->insertNotification($uid, $title, $message, $task->id);
            $created++;
        }

        return $created;
    }

    public function notifyLeaveCreated(CriticalStaffLeave $leave): void
    {
        $recipients = $this->getLeaveNotificationRecipients($leave);
        $actorName = $leave->staff_name ?: 'Staff member';

        $this->notifyUsers(
            $recipients,
            'Leave Submitted',
            "{$actorName} submitted a leave request ({$leave->leave_type}) for {$leave->department_unit}."
        );

        if (in_array($leave->critical_level, ['critical', 'high'], true) || in_array($leave->urgency, ['urgent', 'emergency'], true)) {
            $this->notifyUsers(
                $recipients,
                'Critical Leave Alert',
                "{$actorName} submitted {$leave->critical_level}/{$leave->urgency} leave (".($leave->shift_affected ?: 'shift not set').').'
            );
        }

        $risk = (new LeaveService)->checkLeaveShortageRisk($leave);
        if ($risk && $risk['shortage']) {
            $this->notifyUsers(
                $recipients,
                'Shortage Alert',
                "Remaining {$leave->staff_category} for {$leave->department_unit} ({$leave->shift_affected}) is below required minimum."
            );
        }
    }

    public function notifyLeaveUpdated(CriticalStaffLeave $leave, bool $statusChanged): void
    {
        $recipients = $this->getLeaveNotificationRecipients($leave);

        if ($statusChanged) {
            $this->notifyUsers(
                [$leave->created_by],
                'Leave Status Updated',
                "Your leave request status is now \"{$leave->approval_status}\"."
            );
            $this->notifyUsers(
                $recipients,
                'Leave Status Changed',
                "{$leave->staff_name} leave is now \"{$leave->approval_status}\"."
            );
        }

        if (in_array($leave->critical_level, ['critical', 'high'], true) || in_array($leave->urgency, ['urgent', 'emergency'], true)) {
            $this->notifyUsers(
                $recipients,
                'Critical Leave Update',
                "{$leave->staff_name} critical/urgent leave status updated to \"{$leave->approval_status}\"."
            );
        }

        $risk = (new LeaveService)->checkLeaveShortageRisk($leave);
        if ($risk && $risk['shortage']) {
            $this->notifyUsers(
                $recipients,
                'Shortage Alert',
                "Remaining {$leave->staff_category} for {$leave->department_unit} ({$leave->shift_affected}) is below required minimum."
            );
        }
    }

    public function getLeaveNotificationRecipients(CriticalStaffLeave $leave): array
    {
        $recipientIds = collect([$leave->assigned_coordinator, $leave->direct_supervisor]);

        if ($leave->island_id) {
            $recipientIds->push(Island::query()->whereKey($leave->island_id)->value('assigned_staff_id'));
        }

        // 1) Replacement island staff
        $replacementIslandName = $this->extractIslandNameFromReplacement($leave->replacement_staff);
        $replacementIslandId = null;
        if ($replacementIslandName) {
            $island = Island::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($replacementIslandName)])
                ->first();
            if ($island) {
                if ($island->assigned_staff_id) {
                    $recipientIds->push($island->assigned_staff_id);
                }
                $replacementIslandId = $island->id;
            }
        }

        // 2) Coordinators handling requesting and replacement atolls
        $requestorIslandId = null;
        if ($leave->created_by) {
            $island = Island::query()->where('assigned_staff_id', $leave->created_by)->first();
            $requestorIslandId = $island?->id;
        }

        $islandIds = array_filter([$requestorIslandId, $replacementIslandId]);
        if (! empty($islandIds)) {
            $coordinatorIds = Atoll::query()
                ->whereIn('id', Island::query()->whereIn('id', $islandIds)->pluck('atoll_id'))
                ->whereNotNull('coordinator_id')
                ->pluck('coordinator_id');
            $recipientIds = $recipientIds->concat($coordinatorIds);
        }

        return $recipientIds->unique()->filter()->values()->all();
    }

    private function extractIslandNameFromReplacement(?string $label): ?string
    {
        if (! $label) {
            return null;
        }

        if (preg_match('/\(([^)]+)\)\s*$/', $label, $m)) {
            return trim($m[1]);
        }

        return null;
    }
}
