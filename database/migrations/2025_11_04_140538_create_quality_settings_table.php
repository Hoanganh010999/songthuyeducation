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
        Schema::create('quality_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('industry')->default('education'); // education, healthcare, retail, etc.
            $table->string('setting_key'); // teacher_position_codes, etc.
            $table->json('setting_value'); // Store array or object
            $table->timestamps();
            
            // Unique constraint: one setting per branch per industry per key
            $table->unique(['branch_id', 'industry', 'setting_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_settings');
    }
};
