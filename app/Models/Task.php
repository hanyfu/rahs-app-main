<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'tasks';

    protected $fillable = [
        'id', 'title', 'creator_description', 'completion_description', 'status',
        'priority', 'assigned_by', 'assigned_to', 'department_id', 'island_id',
        'due_date', 'archived', 'task_types', 'attachment_url',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'archived' => 'boolean',
        'task_types' => 'array',
        'assigned_by' => 'string',
        'assigned_to' => 'string',
        'department_id' => 'string',
        'island_id' => 'string',
    ];

    public function assignor(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'assigned_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'assigned_to');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function island(): BelongsTo
    {
        return $this->belongsTo(Island::class, 'island_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'task_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class, 'task_id');
    }

    public function callLogs(): HasMany
    {
        return $this->hasMany(CallLog::class, 'task_id');
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->status !== 'completed'
            && strtotime($this->due_date) < strtotime('today');
    }

    protected static function booted(): void
    {
        // Soft deletion keeps the task (and its comments/activity history)
        // intact for audit purposes. Cleanup only happens on a hard delete,
        // which the Housekeeping purge triggers via forceDelete().
        static::forceDeleting(function (Task $task) {
            $task->comments()->delete();
            $task->activities()->delete();
            $task->callLogs()->delete();

            if ($task->attachment_url) {
                $path = public_path('uploads/'.basename($task->attachment_url));
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        });
    }
}
