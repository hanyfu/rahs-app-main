<?php

namespace Tests\Feature;

use App\Models\AuthUser;
use App\Models\RolePermission;
use Tests\TestCase;

class RolePermissionEnforcementTest extends TestCase
{
    public function test_every_permission_uses_the_enabled_column_for_each_role(): void
    {
        $users = [
            'admin' => AuthUser::where('email', 'admin@rahs.mv')->firstOrFail(),
            'supervisor' => AuthUser::where('email', 'supervisor@rahs.mv')->firstOrFail(),
            'coordinator' => AuthUser::where('email', 'coordinator@rahs.mv')->firstOrFail(),
            'staff' => AuthUser::where('email', 'staff@rahs.mv')->firstOrFail(),
        ];

        foreach (RolePermission::all() as $permission) {
            $permission->update([
                'supervisor_access' => true,
                'coordinator_access' => false,
                'staff_access' => true,
            ]);

            $this->assertTrue(RolePermission::allows($permission->permission_key, $users['admin']));
            $this->assertTrue(RolePermission::allows($permission->permission_key, $users['supervisor']));
            $this->assertFalse(RolePermission::allows($permission->permission_key, $users['coordinator']));
            $this->assertTrue(RolePermission::allows($permission->permission_key, $users['staff']));
        }
    }

    public function test_page_permission_switch_takes_effect_immediately(): void
    {
        $staff = AuthUser::where('email', 'staff@rahs.mv')->firstOrFail();
        $permission = RolePermission::where('permission_key', 'view_tasks')->firstOrFail();

        $permission->update(['staff_access' => false]);
        $this->actingAs($staff)->get('/tasks')->assertForbidden();

        $permission->update(['staff_access' => true]);
        $this->actingAs($staff)->get('/tasks')->assertOk();
    }

    public function test_api_permission_switch_takes_effect_immediately(): void
    {
        $staff = AuthUser::where('email', 'staff@rahs.mv')->firstOrFail();
        $permission = RolePermission::where('permission_key', 'create_tasks')->firstOrFail();

        $permission->update(['staff_access' => false]);
        $this->actingAs($staff)->postJson('/api/tasks', [])->assertForbidden();

        $permission->update(['staff_access' => true]);
        $this->actingAs($staff)->postJson('/api/tasks', [])->assertUnprocessable();
    }

    public function test_management_permission_controls_dashboard_section_and_api(): void
    {
        $supervisor = AuthUser::where('email', 'supervisor@rahs.mv')->firstOrFail();
        $permission = RolePermission::where('permission_key', 'manage_atolls')->firstOrFail();

        $permission->update(['supervisor_access' => false]);
        $this->actingAs($supervisor)->get('/dashboard?tab=atolls')->assertForbidden();
        $this->actingAs($supervisor)->getJson('/api/atolls')->assertForbidden();

        $permission->update(['supervisor_access' => true]);
        $this->actingAs($supervisor)->get('/dashboard?tab=atolls')->assertOk();
        $this->actingAs($supervisor)->getJson('/api/atolls')->assertOk();
    }
}
