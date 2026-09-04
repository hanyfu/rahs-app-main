<?php

namespace Database\Seeders;

use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['view_dashboard', 'View Dashboard', 'Dashboard Access', true, true, true, true],
            ['view_tasks', 'View Tasks', 'Task Management', true, true, true, true],
            ['create_tasks', 'Create Tasks', 'Task Management', true, false, true, true],
            ['edit_tasks', 'Edit Tasks', 'Task Management', true, false, true, true],
            ['delete_tasks', 'Delete Tasks', 'Task Management', true, false, false, false],
            ['view_reports', 'View Reports', 'Task Management', true, true, true, false],
            ['view_hospitals', 'View Hospitals', 'Hospital Management', true, true, true, true],
            ['manage_hospitals', 'Manage Hospitals', 'Hospital Management', true, false, false, false],
            ['edit_hospital_profiles', 'Edit Hospital Profiles', 'Hospital Management', true, false, false, false],
            ['manage_atolls', 'Manage Atolls', 'Location Management', true, false, false, false],
            ['manage_islands', 'Manage Islands', 'Location Management', true, false, false, false],
            ['view_users', 'View Users', 'User Management', true, true, false, false],
            ['manage_users', 'Manage Users', 'User Management', true, false, false, false],
            ['manage_departments', 'Manage Departments', 'Settings', true, false, false, false],
            ['view_operations', 'View Hospital Operations', 'Hospital Operations', true, true, true, true],
            ['manage_operations', 'Manage Hospital Operations', 'Hospital Operations', true, true, true, true],
        ];

        foreach ($permissions as [$key, $name, $cat, $adm, $sup, $coo, $sta]) {
            RolePermission::firstOrCreate(
                ['permission_key' => $key],
                [
                    'permission_name' => $name,
                    'category' => $cat,
                    'admin_access' => $adm,
                    'supervisor_access' => $sup,
                    'coordinator_access' => $coo,
                    'staff_access' => $sta,
                    'user_access' => false,
                ]
            );
        }

        $this->command?->info('Permissions seeded.');
    }
}
