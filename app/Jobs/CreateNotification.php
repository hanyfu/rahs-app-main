<?php

namespace App\Jobs;

use App\Models\Notification;
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

    public function handle(): void
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
    }
}
