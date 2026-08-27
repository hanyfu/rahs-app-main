<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use HasUuids;

    protected $table = 'role_permissions';

    protected $fillable = [
        'permission_key', 'permission_name', 'permission_description', 'category',
        'admin_access', 'supervisor_access', 'coordinator_access', 'staff_access', 'user_access',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'admin_access' => 'boolean',
        'supervisor_access' => 'boolean',
        'coordinator_access' => 'boolean',
        'staff_access' => 'boolean',
        'user_access' => 'boolean',
    ];
}
