<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = [
            'idx_tasks_archived_status' => 'CREATE INDEX IF NOT EXISTS idx_tasks_archived_status ON tasks(archived, status)',
            'idx_tasks_archived_created_at' => 'CREATE INDEX IF NOT EXISTS idx_tasks_archived_created_at ON tasks(archived, created_at DESC)',
            'idx_notifications_user_read' => 'CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications(user_id, is_read)',
            'idx_notifications_created_at' => 'CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON notifications(created_at DESC)',
            'idx_task_comments_task_created' => 'CREATE INDEX IF NOT EXISTS idx_task_comments_task_created ON task_comments(task_id, created_at)',
            'idx_task_activities_task_created' => 'CREATE INDEX IF NOT EXISTS idx_task_activities_task_created ON task_activities(task_id, created_at DESC)',
            'idx_call_logs_task_date' => 'CREATE INDEX IF NOT EXISTS idx_call_logs_task_date ON call_logs(task_id, call_date DESC)',
            'idx_profiles_status' => 'CREATE INDEX IF NOT EXISTS idx_profiles_status ON profiles(status)',
            'idx_hospital_contacts_status_name' => 'CREATE INDEX IF NOT EXISTS idx_hospital_contacts_status_name ON hospital_contacts(status, hospital_name)',
            'idx_islands_status' => 'CREATE INDEX IF NOT EXISTS idx_islands_status ON islands(status)',
            'idx_atolls_status' => 'CREATE INDEX IF NOT EXISTS idx_atolls_status ON atolls(status)',
            'idx_cs_leaves_created_by' => 'CREATE INDEX IF NOT EXISTS idx_cs_leaves_created_by ON critical_staff_leaves(created_by)',
            'idx_cs_leaves_created_at' => 'CREATE INDEX IF NOT EXISTS idx_cs_leaves_created_at ON critical_staff_leaves(created_at DESC)',
            'idx_scheduled_reports_active_due' => 'CREATE INDEX IF NOT EXISTS idx_scheduled_reports_active_due ON scheduled_reports(is_active, last_sent_at)',
            'idx_cs_leaves_scope_shift' => 'CREATE INDEX IF NOT EXISTS idx_cs_leaves_scope_shift ON critical_staff_leaves(staff_category, shift_affected, approval_status)',
        ];

        foreach ($indexes as $sql) {
            DB::statement($sql);
        }
    }

    public function down(): void
    {
        // Intentionally not dropping indexes to avoid disrupting running queries.
    }
};
