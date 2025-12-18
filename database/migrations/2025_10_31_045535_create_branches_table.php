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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Mã chi nhánh (VD: HN01, HCM01)');
            $table->string('name')->comment('Tên chi nhánh');
            $table->string('phone')->nullable()->comment('Số điện thoại');
            $table->string('email')->nullable()->comment('Email liên hệ');
            $table->text('address')->nullable()->comment('Địa chỉ chi nhánh');
            $table->string('city')->nullable()->comment('Thành phố');
            $table->string('district')->nullable()->comment('Quận/Huyện');
            $table->string('ward')->nullable()->comment('Phường/Xã');
            
            // Manager info
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete()->comment('Quản lý chi nhánh');
            
            // Status
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');
            $table->boolean('is_headquarters')->default(false)->comment('Là trụ sở chính');
            
            // Additional info
            $table->text('description')->nullable()->comment('Mô tả');
            $table->json('metadata')->nullable()->comment('Thông tin bổ sung (JSON)');
            
            $table->timestamps();
            
            // Indexes
            $table->index('code');
            $table->index('is_active');
            $table->index('manager_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
