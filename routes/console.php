<?php

use App\Console\Commands\Housekeeping;
use App\Console\Commands\SendScheduledReports;
use App\Console\Commands\SendOperationsAlerts;
use Illuminate\Support\Facades\Schedule;

Schedule::command(Housekeeping::class)->hourly();
Schedule::command(SendScheduledReports::class)->everyFiveMinutes();
Schedule::command(SendOperationsAlerts::class)->dailyAt('08:00')->withoutOverlapping();
