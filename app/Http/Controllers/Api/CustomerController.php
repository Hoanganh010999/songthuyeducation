<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Lấy danh sách customers (List view) - với phân quyền
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $stage = $request->input('stage');
        $branchId = $request->input('branch_id');
        $assignedTo = $request->input('assigned_to');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        // Validate sort fields
        $allowedSortFields = ['name', 'phone', 'email', 'stage', 'estimated_value', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        $query = Customer::with([
            'branch:id,code,name',
            'assignedUser:id,name,email',
            'latestInteraction' => function($q) {
                $q->with([
                    'user:id,name',
                    'interactionType:id,name,code,icon,color',
                    'interactionResult:id,name,code,icon,color'
                ]);
            },
            'children'
        ])
        ->accessibleBy($request->user(), $branchId); // ← Truyền branch_id từ request

        // Additional filters
        if ($search) {
            $query->search($search);
        }

        if ($stage) {
            $query->byStage($stage);
        }

        if ($branchId) {
            $query->byBranch($branchId);
        }

        if ($assignedTo) {
            $query->assignedTo($assignedTo);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortDir);

        $customers = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    /**
     * Lấy customers theo stages (Kanban view) - với phân quyền
     */
    public function kanban(Request $request)
    {
        $query = Customer::with(['branch:id,code,name', 'assignedUser:id,name,email'])
            ->accessibleBy($request->user()); // ← Áp dụng phân quyền

        // Get all customers grouped by stage
        $customers = $query->orderBy('stage_order')->get();
        
        $stages = Customer::getStages();
        $kanbanData = [];

        foreach ($stages as $stageKey => $stageLabel) {
            $kanbanData[$stageKey] = [
                'label' => $stageLabel,
                'customers' => $customers->where('stage', $stageKey)->values(),
                'count' => $customers->where('stage', $stageKey)->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $kanbanData
        ]);
    }

    /**
     * Tạo customer mới
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'ward' => 'nullable|string|max:100',
            'source' => 'nullable|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'estimated_value' => 'nullable|numeric|min:0',
            'expected_close_date' => 'nullable|date',
        ]);

        // Logic gán branch_id
        if ($user->isSuperAdmin()) {
            // Super-admin: PHẢI chọn branch (đã validate required)
            // Sử dụng branch_id từ request
        } else {
            // User thường: Tự động lấy primary branch
            $primaryBranch = $user->getPrimaryBranch();
            if (!$primaryBranch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa được gán vào chi nhánh nào'
                ], 400);
            }
            // Override branch_id với primary branch của user
            $validated['branch_id'] = $primaryBranch->id;
        }

        // Default stage
        $validated['stage'] = Customer::STAGE_LEAD;
        $validated['stage_order'] = Customer::where('stage', Customer::STAGE_LEAD)->max('stage_order') + 1;

        $customer = Customer::create($validated);
        $customer->load(['branch', 'assignedUser']);

        return response()->json([
            'success' => true,
            'message' => 'Tạo khách hàng thành công',
            'data' => $customer
        ], 201);
    }

    /**
     * Xem chi tiết customer
     */
    public function show(string $id)
    {
        $customer = Customer::with(['branch', 'assignedUser'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }

    /**
     * Cập nhật customer
     */
    public function update(Request $request, string $id)
    {
        \Log::info('🎯 [CustomerController] Update called', [
            'customer_id' => $id,
            'request_data' => $request->all(),
            'user_id' => auth()->id(),
        ]);

        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'ward' => 'nullable|string|max:100',
            'source' => 'nullable|string|max:255',
            'stage' => ['nullable', Rule::in(array_keys(Customer::getStages()))],
            'branch_id' => 'nullable|exists:branches,id',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'estimated_value' => 'nullable|numeric|min:0',
            'expected_close_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $customer->update($validated);
        $customer->load(['branch', 'assignedUser']);

        \Log::info('✅ [CustomerController] Customer updated successfully', [
            'customer_id' => $id,
            'updated_fields' => $validated,
            'customer_stage' => $customer->stage,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật khách hàng thành công',
            'data' => $customer
        ]);
    }

    /**
     * Xóa customer (soft delete)
     */
    public function destroy(string $id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa khách hàng thành công'
        ]);
    }

    /**
     * Move customer to different stage (for Kanban)
     */
    public function moveStage(Request $request, string $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'stage' => ['required', Rule::in(array_keys(Customer::getStages()))],
            'stage_order' => 'required|integer|min:0',
        ]);

        $customer->moveToStage($validated['stage'], $validated['stage_order']);

        // Reorder other customers in the same stage
        $this->reorderStage($validated['stage']);

        return response()->json([
            'success' => true,
            'message' => 'Chuyển giai đoạn thành công',
            'data' => $customer->fresh(['branch', 'assignedUser'])
        ]);
    }

    /**
     * Reorder customers in a stage
     */
    private function reorderStage(string $stage)
    {
        $customers = Customer::where('stage', $stage)
            ->orderBy('stage_order')
            ->get();

        foreach ($customers as $index => $customer) {
            $customer->update(['stage_order' => $index]);
        }
    }

    /**
     * Get statistics - với phân quyền
     */
    public function statistics(Request $request)
    {
        $query = Customer::query()
            ->accessibleBy($request->user()); // ← Áp dụng phân quyền

        $stages = Customer::getStages();
        $stats = [
            'total' => $query->count(),
            'by_stage' => [],
            'total_value' => $query->sum('estimated_value'),
            'closed_won_value' => $query->where('stage', Customer::STAGE_CLOSED_WON)->sum('estimated_value'),
        ];

        foreach ($stages as $stageKey => $stageLabel) {
            $stats['by_stage'][$stageKey] = [
                'label' => $stageLabel,
                'count' => $query->clone()->where('stage', $stageKey)->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
