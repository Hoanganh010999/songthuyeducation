<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Lấy danh sách users với filter và sort nâng cao
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $roleId = $request->input('role_id');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        // Validate sort fields
        $allowedSortFields = ['name', 'email', 'phone', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        $query = User::with('roles');

        // Search by name, email, or phone
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('google_email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($roleId) {
            $query->whereHas('roles', function ($q) use ($roleId) {
                $q->where('roles.id', $roleId);
            });
        }

        // Apply sorting
        if ($sortBy === 'name') {
            // Sort by last word of name (Vietnamese last name is at the end)
            // Remove parentheses and special chars before sorting
            $query->orderByRaw("
                SUBSTRING_INDEX(
                    TRIM(
                        REGEXP_REPLACE(
                            REGEXP_REPLACE(name, '\\\\([^)]*\\\\)', ''),
                            '[^a-zA-ZÀ-ỹ\\\\s]',
                            ''
                        )
                    ),
                    ' ',
                    -1
                ) {$sortDir}
            ");
        } else {
        $query->orderBy($sortBy, $sortDir);
        }

        $users = $query->paginate($perPage);

        // Get roles for filter dropdown
        $roles = Role::select('id', 'name', 'display_name')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $users,
            'roles' => $roles
        ]);
    }

    /**
     * Lấy danh sách users (không phân trang - cho dropdown)
     */
    public function list(Request $request)
    {
        $users = User::select('id', 'name', 'email')
            ->orderByRaw("
                SUBSTRING_INDEX(
                    TRIM(
                        REGEXP_REPLACE(
                            REGEXP_REPLACE(name, '\\\\([^)]*\\\\)', ''),
                            '[^a-zA-ZÀ-ỹ\\\\s]',
                            ''
                        )
                    ),
                    ' ',
                    -1
                ) ASC
            ") // Sort by last word (last name), removing special chars
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
    
    /**
     * Tìm user theo số điện thoại
     */
    public function searchByPhone(Request $request)
    {
        $phone = $request->input('phone');
        
        if (!$phone) {
            return response()->json([
                'success' => false,
                'message' => 'Số điện thoại không được để trống'
            ], 400);
        }
        
        $user = User::where('phone', $phone)->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy user với số điện thoại này'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }
    
    /**
     * Lấy danh sách nhân viên của branch hiện tại
     */
    public function branchEmployees(Request $request)
    {
        $user = $request->user();
        $branchId = $request->input('branch_id') ?: optional($user->branches()->first())->id;
        
        if (!$branchId) {
            // If no branch, return empty list for super-admin or users without branch
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }
        
        $query = User::whereHas('branches', function($q) use ($branchId) {
            $q->where('branches.id', $branchId);
        })
        // Only get users who have at least one role
        ->whereHas('roles')
        // Exclude students (users who have active records in students table)
        ->whereNotExists(function($query) {
            $query->select(\DB::raw(1))
                  ->from('students')
                  ->whereColumn('students.user_id', 'users.id')
                  ->where('students.is_active', true);
        })
        // Exclude parents (users who have active records in parents table)
        ->whereNotExists(function($query) {
            $query->select(\DB::raw(1))
                  ->from('parents')
                  ->whereColumn('parents.user_id', 'users.id')
                  ->where('parents.is_active', true);
        })
        ->with(['departments' => function($q) use ($branchId) {
            $q->where('departments.branch_id', $branchId)
              ->withPivot('position_id', 'is_head', 'is_deputy');
        }, 'departments.branch', 'positions']);

        // Permission-based filtering for Zalo conversation assignment
        // Users without zalo.all_conversation_management can only see employees from their departments
        if (!$user->hasRole('super-admin') && !$user->hasPermission('zalo.all_conversation_management')) {
            $userDepartmentIds = $user->departments()->pluck('departments.id')->toArray();

            if (!empty($userDepartmentIds)) {
                // Only show employees from the same departments
                $query->whereHas('departments', function($q) use ($userDepartmentIds) {
                    $q->whereIn('departments.id', $userDepartmentIds);
                });
            } else {
                // User has no departments → show nobody (except themselves for fallback)
                $query->where('id', $user->id);
            }
        }

        // Search
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Filter by department
        if ($deptId = $request->input('department_id')) {
            $query->whereHas('departments', function($q) use ($deptId) {
                $q->where('departments.id', $deptId);
            });
        }
        
        $employees = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $employees
        ]);
    }

    /**
     * Tạo user mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
        ]);

        // Gán roles cho user
        if (!empty($validated['role_ids'])) {
            $user->roles()->attach($validated['role_ids']);
        }

        $user->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'Tạo người dùng thành công',
            'data' => $user
        ], 201);
    }

    /**
     * Xem chi tiết user
     */
    public function show(string $id)
    {
        $user = User::with(['roles.permissions'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Cập nhật user
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'sometimes|required|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        if (isset($validated['phone'])) {
            $user->phone = $validated['phone'];
        }

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // Cập nhật roles
        if (isset($validated['role_ids'])) {
            $user->roles()->sync($validated['role_ids']);
        }

        $user->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật người dùng thành công',
            'data' => $user
        ]);
    }

    /**
     * Xóa user
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        
        // Không cho phép xóa chính mình
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa tài khoản của chính bạn'
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa người dùng thành công'
        ]);
    }

    /**
     * Gán role cho user
     */
    public function assignRole(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $user->assignRole($role);

        $user->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'Gán vai trò thành công',
            'data' => $user
        ]);
    }

    /**
     * Thu hồi role từ user
     */
    public function removeRole(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $user->removeRole($role);

        $user->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'Thu hồi vai trò thành công',
            'data' => $user
        ]);
    }

    /**
     * Get user's roles for a specific branch
     */
    public function getBranchRoles(Request $request)
    {
        $user = $request->user();
        $branchId = $request->input('branch_id');

        if (!$branchId) {
            return response()->json([
                'success' => false,
                'message' => 'Branch ID is required'
            ], 400);
        }

        // Super-admin sees all global roles
        if ($user->hasRole('super-admin')) {
            return response()->json([
                'success' => true,
                'data' => $user->roles
            ]);
        }

        // Get roles from positions in departments of this branch
        $roles = \DB::table('department_user')
            ->join('departments', 'department_user.department_id', '=', 'departments.id')
            ->join('positions', 'department_user.position_id', '=', 'positions.id')
            ->join('position_role', 'positions.id', '=', 'position_role.position_id')
            ->join('roles', 'position_role.role_id', '=', 'roles.id')
            ->where('department_user.user_id', $user->id)
            ->where('department_user.status', 'active')
            ->where('departments.branch_id', $branchId)
            ->whereNotNull('department_user.position_id')
            ->select('roles.*')
            ->distinct()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    /**
     * Reset mật khẩu user về mặc định
     */
    public function resetPassword(Request $request, string $id)
    {
        $request->validate([
            'reset_type' => 'required|in:default,custom',
            'new_password' => 'required_if:reset_type,custom|min:6',
        ]);

        $user = User::findOrFail($id);
        
        if ($request->reset_type === 'custom') {
            // Reset về password tùy chỉnh
            $user->password = Hash::make($request->new_password);
            $message = 'Đã reset mật khẩu thành công';
        } else {
            // Reset về password mặc định
            $defaultPassword = $this->getDefaultPassword($user);
            $user->password = Hash::make($defaultPassword);
            $message = "Đã reset mật khẩu về mặc định: {$defaultPassword}";
        }
        
        $user->save();

        // Vô hiệu hóa tất cả token của user để buộc đăng nhập lại
        $user->tokens()->delete();

        \Log::info('🔐 [UserController] Password reset - all tokens revoked', [
            'user_id' => $user->id,
            'reset_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'user_id' => $user->id,
                'default_password' => $request->reset_type === 'default' ? $this->getDefaultPassword($user) : null,
            ]
        ]);
    }

    /**
     * Lấy mật khẩu mặc định cho user
     */
    protected function getDefaultPassword(User $user): string
    {
        // Nếu user là student, dùng student_code
        $student = \App\Models\Student::where('user_id', $user->id)->first();
        if ($student && $student->student_code) {
            return substr($student->student_code, -6);
        }
        
        // Nếu có phone, dùng 6 số cuối phone
        if ($user->phone && strlen($user->phone) >= 6) {
            return substr($user->phone, -6);
        }
        
        // Fallback
        return '123456';
    }

    /**
     * User tự thay đổi mật khẩu của mình
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        $user = $request->user();

        // Kiểm tra mật khẩu hiện tại
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu hiện tại không đúng'
            ], 400);
        }

        // Kiểm tra mật khẩu mới không trùng mật khẩu cũ
        if (Hash::check($request->new_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu mới không được trùng với mật khẩu hiện tại'
            ], 400);
        }

        // Cập nhật mật khẩu mới
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Vô hiệu hóa tất cả token để buộc đăng nhập lại với mật khẩu mới
        $user->tokens()->delete();

        \Log::info('🔐 [UserController] Password changed - all tokens revoked', [
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công. Vui lòng đăng nhập lại.',
            'require_reauth' => true // Signal frontend to redirect to login
        ]);
    }

    /**
     * Cập nhật thông tin liên hệ (email, phone) - Không cần quyền quản lý user
     */
    public function updateContact(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'email' => ['nullable', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
        ]);

        $updated = false;

        if (isset($validated['email']) && $validated['email']) {
            $user->email = $validated['email'];
            $updated = true;
        }

        if (isset($validated['phone']) && $validated['phone']) {
            $user->phone = $validated['phone'];
            $updated = true;
        }

        if ($updated) {
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin liên hệ thành công',
            'data' => $user
        ]);
    }

    /**
     * Cập nhật Google Email - Không cần quyền quản lý user
     */
    public function updateGoogleEmail(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'google_email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->google_email = $validated['google_email'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật Google Email thành công',
            'data' => $user
        ]);
    }

    /**
     * Cập nhật English Name - Không cần quyền quản lý user
     */
    public function updateEnglishName(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'english_name' => 'nullable|string|max:255',
        ]);

        $user->english_name = $validated['english_name'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật tên tiếng Anh thành công',
            'data' => $user
        ]);
    }
}
