<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCallLogRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCallLogRequest;
use App\Models\CallLog;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskComment;
use App\Services\NotificationService;
use App\Services\TaskService;
use Illuminate\Validation\ValidationException;

class TaskInteractionController extends Controller
{
    private TaskService $taskService;

    private NotificationService $notifications;

    public function __construct()
    {
        $this->taskService = new TaskService;
        $this->notifications = new NotificationService;
    }

    public function comments(string $taskId)
    {
        $this->requireTaskAccess($taskId);

        return response()->json(
            TaskComment::query()->with('user:id,first_name,last_name,avatar_url')->where('task_id', $taskId)->orderBy('created_at')->get()
        );
    }

    public function storeComment(StoreCommentRequest $request, string $taskId)
    {
        $task = Task::query()->findOrFail($taskId);
        $this->requireTaskAccess($taskId);

        $data = $request->validated();

        $comment = TaskComment::create([
            'task_id' => $taskId,
            'user_id' => auth()->id(),
            'content' => $data['content'],
        ]);

        $this->notifications->sendTaskNotification('comment', $taskId, auth()->id(), null, null, $data['content']);

        return response()->json($comment->load('user:id,first_name,last_name,avatar_url'), 201);
    }

    public function destroyComment(string $taskId, string $commentId)
    {
        $comment = TaskComment::query()->where('task_id', $taskId)->findOrFail($commentId);
        $this->requireTaskAccess($taskId);

        if ($comment->user_id !== auth()->id() && auth()->user()->role === 'staff') {
            throw ValidationException::withMessages(['comment' => 'You can only delete your own comments']);
        }

        $comment->delete();

        return response()->json(['success' => true]);
    }

    public function activities(string $taskId)
    {
        $this->requireTaskAccess($taskId);

        return response()->json(
            TaskActivity::query()->with('user:id,first_name,last_name,avatar_url')->where('task_id', $taskId)->orderBy('created_at', 'desc')->get()
        );
    }

    public function callLogs(string $taskId)
    {
        $this->requireTaskAccess($taskId);

        return response()->json(
            CallLog::query()->with('user:id,first_name,last_name,avatar_url')->where('task_id', $taskId)->orderBy('call_date', 'desc')->get()
        );
    }

    public function storeCallLog(StoreCallLogRequest $request, string $taskId)
    {
        $this->requireTaskAccess($taskId);

        $data = $request->validated();

        $log = CallLog::create([
            'task_id' => $taskId,
            'user_id' => auth()->id(),
            ...$data,
        ]);

        return response()->json($log->load('user:id,first_name,last_name,avatar_url'), 201);
    }

    public function updateCallLog(UpdateCallLogRequest $request, string $taskId, string $logId)
    {
        $log = CallLog::query()->where('task_id', $taskId)->findOrFail($logId);
        $this->requireTaskAccess($taskId);

        $data = $request->validated();

        $log->update($data);

        return response()->json($log->load('user:id,first_name,last_name,avatar_url'));
    }

    public function destroyCallLog(string $taskId, string $logId)
    {
        $log = CallLog::query()->where('task_id', $taskId)->findOrFail($logId);
        $this->requireTaskAccess($taskId);

        if ($log->attachment_url) {
            $name = basename($log->attachment_url);
            $path = public_path("uploads/{$name}");
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $log->delete();

        return response()->json(['success' => true]);
    }

    private function requireTaskAccess(string $taskId): void
    {
        $task = Task::query()->findOrFail($taskId);

        if (! $this->taskService->canUserAccessTask(auth()->user()->role, auth()->id(), $task)) {
            throw ValidationException::withMessages(['task' => 'Forbidden: You cannot access this task']);
        }
    }
}
