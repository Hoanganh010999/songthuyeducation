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
        Schema::create('work_items', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['project', 'task'])->index();
            $table->foreignId('parent_id')->nullable()->constrained('work_items')->onDelete('cascade');
            $table->string('code', 50)->unique(); // WP-2024-001, WT-2024-001
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium')->index();
            $table->enum('status', [
                'pending',
                'assigned',
                'in_progress',
                'submitted',
                'revision_requested',
                'completed',
                'cancelled'
            ])->default('pending')->index();

            $table->dateTime('start_date')->nullable();
            $table->dateTime('due_date')->nullable()->index();
            $table->dateTime('completed_at')->nullable();

            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->nullable();

            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');

            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');

            // Metadata cho tính điểm HAY System
            $table->json('metadata')->nullable(); // {difficulty_level, complexity, impact}

            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status', 'due_date']);
            $table->index(['department_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_items');
    }
};
