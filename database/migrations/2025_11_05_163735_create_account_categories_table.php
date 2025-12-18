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
        Schema::create('account_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Mã hạng mục');
            $table->string('name')->comment('Tên hạng mục');
            $table->enum('type', ['income', 'expense'])->comment('Thu/Chi');
            $table->foreignId('parent_id')->nullable()->constrained('account_categories')->onDelete('cascade')->comment('Hạng mục cha');
            $table->text('description')->nullable()->comment('Mô tả');
            $table->boolean('is_active')->default(true)->comment('Đang hoạt động');
            $table->integer('sort_order')->default(0)->comment('Thứ tự sắp xếp');
            $table->timestamps();
            
            // Indexes
            $table->index('code');
            $table->index('type');
            $table->index('parent_id');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_categories');
    }
};
