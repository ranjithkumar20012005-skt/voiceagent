<?php

use App\Models\Automation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled work
|--------------------------------------------------------------------------
|
| Production must run Laravel's scheduler from cron:
|
|   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
|
| The dispatch command only queues jobs, so the scheduler tick stays fast.
|
*/

// Default daily run at 08:00 Asia/Kolkata.
Schedule::command('calls:dispatch-due')
    ->dailyAt('08:00')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping(30)
    ->onOneServer()
    ->runInBackground()
    ->description('Dispatch due renewal calls');

/*
| Automations may each carry their own run time. Register an extra daily tick
| for any distinct time that differs from the default, so changing an
| automation's schedule in the UI takes effect without a code change.
|
| Wrapped defensively: the scheduler is also built by `artisan` before the
| database exists (installs, CI), and a missing table must not break every
| command.
*/
try {
    $times = Automation::query()
        ->where('enabled', true)
        ->pluck('run_at')
        ->map(fn ($t) => substr((string) $t, 0, 5))
        ->filter(fn ($t) => preg_match('/^\d{2}:\d{2}$/', $t) && $t !== '08:00')
        ->unique();

    foreach ($times as $time) {
        Schedule::command('calls:dispatch-due')
            ->dailyAt($time)
            ->timezone(config('app.timezone', 'Asia/Kolkata'))
            ->withoutOverlapping(30)
            ->onOneServer()
            ->runInBackground();
    }
} catch (\Throwable) {
    // No database yet -- the default 08:00 schedule above still stands.
}
