<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name'); // Tết Nguyên Đán, Quốc Khánh
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days'); // Số ngày nghỉ
            $table->enum('type', ['national', 'school', 'semester_break', 'other'])->default('national');
            $table->boolean('affects_schedule')->default(true); // Có ảnh hưởng đến lịch học không
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['branch_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
