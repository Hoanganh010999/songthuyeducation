<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerInteractionType;
use App\Models\CustomerInteractionResult;
use App\Models\CustomerSource;
use App\Models\ModuleDepartmentSetting;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerSettingsController extends Controller
{
    // ==================== INTERACTION TYPES ====================
    
    public function getInteractionTypes()
    {
        $types = CustomerInteractionType::ordered()->get();
        
        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }
    
    public function storeInteractionType(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:customer_interaction_types,code',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);
        
        // Auto-generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = Str::slug($validated['name'], '_');
        }
        
        $type = CustomerInteractionType::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Tạo loại tương tác thành công',
            'data' => $type
        ], 201);
    }
    
    public function updateInteractionType(Request $request, $id)
    {
        $type = CustomerInteractionType::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:customer_interaction_types,code,' . $id,
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);
        
        $type->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Cập nhật loại tương tác thành công',
            'data' => $type
        ]);
    }
    
    public function deleteInteractionType($id)
    {
        $type = CustomerInteractionType::findOrFail($id);
        $type->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Xóa loại tương tác thành công'
        ]);
    }
    
    // ==================== INTERACTION RESULTS ====================
    
    public function getInteractionResults()
    {
        $results = CustomerInteractionResult::ordered()->get();
        
        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }
    
    public function storeInteractionResult(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:customer_interaction_results,code',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);
        
        // Auto-generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = Str::slug($validated['name'], '_');
        }
        
        $result = CustomerInteractionResult::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Tạo kết quả tương tác thành công',
            'data' => $result
        ], 201);
    }
    
    public function updateInteractionResult(Request $request, $id)
    {
        $result = CustomerInteractionResult::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:customer_interaction_results,code,' . $id,
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);
        
        $result->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Cập nhật kết quả tương tác thành công',
            'data' => $result
        ]);
    }
    
    public function deleteInteractionResult($id)
    {
        $result = CustomerInteractionResult::findOrFail($id);
        $result->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Xóa kết quả tương tác thành công'
        ]);
    }
    
    // ==================== CUSTOMER SOURCES ====================
    
    public function getCustomerSources()
    {
        $sources = CustomerSource::ordered()->get();
        
        return response()->json([
            'success' => true,
            'data' => $sources
        ]);
    }
    
    public function storeCustomerSource(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:customer_sources,code',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);
        
        // Auto-generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = Str::slug($validated['name'], '_');
        }
        
        $source = CustomerSource::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Tạo nguồn khách hàng thành công',
            'data' => $source
        ], 201);
    }
    
    public function updateCustomerSource(Request $request, $id)
    {
        $source = CustomerSource::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:customer_sources,code,' . $id,
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);
        
        $source->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Cập nhật nguồn khách hàng thành công',
            'data' => $source
        ]);
    }
    
    public function deleteCustomerSource($id)
    {
        $source = CustomerSource::findOrFail($id);
        $source->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa nguồn khách hàng thành công'
        ]);
    }

    // ==================== DEPARTMENT SETTINGS ====================

    /**
     * Get the department settings for customer module
     */
    public function getDepartmentSettings(Request $request)
    {
        $user = $request->user();
        $branchId = $request->input('branch_id');

        // Check permission
        if (!$user->is_super_admin && !$user->hasRole('super-admin') && !$user->hasPermission('customers.department_settings')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xem thiết lập phòng ban'
            ], 403);
        }

        // Get current setting
        $setting = null;
        if ($branchId) {
            $setting = ModuleDepartmentSetting::with('department:id,name,code,color')
                ->where('module', 'customers')
                ->where('branch_id', $branchId)
                ->first();
        }

        // Get available departments for selection
        $departments = Department::active()
            ->when($branchId, function($q) use ($branchId) {
                $q->forBranch($branchId);
            })
            ->select('id', 'name', 'code', 'color', 'branch_id')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'current_setting' => $setting,
                'departments' => $departments,
            ]
        ]);
    }

    /**
     * Set or update the department for customer module
     */
    public function setDepartmentSettings(Request $request)
    {
        $user = $request->user();

        // Check permission
        if (!$user->is_super_admin && !$user->hasRole('super-admin') && !$user->hasPermission('customers.department_settings')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền thiết lập phòng ban'
            ], 403);
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'department_id' => 'required|exists:departments,id',
        ]);

        // Verify department belongs to the branch
        $department = Department::where('id', $validated['department_id'])
            ->where('branch_id', $validated['branch_id'])
            ->first();

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Phòng ban không thuộc chi nhánh đã chọn'
            ], 422);
        }

        // Set or update the department
        $setting = ModuleDepartmentSetting::setDepartmentForModule(
            'customers',
            $validated['branch_id'],
            $validated['department_id']
        );

        $setting->load('department:id,name,code,color');

        return response()->json([
            'success' => true,
            'message' => 'Thiết lập phòng ban phụ trách thành công',
            'data' => $setting
        ]);
    }

    /**
     * Remove department assignment for customer module
     */
    public function removeDepartmentSettings(Request $request)
    {
        $user = $request->user();

        // Check permission
        if (!$user->is_super_admin && !$user->hasRole('super-admin') && !$user->hasPermission('customers.department_settings')) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa thiết lập phòng ban'
            ], 403);
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        ModuleDepartmentSetting::where('module', 'customers')
            ->where('branch_id', $validated['branch_id'])
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa thiết lập phòng ban phụ trách'
        ]);
    }

    /**
     * Check current user's access level for customer module
     */
    public function checkAccessLevel(Request $request)
    {
        $user = $request->user();
        $branchId = $request->input('branch_id');

        if (!$branchId) {
            $primaryBranch = $user->getPrimaryBranch();
            $branchId = $primaryBranch ? $primaryBranch->id : null;
        }

        if (!$branchId) {
            return response()->json([
                'success' => true,
                'data' => [
                    'access_level' => 'none',
                    'message' => 'Không xác định được chi nhánh'
                ]
            ]);
        }

        $accessLevel = ModuleDepartmentSetting::getUserAccessLevel('customers', $branchId, $user);

        $messages = [
            'full' => 'Bạn có quyền xem tất cả khách hàng',
            'limited' => 'Bạn chỉ xem được khách hàng được gán cho mình',
            'none' => 'Bạn không có quyền xem khách hàng trong module này'
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'access_level' => $accessLevel,
                'message' => $messages[$accessLevel] ?? '',
                'branch_id' => $branchId
            ]
        ]);
    }
}
