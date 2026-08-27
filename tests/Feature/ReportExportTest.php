<?php

namespace Tests\Feature;

use App\Models\AuthUser;
use App\Models\Island;
use App\Models\Profile;
use App\Models\Task;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    private function makeStaff(): Profile
    {
        $id = (string) Str::uuid();

        Profile::create([
            'id' => $id,
            'email' => 'report-staff@example.com',
            'first_name' => 'Report',
            'last_name' => 'Staff',
        ]);

        return Profile::find($id);
    }

    public function test_task_csv_escapes_formula_injection(): void
    {
        $admin = AuthUser::where('email', 'admin@rahs.mv')->first();
        $staff = $this->makeStaff();
        $island = Island::first();

        Task::create([
            'title' => '=HYPERLINK("http://evil.example")',
            'creator_description' => '+SUM(A1:A9)',
            'status' => 'pending',
            'assigned_to' => $staff->id,
            'island_id' => $island->id,
        ]);

        $response = $this->actingAs($admin)->get('/api/reports/export/tasks');

        $response->assertOk();
        $body = $response->getContent();
        $this->assertStringContainsString("'=HYPERLINK(", $body);
        $this->assertStringContainsString("'+SUM(A1:A9)", $body);
    }

    public function test_normal_task_titles_are_not_modified(): void
    {
        $admin = AuthUser::where('email', 'admin@rahs.mv')->first();
        $staff = $this->makeStaff();
        $island = Island::first();

        Task::create(['title' => 'Replace generator filter', 'status' => 'pending', 'assigned_to' => $staff->id, 'island_id' => $island->id]);

        $body = $this->actingAs($admin)->get('/api/reports/export/tasks')->getContent();

        $this->assertStringContainsString('Replace generator filter', $body);
        $this->assertStringNotContainsString("'Replace generator filter", $body);
    }

    public function test_csv_export_requires_admin_or_supervisor(): void
    {
        $staff = AuthUser::where('email', 'staff@rahs.mv')->first();

        $this->actingAs($staff)
            ->get('/api/reports/export/tasks')
            ->assertStatus(403);
    }

    public function test_generated_task_report_applies_status_filter(): void
    {
        $admin = AuthUser::where('email', 'admin@rahs.mv')->first();
        $staff = $this->makeStaff();
        $island = Island::first();

        Task::create(['title' => 'Visible completed task', 'status' => 'completed', 'assigned_to' => $staff->id, 'island_id' => $island->id]);
        Task::create(['title' => 'Hidden pending task', 'status' => 'pending', 'assigned_to' => $staff->id, 'island_id' => $island->id]);

        $body = $this->actingAs($admin)->get('/api/reports/generate/tasks?status=completed')->assertOk()->getContent();

        $this->assertStringContainsString('Visible completed task', $body);
        $this->assertStringNotContainsString('Hidden pending task', $body);
    }

    public function test_generated_workload_report_groups_tasks_by_staff(): void
    {
        $admin = AuthUser::where('email', 'admin@rahs.mv')->first();
        $staff = $this->makeStaff();
        $island = Island::first();

        Task::create(['title' => 'Workload one', 'status' => 'completed', 'assigned_to' => $staff->id, 'island_id' => $island->id]);
        Task::create(['title' => 'Workload two', 'status' => 'pending', 'assigned_to' => $staff->id, 'island_id' => $island->id]);

        $body = $this->actingAs($admin)->get('/api/reports/generate/workload')->assertOk()->getContent();

        $this->assertStringContainsString('Staff member,Total Tasks,Pending,In Progress,Completed,Overdue,Completion Rate', $body);
        $this->assertStringContainsString('Report Staff,2,1,0,1,0,50%', $body);
    }

    public function test_generated_reports_reject_unknown_types_and_staff_access(): void
    {
        $admin = AuthUser::where('email', 'admin@rahs.mv')->first();
        $staff = AuthUser::where('email', 'staff@rahs.mv')->first();

        $this->actingAs($admin)->get('/api/reports/generate/unknown')->assertUnprocessable()->assertJsonValidationErrors('type');
        $this->actingAs($staff)->get('/api/reports/generate/tasks')->assertForbidden();
    }
}
