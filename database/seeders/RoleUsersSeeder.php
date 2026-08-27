<?php

namespace Database\Seeders;

use App\Models\AuthUser;
use App\Models\Profile;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['email' => 'admin@rahs.mv', 'role' => 'admin', 'firstName' => 'System', 'lastName' => 'Admin', 'designation' => 'Administrator'],
            ['email' => 'supervisor@rahs.mv', 'role' => 'supervisor', 'firstName' => 'Senior', 'lastName' => 'Supervisor', 'designation' => 'Hospital Supervisor'],
            ['email' => 'coordinator@rahs.mv', 'role' => 'coordinator', 'firstName' => 'Regional', 'lastName' => 'Coordinator', 'designation' => 'Atoll Coordinator'],
            ['email' => 'staff@rahs.mv', 'role' => 'staff', 'firstName' => 'General', 'lastName' => 'Staff', 'designation' => 'Operations Staff'],
        ];

        foreach ($users as $user) {
            if (AuthUser::where('email', $user['email'])->exists()) {
                continue;
            }

            $id = (string) Str::uuid();
            AuthUser::create(['id' => $id, 'email' => $user['email'], 'password' => 'password123']);
            Profile::create([
                'id' => $id,
                'email' => $user['email'],
                'first_name' => $user['firstName'],
                'last_name' => $user['lastName'],
                'designation' => $user['designation'],
            ]);
            UserRole::create(['user_id' => $id, 'role' => $user['role']]);

            $this->command?->info("Seeded {$user['role']}: {$user['email']} / password123");
        }
    }
}
