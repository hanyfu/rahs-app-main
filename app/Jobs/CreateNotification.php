<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly ?string $userId,
        public readonly string $title,
        public readonly string $message,
        public readonly ?string $taskId = null,
    ) {}

    public function handle(PushNotificationService $push): void
    {
        if ($this->userId === null) {
            return;
        }

        Notification::create([
            'user_id' => $this->userId,
            'task_id' => $this->taskId,
            'title' => $this->title,
            'message' => $this->message,
            'is_read' => false,
        ]);

        $push->send(
            $this->userId,
            $this->title,
            $this->message,
            $this->taskId ? url('/tasks?task='.$this->taskId) : url('/hospital-operations')
        );
    }
}
