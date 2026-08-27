<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = [
            'idx_tasks_island_id' => 'CREATE INDEX IF NOT EXISTS idx_tasks_island_id ON tasks(island_id)',
            'idx_tasks_assigned_to' => 'CREATE INDEX IF NOT EXISTS idx_tasks_assigned_to ON tasks(assigned_to)',
            'idx_tasks_status' => 'CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status)',
            'idx_tasks_due_date' => 'CREATE INDEX IF NOT EXISTS idx_tasks_due_date ON tasks(due_date)',
            'idx_tasks_assigned_by' => 'CREATE INDEX IF NOT EXISTS idx_tasks_assigned_by ON tasks(assigned_by)',
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
