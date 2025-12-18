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
        Schema::create('customer_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên nguồn: Facebook, Google, Giới thiệu, Walk-in, etc.
            $table->string('code')->unique(); // Code: facebook, google, referral, walk_in, etc.
            $table->string('icon')->nullable(); // Icon class (optional)
            $table->string('color')->nullable(); // Màu hiển thị (hex code)
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_sources');
    }
};
