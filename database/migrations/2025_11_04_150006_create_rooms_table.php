<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Phòng 101
            $table->string('code')->unique(); // ROOM_101
            $table->string('building')->nullable(); // Tòa A, Tòa B
            $table->string('floor')->nullable(); // Tầng 1, Tầng 2
            $table->integer('capacity')->default(40); // Sức chứa
            $table->enum('room_type', ['classroom', 'lab', 'computer_lab', 'library', 'gym', 'other'])->default('classroom');
            $table->json('facilities')->nullable(); // Projector, AC, Whiteboard, ...
            $table->boolean('is_available')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['branch_id', 'is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
