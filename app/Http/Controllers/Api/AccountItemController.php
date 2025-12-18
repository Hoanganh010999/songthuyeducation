<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountItem;
use App\Models\AccountCategory;
use Illuminate\Http\Request;

class AccountItemController extends Controller
{
    public function index(Request $request)
    {
        // TEMPORARILY DISABLED: Filter by current user's branch
        $query = AccountItem::with(['category'])->orderBy('sort_order');
        
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }
        
        if ($request->input('active_only')) {
            $query->active();
        }
        
        $perPage = $request->input('per_page', 20);
        $items = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $items->items(),
            'pagination' => [
                'total' => $items->total(),
                'per_page' => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:account_items,code',
            'name' => 'required',
            'category_id' => 'required|exists:account_categories,id',
            'branch_id' => 'nullable|exists:branches,id',
            'type' => 'required|in:income,expense',
            'description' => 'nullable',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);
        
        // Set branch_id to current user's branch if not provided
        if (!isset($validated['branch_id']) && auth()->user()->branch_id) {
            $validated['branch_id'] = auth()->user()->branch_id;
        }
        
        $item = AccountItem::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Đã tạo khoản thu chi thành công',
            'data' => $item->load('category'),
        ], 201);
    }

    public function show($id)
    {
        $item = AccountItem::with(['category'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }

    public function update(Request $request, $id)
    {
        $item = AccountItem::findOrFail($id);
        
        $validated = $request->validate([
            'code' => 'required|unique:account_items,code,' . $id,
            'name' => 'required',
            'category_id' => 'required|exists:account_categories,id',
            'branch_id' => 'nullable|exists:branches,id',
            'type' => 'required|in:income,expense',
            'description' => 'nullable',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);
        
        $item->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật khoản thu chi thành công',
            'data' => $item->load('category'),
        ]);
    }

    public function destroy($id)
    {
        $item = AccountItem::findOrFail($id);
        
        // Check if in use
        if ($item->financialPlanItems()->exists() || 
            $item->expenseProposals()->exists() || 
            $item->incomeReports()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa khoản thu chi đang được sử dụng',
            ], 400);
        }
        
        $item->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Đã xóa khoản thu chi thành công',
        ]);
    }
}
