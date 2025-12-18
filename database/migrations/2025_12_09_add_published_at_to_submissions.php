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
        Schema::table('submissions', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('graded_at');
            $table->unsignedBigInteger('published_by')->nullable()->after('published_at');
            
            $table->foreign('published_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropForeign(['published_by']);
            $table->dropColumn(['published_at', 'published_by']);
        });
    }
};

