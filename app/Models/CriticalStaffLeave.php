<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CriticalStaffLeave extends Model
{
    use HasUuids;

    protected $table = 'critical_staff_leaves';

    protected $fillable = [
        'staff_name', 'staff_id', 'staff_category', 'department_unit', 'hospital_profile_id', 'island_id',
        'assigned_coordinator', 'direct_supervisor', 'leave_type',
        'leave_start_date', 'leave_end_date', 'number_of_leave_days',
        'shift_affected', 'reason_for_leave', 'contact_during_leave',
        'replacement_staff', 'handover_notes', 'critical_level', 'urgency',
        'approval_status', 'reviewed_by', 'remarks', 'created_by',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'assigned_coordinator' => 'string',
        'direct_supervisor' => 'string',
        'reviewed_by' => 'string',
        'created_by' => 'string',
        'hospital_profile_id' => 'string',
        'island_id' => 'string',
        'leave_start_date' => 'date',
        'leave_end_date' => 'date',
        'number_of_leave_days' => 'integer',
    ];

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'assigned_coordinator');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'direct_supervisor');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'reviewed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'created_by');
    }

    public function hospitalProfile(): BelongsTo
    {
        return $this->belongsTo(HospitalProfile::class, 'hospital_profile_id');
    }

    public function island(): BelongsTo
    {
        return $this->belongsTo(Island::class, 'island_id');
    }

}
