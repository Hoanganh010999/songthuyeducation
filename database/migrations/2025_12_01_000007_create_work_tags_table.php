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
        Schema::create('work_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 50)->unique();
            $table->string('color', 7)->default('#3B82F6');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->index(['branch_id', 'slug']);
        });

        Schema::create('work_item_tag', function (Blueprint $table) {
            $table->foreignId('work_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('work_tag_id')->constrained()->onDelete('cascade');
            $table->primary(['work_item_id', 'work_tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_item_tag');
        Schema::dropIfExists('work_tags');
    }
};
