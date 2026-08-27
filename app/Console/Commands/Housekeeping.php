<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class Housekeeping extends Command
{
    protected $signature = 'rahs:housekeeping';

    protected $description = 'Auto-archive completed tasks after 3 days and delete archived tasks after 30 days';

    public function handle(): int
    {
        $threeDaysAgo = now()->subDays(3);
        $thirtyDaysAgo = now()->subDays(30);

        $archived = Task::query()
            ->where('status', 'completed')
            ->where('archived', false)
            ->where('updated_at', '<', $threeDaysAgo)
            ->update(['archived' => true, 'updated_at' => now()]);

        if ($archived > 0) {
            $this->info("Housekeeping: auto-archived {$archived} completed task(s)");
        }

        $deletedTasks = Task::query()
            ->where('archived', true)
            ->where('updated_at', '<', $thirtyDaysAgo)
            ->get(['id', 'attachment_url']);

        $deleted = 0;
        foreach ($deletedTasks as $task) {
            // forceDelete() also removes the attachment file and related
            // comments/activities/call logs (see Task::forceDeleting).
            $task->forceDelete();
            $deleted++;
        }

        if ($deleted > 0) {
            $this->info("Housekeeping: auto-deleted {$deleted} aged archived task(s)");
        }

        return self::SUCCESS;
    }
}
