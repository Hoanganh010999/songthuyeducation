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
        Schema::table('lesson_plan_sessions', function (Blueprint $table) {
            $table->string('lesson_plans_folder_id')->nullable()->after('homework_folder_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_plan_sessions', function (Blueprint $table) {
            $table->dropColumn('lesson_plans_folder_id');
        });
    }
};
