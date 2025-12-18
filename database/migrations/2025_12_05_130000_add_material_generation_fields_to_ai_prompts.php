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
        Schema::table('ai_prompts', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->after('module')->nullable();
            $table->string('prompt_type')->after('branch_id')->nullable();
            $table->text('description')->after('prompt_type')->nullable();
            $table->text('json_format')->after('description')->nullable();

            // Add index
            $table->index(['module', 'branch_id', 'prompt_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_prompts', function (Blueprint $table) {
            $table->dropIndex(['module', 'branch_id', 'prompt_type']);
            $table->dropColumn(['branch_id', 'prompt_type', 'description', 'json_format']);
        });
    }
};
