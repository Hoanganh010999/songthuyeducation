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
            $table->string('lesson_plans_folder_id')->nullable()->after('lesson_plan_url')
                ->comment('Google Drive folder ID for lesson plans');
            $table->string('materials_folder_id')->nullable()->after('materials_url')
                ->comment('Google Drive folder ID for materials');
            $table->string('homework_folder_id')->nullable()->after('homework_url')
                ->comment('Google Drive folder ID for homework');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_lesson_sessions', function (Blueprint $table) {
            $table->dropColumn(['lesson_plans_folder_id', 'materials_folder_id', 'homework_folder_id']);
        });
    }
};
