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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('module')->index(); // Tên module (users, products, orders, etc.)
            $table->string('action'); // Hành động (view, create, edit, delete, etc.)
            $table->string('name')->unique(); // Tên đầy đủ: module.action (users.view, users.create)
            $table->string('display_name')->nullable(); // Tên hiển thị
            $table->text('description')->nullable(); // Mô tả quyền
            $table->integer('sort_order')->default(0); // Thứ tự sắp xếp
            $table->boolean('is_active')->default(true); // Trạng thái kích hoạt
            $table->timestamps();
            
            // Index để tìm kiếm nhanh
            $table->index(['module', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
