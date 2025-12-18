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
        Schema::create('trial_students', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic: Customer or CustomerChild
            $table->string('trialable_type');
            $table->unsignedBigInteger('trialable_id');
            
            // Class and session
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('class_lesson_session_id')->constrained('class_lesson_sessions')->onDelete('cascade');
            
            // Registration info
            $table->foreignId('registered_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('registered_at')->useCurrent();
            
            // Status tracking
            $table->enum('status', ['registered', 'attended', 'absent', 'cancelled', 'converted'])->default('registered');
            
            // Feedback after trial
            $table->text('feedback')->nullable();
            $table->integer('rating')->nullable(); // 1-5 stars
            
            // Notes
            $table->text('notes')->nullable();
            
            // Branch
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['trialable_type', 'trialable_id']);
            $table->index('class_id');
            $table->index('class_lesson_session_id');
            $table->index('status');
            $table->index('registered_at');
            
            // Unique constraint: no duplicate registration
            $table->unique(['trialable_type', 'trialable_id', 'class_lesson_session_id'], 'unique_trial_registration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trial_students');
    }
};
