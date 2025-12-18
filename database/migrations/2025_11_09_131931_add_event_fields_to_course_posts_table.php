<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('course_posts', function (Blueprint $table) {
            // Event-related fields
            $table->datetime('event_start_date')->nullable()->after('content');
            $table->datetime('event_end_date')->nullable()->after('event_start_date');
            $table->string('event_location')->nullable()->after('event_end_date');
            $table->boolean('is_all_day')->default(false)->after('event_location');
            $table->json('event_attendees')->nullable()->after('is_all_day')->comment('Array of user_ids tagged in event');
            
            // Index for querying events
            $table->index('event_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_posts', function (Blueprint $table) {
            $table->dropIndex(['event_start_date']);
            $table->dropColumn([
                'event_start_date',
                'event_end_date',
                'event_location',
                'is_all_day',
                'event_attendees',
            ]);
        });
    }
};
