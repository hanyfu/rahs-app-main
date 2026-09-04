<?php

namespace Tests\Feature;

use App\Models\Atoll;
use App\Models\AuthUser;
use App\Models\Island;
use App\Models\Profile;
use App\Models\RolePermission;
use App\Models\Task;
use App\Models\UserRole;
use Illuminate\Support\Str;
use Tests\TestCase;

class TaskAccessTest extends TestCase
{
    private function makeUser(string $role): AuthUser
    {
        $user = AuthUser::create([
            'email' => 'role-'.$role.'-'.Str::uuid().'@example.com',
            'password_hash' => bcrypt('password123'),
        ]);
        $id = $user->id;

        Profile::create([
            'id' => $id,
            'email' => $user->email,
            'first_name' => ucfirst($role),
            'last_name' => 'User',
            'designation' => 'Test',
        ]);
        UserRole::create(['user_id' => $id, 'role' => $role]);

        return $user;
    }

    private function setupScope(): array
    {
        $staff = $this->makeUser('staff');
        $staff2 = $this->makeUser('staff');
        $coordinator = $this->makeUser('coordinator');

        $atollA = Atoll::first();
        $atollB = Atoll::where('id', '!=', $atollA->id)->first();
        $atollA->update(['coordinator_id' => $coordinator->id]);

        $islandA = Island::where('atoll_id', $atollA->id)->first();
        $islandB = Island::where('atoll_id', $atollB->id)->first();
        $islandA->update(['assigned_staff_id' => $staff->id, 'status' => 'active']);
        $islandB->update(['assigned_staff_id' => $staff2->id, 'status' => 'active']);

        return compact('staff', 'staff2', 'coordinator', 'atollA', 'atollB', 'islandA', 'islandB');
    }

    public function test_staff_without_assignment_cannot_create_task(): void
    {
        $staff = $this->makeUser('staff');
        $island = Island::first();

        $this->actingAs($staff)
            ->postJson('/api/tasks', [
                'title' => 'No assignment task',
                'island_id' => $island->id,
                'assigned_to' => $staff->id,
            ])->assertStatus(422);
    }

    public function test_staff_creates_task_in_their_assigned_island(): void
    {
        $scope = $this->setupScope();

        $this->actingAs($scope['staff'])
            ->postJson('/api/tasks', [
                'title' => 'My island task',
                'island_id' => $scope['islandA']->id,
                'assigned_to' => $scope['staff']->id,
                'assigned_by' => $scope['coordinator']->id,
            ])->assertStatus(201)
            ->assertJsonPath('island_id', $scope['islandA']->id)
            ->assertJsonPath('assigned_by', $scope['staff']->id);

        $this->assertDatabaseHas('tasks', [
            'title' => 'My island task',
            'island_id' => $scope['islandA']->id,
            'assigned_by' => $scope['staff']->id,
        ]);
    }

    public function test_staff_cannot_create_task_in_another_island(): void
    {
        $scope = $this->setupScope();

        $this->actingAs($scope['staff'])
            ->postJson('/api/tasks', [
                'title' => 'Wrong island task',
                'island_id' => $scope['islandB']->id,
                'assigned_to' => $scope['staff']->id,
            ])->assertStatus(422);

        $this->assertDatabaseMissing('tasks', ['title' => 'Wrong island task']);
    }

    public function test_staff_cannot_assign_task_to_another_staff(): void
    {
        $scope = $this->setupScope();

        $this->actingAs($scope['staff'])
            ->postJson('/api/tasks', [
                'title' => 'Invalid assignee task',
                'island_id' => $scope['islandA']->id,
                'assigned_to' => $scope['staff2']->id,
            ])->assertStatus(422);

        $this->assertDatabaseMissing('tasks', ['title' => 'Invalid assignee task']);
    }

