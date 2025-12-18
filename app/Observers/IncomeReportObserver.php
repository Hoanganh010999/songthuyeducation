<?php

namespace App\Observers;

use App\Models\IncomeReport;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Log;

class IncomeReportObserver
{
    /**
     * Handle the IncomeReport "updated" event.
     */
    public function updated(IncomeReport $incomeReport): void
    {
        // Khi IncomeReport được approve
        if ($incomeReport->isDirty('status') && $incomeReport->status === 'approved') {
            $this->handleApproved($incomeReport);
        }
    }

    /**
     * Xử lý khi IncomeReport được approve
     */
    protected function handleApproved(IncomeReport $incomeReport): void
    {
        try {
            // Kiểm tra xem có liên quan đến enrollment không
            $payerInfo = $incomeReport->payer_info;
            
            if (isset($payerInfo['enrollment_id'])) {
                $enrollment = Enrollment::find($payerInfo['enrollment_id']);
                
                if ($enrollment && $enrollment->status === Enrollment::STATUS_PENDING) {
                    // Update enrollment status thành 'approved' (sẵn sàng nhận thanh toán)
                    $enrollment->update([
                        'status' => 'approved', // Trạng thái mới: đã duyệt, chờ thanh toán
                    ]);

                    Log::info("Enrollment #{$enrollment->id} status updated to 'approved' after IncomeReport approval", [
                        'income_report_id' => $incomeReport->id,
                        'enrollment_id' => $enrollment->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to update enrollment status after IncomeReport approval: ' . $e->getMessage());
        }
    }
}

