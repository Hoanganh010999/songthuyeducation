<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get branch from request (localStorage) or user's first branch
        $branchId = $request->input('branch_id') ?? $user->primary_branch_id ?? optional($user->branches()->first())->id;

        // Ensure "Ban quản trị" exists and fix orphaned departments
        if ($branchId) {
            $this->ensureRootDepartment($branchId);
        }

        $query = Department::with(['users' => function($q) {
                $q->select('users.id', 'users.name', 'users.email', 'users.avatar')
                    ->withPivot('position_id', 'is_head', 'is_deputy', 'status');
            }]);

        // Chỉ filter theo branch nếu có branchId
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        if ($request->boolean('include_inactive')) {
            // Lấy cả inactive
        } else {
            $query->where('is_active', true);
        }

        $departments = $query->orderBy('sort_order')->get();

        return response()->json($departments);
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:departments,id',
            'sort_order' => 'nullable|integer',
            'branch_id' => 'nullable|exists:branches,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = $request->user();
        
        // Get branch_id from request, or from user's first branch
        $branchId = $request->branch_id;
        if (!$branchId) {
            $firstBranch = $user->branches()->first();
            $branchId = $firstBranch ? $firstBranch->id : null;
        }
        
        if (!$branchId) {
            return response()->json([
                'success' => false,
                'message' => 'Không xác định được chi nhánh. Vui lòng chọn chi nhánh trước.'
            ], 400);
        }
        
        // Auto-generate code if not provided
        $code = $request->code ?? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $request->name), 0, 10)) . '_' . time();
        
        $department = Department::create([
            'name' => $request->name,
            'code' => $code,
            'description' => $request->description,
            'branch_id' => $branchId,
            'parent_id' => $request->parent_id,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => true,
            'default_position_id' => $request->default_position_id,
        ]);
        
        return response()->json($department, 201);
    }
    
    public function show(Department $department)
    {
        $department->load(['parent', 'children']);
        
        // Load users with pivot data
        $users = $department->users()
            ->wherePivot('status', 'active')
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_head' => $user->pivot->is_head ?? false,
                    'is_deputy' => $user->pivot->is_deputy ?? false,
                ];
            });
        
        $data = $department->toArray();
        $data['users'] = $users;
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
    
    public function update(Request $request, Department $department)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:departments,id',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $department->update($request->only([
            'name', 'description', 'parent_id', 'sort_order', 'is_active', 'default_position_id'
        ]));
        
        return response()->json($department);
    }
    
    public function destroy(Department $department)
    {
        // Kiểm tra xem có phòng ban con không
        if ($department->children()->count() > 0) {
            return response()->json([
                'message' => 'Không thể xóa phòng ban có phòng ban con'
            ], 400);
        }
        
        // Kiểm tra xem có nhân viên không
        if ($department->users()->count() > 0) {
            return response()->json([
                'message' => 'Không thể xóa phòng ban có nhân viên'
            ], 400);
        }
        
        $department->delete();
        
        return response()->json(['message' => 'Đã xóa phòng ban thành công']);
    }
    
    public function assignUser(Request $request, Department $department)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'position_id' => 'nullable|exists:positions,id',
            'is_head' => 'boolean',
            'is_deputy' => 'boolean',
            'start_date' => 'nullable|date',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = User::find($request->user_id);
        
        // Priority: use position_id from request, fallback to department's default position
        $positionId = $request->position_id ?? $department->default_position_id;
        
        // Validate that position_id is required
        if (!$positionId) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng chọn Job Title hoặc thiết lập Job Title mặc định cho phòng ban.'
            ], 422);
        }
        
        // Check if user already assigned to this department
        if ($department->users()->where('user_id', $request->user_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Nhân viên đã thuộc phòng ban này.'
            ], 400);
        }
        
        \Log::info('Assigning user to department', [
            'user_id' => $request->user_id,
            'department_id' => $department->id,
            'position_id' => $positionId,
            'is_head' => $request->boolean('is_head'),
            'is_deputy' => $request->boolean('is_deputy')
        ]);
        
        $department->users()->attach($request->user_id, [
            'position_id' => $positionId,
            'is_head' => $request->boolean('is_head'),
            'is_deputy' => $request->boolean('is_deputy'),
            'start_date' => $request->start_date ?? now(),
            'status' => 'active',
        ]);
        
        // Assign roles from position if position has roles
        $position = Position::with('roles.permissions')->find($positionId);
        if ($position && $position->roles->isNotEmpty()) {
            $roleIds = $position->roles->pluck('id')->toArray();
            
            // Sync roles (add new ones, keep existing)
            $currentRoleIds = $user->roles()->pluck('roles.id')->toArray();
            $newRoleIds = array_unique(array_merge($currentRoleIds, $roleIds));
            $user->roles()->sync($newRoleIds);
            
            // Log permissions granted
            $permissionsGranted = $position->roles->flatMap(function($role) {
                return $role->permissions->pluck('name');
            })->unique();
            
            \Log::info('Roles and permissions assigned from position', [
                'user_id' => $user->id,
                'position_id' => $positionId,
                'position_name' => $position->name,
                'roles_assigned' => $position->roles->pluck('name')->toArray(),
                'permissions_granted' => $permissionsGranted->toArray()
            ]);
        } else {
            \Log::warning('Position has no roles defined', [
                'position_id' => $positionId,
                'position_name' => $position->name ?? 'Unknown'
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Đã phân công nhân viên thành công'
        ]);
    }
    
    public function removeUser(Request $request, Department $department)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $department->users()->updateExistingPivot($request->user_id, [
            'end_date' => now(),
            'status' => 'inactive',
        ]);
        
        return response()->json(['message' => 'Đã gỡ nhân viên khỏi phòng ban']);
    }
    
    public function setManager(Request $request, Department $department)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'manager_id' => 'nullable|exists:users,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Check if both users are in the department
        $userInDept = $department->users()->where('user_id', $request->user_id)->exists();
        if (!$userInDept) {
            return response()->json(['message' => 'Nhân viên không thuộc phòng ban này'], 400);
        }
        
        if ($request->manager_id) {
            $managerInDept = $department->users()->where('user_id', $request->manager_id)->exists();
            if (!$managerInDept) {
                return response()->json(['message' => 'Người quản lý không thuộc phòng ban này'], 400);
            }
        }
        
        // Update manager in department context
        $department->users()->updateExistingPivot($request->user_id, [
            'manager_user_id' => $request->manager_id,
            'dept_hierarchy_level' => $request->manager_id ? 1 : 0, // 0 = head, 1+ = subordinate
        ]);
        
        return response()->json(['message' => 'Đã cập nhật người quản lý']);
    }
    
    public function updateUser(Request $request, Department $department)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'position_id' => 'nullable|exists:positions,id',
            'is_head' => 'boolean',
            'is_deputy' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $department->users()->updateExistingPivot($request->user_id, [
            'position_id' => $request->position_id,
            'is_head' => $request->boolean('is_head'),
            'is_deputy' => $request->boolean('is_deputy'),
        ]);
        
        return response()->json(['message' => 'Đã cập nhật thông tin nhân viên']);
    }
    
    public function getTree(Request $request)
    {
        $user = $request->user();

        // Get branch from request (localStorage) or user's first branch
        $branchId = $request->input('branch_id') ?? $user->primary_branch_id ?? optional($user->branches()->first())->id;

        // Ensure "Ban quản trị" exists and fix orphaned departments
        if ($branchId) {
            $this->ensureRootDepartment($branchId);
        }

        $query = Department::where('is_active', true)
            ->with(['users' => function($q) {
                $q->select('users.id', 'users.name', 'users.email', 'users.avatar', 'users.employee_code')
                    ->withPivot('position_id', 'is_head', 'is_deputy', 'status')
                    ->wherePivot('status', 'active');
            }, 'users.positions'])
            ->orderBy('sort_order');

        // Chỉ filter theo branch nếu có branchId
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $departments = $query->get();

        // Build tree structure
        $tree = $this->buildTree($departments);

        return response()->json($tree);
    }

    /**
     * Ensure "Ban quản trị" (root department) exists for branch
     * and set it as parent for orphaned departments
     */
    private function ensureRootDepartment($branchId)
    {
        // Check if "Ban quản trị" exists for this branch
        $rootDept = Department::where('branch_id', $branchId)
            ->where('name', 'Ban quản trị')
            ->whereNull('parent_id')
            ->first();

        // If not exists, create it
        if (!$rootDept) {
            $rootDept = Department::create([
                'name' => 'Ban quản trị',
                'code' => 'BQT_' . $branchId . '_' . time(),
                'description' => 'Ban lãnh đạo cao nhất của chi nhánh',
                'branch_id' => $branchId,
                'parent_id' => null,
                'sort_order' => 0,
                'is_active' => true,
            ]);

            \Log::info('Created root department "Ban quản trị"', [
                'branch_id' => $branchId,
                'department_id' => $rootDept->id
            ]);
        }

        // Find all orphaned departments (no parent_id, not the root itself)
        $orphanedDepts = Department::where('branch_id', $branchId)
            ->whereNull('parent_id')
            ->where('id', '!=', $rootDept->id)
            ->get();

        // Set "Ban quản trị" as parent for orphaned departments
        if ($orphanedDepts->isNotEmpty()) {
            foreach ($orphanedDepts as $dept) {
                $dept->parent_id = $rootDept->id;
                $dept->save();

                \Log::info('Set parent for orphaned department', [
                    'department_id' => $dept->id,
                    'department_name' => $dept->name,
                    'new_parent_id' => $rootDept->id
                ]);
            }
        }

        return $rootDept;
    }

    private function buildTree($departments, $parentId = null)
    {
        $branch = [];

        foreach ($departments as $department) {
            if ($department->parent_id == $parentId) {
                $children = $this->buildTree($departments, $department->id);
                if ($children) {
                    $department->children = $children;
                }
                $branch[] = $department;
            }
        }

        return $branch;
    }
}
