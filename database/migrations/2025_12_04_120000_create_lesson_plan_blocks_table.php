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
        Schema::create('lesson_plan_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_plan_session_id')->constrained('lesson_plan_sessions')->onDelete('cascade');
            $table->integer('block_number')->default(1);
            $table->string('block_title')->nullable();
            $table->text('block_description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable(); // For any additional data
            $table->timestamps();

            $table->index(['lesson_plan_session_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_plan_blocks');
    }
};
