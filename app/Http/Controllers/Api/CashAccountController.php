<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashAccountController extends Controller
{
    public function index(Request $request)
    {
        \Log::info('🔍 CashAccount index called', [
            'filters' => $request->all(),
            'user_id' => auth()->id(),
            'user' => auth()->user()?->name,
        ]);

        $query = CashAccount::with('branch')->orderByRaw('COALESCE(sort_order, 999)');

        // Debug: Get all before filtering
        $allAccounts = CashAccount::all();
        \Log::info('📊 Total cash accounts in DB (before filter)', [
            'total' => $allAccounts->count(),
            'accounts' => $allAccounts->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'type' => $a->type,
                'branch_id' => $a->branch_id,
                'is_active' => $a->is_active,
            ])->toArray(),
        ]);

        // Filter by type
        if ($request->filled('type')) {
            \Log::info('Filtering by type', ['type' => $request->type]);
            $query->where('type', $request->type);
        }

        // Filter by branch
        if ($request->filled('branch_id')) {
            \Log::info('Filtering by branch_id', ['branch_id' => $request->branch_id]);
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by active status
        if ($request->has('is_active') && $request->is_active !== '') {
            \Log::info('Filtering by is_active', ['is_active' => $request->is_active]);
            $query->where('is_active', $request->is_active);
        }

        // Search
        if ($request->filled('search')) {
            \Log::info('Filtering by search', ['search' => $request->search]);
            $query->where(function($q) use ($request) {
                $q->where('code', 'like', '%' . $request->search . '%')
                  ->orWhere('name', 'like', '%' . $request->search . '%')
                  ->orWhere('account_number', 'like', '%' . $request->search . '%');
            });
        }

        $accounts = $query->get();

        \Log::info('✅ CashAccounts fetched (after filters)', [
            'count' => $accounts->count(),
            'ids' => $accounts->pluck('id')->toArray(),
            'accounts' => $accounts->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'branch_id' => $a->branch_id,
            ])->toArray(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $accounts
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,bank',
            'account_number' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'bank_branch' => 'nullable|string',
            'balance' => 'nullable|numeric|min:0',
            'branch_id' => 'nullable|exists:branches,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $account = CashAccount::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo tài khoản tiền thành công',
            'data' => $account->load('branch'),
        ], 201);
    }

    public function show($id)
    {
        $account = CashAccount::with('branch')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $account,
        ]);
    }

    public function update(Request $request, $id)
    {
        $account = CashAccount::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,bank',
            'account_number' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'bank_branch' => 'nullable|string',
            'branch_id' => 'nullable|exists:branches,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $account->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật tài khoản tiền thành công',
            'data' => $account->load('branch'),
        ]);
    }

    public function destroy($id)
    {
        $account = CashAccount::findOrFail($id);

        // Check if account has balance
        if ($account->balance != 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa tài khoản còn số dư',
            ], 400);
        }

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa tài khoản tiền thành công',
        ]);
    }

    /**
     * Get summary of all cash accounts
     */
    public function summary(Request $request)
    {
        // Filter by branch_id if provided
        $query = CashAccount::active();
        
        if ($request->filled('branch_id')) {
            $query->where(function($q) use ($request) {
                $q->where('branch_id', $request->branch_id)
                  ->orWhereNull('branch_id'); // Include accounts without branch
            });
        }

        $totalCash = (clone $query)->cash()->sum('balance');
        $totalBank = (clone $query)->bank()->sum('balance');
        $totalBalance = $totalCash + $totalBank;

        \Log::info('📊 CashAccount summary', [
            'branch_id' => $request->input('branch_id'),
            'total_cash' => $totalCash,
            'total_bank' => $totalBank,
            'total_balance' => $totalBalance,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'total_cash' => $totalCash,
                'total_bank' => $totalBank,
                'total_balance' => $totalBalance,
            ],
        ]);
    }
}
