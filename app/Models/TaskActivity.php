<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskActivity extends Model
{
    use HasUuids;

    protected $table = 'task_activities';

    protected $fillable = ['task_id', 'user_id', 'action', 'field_name', 'old_value', 'new_value'];

    protected $keyType = 'string';

    public $incrementing = false;

    public const UPDATED_AT = null;

    protected $casts = [
        'task_id' => 'string',
        'user_id' => 'string',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'user_id');
    }
}
