<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Đăng ký middleware alias
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'branch.access' => \App\Http\Middleware\CheckBranchAccess::class,
        ]);

        // Enable CORS for API routes
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Refresh Google Drive tokens every 30 minutes (before 1-hour expiry)
        $schedule->command('google:refresh-tokens')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Send appointment reminders 1 hour before scheduled time
        $schedule->command('appointments:send-reminders')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Send daily schedule notifications (default 9:00 AM, configurable in settings)
        $schedule->command('schedule:send-daily-notifications')
            ->dailyAt('09:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Clean up orphan pending Zalo accounts (older than 30 minutes) every 15 minutes
        $schedule->command('zalo:cleanup-pending --minutes=30')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Clean up vocabulary pronunciation audio files (older than 24 hours) daily at 4:00 AM
        $schedule->command('vocabulary:clean-audio --hours=24')
            ->dailyAt('04:00')
            ->withoutOverlapping()
            ->runInBackground();

        // 🔥 NEW: Send homework reminders for overdue assignments (every 2 hours during work hours)
        $schedule->command('homework:send-reminders')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->runInBackground();

        // 🔥 NEW: Send vocabulary daily reports (every evening at 8 PM)
        $schedule->command('vocabulary:send-daily-report')
            ->dailyAt('20:00')
            ->withoutOverlapping()
            ->runInBackground();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
