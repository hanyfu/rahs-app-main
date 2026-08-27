<?php

namespace Tests\Feature;

use App\Models\AuthUser;
use App\Models\Island;
use App\Models\Notification;
use App\Models\Task;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    public function test_task_creation_notifies_assigned_user(): void
    {
        $supervisor = AuthUser::where('email', 'supervisor@rahs.mv')->first();
        $staff = AuthUser::where('email', 'staff@rahs.mv')->first();
        $island = Island::first();

        $this->actingAs($supervisor);

        $this->postJson('/api/tasks', [
            'title' => 'Dhivehi notification flow',
            'creator_description' => 'verify notification on creation',
            'atoll_id' => $island->atoll_id,
            'island_id' => $island->id,
            'assigned_to' => $staff->id,
            'due_date' => now()->addDays(2)->format('Y-m-d'),
            'priority' => 'medium',
            'task_types' => ['Medical'],
        ])->assertStatus(201);

        $task = Task::where('title', 'Dhivehi notification flow')->first();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $staff->id,
            'task_id' => $task->id,
            'title' => 'New Task Assigned',
            'is_read' => false,
        ]);
    }

    public function test_notification_endpoints_return_and_update_state(): void
    {
        $user = AuthUser::where('email', 'staff@rahs.mv')->first();

        Notification::create([
            'user_id' => $user->id,
            'task_id' => null,
            'title' => 'Leave Submitted',
            'message' => 'A colleague submitted leave.',
            'is_read' => false,
        ]);

        $this->actingAs($user);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Leave Submitted', 'is_read' => false]);

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJson(['count' => 1]);

        $id = Notification::first()->id;

        $this->postJson("/api/notifications/{$id}/read")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJson(['count' => 0]);

        $this->postJson('/api/notifications/mark-all-read')
            ->assertOk();
    }

    public function test_user_cannot_touch_other_users_notifications(): void
    {
        $admin = AuthUser::where('email', 'admin@rahs.mv')->first();
        $staff = AuthUser::where('email', 'staff@rahs.mv')->first();

        $notification = Notification::create([
            'user_id' => $admin->id,
            'task_id' => null,
            'title' => 'Secret',
            'message' => 'admin only',
            'is_read' => false,
        ]);

        $this->actingAs($staff);

        $this->postJson("/api/notifications/{$notification->id}/read")
            ->assertOk();

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => false,
        ]);
    }
}
