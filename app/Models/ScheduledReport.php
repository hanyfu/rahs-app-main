<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledReport extends Model
{
    use HasUuids;

    protected $table = 'scheduled_reports';

    protected $fillable = [
        'user_id', 'name', 'recipients', 'frequency', 'day_of_week',
        'day_of_month', 'time_of_day', 'filters', 'is_active', 'last_sent_at',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    public const UPDATED_AT = null;

    protected $casts = [
        'recipients' => 'array',
        'filters' => 'array',
        'is_active' => 'boolean',
    ];

    public const FREQUENCIES = ['daily', 'weekly', 'monthly'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'user_id');
    }
}
