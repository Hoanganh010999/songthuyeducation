<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Lấy danh sách permissions
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', null);
        $module = $request->input('module');
        $groupByModule = $request->input('group_by_module', false);

        $query = Permission::query();

        if ($module) {
            $query->where('module', $module);
        }

        if ($groupByModule) {
            // Nhóm permissions theo module
            $permissions = Permission::where('is_active', true)
                ->orderBy('module')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('module');

            return response()->json([
                'success' => true,
                'data' => $permissions
            ]);
        }

        if ($perPage) {
            $permissions = $query->orderBy('module')
                ->orderBy('sort_order')
                ->paginate($perPage);
        } else {
            $permissions = $query->orderBy('module')
                ->orderBy('sort_order')
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }

    /**
     * Lấy danh sách modules
     */
    public function modules()
    {
        $modules = Permission::getModules();

        return response()->json([
            'success' => true,
            'data' => $modules
        ]);
    }

    /**
     * Lấy permissions theo module
     */
    public function byModule(string $module)
    {
        $permissions = Permission::getByModule($module);

        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }

    /**
     * Tạo permission mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'module' => 'required|string|max:255',
            'action' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $name = Permission::makeName($validated['module'], $validated['action']);

        // Kiểm tra permission đã tồn tại chưa
        if (Permission::where('name', $name)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Quyền này đã tồn tại'
            ], 400);
        }

        $permission = Permission::create([
            'module' => $validated['module'],
            'action' => $validated['action'],
            'name' => $name,
            'display_name' => $validated['display_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo quyền thành công',
            'data' => $permission
        ], 201);
    }

    /**
     * Xem chi tiết permission
     */
    public function show(string $id)
    {
        $permission = Permission::with('roles')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $permission
        ]);
    }

    /**
     * Cập nhật permission
     */
    public function update(Request $request, string $id)
    {
        $permission = Permission::findOrFail($id);

        $validated = $request->validate([
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $permission->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật quyền thành công',
            'data' => $permission
        ]);
    }

    /**
     * Xóa permission
     */
    public function destroy(string $id)
    {
        $permission = Permission::findOrFail($id);

        // Kiểm tra permission có roles không
        if ($permission->roles()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa quyền đang được sử dụng bởi vai trò'
            ], 400);
        }

        $permission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa quyền thành công'
        ]);
    }
}
