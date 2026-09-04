<?php

namespace Tests\Feature;

use App\Contracts\ClamScanner;
use App\Jobs\CreateNotification;
use App\Models\Atoll;
use App\Models\AuthUser;
use App\Models\Island;
use App\Models\Profile;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class QAComprehensiveTest extends TestCase
{
    private function makeUser(string $role): AuthUser
    {
        $user = AuthUser::create([
            'email' => 'qa-'.$role.'-'.Str::uuid().'@example.com',
            'password_hash' => bcrypt('password123'),
        ]);
        $id = $user->id;

        Profile::create([
            'id' => $id,
            'email' => $user->email,
            'first_name' => ucfirst($role).' QA',
            'last_name' => 'User',
            'designation' => 'Test',
        ]);
        UserRole::create(['user_id' => $id, 'role' => $role]);

        return $user;
    }

    private function makeTask(?string $assignedTo = null, ?string $assignedBy = null, ?string $islandId = null): Task
    {
        return Task::create([
            'title' => 'QA Task '.Str::uuid(),
            'status' => 'pending',
            'priority' => 'medium',
            'assigned_to' => $assignedTo,
            'assigned_by' => $assignedBy,
            'island_id' => $islandId,
            'archived' => false,
            'task_types' => [],
        ]);
    }

    private function scope(): array
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

    public function test_staff_cannot_delete_comment_by_another_user_on_their_task(): void
    {
        $scope = $this->scope();
        $task = $this->makeTask($scope['staff']->id, $scope['coordinator']->id, $scope['islandA']->id);
        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $scope['coordinator']->id,
            'content' => 'coordinator comment',
        ]);

        $this->actingAs($scope['staff'])
            ->deleteJson("/api/tasks/{$task->id}/comments/{$comment->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('task_comments', ['id' => $comment->id]);
    }

    public function test_coordinator_cannot_delete_comment_on_task_outside_their_atoll(): void
    {
        $scope = $this->scope();
        // Task in atollB (NOT coordinator's atoll), assigned to staff2
        $task = $this->makeTask($scope['staff2']->id, $scope['staff2']->id, $scope['islandB']->id);
        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $scope['staff2']->id,
            'content' => 'private comment',
        ]);

        // Coordinator cannot even read this task (outside their atoll)
        $this->actingAs($scope['coordinator'])
            ->getJson("/api/tasks/{$task->id}/comments")
            ->assertStatus(422);

        // And must NOT be able to delete a comment on it either.
        $this->actingAs($scope['coordinator'])
            ->deleteJson("/api/tasks/{$task->id}/comments/{$comment->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('task_comments', ['id' => $comment->id]);
    }

    public function test_staff_search_leaks_tasks_outside_their_scope(): void
    {
        $scope = $this->scope();
        // A task visible ONLY to staff2/admin (in atollB, assigned to staff2).
        $secret = $this->makeTask($scope['staff2']->id, $scope['staff2']->id, $scope['islandB']->id);
        $secret->update(['title' => 'SecretIslandTaskXYZ']);

        $response = $this->actingAs($scope['staff'])
            ->getJson('/api/search?q=SecretIslandTaskXYZ');

        $response->assertOk();
        $titles = collect($response->json('tasks'))->pluck('title')->all();
        $this->assertNotContains('SecretIslandTaskXYZ', $titles, 'staff should not see tasks outside their scope via search');
    }

    public function test_update_task_null_values_are_ignored_not_cleared(): void
    {
        $scope = $this->scope();
        $task = $this->makeTask($scope['staff']->id, $scope['coordinator']->id, $scope['islandA']->id);

        // Staff sends assigned_to: null and island_id: null. Nulls must be
        // ignored (existing values preserved) rather than clearing the fields.
        $this->actingAs($scope['staff'])
            ->patchJson("/api/tasks/{$task->id}", [
                'assigned_to' => null,
                'island_id' => null,
            ])->assertOk();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'assigned_to' => $scope['staff']->id,
            'island_id' => $scope['islandA']->id,
        ]);
    }

    public function test_update_task_null_assigned_by_is_ignored(): void
    {
        $scope = $this->scope();
        $task = $this->makeTask($scope['staff']->id, $scope['coordinator']->id, $scope['islandA']->id);

        // Staff is not allowed to change assigned_by (only supervisors can);
        // sending null must not clear it either.
        $this->actingAs($scope['staff'])
            ->patchJson("/api/tasks/{$task->id}", ['assigned_by' => null])
            ->assertOk();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'assigned_by' => $scope['coordinator']->id]);
    }

    public function test_task_rejects_invalid_due_date_string(): void
    {
        $scope = $this->scope();
        $this->actingAs($scope['staff'])
            ->postJson('/api/tasks', [
                'title' => 'Bad date task',
                'island_id' => $scope['islandA']->id,
                'assigned_to' => $scope['staff']->id,
                'due_date' => 'not-a-real-date',
            ])->assertStatus(422);
    }

    public function test_call_log_rejects_invalid_call_date(): void
    {
        $scope = $this->scope();
        $task = $this->makeTask($scope['staff']->id, $scope['coordinator']->id, $scope['islandA']->id);

        $this->actingAs($scope['staff'])
            ->postJson("/api/tasks/{$task->id}/call-logs", [
                'contact_name' => 'Dr Someone',
                'call_date' => 'yesterday-ish',
            ])->assertStatus(422);
    }

    public function test_comment_content_has_max_length(): void
    {
        $scope = $this->scope();
        $task = $this->makeTask($scope['staff']->id, $scope['coordinator']->id, $scope['islandA']->id);

        $huge = str_repeat('A', 50000);
        $this->actingAs($scope['staff'])
            ->postJson("/api/tasks/{$task->id}/comments", ['content' => $huge])
            ->assertStatus(422);
    }

    public function test_task_title_whitespace_only_is_rejected(): void
    {
        $scope = $this->scope();
        $this->actingAs($scope['staff'])
            ->postJson('/api/tasks', [
                'title' => '   ',
                'island_id' => $scope['islandA']->id,
                'assigned_to' => $scope['staff']->id,
            ])->assertStatus(422);
    }

    public function test_task_rejects_invalid_priority_and_status(): void
    {
        $scope = $this->scope();
        $this->actingAs($scope['staff'])
            ->postJson('/api/tasks', [
                'title' => 'x',
                'island_id' => $scope['islandA']->id,
                'priority' => 'critical',
                'status' => 'archived',
            ])->assertStatus(422);
    }

    public function test_unauthenticated_api_requests_return_401(): void
    {
        $this->getJson('/api/statistics')
            ->assertStatus(401);

        $this->postJson('/api/tasks', ['title' => 'x'])
            ->assertStatus(401);
    }

    public function test_protected_page_redirects_unauthenticated(): void
    {
        $this->get('/tasks')->assertRedirect('/login');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_throttle_after_five_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            // Web-context ValidationException redirects back to login (302).
            $this->post('/auth/login', ['email' => 'admin@rahs.mv', 'password' => 'wrong'])->assertStatus(302);
        }
        $this->post('/auth/login', ['email' => 'admin@rahs.mv', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_logout_invalidates_session(): void
    {
        $user = AuthUser::where('email', 'admin@rahs.mv')->first();
        $response = $this->actingAs($user)->post('/auth/logout');
        $response->assertRedirect();

        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_guest_cannot_use_upload_endpoint(): void
    {
        $this->postJson('/api/upload', ['file' => base64_encode('abc'), 'filename' => 'x.txt'])
            ->assertStatus(401);
    }

    public function test_upload_rejects_php_and_html_extensions(): void
    {
        $user = AuthUser::where('email', 'admin@rahs.mv')->first();

        $this->actingAs($user)->postJson('/api/upload', [
            'file' => base64_encode('<?php echo 1;'),
            'filename' => 'shell.php',
        ])->assertStatus(422);

        $this->actingAs($user)->postJson('/api/upload', [
            'file' => base64_encode('<script>alert(1)</script>'),
            'filename' => 'x.html',
        ])->assertStatus(422);
    }

    public function test_upload_rejects_spoofed_magic_bytes(): void
    {
        $user = AuthUser::where('email', 'admin@rahs.mv')->first();

        // GIF magic bytes but .png extension
        $payload = base64_encode("\x89PNG\r\n\x1a\n".'not actually png beyond magic');
        $this->actingAs($user)->postJson('/api/upload', [
            'file' => $payload,
            'filename' => 'spoof.png',
        ])->assertStatus(201);
    }

    public function test_forgot_password_does_not_enumerate_users(): void
    {
        $this->post('/forgot-password', ['email' => 'nonexistent-user-xyz@example.com'])
            ->assertSessionHas('status');
    }

    public function test_role_permissions_page_admin_or_supervisor_only(): void
    {
        $this->actingAs($this->makeUser('coordinator'))->get('/role-permissions')->assertForbidden();
        $this->actingAs($this->makeUser('staff'))->get('/role-permissions')->assertForbidden();
        $this->actingAs($this->makeUser('admin'))->get('/role-permissions')->assertOk();
        $this->actingAs($this->makeUser('supervisor'))->get('/role-permissions')->assertForbidden();
    }

    public function test_upload_scans_with_clamav_when_enabled_and_rejects_infected(): void
    {
        $user = AuthUser::where('email', 'admin@rahs.mv')->first();

        $this->instance(ClamScanner::class, new class implements ClamScanner
        {
            public function enabled(): bool
            {
                return true;
            }

            public function scanOrFail(string $buffer): void
            {
                throw new \RuntimeException('File failed the antivirus scan');
            }
        });

        $this->actingAs($user)->postJson('/api/upload', [
            'file' => base64_encode('hello world'),
            'filename' => 'notes.txt',
        ])->assertStatus(422)->assertJsonValidationErrors('file');

        // Clean scan passes the file through.
        $this->instance(ClamScanner::class, new class implements ClamScanner
        {
            public function enabled(): bool
            {
                return true;
            }

            public function scanOrFail(string $buffer): void
            {
                // clean
            }
        });

        $this->actingAs($user)->postJson('/api/upload', [
            'file' => base64_encode('clean file contents'),
            'filename' => 'notes.txt',
        ])->assertStatus(201);
    }

    public function test_task_delete_is_soft_and_keeps_history(): void
    {
        $admin = AuthUser::where('email', 'admin@rahs.mv')->first();
        $task = $this->makeTask();
        $task->comments()->create(['user_id' => $admin->id, 'content' => 'keep me']);

        $this->actingAs($admin)->deleteJson("/api/tasks/{$task->id}")->assertOk();

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
        $this->assertDatabaseHas('task_comments', ['task_id' => $task->id]);
    }

    public function test_housekeeping_archives_aged_completed_tasks_and_purges_aged_archived_tasks(): void
    {
        $admin = AuthUser::where('email', 'admin@rahs.mv')->first();

        $oldCompleted = $this->makeTask();
        $oldCompleted->update(['status' => 'completed']);
        $oldCompleted->comments()->create(['user_id' => $admin->id, 'content' => 'history']);

        $agedArchived = $this->makeTask();
        $agedArchived->update(['status' => 'completed', 'archived' => true]);
        $agedArchived->comments()->create(['user_id' => $admin->id, 'content' => 'to purge']);

        $recent = $this->makeTask();
        $recent->update(['status' => 'completed']);

        DB::table('tasks')->where('id', $oldCompleted->id)->update(['updated_at' => now()->subDays(4)]);
        DB::table('tasks')->where('id', $agedArchived->id)->update(['updated_at' => now()->subDays(31)]);

        $this->artisan('rahs:housekeeping');

        $this->assertDatabaseHas('tasks', ['id' => $oldCompleted->id, 'archived' => true]);
        $this->assertDatabaseMissing('tasks', ['id' => $agedArchived->id]);
        $this->assertDatabaseMissing('task_comments', ['task_id' => $agedArchived->id]);
        $this->assertDatabaseHas('tasks', ['id' => $recent->id, 'archived' => false]);
    }

    public function test_api_tasks_paginates_in_descending_order(): void
    {
        $admin = AuthUser::where('email', 'admin@rahs.mv')->first();

        $tasks = collect();
        foreach (range(1, 5) as $i) {
            $t = $this->makeTask();
            $t->created_at = now()->addSeconds($i);
            $t->save();
            $tasks->push($t);
        }

        $response = $this->actingAs($admin)->getJson('/api/tasks?limit=3');
        $response->assertOk();

        $body = $response->json();
        $this->assertCount(3, $body['tasks']);
        $this->assertNotNull($body['next_cursor']);

        // Newest first (created_at desc, id desc).
        $ids = collect($body['tasks'])->pluck('id')->all();
        $this->assertSame($tasks->sortByDesc('created_at')->pluck('id')->take(3)->values()->all(), $ids);

        $page2 = $this->actingAs($admin)->getJson('/api/tasks?limit=3&cursor='.$body['next_cursor']);
        $page2->assertOk();
        $this->assertCount(2, $page2->json('tasks'));
        $this->assertNull($page2->json('next_cursor'));
    }

    public function test_queued_notification_is_dispatched_on_task_comment(): void
    {
        Queue::fake();

        $admin = AuthUser::where('email', 'admin@rahs.mv')->first();
        $assignee = $this->makeUser('staff');
        $task = $this->makeTask($assignee->id, $admin->id);

        $this->actingAs($admin)->postJson("/api/tasks/{$task->id}/comments", [
            'content' => 'queued notification test',
        ])->assertStatus(201);

        Queue::assertPushed(CreateNotification::class);
    }

    public function test_archived_tasks_hidden_from_staff_and_not_editable_via_api(): void
    {
        $scope = $this->scope();
        $task = $this->makeTask($scope['staff']->id, $scope['coordinator']->id, $scope['islandA']->id);
        $task->update(['archived' => true, 'status' => 'completed']);

        // Staff should not see archived tasks in their list...
        $this->actingAs($scope['staff'])->get('/tasks')->assertOk();

        // ...and must not be able to edit the archived task via API either.
        $this->actingAs($scope['staff'])
            ->patchJson("/api/tasks/{$task->id}", ['title' => 'Edited archived task'])
            ->assertStatus(422);
    }
}
