<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Services\AttendanceFeeService;
use Illuminate\Support\Facades\Log;

class AttendanceObserver
{
    protected $feeService;

    public function __construct(AttendanceFeeService $feeService)
    {
        $this->feeService = $feeService;
    }

    /**
     * Handle the Attendance "created" event.
     */
    public function created(Attendance $attendance): void
    {
        $this->processAttendanceFee($attendance);
    }

    /**
     * Handle the Attendance "updated" event.
     */
    public function updated(Attendance $attendance): void
    {
        // Process if status or is_excused changed
        if ($attendance->isDirty('status') || $attendance->isDirty('is_excused') || $attendance->isDirty('check_in_time')) {
            $this->processAttendanceFee($attendance);
        }
    }

    /**
     * Process attendance fee (charge + penalty + refund tracking)
     */
    protected function processAttendanceFee(Attendance $attendance): void
    {
        try {
            Log::info("👀 AttendanceObserver: Triggering fee process", [
                'attendance_id' => $attendance->id,
                'status' => $attendance->status,
            ]);

            $result = $this->feeService->processAttendanceFee($attendance);

            if ($result['success']) {
                Log::info("✅ AttendanceObserver: Fee processed successfully", [
                    'attendance_id' => $attendance->id,
                    'deductions_count' => count($result['deductions'] ?? []),
                ]);
            } else {
                Log::warning("⚠️ AttendanceObserver: Fee processing failed", [
                    'attendance_id' => $attendance->id,
                    'message' => $result['message'] ?? 'Unknown error',
                ]);
            }
        } catch (\Exception $e) {
            Log::error("❌ AttendanceObserver: Exception during fee processing", [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
