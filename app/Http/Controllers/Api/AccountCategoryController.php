<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountCategoryController extends Controller
{
    /**
     * Display a listing with tree structure
     */
    public function index(Request $request)
    {
        // TEMPORARILY DISABLED: Filter by current user's branch
        // TODO: Fix branch filtering logic
        $query = AccountCategory::with('parent');

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by cost_type
        if ($request->filled('cost_type')) {
            $query->where('cost_type', $request->cost_type);
        }

        // Filter by active status
        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        // Search
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('code', 'like', '%' . $request->search . '%')
                  ->orWhere('name', 'like', '%' . $request->search . '%');
            });
        }

        $categories = $query->orderBy('type')->orderBy('sort_order')->get();

        return response()->json($categories);
    }

    /**
     * Get tree structure for select dropdown
     */
    public function tree(Request $request)
    {
        $type = $request->get('type');
        
        $query = AccountCategory::roots()->active()->with('children.children');
        
        if ($type) {
            $query->where('type', $type);
        }
        
        $tree = $query->orderBy('sort_order')->get();
        
        return response()->json($this->buildTree($tree));
    }

    private function buildTree($categories, $level = 0)
    {
        $result = [];
        
        foreach ($categories as $category) {
            $result[] = [
                'id' => $category->id,
                'code' => $category->code,
                'name' => str_repeat('— ', $level) . $category->name,
                'type' => $category->type,
                'level' => $level,
            ];
            
            if ($category->children->isNotEmpty()) {
                $result = array_merge($result, $this->buildTree($category->children, $level + 1));
            }
        }
        
        return $result;
    }

    /**
     * Store a newly created resource
     */
    public function store(Request $request)
    {
        if (!Auth::user()->hasPermission('account_categories.create')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:account_categories',
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'parent_id' => 'nullable|exists:account_categories,id',
            'branch_id' => 'nullable|exists:branches,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        // Set branch_id to current user's branch if not provided
        if (!isset($validated['branch_id']) && Auth::user()->branch_id) {
            $validated['branch_id'] = Auth::user()->branch_id;
        }

        $category = AccountCategory::create($validated);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => $category->load(['parent', 'children'])
        ], 201);
    }

    /**
     * Display the specified resource
     */
    public function show(AccountCategory $accountCategory)
    {
        return response()->json($accountCategory->load(['parent', 'children', 'accountItems']));
    }

    /**
     * Update the specified resource
     */
    public function update(Request $request, AccountCategory $accountCategory)
    {
        if (!Auth::user()->hasPermission('account_categories.edit')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:account_categories,code,' . $accountCategory->id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'parent_id' => 'nullable|exists:account_categories,id',
            'branch_id' => 'nullable|exists:branches,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        // Prevent self-parenting or circular references
        if (isset($validated['parent_id']) && $validated['parent_id'] == $accountCategory->id) {
            return response()->json(['message' => 'Category cannot be its own parent'], 422);
        }

        $accountCategory->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => $accountCategory->load(['parent', 'children'])
        ]);
    }

    /**
     * Remove the specified resource
     */
    public function destroy(AccountCategory $accountCategory)
    {
        if (!Auth::user()->hasPermission('account_categories.delete')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if category has children
        if ($accountCategory->children()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with children'
            ], 422);
        }

        // Check if category has account items
        if ($accountCategory->accountItems()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with account items'
            ], 422);
        }

        $accountCategory->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }
}

