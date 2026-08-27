<?php

namespace App\Console\Commands;

use App\Models\ScheduledReport;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendScheduledReports extends Command
{
    protected $signature = 'rahs:send-scheduled-reports';

    protected $description = 'Send due scheduled reports to their recipients';

    public function handle(): int
    {
        $now = now();

        $reports = ScheduledReport::query()
            ->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->where('last_sent_at', '<', $now->copy()->subDay()->toDateString())
                    ->orWhereNull('last_sent_at');
            })
            ->get();

        $sent = 0;

        foreach ($reports as $report) {
            if (! $this->isDue($report, $now)) {
                continue;
            }

            $this->sendReport($report);
            $report->update(['last_sent_at' => $now->toDateString()]);
            $sent++;
        }

        $this->info("Scheduled reports sent: {$sent}");

        return self::SUCCESS;
    }

    private function isDue(ScheduledReport $report, Carbon $now): bool
    {
        if ($now->format('H:i') < $report->time_of_day) {
            return false;
        }

        return match ($report->frequency) {
            'daily' => true,
            'weekly' => $now->dayOfWeek === (int) $report->day_of_week,
            'monthly' => $now->day === (int) $report->day_of_month,
            default => false,
        };
    }

    private function sendReport(ScheduledReport $report): void
    {
        $filters = $report->filters ?? [];

        $query = Task::query();

        if (! empty($filters['island_id'] ?? $filters['islandFilter'] ?? null)) {
            $query->where('island_id', $filters['island_id'] ?? $filters['islandFilter']);
        }
        if (! empty($filters['department_id'] ?? $filters['departmentFilter'] ?? null)) {
            $query->where('department_id', $filters['department_id'] ?? $filters['departmentFilter']);
        }
        if (! empty($filters['atoll_id'])) {
            $query->whereHas('island', fn ($islands) => $islands->where('atoll_id', $filters['atoll_id']));
        }
        foreach (['assigned_to', 'status', 'priority'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $reportType = $filters['report_type'] ?? 'tasks';
        if ($reportType === 'completed') {
            $query->where('status', 'completed');
        }

        $tasks = $query->get();
        if ($reportType === 'overdue') {
            $tasks = $tasks->filter(fn ($task) => $task->isOverdue());
        }
        $counts = [
            'total' => $tasks->count(),
            'pending' => $tasks->where('status', 'pending')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
        ];

        $recipients = array_values(array_filter($report->recipients ?? []));

        foreach ($recipients as $email) {
            try {
                Mail::raw(
                    "RAHS Report: {$report->name}\n\n".
                    "Total tasks: {$counts['total']}\n".
                    "Pending: {$counts['pending']}\n".
                    "In progress: {$counts['in_progress']}\n".
                    "Completed: {$counts['completed']}\n\n".
                    'Report type: '.str_replace('-', ' ', $reportType)."\n".
                    'Generated on '.now()->format('Y-m-d H:i'),
                    function ($message) use ($email, $report) {
                        $message->to($email)
                            ->subject("RAHS Report: {$report->name}");
                    }
                );
            } catch (\Throwable $e) {
                $this->error("Failed to send report to {$email}: {$e->getMessage()}");
            }
        }
    }
}
