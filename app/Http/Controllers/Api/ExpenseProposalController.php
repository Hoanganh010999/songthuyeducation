<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseProposal;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseProposalController extends Controller
{
    public function index(Request $request)
    {
        $query = ExpenseProposal::with([
            'financialPlan',
            'accountItem.category',
            'cashAccount',
            'requestedBy',
            'approvedBy',
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
            $query->where('requested_date', '>=', $request->from_date);
        }
        if ($request->has('to_date') && !empty($request->to_date)) {
            $query->where('requested_date', '<=', $request->to_date);
        }

        // Search
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function($q) use ($request) {
                $q->where('code', 'like', '%' . $request->search . '%')
                  ->orWhere('title', 'like', '%' . $request->search . '%');
            });
        }

        $proposals = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $proposals]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->hasPermission('expense_proposals.create')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Debug: Log incoming request
        \Log::info('📥 Incoming expense proposal request', [
            'all_data' => $request->all(),
            'cash_account_id_from_request' => $request->input('cash_account_id'),
        ]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'financial_plan_id' => 'required|exists:financial_plans,id',
            'financial_plan_item_id' => 'required|exists:financial_plan_items,id',
            'account_item_id' => 'required|exists:account_items,id',
            'cash_account_id' => 'nullable|exists:cash_accounts,id',
            'amount' => 'required|numeric|min:0',
            'requested_date' => 'required|date',
            'description' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'payment_ref' => 'nullable|string',
            'branch_id' => 'required|exists:branches,id',
            'attachments' => 'nullable|array',
        ], [
            'financial_plan_id.required' => 'Đề xuất chi bắt buộc phải có kế hoạch thu chi.',
            'financial_plan_id.exists' => 'Kế hoạch thu chi không tồn tại.',
        ]);

        // Debug logging
        \Log::info('🚀 Creating expense proposal', [
            'validated_data' => $validated,
            'cash_account_id' => $validated['cash_account_id'] ?? 'NOT SET',
        ]);

        DB::beginTransaction();
        try {
            $validated['requested_by'] = Auth::id();
            $validated['status'] = 'pending';

            $proposal = ExpenseProposal::create($validated);
            
            \Log::info('✅ Expense proposal created', [
                'id' => $proposal->id,
                'cash_account_id' => $proposal->cash_account_id,
            ]);

            // Create pending transaction (NEW: Central workflow)
            FinancialTransaction::create([
                'transaction_type' => 'expense',
                'status' => 'pending',
                'transactionable_type' => ExpenseProposal::class,
                'transactionable_id' => $proposal->id,
                'financial_plan_id' => $proposal->financial_plan_id,
                'account_item_id' => $proposal->account_item_id,
                'cash_account_id' => $proposal->cash_account_id,
                'amount' => $proposal->amount,
                'transaction_date' => $proposal->requested_date,
                'description' => $proposal->description,
                'payment_method' => $proposal->payment_method,
                'payment_ref' => $proposal->payment_ref,
                'recorded_by' => Auth::id(),
                'branch_id' => $proposal->branch_id,
                'metadata' => [
                    'proposal_code' => $proposal->code,
                    'proposal_title' => $proposal->title,
                ],
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Expense proposal created and submitted to Transactions workflow',
                'data' => $proposal->load(['financialPlan', 'accountItem', 'cashAccount', 'requestedBy'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create expense proposal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(ExpenseProposal $expenseProposal)
    {
        return response()->json([
            'data' => $expenseProposal->load([
                'financialPlan.planItems',
                'financialPlanItem',
                'accountItem.category',
                'cashAccount',
                'requestedBy',
                'approvedBy',
                'branch',
                'financialTransaction'
            ])
        ]);
    }

    public function update(Request $request, ExpenseProposal $expenseProposal)
    {
        if (!Auth::user()->hasPermission('expense_proposals.edit')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Can only edit pending proposals
        if ($expenseProposal->status !== 'pending') {
            return response()->json([
                'message' => 'Can only edit pending proposals'
            ], 422);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'financial_plan_id' => 'required|exists:financial_plans,id',
            'financial_plan_item_id' => 'nullable|exists:financial_plan_items,id',
            'account_item_id' => 'required|exists:account_items,id',
            'cash_account_id' => 'nullable|exists:cash_accounts,id',
            'amount' => 'required|numeric|min:0',
            'requested_date' => 'required|date',
            'description' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'payment_ref' => 'nullable|string',
            'attachments' => 'nullable|array',
        ]);

        $expenseProposal->update($validated);

        // Update corresponding transaction if exists
        $transaction = FinancialTransaction::where('transactionable_type', ExpenseProposal::class)
            ->where('transactionable_id', $expenseProposal->id)
            ->first();
        
        if ($transaction && $transaction->status === 'pending') {
            $transaction->update([
                'financial_plan_id' => $expenseProposal->financial_plan_id,
                'account_item_id' => $expenseProposal->account_item_id,
                'cash_account_id' => $expenseProposal->cash_account_id,
                'amount' => $expenseProposal->amount,
                'transaction_date' => $expenseProposal->requested_date,
                'description' => $expenseProposal->description,
                'payment_method' => $expenseProposal->payment_method,
                'payment_ref' => $expenseProposal->payment_ref,
            ]);
        }

        return response()->json([
            'message' => 'Expense proposal updated successfully',
            'data' => $expenseProposal->load(['financialPlan', 'accountItem', 'cashAccount', 'requestedBy'])
        ]);
    }

    public function approve(Request $request, ExpenseProposal $expenseProposal)
    {
        if (!Auth::user()->hasPermission('expense_proposals.approve')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($expenseProposal->status !== 'pending') {
            return response()->json([
                'message' => 'Can only approve pending proposals'
            ], 422);
        }

        $expenseProposal->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Expense proposal approved successfully',
            'data' => $expenseProposal->load(['approvedBy'])
        ]);
    }

    public function reject(Request $request, ExpenseProposal $expenseProposal)
    {
        if (!Auth::user()->hasPermission('expense_proposals.approve')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($expenseProposal->status !== 'pending') {
            return response()->json([
                'message' => 'Can only reject pending proposals'
            ], 422);
        }

        $validated = $request->validate([
            'rejected_reason' => 'required|string',
        ]);

        $expenseProposal->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejected_reason' => $validated['rejected_reason'],
        ]);

        return response()->json([
            'message' => 'Expense proposal rejected',
            'data' => $expenseProposal
        ]);
    }

    public function markPaid(Request $request, ExpenseProposal $expenseProposal)
    {
        if (!Auth::user()->hasPermission('expense_proposals.approve')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($expenseProposal->status !== 'approved') {
            return response()->json([
                'message' => 'Can only mark approved proposals as paid'
            ], 422);
        }

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'payment_ref' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $expenseProposal->update([
                'status' => 'paid',
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'payment_ref' => $validated['payment_ref'] ?? null,
            ]);

            // Create financial transaction
            FinancialTransaction::create([
                'transaction_type' => 'expense',
                'transactionable_type' => ExpenseProposal::class,
                'transactionable_id' => $expenseProposal->id,
                'financial_plan_id' => $expenseProposal->financial_plan_id,
                'account_item_id' => $expenseProposal->account_item_id,
                'amount' => $expenseProposal->amount,
                'transaction_date' => $validated['payment_date'],
                'description' => $expenseProposal->description,
                'payment_method' => $validated['payment_method'],
                'payment_ref' => $validated['payment_ref'] ?? null,
                'recorded_by' => Auth::id(),
                'branch_id' => $expenseProposal->branch_id,
                'metadata' => [
                    'proposal_code' => $expenseProposal->code,
                    'proposal_title' => $expenseProposal->title,
                ],
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Expense marked as paid successfully',
                'data' => $expenseProposal->load('financialTransaction')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to mark as paid',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(ExpenseProposal $expenseProposal)
    {
        if (!Auth::user()->hasPermission('expense_proposals.delete')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Can only delete pending or rejected proposals
        if (!in_array($expenseProposal->status, ['pending', 'rejected'])) {
            return response()->json([
                'message' => 'Can only delete pending or rejected proposals'
            ], 422);
        }

        $expenseProposal->delete();

        return response()->json([
            'message' => 'Expense proposal deleted successfully'
        ]);
    }
}
