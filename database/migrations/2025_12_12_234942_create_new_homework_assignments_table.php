<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create NEW homework_assignments table for actual assignments to classes
     * This table links homework_bank templates to classes with deadline and student assignments
     */
    public function up(): void
    {
        Schema::create('homework_assignments', function (Blueprint $table) {
            $table->id();

            // Assignment details
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('deadline')->nullable();

            // Homework from bank - JSON array of homework_bank IDs
            // Example: [1, 2, 3] means this assignment includes homework 1, 2, and 3 from the bank
            $table->json('hw_ids')->comment('JSON array of homework_bank IDs');

            // Student assignment - JSON array of user IDs or NULL for all students
            // NULL = assigned to all students in class
            // [user_id_1, user_id_2] = assigned to specific students only
            $table->json('assigned_to')->nullable()->comment('JSON array of student user_ids, NULL = all students');

            // Status
            $table->enum('status', ['active', 'closed'])->default('active');

            // Who assigned this homework
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');

            // Branch for multi-tenancy
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');

            $table->timestamps();

            // Indexes
            $table->index('class_id');
            $table->index('deadline');
            $table->index('status');
            $table->index('assigned_by');
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homework_assignments');
    }
};
