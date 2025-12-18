<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Học kỳ 1, Học kỳ 2, Học kỳ hè
            $table->string('code'); // SEM_1, SEM_2, SEM_SUMMER
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_weeks')->default(18); // Tổng số tuần
            $table->boolean('is_current')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['academic_year_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};
