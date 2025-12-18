<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homework_exercise_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->constrained('homework_exercises')->onDelete('cascade');
            $table->string('label', 10); // A, B, C, D, etc.
            $table->text('content');
            $table->boolean('is_correct')->default(false);
            $table->integer('sort_order')->default(0);
            $table->text('feedback')->nullable(); // Feedback for this option
            $table->timestamps();

            // Indexes
            $table->index('exercise_id');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_exercise_options');
    }
};
