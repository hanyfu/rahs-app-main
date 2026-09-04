<?php

namespace App\Http\Middleware;

use App\Models\RolePermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permissionKey): Response
    {
        if (! RolePermission::allows($permissionKey, $request->user())) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
