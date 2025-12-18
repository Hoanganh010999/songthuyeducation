<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('lesson_plan_id')->nullable()->after('homeroom_teacher_id')->constrained()->nullOnDelete();
            $table->foreignId('semester_id')->nullable()->after('lesson_plan_id')->constrained()->nullOnDelete();
            $table->integer('total_sessions')->nullable()->after('semester_id'); // Tổng số buổi học
            $table->integer('completed_sessions')->default(0)->after('total_sessions'); // Số buổi đã học
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['lesson_plan_id']);
            $table->dropForeign(['semester_id']);
            $table->dropColumn(['lesson_plan_id', 'semester_id', 'total_sessions', 'completed_sessions']);
        });
    }
};
