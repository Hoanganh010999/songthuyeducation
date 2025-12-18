<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homework_assignment_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('homework_assignments')->onDelete('cascade');
            $table->foreignId('exercise_id')->constrained('homework_exercises')->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->decimal('points', 8, 2)->nullable(); // Override exercise points if needed
            $table->boolean('is_required')->default(true);
            $table->string('section')->nullable(); // Optional section grouping
            $table->timestamps();

            // Unique constraint to prevent duplicate exercises in same assignment
            $table->unique(['assignment_id', 'exercise_id']);

            // Indexes
            $table->index('assignment_id');
            $table->index('exercise_id');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_assignment_exercises');
    }
};
