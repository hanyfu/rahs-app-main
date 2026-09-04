<?php

namespace Tests\Feature;

use App\Models\Atoll;
use App\Models\AuthUser;
use App\Models\Profile;
use App\Models\RolePermission;
use App\Models\UserRole;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    private function makeTargetUser(string $role = 'supervisor'): Profile
    {
        $id = (string) Str::uuid();

        Profile::create([
            'id' => $id,
            'email' => 'target@example.com',
            'first_name' => 'Target',
            'last_name' => 'User',
            'designation' => 'Test',
        ]);

        UserRole::create(['user_id' => $id, 'role' => $role]);

        return Profile::find($id);
    }

    public function test_coordinator_cannot_deactivate_users(): void
    {
        $coordinator = AuthUser::where('email', 'coordinator@rahs.mv')->first();
        $target = $this->makeTargetUser('supervisor');

        $this->actingAs($coordinator)
            ->postJson('/api/coordinators/deactivate', ['profileId' => $target->id])
            ->assertStatus(403);

        $this->assertDatabaseHas('user_roles', ['user_id' => $target->id, 'role' => 'supervisor']);
    }

    public function test_coordinator_cannot_reassign_atolls(): void
    {
        $coordinator = AuthUser::where('email', 'coordinator@rahs.mv')->first();
        $atoll = Atoll::first();

        $this->actingAs($coordinator)
            ->postJson('/api/coordinators/assignments', [
                'profileId' => 'some-profile-id',
                'role' => 'supervisor',
                'atollIds' => [$atoll->id],
            ])->assertStatus(403);

        $this->assertNull($atoll->fresh()->supervisor_id);
    }

    public function test_admin_can_deactivate_users(): void
    {
        $admin = AuthUser::where('email', 'admin@rahs.mv')->first();
        $target = $this->makeTargetUser('supervisor');

        $this->actingAs($admin)
            ->postJson('/api/coordinators/deactivate', ['profileId' => $target->id])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_roles', ['user_id' => $target->id, 'role' => 'staff']);
    }

    public function test_admin_can_assign_coordinator_to_atoll(): void
    {
        $admin = AuthUser::where('email', 'admin@rahs.mv')->first();
        $target = $this->makeTargetUser('coordinator');
        $atoll = Atoll::first();

        $this->actingAs($admin)
            ->postJson('/api/coordinators/assignments', [
                'profileId' => $target->id,
                'role' => 'coordinator',
                'atollIds' => [$atoll->id],
            ])->assertOk();

        $this->assertSame($target->id, $atoll->fresh()->coordinator_id);
    }

    public function test_supervisor_cannot_grant_admin_role(): void
    {
        RolePermission::where('permission_key', 'manage_users')->update(['supervisor_access' => true]);
        $supervisor = AuthUser::where('email', 'supervisor@rahs.mv')->first();
        $staff = $this->makeTargetUser('staff');

        $this->actingAs($supervisor)
            ->postJson("/api/users/{$staff->id}/role", ['role' => 'admin'])
            ->assertStatus(422);

        $this->assertDatabaseHas('user_roles', ['user_id' => $staff->id, 'role' => 'staff']);
    }

    public function test_supervisor_cannot_create_admin_users(): void
    {
        RolePermission::where('permission_key', 'manage_users')->update(['supervisor_access' => true]);
        $supervisor = AuthUser::where('email', 'supervisor@rahs.mv')->first();

        $this->actingAs($supervisor)
            ->postJson('/api/users', [
                'email' => 'new-admin@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'first_name' => 'New',
                'last_name' => 'Admin',
                'role' => 'admin',
            ])->assertStatus(422);

        $this->assertDatabaseMissing('auth_users', ['email' => 'new-admin@example.com']);
    }

    public function test_admin_can_grant_admin_role(): void
    {
        $admin = AuthUser::where('email', 'admin@rahs.mv')->first();
        $staff = $this->makeTargetUser('staff');

        $this->actingAs($admin)
            ->postJson("/api/users/{$staff->id}/role", ['role' => 'admin'])
            ->assertOk();

        $this->assertDatabaseHas('user_roles', ['user_id' => $staff->id, 'role' => 'admin']);
    }

    public function test_admin_cannot_demote_themselves(): void
    {
        $admin = AuthUser::where('email', 'admin@rahs.mv')->first();

        $this->actingAs($admin)
            ->postJson("/api/users/{$admin->id}/role", ['role' => 'staff'])
            ->assertStatus(422);

        $this->assertDatabaseHas('user_roles', ['user_id' => $admin->id, 'role' => 'admin']);
    }

    public function test_staff_cannot_access_user_management(): void
    {
        $staff = AuthUser::where('email', 'staff@rahs.mv')->first();

        $this->actingAs($staff)
            ->getJson('/api/users')
            ->assertStatus(403);
    }

    public function test_all_authenticated_roles_can_view_important_contacts(): void
    {
        foreach (['admin@rahs.mv', 'supervisor@rahs.mv', 'coordinator@rahs.mv', 'staff@rahs.mv'] as $email) {
            $user = AuthUser::where('email', $email)->firstOrFail();

            $this->actingAs($user)
                ->get('/important-contacts')
                ->assertOk()
                ->assertSee('Important Contacts');
        }
    }

    public function test_non_admins_cannot_manage_important_contacts(): void
    {
        foreach (['supervisor@rahs.mv', 'coordinator@rahs.mv', 'staff@rahs.mv'] as $email) {
            $user = AuthUser::where('email', $email)->firstOrFail();

            $this->actingAs($user)
                ->get('/important-contacts-admin')
                ->assertRedirect('/important-contacts');

            $this->actingAs($user)
                ->postJson('/api/important-contacts', [
                    'name' => 'Restricted Contact',
                    'title' => 'Test Contact',
                    'phone_primary' => '1234567',
                ])
                ->assertForbidden();
        }

        $this->assertDatabaseMissing('important_contacts', ['name' => 'Restricted Contact']);
    }

    public function test_admin_can_manage_important_contacts(): void
    {
        $admin = AuthUser::where('email', 'admin@rahs.mv')->firstOrFail();

        $this->actingAs($admin)
            ->get('/important-contacts-admin')
            ->assertOk();

        $this->actingAs($admin)
            ->postJson('/api/important-contacts', [
                'name' => 'Admin Contact',
                'title' => 'Test Contact',
                'phone_primary' => '1234567',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('important_contacts', ['name' => 'Admin Contact']);
    }

    public function test_admin_can_edit_user_without_optional_manager_field(): void
    {
        $admin = AuthUser::where('email', 'admin@rahs.mv')->firstOrFail();
        $staff = AuthUser::where('email', 'staff@rahs.mv')->firstOrFail();

        $this->actingAs($admin)
            ->patchJson("/api/users/{$staff->id}", [
                'first_name' => 'Updated',
                'last_name' => 'Staff',
                'status' => 'active',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('profiles', [
            'id' => $staff->id,
            'first_name' => 'Updated',
            'manager_id' => null,
        ]);
    }

    public function test_admin_can_delete_user_using_route_id(): void
    {
        $admin = AuthUser::where('email', 'admin@rahs.mv')->firstOrFail();
        $staff = AuthUser::where('email', 'staff@rahs.mv')->firstOrFail();

        $this->actingAs($admin)
            ->deleteJson("/api/users/{$staff->id}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('auth_users', ['id' => $staff->id]);
        $this->assertDatabaseMissing('profiles', ['id' => $staff->id]);
        $this->assertDatabaseMissing('user_roles', ['user_id' => $staff->id]);
    }
}
