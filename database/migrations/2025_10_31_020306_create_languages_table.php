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
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // English, Vietnamese
            $table->string('code', 10)->unique(); // en, vi
            $table->string('flag')->nullable(); // 🇬🇧, 🇻🇳 hoặc URL icon
            $table->string('direction', 3)->default('ltr'); // ltr, rtl
            $table->boolean('is_default')->default(false); // Ngôn ngữ mặc định
            $table->boolean('is_active')->default(true); // Kích hoạt/Vô hiệu hóa
            $table->integer('sort_order')->default(0); // Thứ tự hiển thị
            $table->timestamps();
            
            // Index
            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
