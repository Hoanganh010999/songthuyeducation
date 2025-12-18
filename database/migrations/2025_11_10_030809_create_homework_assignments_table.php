<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homework_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('lesson_plan_session_id')->nullable()->constrained('lesson_plan_sessions')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('deadline')->nullable(); // Null = unlimited
            
            // Google Drive files (JSON array of file IDs from homework folder)
            $table->json('file_ids')->nullable();
            
            // Student assignments (JSON array of user IDs, null = all students in class)
            $table->json('assigned_to')->nullable(); // null = all students, or [user_id1, user_id2, ...]
            
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();
            
            $table->index(['class_id', 'status']);
            $table->index('lesson_plan_session_id');
            $table->index('deadline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_assignments');
    }
};
