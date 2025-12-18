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
        Schema::create('customer_interaction_results', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên kết quả: Thành công, Không liên lạc được, Hẹn gặp, etc.
            $table->string('code')->unique(); // Code: success, no_contact, scheduled, rejected, etc.
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
        Schema::dropIfExists('customer_interaction_results');
    }
};
