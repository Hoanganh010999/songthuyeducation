<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add late penalty fields to policies table
        Schema::table('attendance_fee_policies', function (Blueprint $table) {
            $table->decimal('late_penalty_amount', 10, 2)->default(0)->after('late_grace_minutes')
                ->comment('Số tiền phạt cố định khi đi trễ quá nhiều');
            $table->integer('late_penalty_threshold')->default(3)->after('late_penalty_amount')
                ->comment('Số buổi trễ/tháng trước khi bị phạt');
        });

        // Add refund tracking fields to deductions table
        Schema::table('attendance_fee_deductions', function (Blueprint $table) {
            // Change deduction_type to include more types
            $table->dropColumn('deduction_type');
        });

        Schema::table('attendance_fee_deductions', function (Blueprint $table) {
            $table->enum('transaction_type', [
                'charge',           // Phí buổi học bình thường
                'penalty',          // Phạt (late penalty)
                'refund_pending',   // Hoàn phí chờ duyệt
            ])->default('charge')->after('policy_id')
                ->comment('Loại giao dịch');
            
            $table->integer('consecutive_absence_count')->nullable()->after('transaction_type')
                ->comment('Số buổi vắng liên tiếp (cho refund tracking)');
            
            $table->enum('refund_status', ['pending', 'approved', 'rejected'])->nullable()
                ->after('consecutive_absence_count')
                ->comment('Trạng thái hoàn phí');
            
            $table->foreignId('refund_approved_by')->nullable()
                ->after('refund_status')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin duyệt refund');
            
            $table->timestamp('refund_approved_at')->nullable()
                ->after('refund_approved_by')
                ->comment('Thời gian duyệt refund');
            
            $table->text('refund_reason')->nullable()
                ->after('refund_approved_at')
                ->comment('Lý do hoàn phí');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_fee_policies', function (Blueprint $table) {
            $table->dropColumn(['late_penalty_amount', 'late_penalty_threshold']);
        });

        Schema::table('attendance_fee_deductions', function (Blueprint $table) {
            $table->dropColumn([
                'transaction_type',
                'consecutive_absence_count',
                'refund_status',
                'refund_approved_by',
                'refund_approved_at',
                'refund_reason'
            ]);
        });

        // Restore old deduction_type
        Schema::table('attendance_fee_deductions', function (Blueprint $table) {
            $table->enum('deduction_type', [
                'unexcused_absence',
                'excused_over_limit',
                'late',
            ])->after('policy_id');
        });
    }
};
