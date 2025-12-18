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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('name'); // Tên môn học (Toán, Lý, Hóa, etc.)
            $table->string('code', 50)->nullable(); // Mã môn học (MATH, PHYS, CHEM)
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#3B82F6'); // Màu đại diện (hex color)
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Unique constraint: Mã môn học unique trong cùng branch
            $table->unique(['branch_id', 'code']);
            
            // Index
            $table->index(['branch_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
