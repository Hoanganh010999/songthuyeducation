<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\IncomeReport;
use App\Models\AccountItem;
use Illuminate\Support\Facades\Log;

/**
 * Transaction Service
 * 
 * Service để tích hợp với Accounting Module
 * Tạo Income Report khi có enrollment payment
 */
class TransactionService
{
    /**
     * Tạo Income Report thu tiền từ enrollment
     */
    public function createIncomeFromEnrollment(Enrollment $enrollment, float $amount, string $paymentMethod): ?IncomeReport
    {
        try {
            // Tìm account_item cho học phí
            $accountItem = AccountItem::where('code', 'LIKE', 'THU-HP-%')
                ->orWhere('name', 'LIKE', '%học phí%')
                ->first();
            
            if (!$accountItem) {
                // Fallback: Lấy account item đầu tiên của loại thu nhập
                $accountItem = AccountItem::where('type', 'income')->first();
            }

            if (!$accountItem) {
                Log::error('No AccountItem found for enrollment income report');
                return null;
            }

            $student = $enrollment->student;
            $studentName = $student instanceof \App\Models\Customer 
                ? $student->name 
                : $student->name;

            // Tạo Income Report với status 'pending' chờ duyệt
            $incomeReport = IncomeReport::create([
                'title' => "Thu học phí - {$enrollment->product->name}",
                'account_item_id' => $accountItem->id,
                'amount' => $amount,
                'received_date' => now()->toDateString(),
                'payer_name' => $enrollment->customer->name,
                'payer_phone' => $enrollment->customer->phone,
                'payer_info' => [
                    'enrollment_id' => $enrollment->id,
                    'enrollment_code' => $enrollment->code,
                    'customer_id' => $enrollment->customer_id,
                    'student_id' => $enrollment->student_id,
                    'student_type' => $enrollment->student_type,
                    'student_name' => $studentName,
                    'product_id' => $enrollment->product_id,
                    'product_name' => $enrollment->product->name,
                ],
                'description' => "Thu học phí từ đơn đăng ký {$enrollment->code} - Học viên: {$studentName} - Khóa học: {$enrollment->product->name}",
                'status' => 'pending', // Chờ duyệt
                'payment_method' => $paymentMethod,
                'branch_id' => $enrollment->branch_id,
                'reported_by' => auth()->id(),
            ]);

            Log::info('IncomeReport created from enrollment:', [
                'income_report_id' => $incomeReport->id,
                'income_report_code' => $incomeReport->code,
                'enrollment_id' => $enrollment->id,
                'enrollment_code' => $enrollment->code,
                'amount' => $amount,
            ]);

            return $incomeReport;

        } catch (\Exception $e) {
            Log::error('Failed to create income report from enrollment: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Tạo transaction hoàn tiền khi cancel enrollment
     */
    public function createRefundFromEnrollment(Enrollment $enrollment, float $amount, string $reason = null): void
    {
        try {
            // TODO: Uncomment when Transaction model is ready
            
            /*
            Transaction::create([
                'type' => 'refund',
                'category' => 'enrollment',
                'amount' => $amount,
                'reference_type' => Enrollment::class,
                'reference_id' => $enrollment->id,
                'customer_id' => $enrollment->customer_id,
                'branch_id' => $enrollment->branch_id,
                'description' => "Hoàn tiền đơn đăng ký {$enrollment->code}" . ($reason ? " - {$reason}" : ''),
                'status' => 'completed',
                'transaction_date' => now(),
                'created_by' => auth()->id(),
                'metadata' => [
                    'enrollment_code' => $enrollment->code,
                    'cancellation_reason' => $reason,
                ],
            ]);
            */

            Log::info('Refund transaction would be created:', [
                'type' => 'refund',
                'amount' => $amount,
                'enrollment_id' => $enrollment->id,
                'reason' => $reason,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create refund transaction: ' . $e->getMessage());
        }
    }

    /**
     * Tạo transaction chi tiền (trừ tiền ví sau mỗi buổi học)
     */
    public function createExpenseForAttendance($attendance, float $amount): void
    {
        try {
            // TODO: Implement when Attendance tracking is ready
            
            Log::info('Attendance expense transaction would be created:', [
                'type' => 'expense',
                'amount' => $amount,
                'attendance_id' => $attendance->id ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create attendance expense: ' . $e->getMessage());
        }
    }
}

