<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add daily schedule notification time setting (default 09:00)
        DB::table('settings')->insert([
            'key' => 'daily_schedule_notification_time',
            'value' => '09:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add daily schedule notification enabled setting
        DB::table('settings')->insert([
            'key' => 'daily_schedule_notification_enabled',
            'value' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'daily_schedule_notification_time',
            'daily_schedule_notification_enabled',
        ])->delete();
    }
};
