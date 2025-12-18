<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialPlan;
use App\Models\FinancialPlanItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinancialPlanController extends Controller
{
    public function index(Request $request)
    {
        $query = FinancialPlan::with(['branch', 'approvedBy', 'planItems.accountItem']);

        // Filter by year
        if ($request->has('year') && !empty($request->year)) {
            $query->forYear($request->year);
        }

        // Filter by plan type
        if ($request->has('plan_type') && !empty($request->plan_type)) {
            $query->where('plan_type', $request->plan_type);
        }

        // Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Filter by branch
        if ($request->has('branch_id') && !empty($request->branch_id)) {
            $query->where('branch_id', $request->branch_id);
        }

        // Search
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function($q) use ($request) {
                $q->where('code', 'like', '%' . $request->search . '%')
                  ->orWhere('name', 'like', '%' . $request->search . '%');
            });
        }

        $plans = $query->orderBy('year', 'desc')
            ->orderBy('quarter', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return response()->json(['data' => $plans]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->hasPermission('financial_plans.create')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'plan_type' => 'required|in:quarterly,monthly',
            'year' => 'required|integer|min:2020|max:2100',
            'quarter' => 'required_if:plan_type,quarterly|nullable|integer|min:1|max:4',
            'month' => 'required_if:plan_type,monthly|nullable|integer|min:1|max:12',
            'branch_id' => 'required|exists:branches,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.account_item_id' => 'required|exists:account_items,id',
            'items.*.type' => 'required|in:income,expense',
            'items.*.planned_amount' => 'required|numeric|min:0',
            'items.*.description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Create plan
            $planData = $validated;
            unset($planData['items']);
            $planData['status'] = 'draft';
            
            $plan = FinancialPlan::create($planData);

            // Create plan items
            foreach ($validated['items'] as $item) {
                $plan->planItems()->create($item);
            }

            // Calculate totals
            $plan->total_income_planned = $plan->planItems()->income()->sum('planned_amount');
            $plan->total_expense_planned = $plan->planItems()->expense()->sum('planned_amount');
            $plan->save();

            DB::commit();

            return response()->json([
                'message' => 'Financial plan created successfully',
                'data' => $plan->load(['planItems.accountItem', 'branch'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create financial plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(FinancialPlan $financialPlan)
    {
        return response()->json([
            'data' => $financialPlan->load([
                'branch',
                'approvedBy',
                'planItems.accountItem.category',
                'expenseProposals',
                'incomeReports'
            ])
        ]);
    }

    public function update(Request $request, FinancialPlan $financialPlan)
    {
        if (!Auth::user()->hasPermission('financial_plans.edit')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Can only edit draft plans
        if ($financialPlan->status !== 'draft') {
            return response()->json([
                'message' => 'Can only edit draft plans'
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:financial_plan_items,id',
            'items.*.account_item_id' => 'required|exists:account_items,id',
            'items.*.type' => 'required|in:income,expense',
            'items.*.planned_amount' => 'required|numeric|min:0',
            'items.*.description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Update plan
            $financialPlan->update([
                'name' => $validated['name'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update items
            $submittedIds = collect($validated['items'])->pluck('id')->filter();
            
            // Delete removed items
            $financialPlan->planItems()->whereNotIn('id', $submittedIds)->delete();
            
            // Update or create items
            foreach ($validated['items'] as $itemData) {
                if (isset($itemData['id'])) {
                    $financialPlan->planItems()->where('id', $itemData['id'])->update($itemData);
                } else {
                    $financialPlan->planItems()->create($itemData);
                }
            }

            // Recalculate totals
            $financialPlan->total_income_planned = $financialPlan->planItems()->income()->sum('planned_amount');
            $financialPlan->total_expense_planned = $financialPlan->planItems()->expense()->sum('planned_amount');
            $financialPlan->save();

            DB::commit();

            return response()->json([
                'message' => 'Financial plan updated successfully',
                'data' => $financialPlan->load(['planItems.accountItem', 'branch'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update financial plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit plan for approval (draft -> pending)
     */
    public function submit(FinancialPlan $financialPlan)
    {
        if (!Auth::user()->hasPermission('financial_plans.create')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($financialPlan->status !== 'draft') {
            return response()->json([
                'message' => 'Can only submit draft plans'
            ], 422);
        }

        $financialPlan->update([
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Financial plan submitted for approval successfully',
            'data' => $financialPlan
        ]);
    }

    /**
     * Approve plan (pending -> approved)
     */
    public function approve(FinancialPlan $financialPlan)
    {
        if (!Auth::user()->hasPermission('financial_plans.approve')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($financialPlan->status !== 'pending') {
            return response()->json([
                'message' => 'Can only approve pending plans'
            ], 422);
        }

        $financialPlan->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Financial plan approved successfully',
            'data' => $financialPlan->load(['approvedBy'])
        ]);
    }

    public function activate(FinancialPlan $financialPlan)
    {
        if (!Auth::user()->hasPermission('financial_plans.approve')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($financialPlan->status !== 'approved') {
            return response()->json([
                'message' => 'Can only activate approved plans'
            ], 422);
        }

        $financialPlan->update(['status' => 'active']);

        return response()->json([
            'message' => 'Financial plan activated successfully',
            'data' => $financialPlan
        ]);
    }

    public function close(FinancialPlan $financialPlan)
    {
        if (!Auth::user()->hasPermission('financial_plans.approve')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!in_array($financialPlan->status, ['approved', 'active'])) {
            return response()->json([
                'message' => 'Can only close approved or active plans'
            ], 422);
        }

        $financialPlan->update(['status' => 'closed']);

        return response()->json([
            'message' => 'Financial plan closed successfully',
            'data' => $financialPlan
        ]);
    }

    public function destroy(FinancialPlan $financialPlan)
    {
        if (!Auth::user()->hasPermission('financial_plans.delete')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Can only delete draft plans
        if ($financialPlan->status !== 'draft') {
            return response()->json([
                'message' => 'Can only delete draft plans'
            ], 422);
        }

        // Check if has proposals or reports
        if ($financialPlan->expenseProposals()->count() > 0 || $financialPlan->incomeReports()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete plan with existing proposals or reports'
            ], 422);
        }

        $financialPlan->planItems()->delete();
        $financialPlan->delete();

        return response()->json([
            'message' => 'Financial plan deleted successfully'
        ]);
    }

    /**
     * Get available plans for expense proposals or income reports
     */
    public function available(Request $request)
    {
        $type = $request->get('type'); // 'expense' or 'income'
        
        $query = FinancialPlan::with('planItems.accountItem')
            ->whereIn('status', ['approved', 'active']); // Only approved/active plans

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where(function($q) use ($request) {
                $q->where('branch_id', $request->branch_id)
                  ->orWhereNull('branch_id'); // Include global plans
            });
        }

        $plans = $query->orderBy('year', 'desc')
            ->orderBy('quarter', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return response()->json($plans);
    }
}
