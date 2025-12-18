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
        Schema::table('class_lesson_sessions', function (Blueprint $table) {
            $table->string('lesson_title')->nullable()->after('session_number');
            $table->text('lesson_objectives')->nullable()->after('lesson_title');
            $table->text('lesson_content')->nullable()->after('lesson_objectives');
            $table->string('lesson_plan_url')->nullable()->after('lesson_content');
            $table->string('materials_url')->nullable()->after('lesson_plan_url');
            $table->string('homework_url')->nullable()->after('materials_url');
            $table->integer('duration_minutes')->nullable()->default(45)->after('homework_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_lesson_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'lesson_title', 'lesson_objectives', 'lesson_content',
                'lesson_plan_url', 'materials_url', 'homework_url', 'duration_minutes'
            ]);
        });
    }
};
