<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\IncomeReport;
use App\Observers\IncomeReportObserver;
use App\Models\Attendance;
use App\Observers\AttendanceObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register IncomeReport Observer
        IncomeReport::observe(IncomeReportObserver::class);
        
        // Register Attendance Observer (for fee deductions)
        Attendance::observe(AttendanceObserver::class);
    }
}
