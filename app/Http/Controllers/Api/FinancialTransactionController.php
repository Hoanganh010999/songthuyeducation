<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = FinancialTransaction::with([
            'transactionable',
            'financialPlan',
            'accountItem.category',
            'cashAccount',
            'recordedBy',
            'approvedBy',
            'verifiedBy',
            'rejectedBy',
            'branch'
        ]);

        // Filter by type
        if ($request->has('transaction_type') && !empty($request->transaction_type)) {
            $query->where('transaction_type', $request->transaction_type);
        }

        // Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Filter by financial plan
        if ($request->has('financial_plan_id') && !empty($request->financial_plan_id)) {
            $query->where('financial_plan_id', $request->financial_plan_id);
        }

        // Filter by branch
        if ($request->has('branch_id') && !empty($request->branch_id)) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by date range
        if ($request->has('from_date') && !empty($request->from_date)) {
            $query->where('transaction_date', '>=', $request->from_date);
        }
        if ($request->has('to_date') && !empty($request->to_date)) {
            $query->where('transaction_date', '<=', $request->to_date);
        }

        // Search
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function($q) use ($request) {
                $q->where('code', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $transactions]);
    }

    public function show(FinancialTransaction $financialTransaction)
    {
        return response()->json([
            'data' => $financialTransaction->load([
                'transactionable',
                'financialPlan.planItems',
                'accountItem.category',
                'recordedBy',
                'branch'
            ])
        ]);
    }

    /**
     * Get financial summary (ONLY VERIFIED TRANSACTIONS)
     */
    public function summary(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month');
        $branch_id = $request->get('branch_id');

        $query = FinancialTransaction::query();

        // ⚠️ CRITICAL: CHỈ TÍNH CÁC GIAO DỊCH ĐÃ VERIFIED
        $query->where('status', 'verified');

        // Filter by date
        if ($month) {
            $startDate = "{$year}-{$month}-01";
            $endDate = date('Y-m-t', strtotime($startDate));
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        } else {
            $query->whereYear('transaction_date', $year);
        }

        // Filter by branch
        if ($branch_id) {
            $query->where('branch_id', $branch_id);
        }

        // Calculate totals (ONLY VERIFIED)
        $totalIncome = (clone $query)->income()->sum('amount');
        $totalExpense = (clone $query)->expense()->sum('amount');
        $balance = $totalIncome - $totalExpense;

        // Monthly breakdown (ONLY VERIFIED)
        $monthlyData = [];
        for ($i = 1; $i <= 12; $i++) {
            $startOfMonth = "{$year}-" . str_pad($i, 2, '0', STR_PAD_LEFT) . "-01";
            $endOfMonth = date('Y-m-t', strtotime($startOfMonth));

            $monthQuery = FinancialTransaction::whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->where('status', 'verified'); // ⚠️ ONLY VERIFIED
            
            if ($branch_id) {
                $monthQuery->where('branch_id', $branch_id);
            }

            $monthlyData[] = [
                'month' => $i,
                'income' => (clone $monthQuery)->income()->sum('amount'),
                'expense' => (clone $monthQuery)->expense()->sum('amount'),
            ];
        }

        \Log::info('📊 Transactions Summary', [
            'year' => $year,
            'month' => $month,
            'branch_id' => $branch_id,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $balance,
            'note' => 'ONLY VERIFIED transactions counted',
        ]);

        return response()->json([
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $balance,
            'monthly_data' => $monthlyData,
        ]);
    }

    /**
     * Get category breakdown (ONLY VERIFIED TRANSACTIONS)
     */
    public function categoryBreakdown(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $type = $request->get('type', 'expense'); // income or expense
        $branch_id = $request->get('branch_id');

        $query = FinancialTransaction::with('accountItem.category')
            ->where('transaction_type', $type)
            ->where('status', 'verified') // ⚠️ ONLY VERIFIED
            ->whereYear('transaction_date', $year);

        if ($branch_id) {
            $query->where('branch_id', $branch_id);
        }

        $transactions = $query->get();

        $breakdown = $transactions->groupBy(function($transaction) {
            return $transaction->accountItem?->category?->name ?? 'Uncategorized';
        })->map(function($group, $categoryName) {
            return [
                'category_name' => $categoryName,
                'total' => $group->sum('amount'),
                'count' => $group->count(),
            ];
        })->values(); // Convert to indexed array

        \Log::info('📊 Category Breakdown', [
            'year' => $year,
            'type' => $type,
            'branch_id' => $branch_id,
            'transactions_count' => $transactions->count(),
            'breakdown_count' => $breakdown->count(),
            'breakdown' => $breakdown->toArray(),
        ]);

        return response()->json($breakdown);
    }

    /**
     * Get cash flow data (ONLY VERIFIED TRANSACTIONS)
     */
    public function cashFlow(Request $request)
    {
        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate = $request->get('end_date', date('Y-m-t'));
        $branch_id = $request->get('branch_id');

        $query = FinancialTransaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'verified'); // ⚠️ ONLY VERIFIED

        if ($branch_id) {
            $query->where('branch_id', $branch_id);
        }

        $transactions = $query->orderBy('transaction_date')->get();

        $cashFlow = [];
        $runningBalance = 0;

        // Group by date
        $dailyData = $transactions->groupBy(function($transaction) {
            return $transaction->transaction_date->format('Y-m-d');
        });

        foreach ($dailyData as $date => $dayTransactions) {
            $dayIncome = $dayTransactions->where('transaction_type', 'income')->sum('amount');
            $dayExpense = $dayTransactions->where('transaction_type', 'expense')->sum('amount');
            $dayNet = $dayIncome - $dayExpense;
            $runningBalance += $dayNet;

            $cashFlow[] = [
                'date' => $date,
                'income' => $dayIncome,
                'expense' => $dayExpense,
                'net' => $dayNet,
                'balance' => $runningBalance,
            ];
        }

        return response()->json(['data' => $cashFlow]);
    }

    /**
     * Export transactions to CSV
     */
    public function export(Request $request)
    {
        if (!Auth::user()->hasPermission('financial_transactions.export')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = FinancialTransaction::with(['accountItem', 'financialPlan', 'recordedBy']);

        // Filters
        if ($request->has('transaction_type') && !empty($request->transaction_type)) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->has('from_date') && !empty($request->from_date)) {
            $query->whereDate('transaction_date', '>=', $request->from_date);
        }

        if ($request->has('to_date') && !empty($request->to_date)) {
            $query->whereDate('transaction_date', '<=', $request->to_date);
        }

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function($q) use ($request) {
                $q->where('code', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('branch_id')) {
            $query->where(function($q) use ($request) {
                $q->where('branch_id', $request->branch_id)
                  ->orWhereNull('branch_id');
            });
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->get();

        // Create CSV
        $filename = 'transactions_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'Mã GD',
                'Ngày GD',
                'Loại',
                'Khoản mục',
                'Số tiền',
                'Phương thức TT',
                'Kế hoạch',
                'Người ghi nhận',
                'Mô tả'
            ]);

            // Data rows
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->code,
                    $transaction->transaction_date,
                    $transaction->transaction_type === 'income' ? 'Thu' : 'Chi',
                    $transaction->accountItem->name ?? '',
                    $transaction->amount,
                    $transaction->payment_method ?? '',
                    $transaction->financialPlan->name ?? '',
                    $transaction->recordedBy->name ?? '',
                    $transaction->description ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Approve transaction (Accountant)
     * Selects cash account and approves
     */
    public function approve(Request $request, $transaction)
    {
        // Manually load transaction if not model-bound
        if (!($transaction instanceof FinancialTransaction)) {
            $transaction = FinancialTransaction::findOrFail($transaction);
        }

        \Log::info('🔵 Approve request received', [
            'transaction_id' => $transaction->id,
            'transaction_status' => $transaction->status,
            'transaction_cash_account_id' => $transaction->cash_account_id,
            'request_data' => $request->all(),
        ]);

        if (!auth()->user()->hasPermission('transactions.approve')) {
            \Log::warning('❌ Unauthorized approve attempt');
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($transaction->status !== 'pending') {
            \Log::warning('❌ Transaction not pending', [
                'status' => $transaction->status,
            ]);
            return response()->json([
                'message' => 'Can only approve pending transactions',
                'current_status' => $transaction->status
            ], 422);
        }

        // If transaction already has cash_account_id (from expense proposal), use it
        // Otherwise, require it from the request (for income reports)
        $validationRule = $transaction->cash_account_id 
            ? 'nullable|exists:cash_accounts,id' 
            : 'required|exists:cash_accounts,id';
        
        \Log::info('🔍 Validation rule for cash_account_id', [
            'rule' => $validationRule,
            'has_existing' => !!$transaction->cash_account_id,
        ]);

        $validated = $request->validate([
            'cash_account_id' => $validationRule,
            'payment_method' => 'nullable|string',
            'payment_ref' => 'nullable|string',
        ]);

        \Log::info('✅ Validation passed', [
            'validated' => $validated,
        ]);

        \Log::info('💰 Approving transaction', [
            'transaction_id' => $transaction->id,
            'existing_cash_account_id' => $transaction->cash_account_id,
            'new_cash_account_id' => $validated['cash_account_id'] ?? null,
        ]);

        DB::beginTransaction();
        try {
            $transaction->update([
                'status' => 'approved',
                'cash_account_id' => $validated['cash_account_id'] ?? $transaction->cash_account_id,
                'payment_method' => $validated['payment_method'] ?? $transaction->payment_method,
                'payment_ref' => $validated['payment_ref'] ?? $transaction->payment_ref,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Update source record if needed
            if ($transaction->transactionable) {
                $transaction->transactionable->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'cash_account_id' => $validated['cash_account_id'] ?? $transaction->cash_account_id,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Transaction approved successfully. Waiting for cashier verification.',
                'data' => $transaction->load(['cashAccount', 'approvedBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to approve transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify transaction (Cashier)
     * Confirms payment and updates balance + wallet for enrollment
     */
    public function verify(Request $request, $transaction)
    {
        // Manually load transaction if not model-bound
        if (!($transaction instanceof FinancialTransaction)) {
            $transaction = FinancialTransaction::findOrFail($transaction);
        }

        \Log::info('🎯 TRANSACTION VERIFY: Method called', [
            'transaction_id' => $transaction->id,
            'transaction_code' => $transaction->code,
            'transaction_type' => $transaction->transaction_type,
            'current_status' => $transaction->status,
            'transactionable_type' => $transaction->transactionable_type,
            'transactionable_id' => $transaction->transactionable_id,
            'user_id' => auth()->id(),
        ]);

        if (!auth()->user()->hasPermission('transactions.verify')) {
            \Log::warning('❌ TRANSACTION VERIFY: User lacks permission', ['user_id' => auth()->id()]);
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($transaction->status !== 'approved') {
            \Log::warning('❌ TRANSACTION VERIFY: Invalid status', [
                'transaction_id' => $transaction->id,
                'current_status' => $transaction->status,
                'expected_status' => 'approved',
            ]);
            return response()->json([
                'message' => 'Can only verify approved transactions'
            ], 422);
        }

        \Log::info('✅ TRANSACTION VERIFY: Starting verification process', [
            'transaction_id' => $transaction->id,
        ]);

        DB::beginTransaction();
        try {
            $transaction->update([
                'status' => 'verified',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);

            \Log::info('✅ TRANSACTION VERIFY: Transaction status updated', [
                'transaction_id' => $transaction->id,
            ]);

            // Update cash account balance
            if ($transaction->cashAccount) {
                if ($transaction->transaction_type === 'income') {
                    $transaction->cashAccount->increment('balance', $transaction->amount);
                } else {
                    $transaction->cashAccount->decrement('balance', $transaction->amount);
                }
                
                \Log::info('✅ TRANSACTION VERIFY: Cash account updated', [
                    'cash_account_id' => $transaction->cashAccount->id,
                    'transaction_type' => $transaction->transaction_type,
                    'amount' => $transaction->amount,
                ]);
            }

            // Update source record if needed
            if ($transaction->transactionable) {
                $transaction->transactionable->update([
                    'status' => 'verified',
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                ]);
                
                \Log::info('✅ TRANSACTION VERIFY: Transactionable updated', [
                    'transactionable_type' => get_class($transaction->transactionable),
                    'transactionable_id' => $transaction->transactionable->id,
                ]);
            }

            // ✨ WALLET DEPOSIT LOGIC: If income from enrollment → deposit to student wallet
            \Log::info('🔍 TRANSACTION VERIFY: Checking conditions', [
                'transaction_type' => $transaction->transaction_type,
                'transactionable_type' => $transaction->transactionable_type,
                'has_transactionable' => $transaction->transactionable ? 'YES' : 'NO',
            ]);
            
            if ($transaction->transaction_type === 'income' && 
                $transaction->transactionable_type === 'App\Models\IncomeReport' &&
                $transaction->transactionable) {
                
                \Log::info('✅ TRANSACTION VERIFY: Conditions met! Checking for enrollment income', [
                    'income_report_id' => $transaction->transactionable->id,
                    'payer_info' => $transaction->transactionable->payer_info,
                ]);
                
                $incomeReport = $transaction->transactionable;
                $payerInfo = $incomeReport->payer_info;
                
                if (isset($payerInfo['enrollment_id'])) {
                    \Log::info('✅ TRANSACTION VERIFY: Found enrollment_id in payer_info', [
                        'enrollment_id' => $payerInfo['enrollment_id'],
                    ]);
                    
                    $enrollment = \App\Models\Enrollment::find($payerInfo['enrollment_id']);
                    
                    if (!$enrollment) {
                        \Log::warning('❌ TRANSACTION VERIFY: Enrollment not found', [
                            'enrollment_id' => $payerInfo['enrollment_id'],
                        ]);
                    } elseif (!$enrollment->student) {
                        \Log::warning('❌ TRANSACTION VERIFY: Enrollment has no student', [
                            'enrollment_id' => $enrollment->id,
                            'student_type' => $enrollment->student_type,
                            'student_id' => $enrollment->student_id,
                        ]);
                    } else {
                        $student = $enrollment->student;
                        \Log::info('✅ TRANSACTION VERIFY: Found student', [
                            'student_id' => $student->id,
                            'student_type' => get_class($student),
                        ]);
                        
                        // ✨ BƯỚC 3: TẠO USER & WALLET KHI VERIFY PAYMENT
                        // Tạo user cho customer/student nếu chưa có
                        \Log::info('🚀 TRANSACTION VERIFY: Calling UserCreationService...');
                        $userCreationService = app(\App\Services\UserCreationService::class);
                        $createdUsers = $userCreationService->createUsersFromEnrollment($enrollment);
                        
                        \Log::info('✅ TRANSACTION VERIFY: UserCreationService returned', [
                            'enrollment_id' => $enrollment->id,
                            'customer_user_id' => $createdUsers['customer_user']?->id,
                            'student_user_id' => $createdUsers['student_user']?->id,
                        ]);
                        
                        // Refresh student to get updated wallet
                        $student = $enrollment->student()->first();
                        
                        // Get or create wallet for student
                        $wallet = $student->wallet;
                        if (!$wallet) {
                            \Log::info('📝 TRANSACTION VERIFY: Creating new wallet for student', [
                                'student_id' => $student->id,
                            ]);
                            
                            $wallet = \App\Models\Wallet::create([
                                'owner_id' => $student->id,
                                'owner_type' => get_class($student),
                                'branch_id' => $enrollment->branch_id,
                                'is_active' => true,
                            ]);
                            \Log::info("✨ TRANSACTION VERIFY: Created wallet for student #{$student->id}", [
                                'wallet_id' => $wallet->id,
                                'enrollment_id' => $enrollment->id,
                            ]);
                        } else {
                            \Log::info('✅ TRANSACTION VERIFY: Using existing wallet', [
                                'wallet_id' => $wallet->id,
                                'current_balance' => $wallet->balance,
                            ]);
                        }
                        
                        // Deposit money into wallet
                        try {
                            \Log::info('💰 TRANSACTION VERIFY: About to call wallet->deposit()', [
                                'wallet_id' => $wallet->id,
                                'amount' => $transaction->amount,
                                'transactionable_type' => get_class($incomeReport),
                                'transactionable_id' => $incomeReport->id,
                            ]);
                            
                            $walletTransaction = $wallet->deposit(
                                $transaction->amount,
                                $incomeReport,
                                "Nạp tiền từ học phí {$enrollment->code} (Transaction #{$transaction->code})"
                            );
                            
                            \Log::info("💰 TRANSACTION VERIFY: Deposited {$transaction->amount} to student wallet", [
                                'student_id' => $student->id,
                                'wallet_id' => $wallet->id,
                                'wallet_transaction_id' => $walletTransaction->id,
                                'wallet_transaction_code' => $walletTransaction->transaction_code,
                                'enrollment_id' => $enrollment->id,
                                'transaction_id' => $transaction->id,
                                'new_balance' => $wallet->fresh()->balance,
                            ]);
                        } catch (\Exception $e) {
                            \Log::error('❌ TRANSACTION VERIFY: Failed to deposit to wallet', [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                                'wallet_id' => $wallet->id,
                                'amount' => $transaction->amount,
                            ]);
                            throw $e; // Re-throw to rollback transaction
                        }

                        // Update enrollment payment info
                        $enrollment->update([
                            'paid_amount' => $enrollment->paid_amount + $transaction->amount,
                            'remaining_amount' => $enrollment->final_price - ($enrollment->paid_amount + $transaction->amount),
                            'status' => ($enrollment->paid_amount + $transaction->amount >= $enrollment->final_price) 
                                ? \App\Models\Enrollment::STATUS_PAID 
                                : $enrollment->status,
                        ]);

                        // If fully paid, activate enrollment
                        if ($enrollment->status === \App\Models\Enrollment::STATUS_PAID) {
                            $enrollment->activate();
                            \Log::info("✅ TRANSACTION VERIFY: Enrollment activated", ['enrollment_id' => $enrollment->id]);
                        }
                    }
                } else {
                    \Log::info('ℹ️ TRANSACTION VERIFY: No enrollment_id in payer_info', [
                        'income_report_id' => $incomeReport->id,
                        'payer_info_keys' => $payerInfo ? array_keys($payerInfo) : null,
                    ]);
                }
            }

            // ✨ REFUND LOGIC: If expense with student_id in metadata → deposit to student wallet
            if ($transaction->transaction_type === 'expense' && 
                $transaction->transactionable_type === 'App\Models\ExpenseProposal' &&
                $transaction->transactionable &&
                isset($transaction->metadata['student_id'])) {
                
                \Log::info('🔄 TRANSACTION VERIFY: Processing attendance fee refund', [
                    'expense_proposal_id' => $transaction->transactionable->id,
                    'student_id' => $transaction->metadata['student_id'],
                    'amount' => $transaction->amount,
                ]);
                
                $studentId = $transaction->metadata['student_id'];
                $student = \App\Models\Student::find($studentId);
                
                if (!$student) {
                    \Log::error('❌ TRANSACTION VERIFY: Student not found for refund', [
                        'student_id' => $studentId,
                    ]);
                } else {
                    $wallet = $student->wallet;
                    if (!$wallet) {
                        \Log::error('❌ TRANSACTION VERIFY: Student wallet not found', [
                            'student_id' => $studentId,
                        ]);
                    } else {
                        try {
                            // Deposit refund to wallet
                            $walletTransaction = $wallet->deposit(
                                $transaction->amount,
                                $transaction->transactionable,
                                "Hoàn học phí (Expense #{$transaction->code})"
                            );
                            
                            \Log::info("💰 TRANSACTION VERIFY: Refund deposited to student wallet", [
                                'student_id' => $student->id,
                                'wallet_id' => $wallet->id,
                                'wallet_transaction_id' => $walletTransaction->id,
                                'amount' => $transaction->amount,
                                'new_balance' => $wallet->fresh()->balance,
                            ]);

                            // Update related deductions to 'approved'
                            if (isset($transaction->metadata['deduction_ids'])) {
                                $deductionIds = $transaction->metadata['deduction_ids'];
                                \App\Models\AttendanceFeeDeduction::whereIn('id', $deductionIds)
                                    ->update([
                                        'refund_status' => 'approved',
                                        'refund_approved_by' => auth()->id(),
                                        'refund_approved_at' => now(),
                                    ]);
                                
                                \Log::info('✅ TRANSACTION VERIFY: Updated deduction refund status', [
                                    'deduction_ids' => $deductionIds,
                                ]);
                            }

                        } catch (\Exception $e) {
                            \Log::error('❌ TRANSACTION VERIFY: Failed to deposit refund to wallet', [
                                'error' => $e->getMessage(),
                                'student_id' => $studentId,
                                'amount' => $transaction->amount,
                            ]);
                            throw $e;
                        }
                    }
                }
            }

            \Log::info('✅ TRANSACTION VERIFY: About to commit', [
                'transaction_id' => $transaction->id,
            ]);

            DB::commit();

            \Log::info('✅ TRANSACTION VERIFY: Transaction committed successfully', [
                'transaction_id' => $transaction->id,
            ]);

            return response()->json([
                'message' => 'Transaction verified and completed successfully',
                'data' => $transaction->load(['cashAccount', 'verifiedBy'])
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ TRANSACTION VERIFY: Failed to verify', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to verify transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject transaction
     */
    public function reject(Request $request, $transaction)
    {
        // Manually load transaction if not model-bound
        if (!($transaction instanceof FinancialTransaction)) {
            $transaction = FinancialTransaction::findOrFail($transaction);
        }

        if (!auth()->user()->hasPermission('transactions.approve')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!in_array($transaction->status, ['pending', 'approved'])) {
            return response()->json([
                'message' => 'Can only reject pending or approved transactions'
            ], 422);
        }

        $validated = $request->validate([
            'rejected_reason' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $transaction->update([
                'status' => 'rejected',
                'rejected_reason' => $validated['rejected_reason'],
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Update source record if needed
            if ($transaction->transactionable) {
                $transaction->transactionable->update([
                    'status' => 'rejected',
                    'rejected_reason' => $validated['rejected_reason'],
                ]);
            }
            
            // Release voucher usage if transaction is linked to an enrollment income
            if ($transaction->transaction_type === 'income' && 
                $transaction->transactionable_type === 'App\Models\IncomeReport' &&
                $transaction->transactionable) {
                
                $incomeReport = $transaction->transactionable;
                $payerInfo = $incomeReport->payer_info;
                
                if (isset($payerInfo['enrollment_id'])) {
                    $enrollment = \App\Models\Enrollment::find($payerInfo['enrollment_id']);
                    if ($enrollment && $enrollment->voucher_id) {
                        \Log::info('🔓 TRANSACTION REJECT: Releasing voucher for enrollment', [
                            'transaction_id' => $transaction->id,
                            'income_report_id' => $incomeReport->id,
                            'enrollment_id' => $enrollment->id,
                            'voucher_id' => $enrollment->voucher_id,
                        ]);
                        
                        $enrollment->releaseVoucherUsage();
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Transaction rejected',
                'data' => $transaction
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('❌ TRANSACTION REJECT: Failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id,
            ]);
            
            return response()->json([
                'message' => 'Failed to reject transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
