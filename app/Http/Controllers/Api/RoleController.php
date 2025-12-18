<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Lấy danh sách roles
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', null);
        $withPermissions = $request->input('with_permissions', false);

        $query = Role::query();

        if ($withPermissions) {
            $query->with('permissions');
        }

        // Add counts for permissions and users
        $query->withCount(['permissions', 'users']);

        if ($perPage) {
            $roles = $query->latest()->paginate($perPage);
        } else {
            $roles = $query->latest()->get();
        }

        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    /**
     * Tạo role mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Gán permissions cho role
        if (!empty($validated['permission_ids'])) {
            $role->permissions()->attach($validated['permission_ids']);
        }

        $role->load('permissions');

        return response()->json([
            'success' => true,
            'message' => 'Tạo vai trò thành công',
            'data' => $role
        ], 201);
    }

    /**
     * Xem chi tiết role
     */
    public function show(string $id)
    {
        $role = Role::with(['permissions', 'users'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $role
        ]);
    }

    /**
     * Cập nhật role
     */
    public function update(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $role->update([
            'name' => $validated['name'] ?? $role->name,
            'display_name' => $validated['display_name'] ?? $role->display_name,
            'description' => $validated['description'] ?? $role->description,
            'is_active' => $validated['is_active'] ?? $role->is_active,
        ]);

        // Cập nhật permissions
        if (isset($validated['permission_ids'])) {
            $role->permissions()->sync($validated['permission_ids']);
        }

        $role->load('permissions');

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật vai trò thành công',
            'data' => $role
        ]);
    }

    /**
     * Xóa role
     */
    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);

        // Kiểm tra role có users không
        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa vai trò đang được sử dụng bởi người dùng'
            ], 400);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa vai trò thành công'
        ]);
    }

    /**
     * Lấy danh sách permissions của role
     */
    public function getPermissions(string $id)
    {
        $role = Role::findOrFail($id);
        $permissions = $role->permissions;

        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }

    /**
     * Cập nhật permissions cho role (sync)
     */
    public function syncPermissions(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $role->permissions()->sync($validated['permission_ids']);
        $role->load('permissions');

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật quyền thành công',
            'data' => $role
        ]);
    }

    /**
     * Gán permission cho role
     */
    public function assignPermission(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'permission_id' => 'required|exists:permissions,id',
        ]);

        $permission = Permission::findOrFail($validated['permission_id']);
        $role->givePermissionTo($permission);

        $role->load('permissions');

        return response()->json([
            'success' => true,
            'message' => 'Gán quyền thành công',
            'data' => $role
        ]);
    }

    /**
     * Thu hồi permission từ role
     */
    public function revokePermission(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'permission_id' => 'required|exists:permissions,id',
        ]);

        $permission = Permission::findOrFail($validated['permission_id']);
        $role->revokePermissionTo($permission);

        $role->load('permissions');

        return response()->json([
            'success' => true,
            'message' => 'Thu hồi quyền thành công',
            'data' => $role
        ]);
    }
}
