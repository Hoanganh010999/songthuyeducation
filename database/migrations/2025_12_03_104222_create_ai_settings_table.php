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
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('module')->default('examination'); // examination, quality, etc.
            $table->string('provider')->default('openai'); // openai, anthropic
            $table->text('api_key_encrypted'); // Encrypted API key
            $table->string('model')->nullable(); // gpt-4o, claude-sonnet, etc.
            $table->json('settings')->nullable(); // Additional settings (temperature, etc.)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // One setting per branch per module
            $table->unique(['branch_id', 'module']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
