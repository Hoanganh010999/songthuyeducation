<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncomeReport;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IncomeReportController extends Controller
{
    public function index(Request $request)
    {
        $query = IncomeReport::with([
            'financialPlan',
            'financialPlanItem.accountItem',
            'accountItem.category',
            'cashAccount',
            'reportedBy',
            'approvedBy',
            'verifiedBy',
            'branch'
        ]);

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
            $query->where('received_date', '>=', $request->from_date);
        }
        if ($request->has('to_date') && !empty($request->to_date)) {
            $query->where('received_date', '<=', $request->to_date);
        }

        // Filter unplanned
        if ($request->has('unplanned') && $request->unplanned == '1') {
            $query->unplanned();
        }

        // Search
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function($q) use ($request) {
                $q->where('code', 'like', '%' . $request->search . '%')
                  ->orWhere('title', 'like', '%' . $request->search . '%')
                  ->orWhere('payer_name', 'like', '%' . $request->search . '%');
            });
        }

        $reports = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $reports]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->hasPermission('income_reports.create')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'financial_plan_id' => 'nullable|exists:financial_plans,id',
            'financial_plan_item_id' => 'nullable|exists:financial_plan_items,id',
            'account_item_id' => 'required|exists:account_items,id',
            'amount' => 'required|numeric|min:0',
            'received_date' => 'required|date',
            'payer_name' => 'nullable|string|max:255',
            'payer_phone' => 'nullable|string|max:20',
            'payer_info' => 'nullable|array',
            'description' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'payment_ref' => 'nullable|string',
            'branch_id' => 'required|exists:branches,id',
            'attachments' => 'nullable|array',
        ]);

        $validated['reported_by'] = Auth::id();
        $validated['status'] = 'pending';
        
        $report = IncomeReport::create($validated);

        return response()->json([
            'message' => 'Income report created successfully. Waiting for approval.',
            'data' => $report->load(['financialPlan', 'accountItem', 'reportedBy'])
        ], 201);
    }

    public function show(IncomeReport $incomeReport)
    {
        return response()->json([
            'data' => $incomeReport->load([
                'financialPlan.planItems',
                'financialPlanItem',
                'accountItem.category',
                'reportedBy',
                'approvedBy',
                'branch',
                'financialTransaction'
            ])
        ]);
    }

    public function update(Request $request, IncomeReport $incomeReport)
    {
        if (!Auth::user()->hasPermission('income_reports.edit')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // ⚠️ BẢO VỆ DỮ LIỆU: Chỉ cho sửa phiếu báo thu đang chờ duyệt
        if ($incomeReport->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể sửa phiếu báo thu đang chờ duyệt (pending)'
            ], 422);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'financial_plan_id' => 'nullable|exists:financial_plans,id',
            'financial_plan_item_id' => 'nullable|exists:financial_plan_items,id',
            'account_item_id' => 'required|exists:account_items,id',
            'amount' => 'required|numeric|min:0',
            'received_date' => 'required|date',
            'payer_name' => 'nullable|string|max:255',
            'payer_phone' => 'nullable|string|max:20',
            'payer_info' => 'nullable|array',
            'description' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'payment_ref' => 'nullable|string',
            'attachments' => 'nullable|array',
        ]);

        $incomeReport->update($validated);

        return response()->json([
            'message' => 'Income report updated successfully',
            'data' => $incomeReport->load(['financialPlan', 'accountItem', 'reportedBy'])
        ]);
    }

    public function approve(Request $request, IncomeReport $incomeReport)
    {
        if (!Auth::user()->hasPermission('income_reports.approve')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($incomeReport->status !== 'pending') {
            return response()->json([
                'message' => 'Can only approve pending reports'
            ], 422);
        }

        $validated = $request->validate([
            'cash_account_id' => 'required|exists:cash_accounts,id',
            'payment_method' => 'nullable|string',
            'payment_ref' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Accountant approves and selects cash account
            $incomeReport->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'cash_account_id' => $validated['cash_account_id'],
                'payment_method' => $validated['payment_method'] ?? $incomeReport->payment_method,
                'payment_ref' => $validated['payment_ref'] ?? $incomeReport->payment_ref,
            ]);

            // Create transaction with 'approved' status (ready for verification in Transactions module)
            // ⚠️ LƯU Ý: Cash account KHÔNG được update ở đây!
            FinancialTransaction::create([
                'transaction_type' => 'income',
                'status' => 'approved',
                'transactionable_type' => IncomeReport::class,
                'transactionable_id' => $incomeReport->id,
                'financial_plan_id' => $incomeReport->financial_plan_id,
                'account_item_id' => $incomeReport->account_item_id,
                'cash_account_id' => $validated['cash_account_id'],
                'amount' => $incomeReport->amount,
                'transaction_date' => $incomeReport->received_date,
                'description' => $incomeReport->description,
                'payment_method' => $validated['payment_method'] ?? $incomeReport->payment_method,
                'payment_ref' => $validated['payment_ref'] ?? $incomeReport->payment_ref,
                'recorded_by' => Auth::id(),
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'branch_id' => $incomeReport->branch_id,
                'metadata' => [
                    'report_code' => $incomeReport->code,
                    'report_title' => $incomeReport->title,
                    'payer_name' => $incomeReport->payer_name,
                    'payer_phone' => $incomeReport->payer_phone,
                ],
            ]);

            \Log::warning('⚠️ APPROVE: FinancialTransaction created but Cash Account NOT updated yet', [
                'income_report_id' => $incomeReport->id,
                'cash_account_id' => $validated['cash_account_id'],
                'amount' => $incomeReport->amount,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Income report approved successfully. Transaction sent to Transactions module for verification.',
                'data' => $incomeReport->load(['approvedBy', 'cashAccount'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to approve report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ❌ REMOVED: verify() method moved to FinancialTransactionController
    // Reason: Verify is now handled through Transactions module workflow
    // Old flow: IncomeReport -> approve -> verify (direct)
    // New flow: IncomeReport -> approve -> FinancialTransaction -> verify
    // This ensures all financial transactions go through unified verify process

    public function reject(Request $request, IncomeReport $incomeReport)
    {
        if (!Auth::user()->hasPermission('income_reports.approve')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($incomeReport->status !== 'pending') {
            return response()->json([
                'message' => 'Can only reject pending reports'
            ], 422);
        }

        $validated = $request->validate([
            'rejected_reason' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $incomeReport->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejected_reason' => $validated['rejected_reason'],
            ]);
            
            // Release voucher usage if this income report is linked to an enrollment
            $payerInfo = $incomeReport->payer_info;
            if (isset($payerInfo['enrollment_id'])) {
                $enrollment = \App\Models\Enrollment::find($payerInfo['enrollment_id']);
                if ($enrollment && $enrollment->voucher_id) {
                    \Log::info('🔓 INCOME REPORT REJECT: Releasing voucher for enrollment', [
                        'income_report_id' => $incomeReport->id,
                        'enrollment_id' => $enrollment->id,
                        'voucher_id' => $enrollment->voucher_id,
                    ]);
                    
                    $enrollment->releaseVoucherUsage();
                }
            }
            
            DB::commit();

            return response()->json([
                'message' => 'Income report rejected',
                'data' => $incomeReport
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('❌ INCOME REPORT REJECT: Failed', [
                'error' => $e->getMessage(),
                'income_report_id' => $incomeReport->id,
            ]);
            
            return response()->json([
                'message' => 'Failed to reject income report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(IncomeReport $incomeReport)
    {
        if (!Auth::user()->hasPermission('income_reports.delete')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // ⚠️ BẢO VỆ DỮ LIỆU: Chỉ cho xóa phiếu báo thu pending hoặc rejected (chưa approved/verified)
        if (!in_array($incomeReport->status, ['pending', 'rejected'])) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể xóa phiếu báo thu đang chờ duyệt (pending) hoặc đã từ chối (rejected)'
            ], 422);
        }

        $incomeReport->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa phiếu báo thu thành công'
        ]);
    }
}
