<?php

namespace Tests\Feature;

use App\Models\AuthUser;
use App\Models\Island;
use App\Models\Profile;
use App\Models\Task;
use Illuminate\Support\Str;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    private function makeStaff(): Profile
    {
        $id = (string) Str::uuid();

        Profile::create([
            'id' => $id,
            'email' => 'stats-staff@example.com',
            'first_name' => 'Stats',
            'last_name' => 'Staff',
        ]);

        return Profile::find($id);
    }

    public function test_statistics_returns_correct_aggregates(): void
    {
        $admin = AuthUser::where('email', 'admin@rahs.mv')->first();
        $staff = $this->makeStaff();
        $island = Island::first();

        Task::create(['title' => 'pending one', 'status' => 'pending', 'assigned_to' => $staff->id, 'island_id' => $island->id]);
        Task::create(['title' => 'in progress one', 'status' => 'in_progress', 'assigned_to' => $staff->id, 'island_id' => $island->id]);
        Task::create(['title' => 'completed one', 'status' => 'completed', 'assigned_to' => $staff->id, 'island_id' => $island->id]);
        Task::create(['title' => 'completed two', 'status' => 'completed', 'assigned_to' => $staff->id, 'island_id' => $island->id]);
        Task::create(['title' => 'overdue one', 'status' => 'pending', 'due_date' => now()->subDays(3)->toDateString(), 'assigned_to' => $staff->id, 'island_id' => $island->id]);

        $this->actingAs($admin)
            ->getJson('/api/statistics')
            ->assertOk()
            ->assertJson([
                'total' => 5,
                'pending' => 2,
                'inProgress' => 1,
                'completed' => 2,
                'overdue' => 1,
                'assignedUsers' => 1,
                'efficiency' => 40,
            ]);
    }

    public function test_archived_tasks_are_excluded_from_statistics(): void
    {
        $admin = AuthUser::where('email', 'admin@rahs.mv')->first();
        $staff = $this->makeStaff();
        $island = Island::first();

        Task::create(['title' => 'active task', 'status' => 'completed', 'assigned_to' => $staff->id, 'island_id' => $island->id]);
        Task::create(['title' => 'archived task', 'status' => 'completed', 'archived' => true, 'assigned_to' => $staff->id, 'island_id' => $island->id]);

        $this->actingAs($admin)
            ->getJson('/api/statistics')
            ->assertOk()
            ->assertJson([
                'total' => 1,
                'completed' => 1,
            ]);
    }

    public function test_statistics_requires_authentication(): void
    {
        $this->getJson('/api/statistics')->assertStatus(401);
    }
}
