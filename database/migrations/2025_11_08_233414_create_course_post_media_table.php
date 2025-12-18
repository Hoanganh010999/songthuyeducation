<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_post_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('course_posts')->cascadeOnDelete();
            $table->enum('media_type', ['image', 'video', 'document', 'link'])->default('image');
            $table->string('file_name')->nullable();
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable()->comment('in bytes');
            $table->string('url')->nullable()->comment('For external links');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('post_id');
            $table->index('media_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_post_media');
    }
};
