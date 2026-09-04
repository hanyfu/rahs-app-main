<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Profile extends Model
{
    use HasUuids;

    protected $table = 'profiles';

    protected $fillable = [
        'id', 'email', 'first_name', 'last_name', 'avatar_url', 'designation',
        'contact_no', 'department_id', 'user_department_id', 'manager_id', 'status',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'department_id' => 'string',
        'user_department_id' => 'string',
        'manager_id' => 'string',
    ];

    public function userRole(): HasOne
    {
        return $this->hasOne(UserRole::class, 'user_id', 'id');
    }

    public function getRoleAttribute(): string
    {
        return $this->userRole?->role ?? 'user';
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function userDepartment(): BelongsTo
    {
        return $this->belongsTo(UserDepartment::class, 'user_department_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'manager_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: ($this->email ?? 'Unknown');
    }

    public function getInitialsAttribute(): string
    {
        $initials = substr($this->first_name ?? '', 0, 1).substr($this->last_name ?? '', 0, 1);

        return strtoupper($initials ?: substr($this->email ?? '?', 0, 1));
    }
}
