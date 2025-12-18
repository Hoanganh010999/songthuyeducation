<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountCategory;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    /**
     * Dashboard statistics
     */
    public function dashboard(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $branchId = $request->input('branch_id');
        
        $query = FinancialTransaction::query();
        
        // ⚠️ QUAN TRỌNG: CHỈ TÍNH CÁC GIAO DỊCH ĐÃ VERIFIED
        $query->where('status', 'verified');
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $query->whereYear('transaction_date', $year);
        
        // Total income and expense (ONLY VERIFIED)
        $totalIncome = (clone $query)->where('transaction_type', 'income')->sum('amount');
        $totalExpense = (clone $query)->where('transaction_type', 'expense')->sum('amount');
        
        // Monthly breakdown (ONLY VERIFIED)
        $monthlyData = (clone $query)
            ->select(
                DB::raw('MONTH(transaction_date) as month'),
                DB::raw('SUM(CASE WHEN transaction_type = "income" THEN amount ELSE 0 END) as income'),
                DB::raw('SUM(CASE WHEN transaction_type = "expense" THEN amount ELSE 0 END) as expense')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        \Log::info('📊 Accounting Dashboard', [
            'branch_id' => $branchId,
            'year' => $year,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
            'note' => 'Only VERIFIED transactions counted',
        ]);
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'balance' => $totalIncome - $totalExpense,
                'monthly_data' => $monthlyData,
            ],
        ]);
    }

    /**
     * Get account categories tree
     */
    public function getCategoryTree(Request $request)
    {
        $type = $request->input('type'); // income/expense
        
        $query = AccountCategory::with(['children.accountItems', 'accountItems'])
            ->roots()
            ->active()
            ->orderBy('sort_order');
        
        if ($type) {
            $query->where('type', $type);
        }
        
        $categories = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }
}
