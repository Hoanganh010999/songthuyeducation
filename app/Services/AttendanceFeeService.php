<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceFeeDeduction;
use App\Models\AttendanceFeePolicy;
use App\Models\ClassModel;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\ExpenseProposal;
use App\Models\FinancialTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceFeeService
{
    /**
     * Process fee charge and penalty for attendance
     * 
     * NEW LOGIC:
     * - Present: Charge 100% fee
     * - Unexcused: Charge 100% fee, track for refund if consecutive > threshold
     * - Excused (≤ limit): FREE
     * - Excused (> limit): Charge 100% fee
     * - Late: Charge 100% fee + penalty if over threshold/month
     */
    public function processAttendanceFee(Attendance $attendance): array
    {
        Log::info("🎯 START: Processing attendance fee", [
            'attendance_id' => $attendance->id,
            'student_id' => $attendance->student_id,
            'status' => $attendance->status,
            'is_excused' => $attendance->is_excused,
        ]);

        // Get active policy
        $class = ClassModel::find($attendance->class_id);
        if (!$class) {
            Log::error("❌ Class not found", ['class_id' => $attendance->class_id]);
            return ['success' => false, 'message' => 'Class not found'];
        }

        $policy = $this->getActivePolicy($class->branch_id);
        if (!$policy) {
            Log::warning("⚠️ No active policy found");
            return ['success' => false, 'message' => 'No active policy'];
        }

        $hourlyRate = $class->hourly_rate ?? 0;
        if ($hourlyRate <= 0) {
            Log::warning("⚠️ Invalid hourly rate", ['hourly_rate' => $hourlyRate]);
            return ['success' => false, 'message' => 'Invalid hourly rate'];
        }

        DB::beginTransaction();
        try {
            $results = [];

            // === LOGIC BY STATUS ===
            if ($attendance->status === 'present') {
                // PRESENT: Charge 100% fee
                $results[] = $this->chargeFee($attendance, $policy, $hourlyRate, 100, 'charge', 'Phí buổi học');
            
            } elseif ($attendance->status === 'absent' && !$attendance->is_excused) {
                // UNEXCUSED ABSENCE: Charge 100% + track for refund
                $consecutiveCount = $this->getConsecutiveUnexcusedAbsences($attendance->student_id, $attendance->class_id);
                
                $feeResult = $this->chargeFee($attendance, $policy, $hourlyRate, 100, 'charge', 'Phí buổi học (vắng không phép)');
                $results[] = $feeResult;

                // Check if need to mark for refund
                if ($consecutiveCount > $policy->absence_consecutive_threshold) {
                    Log::info("🔄 Consecutive threshold exceeded, marking for refund", [
                        'consecutive_count' => $consecutiveCount,
                        'threshold' => $policy->absence_consecutive_threshold,
                    ]);
                    
                    $this->markConsecutiveAbsencesForRefund($attendance->student_id, $attendance->class_id, $consecutiveCount, $policy);
                }
            
            } elseif ($attendance->status === 'absent' && $attendance->is_excused) {
                // EXCUSED ABSENCE: Check monthly limit
                $excusedCount = $this->getMonthlyExcusedAbsencesCount(
                    $attendance->student_id,
                    $attendance->class_id,
                    Carbon::now()->format('m'),
                    Carbon::now()->format('Y')
                );

                if ($excusedCount <= $policy->absence_excused_free_limit) {
                    Log::info("✅ Excused absence within limit - FREE", [
                        'excused_count' => $excusedCount,
                        'limit' => $policy->absence_excused_free_limit,
                    ]);
                    // No charge
                } else {
                    Log::info("💰 Excused absence over limit - CHARGE", [
                        'excused_count' => $excusedCount,
                        'limit' => $policy->absence_excused_free_limit,
                    ]);
                    $results[] = $this->chargeFee($attendance, $policy, $hourlyRate, 100, 'charge', 'Phí buổi học (vắng có phép vượt giới hạn)');
                }
            
            } elseif ($attendance->status === 'late') {
                // LATE: Charge 100% fee
                $results[] = $this->chargeFee($attendance, $policy, $hourlyRate, 100, 'charge', 'Phí buổi học (đi trễ)');

                // Check late penalty
                $lateCount = $this->getMonthlyLateCount(
                    $attendance->student_id,
                    $attendance->class_id,
                    Carbon::now()->format('m'),
                    Carbon::now()->format('Y')
                );

                if ($lateCount > $policy->late_penalty_threshold && $policy->late_penalty_amount > 0) {
                    // Check if already penalized this month
                    $alreadyPenalized = AttendanceFeeDeduction::where('student_id', $attendance->student_id)
                        ->where('class_id', $attendance->class_id)
                        ->where('transaction_type', 'penalty')
                        ->whereYear('applied_at', Carbon::now()->year)
                        ->whereMonth('applied_at', Carbon::now()->month)
                        ->exists();

                    if (!$alreadyPenalized) {
                        Log::info("⚠️ Late penalty triggered", [
                            'late_count' => $lateCount,
                            'threshold' => $policy->late_penalty_threshold,
                            'penalty_amount' => $policy->late_penalty_amount,
                        ]);
                        
                        $results[] = $this->applyPenalty($attendance, $policy, $policy->late_penalty_amount, 'Phạt đi trễ quá nhiều');
                    }
                }
            }

            DB::commit();
            Log::info("✅ COMPLETE: Attendance fee processed successfully", [
                'deductions_count' => count($results),
            ]);

            return [
                'success' => true,
                'deductions' => $results,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("❌ ERROR: Failed to process attendance fee", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Charge fee (create deduction + wallet withdrawal)
     */
    protected function chargeFee(
        Attendance $attendance,
        AttendanceFeePolicy $policy,
        float $hourlyRate,
        float $percent,
        string $transactionType,
        string $notes
    ): AttendanceFeeDeduction {
        $amount = $hourlyRate * ($percent / 100);

        // Withdraw from wallet
        $student = $attendance->student;
        $wallet = $student->wallet;
        if (!$wallet) {
            throw new \Exception("Student wallet not found");
        }

        $walletTransaction = $this->withdrawFromWallet($wallet, $amount, $notes);

        // Create deduction record
        $deduction = AttendanceFeeDeduction::create([
            'attendance_id' => $attendance->id,
            'student_id' => $attendance->student_id,
            'class_id' => $attendance->class_id,
            'session_id' => $attendance->session_id,
            'policy_id' => $policy->id,
            'transaction_type' => $transactionType,
            'hourly_rate' => $hourlyRate,
            'deduction_percent' => $percent,
            'deduction_amount' => $amount,
            'wallet_transaction_id' => $walletTransaction->id,
            'notes' => $notes,
            'applied_at' => now(),
        ]);

        Log::info("💰 Fee charged", [
            'deduction_id' => $deduction->id,
            'amount' => $amount,
            'type' => $transactionType,
        ]);

        return $deduction;
    }

    /**
     * Apply penalty (fixed amount)
     */
    protected function applyPenalty(
        Attendance $attendance,
        AttendanceFeePolicy $policy,
        float $amount,
        string $notes
    ): AttendanceFeeDeduction {
        $student = $attendance->student;
        $wallet = $student->wallet;

        $walletTransaction = $this->withdrawFromWallet($wallet, $amount, $notes);

        $deduction = AttendanceFeeDeduction::create([
            'attendance_id' => $attendance->id,
            'student_id' => $attendance->student_id,
            'class_id' => $attendance->class_id,
            'session_id' => $attendance->session_id,
            'policy_id' => $policy->id,
            'transaction_type' => 'penalty',
            'hourly_rate' => 0,
            'deduction_percent' => 0,
            'deduction_amount' => $amount,
            'wallet_transaction_id' => $walletTransaction->id,
            'notes' => $notes,
            'applied_at' => now(),
        ]);

        Log::info("⚠️ Penalty applied", [
            'deduction_id' => $deduction->id,
            'amount' => $amount,
        ]);

        return $deduction;
    }

    /**
     * Mark consecutive absences for refund by creating Expense Proposal
     */
    protected function markConsecutiveAbsencesForRefund(
        int $studentId,
        int $classId,
        int $consecutiveCount,
        AttendanceFeePolicy $policy
    ): void {
        // Get all recent unexcused absence deductions
        $deductions = AttendanceFeeDeduction::where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('transaction_type', 'charge')
            ->whereHas('attendance', function ($q) {
                $q->where('status', 'absent')->where('is_excused', false);
            })
            ->whereNull('refund_status')
            ->latest('applied_at')
            ->limit($consecutiveCount)
            ->get();

        if ($deductions->isEmpty()) {
            return;
        }

        // Calculate total refund amount
        $totalAmount = $deductions->sum('deduction_amount');
        $class = ClassModel::find($classId);
        $student = \App\Models\Student::find($studentId);

        // Create Expense Proposal for refund
        try {
            $expenseProposal = ExpenseProposal::create([
                'title' => "Hoàn học phí - Vắng {$consecutiveCount} buổi liên tiếp - {$student->full_name}",
                'financial_plan_id' => 1, // Default plan (should be configurable)
                'financial_plan_item_id' => 1, // Default item (should be configurable)
                'account_item_id' => 1, // Should be "Học phí" account item
                'cash_account_id' => null, // Will be set when approved
                'amount' => $totalAmount,
                'requested_date' => now(),
                'description' => "Hoàn học phí cho học viên {$student->full_name} do vắng không phép {$consecutiveCount} buổi liên tiếp (vượt ngưỡng {$policy->absence_consecutive_threshold}). Lớp: {$class->name}",
                'status' => 'pending',
                'requested_by' => auth()->id() ?? 1,
                'branch_id' => $class->branch_id,
                'payment_method' => 'wallet_deposit',
            ]);

            // Create pending FinancialTransaction
            FinancialTransaction::create([
                'transaction_type' => 'expense',
                'status' => 'pending',
                'transactionable_type' => ExpenseProposal::class,
                'transactionable_id' => $expenseProposal->id,
                'financial_plan_id' => $expenseProposal->financial_plan_id,
                'account_item_id' => $expenseProposal->account_item_id,
                'cash_account_id' => $expenseProposal->cash_account_id,
                'amount' => $expenseProposal->amount,
                'transaction_date' => $expenseProposal->requested_date,
                'description' => $expenseProposal->description,
                'payment_method' => 'wallet_deposit',
                'recorded_by' => auth()->id() ?? 1,
                'branch_id' => $expenseProposal->branch_id,
                'metadata' => [
                    'student_id' => $studentId,
                    'class_id' => $classId,
                    'consecutive_count' => $consecutiveCount,
                    'deduction_ids' => $deductions->pluck('id')->toArray(),
                ],
            ]);

            // Update deductions to link with expense proposal
            foreach ($deductions as $deduction) {
                $deduction->update([
                    'refund_status' => 'pending',
                    'consecutive_absence_count' => $consecutiveCount,
                    'refund_reason' => "Vắng {$consecutiveCount} buổi liên tiếp (vượt threshold {$policy->absence_consecutive_threshold})",
                    'notes' => ($deduction->notes ? $deduction->notes . ' | ' : '') . "Expense Proposal: {$expenseProposal->code}",
                ]);

                Log::info("🔄 Marked for refund", [
                    'deduction_id' => $deduction->id,
                    'expense_proposal_id' => $expenseProposal->id,
                    'expense_code' => $expenseProposal->code,
                ]);
            }

            Log::info("✅ Expense Proposal created for refund", [
                'expense_proposal_id' => $expenseProposal->id,
                'code' => $expenseProposal->code,
                'amount' => $totalAmount,
                'student_id' => $studentId,
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Failed to create Expense Proposal for refund", [
                'error' => $e->getMessage(),
                'student_id' => $studentId,
                'class_id' => $classId,
            ]);
        }
    }

    /**
     * Withdraw from wallet
     */
    protected function withdrawFromWallet(Wallet $wallet, float $amount, string $description): WalletTransaction
    {
        return WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'deduction',
            'amount' => $amount,
            'balance_before' => $wallet->balance,
            'balance_after' => $wallet->balance - $amount,
            'description' => $description,
            'transaction_date' => now(),
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Get active policy for branch
     */
    protected function getActivePolicy(?int $branchId): ?AttendanceFeePolicy
    {
        // Try branch-specific policy first
        if ($branchId) {
            $policy = AttendanceFeePolicy::where('is_active', true)
                ->where('branch_id', $branchId)
                ->first();
            if ($policy) {
                return $policy;
            }
        }

        // Fallback to global policy
        return AttendanceFeePolicy::where('is_active', true)
            ->whereNull('branch_id')
            ->first();
    }

    /**
     * Get consecutive unexcused absences count
     */
    protected function getConsecutiveUnexcusedAbsences(int $studentId, int $classId): int
    {
        $attendances = Attendance::where('student_id', $studentId)
            ->where('class_id', $classId)
            ->orderBy('id', 'desc')
            ->limit(50) // Check last 50 sessions
            ->get();

        $consecutiveCount = 0;
        foreach ($attendances as $att) {
            if ($att->status === 'absent' && !$att->is_excused) {
                $consecutiveCount++;
            } else {
                break; // Stop counting when found present/late/excused
            }
        }

        return $consecutiveCount;
    }

    /**
     * Get monthly excused absences count
     */
    protected function getMonthlyExcusedAbsencesCount(int $studentId, int $classId, string $month, string $year): int
    {
        return Attendance::where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('status', 'absent')
            ->where('is_excused', true)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();
    }

    /**
     * Get monthly late count
     */
    protected function getMonthlyLateCount(int $studentId, int $classId, string $month, string $year): int
    {
        return Attendance::where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('status', 'late')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();
    }
}
