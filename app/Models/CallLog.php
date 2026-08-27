<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallLog extends Model
{
    use HasUuids;

    protected $table = 'call_logs';

    protected $fillable = ['task_id', 'user_id', 'contact_name', 'contact_phone', 'notes', 'call_date', 'attachment_url'];

    protected $keyType = 'string';

    public $incrementing = false;

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
