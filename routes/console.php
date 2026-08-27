<?php

use App\Console\Commands\Housekeeping;
use App\Console\Commands\SendScheduledReports;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(Housekeeping::class)->hourly();
Schedule::command(SendScheduledReports::class)->everyFiveMinutes();
