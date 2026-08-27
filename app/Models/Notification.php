<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasUuids;

    protected $table = 'notifications';

    protected $fillable = ['user_id', 'task_id', 'title', 'message', 'is_read'];

    protected $keyType = 'string';

    public $incrementing = false;

    public const UPDATED_AT = null;

    protected $casts = [
        'is_read' => 'boolean',
        'task_id' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'user_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
