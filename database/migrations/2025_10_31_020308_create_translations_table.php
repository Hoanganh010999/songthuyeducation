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
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained()->onDelete('cascade');
            $table->string('group')->index(); // common, auth, users, products, etc.
            $table->string('key')->index(); // welcome_message, login_button, etc.
            $table->text('value'); // Nội dung dịch
            $table->timestamps();
            
            // Unique constraint: Mỗi key chỉ có 1 bản dịch cho mỗi ngôn ngữ
            $table->unique(['language_id', 'group', 'key']);
            
            // Index để tìm kiếm nhanh
            $table->index(['group', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
