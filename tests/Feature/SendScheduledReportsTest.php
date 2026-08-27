<?php

namespace Tests\Feature;

use App\Models\ScheduledReport;
use Tests\TestCase;

class SendScheduledReportsTest extends TestCase
{
    private function makeReport(array $overrides = []): ScheduledReport
    {
        return ScheduledReport::create(array_merge([
            'user_id' => null,
            'name' => 'Daily report',
            'recipients' => ['recipient@example.com'],
            'frequency' => 'daily',
            'time_of_day' => '00:00',
            'filters' => [],
            'is_active' => true,
            'last_sent_at' => null,
        ], $overrides));
    }

    public function test_inactive_reports_with_null_last_sent_at_are_never_sent(): void
    {
        $report = $this->makeReport(['name' => 'Inactive report', 'is_active' => false]);

        $this->artisan('rahs:send-scheduled-reports')->assertSuccessful();

        $this->assertNull($report->fresh()->last_sent_at);
    }

    public function test_active_due_report_is_sent_and_last_sent_at_updated(): void
    {
        $report = $this->makeReport(['name' => 'Active due report']);

        $this->artisan('rahs:send-scheduled-reports')->assertSuccessful();

        $this->assertNotNull($report->fresh()->last_sent_at);
    }

    public function test_active_report_sent_recently_is_not_resent(): void
    {
        $report = $this->makeReport([
            'name' => 'Already sent today',
            'last_sent_at' => now()->toDateString(),
        ]);

        $this->artisan('rahs:send-scheduled-reports')->assertSuccessful();

        $this->assertSame(now()->toDateString(), $report->fresh()->last_sent_at);
    }
}
