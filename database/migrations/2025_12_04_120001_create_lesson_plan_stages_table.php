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
        Schema::create('lesson_plan_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_plan_block_id')->constrained('lesson_plan_blocks')->onDelete('cascade');
            $table->integer('stage_number')->default(1);
            $table->string('stage_name');
            $table->text('stage_aim')->nullable();
            $table->integer('total_timing')->default(0)->comment('Total time in minutes');
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['lesson_plan_block_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_plan_stages');
    }
};
