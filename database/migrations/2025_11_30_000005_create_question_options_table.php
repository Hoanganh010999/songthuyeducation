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
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->text('content');
            $table->string('label', 10)->nullable()->comment('A, B, C, D or 1, 2, 3...');
            $table->boolean('is_correct')->default(false);
            $table->integer('sort_order')->default(0);
            $table->text('feedback')->nullable()->comment('Feedback when this option is selected');
            $table->string('image_url', 500)->nullable();
            $table->timestamps();

            $table->index(['question_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};
