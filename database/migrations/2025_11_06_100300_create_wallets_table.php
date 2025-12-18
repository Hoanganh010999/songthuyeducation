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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic - Có thể là ví của Customer hoặc CustomerChild
            $table->morphs('owner'); // owner_id, owner_type
            
            $table->string('code')->unique(); // Mã ví: WAL001
            $table->decimal('balance', 15, 2)->default(0); // Số dư hiện tại
            $table->decimal('total_deposited', 15, 2)->default(0); // Tổng đã nạp
            $table->decimal('total_spent', 15, 2)->default(0); // Tổng đã chi
            
            // Thông tin
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete(); // Ví thuộc chi nhánh
            $table->string('currency')->default('VND');
            
            // Trạng thái
            $table->boolean('is_active')->default(true);
            $table->boolean('is_locked')->default(false); // Khóa ví (không cho giao dịch)
            $table->text('lock_reason')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes (morphs already creates index for owner_type, owner_id)
            $table->index('branch_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};

