<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CriticalStaffAvailabilitySetup extends Model
{
    use HasUuids;

    protected $table = 'critical_staff_availability_setup';

    protected $fillable = [
        'department_unit', 'staff_category', 'shift', 'total_active_staff',
        'required_minimum_staff', 'coordinator_responsible', 'status',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'total_active_staff' => 'integer',
        'required_minimum_staff' => 'integer',
        'coordinator_responsible' => 'string',
    ];

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'coordinator_responsible');
    }
}
