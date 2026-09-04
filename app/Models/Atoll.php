<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Atoll extends Model
{
    use HasUuids;

    protected $table = 'atolls';

    protected $fillable = ['name', 'coordinator_id', 'supervisor_id', 'status'];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'coordinator_id' => 'string',
        'supervisor_id' => 'string',
    ];

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'coordinator_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'supervisor_id');
    }
}
