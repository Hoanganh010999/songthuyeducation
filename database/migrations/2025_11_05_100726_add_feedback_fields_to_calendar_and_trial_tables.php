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
        // Thêm field assigned_teacher_id cho placement test
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_teacher_id')->nullable()->after('user_id');
            $table->foreign('assigned_teacher_id')->references('id')->on('users')->onDelete('set null');
            $table->index('assigned_teacher_id');
        });

        // Thêm fields tracking feedback cho trial students
        Schema::table('trial_students', function (Blueprint $table) {
            $table->unsignedBigInteger('feedback_by')->nullable()->after('feedback');
            $table->timestamp('feedback_at')->nullable()->after('feedback_by');
            $table->foreign('feedback_by')->references('id')->on('users')->onDelete('set null');
            $table->index('feedback_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropForeign(['assigned_teacher_id']);
            $table->dropColumn('assigned_teacher_id');
        });

        Schema::table('trial_students', function (Blueprint $table) {
            $table->dropForeign(['feedback_by']);
            $table->dropColumn(['feedback_by', 'feedback_at']);
        });
    }
};
