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
        'staff_name', 'staff_id', 'staff_category', 'department_unit',
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
        'leave_start_date' => 'date',
        'leave_end_date' => 'date',
        'number_of_leave_days' => 'integer',
    ];

    public const APPROVAL_STATUSES = ['submitted', 'pending_review', 'approved', 'rejected', 'cancelled'];

    public const CRITICAL_LEVELS = ['low', 'medium', 'high', 'critical'];

    public const URGENCIES = ['normal', 'urgent', 'emergency'];

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

    public function isActiveStatus(): bool
    {
        return in_array($this->approval_status, ['submitted', 'pending_review', 'approved']);
    }

    public function isHighRisk(): bool
    {
        return in_array($this->critical_level, ['high', 'critical']) || in_array($this->urgency, ['urgent', 'emergency']);
    }
}
