<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;

abstract class Controller
{
    protected function requirePermission(string $permissionKey): void
    {
        if (! RolePermission::allows($permissionKey)) {
            abort(403, 'You do not have permission to perform this action.');
        }
    }
}