    public function test_staff_can_assign_task_to_their_coordinator(): void
    {
        $scope = $this->setupScope();

        $this->actingAs($scope['staff'])
            ->postJson('/api/tasks', [
                'title' => 'Assign to coordinator',
                'island_id' => $scope['islandA']->id,
                'assigned_to' => $scope['coordinator']->id,
            ])->assertStatus(201);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Assign to coordinator',
            'assigned_to' => $scope['coordinator']->id,
        ]);
    }

    public function test_coordinator_creates_task_in_their_atoll(): void
    {
        $scope = $this->setupScope();

        $this->actingAs($scope['coordinator'])
            ->postJson('/api/tasks', [
                'title' => 'Atoll task',
                'island_id' => $scope['islandA']->id,
                'assigned_to' => $scope['staff']->id,
            ])->assertStatus(201)
            ->assertJsonPath('assigned_by', $scope['coordinator']->id);
    }

    public function test_coordinator_cannot_create_task_outside_their_atolls(): void
    {
        $scope = $this->setupScope();

        $this->actingAs($scope['coordinator'])
            ->postJson('/api/tasks', [
                'title' => 'Out of scope task',
                'island_id' => $scope['islandB']->id,
                'assigned_to' => $scope['staff']->id,
            ])->assertStatus(422);

        $this->assertDatabaseMissing('tasks', ['title' => 'Out of scope task']);
    }

    public function test_coordinator_cannot_assign_to_staff_outside_their_atolls(): void
    {
        $scope = $this->setupScope();

        $this->actingAs($scope['coordinator'])
            ->postJson('/api/tasks', [
                'title' => 'Wrong assignee task',
                'island_id' => $scope['islandA']->id,
                'assigned_to' => $scope['staff2']->id,
            ])->assertStatus(422);

        $this->assertDatabaseMissing('tasks', ['title' => 'Wrong assignee task']);
    }

    public function test_supervisor_can_create_across_multiple_assigned_atolls_but_not_outside_them(): void
    {
        $scope = $this->setupScope();
        $supervisor = $this->makeUser('supervisor');
        $scope['atollA']->update(['supervisor_id' => $supervisor->id]);
        RolePermission::query()->where('permission_key', 'create_tasks')->update(['supervisor_access' => true]);

        $this->actingAs($supervisor)
            ->postJson('/api/tasks', [
                'title' => 'Supervised hospital task',
                'island_id' => $scope['islandA']->id,
                'assigned_to' => $scope['staff']->id,
            ])->assertCreated();

        $this->actingAs($supervisor)
            ->postJson('/api/tasks', [
                'title' => 'Outside supervisor scope',
                'island_id' => $scope['islandB']->id,
                'assigned_to' => $scope['staff2']->id,
            ])->assertUnprocessable();

        $this->assertDatabaseMissing('tasks', ['title' => 'Outside supervisor scope']);
    }

    public function test_staff_only_sees_their_own_and_assigned_tasks(): void
    {
        $scope = $this->setupScope();

        $mine = Task::create([
            'title' => 'STAFF_CREATED_VISIBLE',
            'island_id' => $scope['islandA']->id,
            'assigned_by' => $scope['staff']->id,
            'assigned_to' => $scope['staff']->id,
        ]);
        $assignedToMe = Task::create([
            'title' => 'ASSIGNED_TO_STAFF_VISIBLE',
            'island_id' => $scope['islandA']->id,
            'assigned_by' => $scope['coordinator']->id,
            'assigned_to' => $scope['staff']->id,
        ]);
        Task::create([
            'title' => 'STAFF_HIDDEN',
            'island_id' => $scope['islandB']->id,
            'assigned_by' => $scope['staff2']->id,
            'assigned_to' => $scope['staff2']->id,
        ]);

        $this->actingAs($scope['staff'])
            ->get('/tasks')
            ->assertOk()
            ->assertSee('STAFF_CREATED_VISIBLE')
            ->assertSee('ASSIGNED_TO_STAFF_VISIBLE')
            ->assertDontSee('STAFF_HIDDEN');
    }

    public function test_coordinator_only_sees_their_atoll_and_assigned_tasks(): void
    {
        $scope = $this->setupScope();

        Task::create([
            'title' => 'COORD_ATOLL_VISIBLE',
            'island_id' => $scope['islandA']->id,
            'assigned_by' => $scope['staff']->id,
            'assigned_to' => $scope['staff']->id,
        ]);
        Task::create([
            'title' => 'COORD_ASSIGNED_VISIBLE',
            'island_id' => $scope['islandB']->id,
            'assigned_by' => $scope['staff2']->id,
            'assigned_to' => $scope['coordinator']->id,
        ]);
        Task::create([
            'title' => 'COORD_HIDDEN',
            'island_id' => $scope['islandB']->id,
            'assigned_by' => $scope['staff2']->id,
            'assigned_to' => $scope['staff2']->id,
        ]);

        $this->actingAs($scope['coordinator'])
            ->get('/tasks')
            ->assertOk()
            ->assertSee('COORD_ATOLL_VISIBLE')
            ->assertSee('COORD_ASSIGNED_VISIBLE')
            ->assertDontSee('COORD_HIDDEN');
    }
}
