<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_post_likes', function (Blueprint $table) {
            $table->id();
            $table->string('likeable_type'); // CoursePost or CoursePostComment
            $table->unsignedBigInteger('likeable_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['likeable_type', 'likeable_id', 'user_id'], 'unique_like');
            $table->index(['likeable_type', 'likeable_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_post_likes');
    }
};
