<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Island extends Model
{
    use HasUuids;

    protected $table = 'islands';

    protected $fillable = ['atoll_id', 'name', 'assigned_staff_id', 'status'];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'atoll_id' => 'string',
        'assigned_staff_id' => 'string',
    ];

    public function atoll(): BelongsTo
    {
        return $this->belongsTo(Atoll::class, 'atoll_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'assigned_staff_id');
    }

    public function hospitalProfiles(): HasMany
    {
        return $this->hasMany(HospitalProfile::class, 'island_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'island_id');
    }
}
