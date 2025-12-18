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
        Schema::create('work_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['executor', 'assigner', 'observer', 'supporter'])->index();

            $table->dateTime('assigned_at');
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');

            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->text('decline_reason')->nullable();

            // Cho trường hợp assign department
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('is_department_assignment')->default(false);

            $table->timestamps();

            $table->unique(['work_item_id', 'user_id', 'role'], 'work_assignment_unique');
            $table->index(['user_id', 'role', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_assignments');
    }
};
