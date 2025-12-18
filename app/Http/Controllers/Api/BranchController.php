<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    /**
     * Lấy danh sách branches
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $isActive = $request->input('is_active');

        $query = Branch::with(['manager:id,name,email']);

        // Search
        if ($search) {
            $query->search($search);
        }

        // Filter by status
        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        // Count users per branch
        $query->withCount('users');

        $branches = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $branches
        ]);
    }

    /**
     * Tạo branch mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:branches,code',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'ward' => 'nullable|string|max:100',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'is_headquarters' => 'boolean',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        // Nếu đánh dấu là headquarters, bỏ flag của branch cũ
        if ($validated['is_headquarters'] ?? false) {
            Branch::where('is_headquarters', true)->update(['is_headquarters' => false]);
        }

        $branch = Branch::create($validated);
        $branch->load('manager:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Tạo chi nhánh thành công',
            'data' => $branch
        ], 201);
    }

    /**
     * Xem chi tiết branch
     */
    public function show(string $id)
    {
        $branch = Branch::with([
            'manager:id,name,email,phone',
            'users:id,name,email,branch_id'
        ])->withCount('users')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $branch
        ]);
    }

    /**
     * Cập nhật branch
     */
    public function update(Request $request, string $id)
    {
        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('branches', 'code')->ignore($branch->id)
            ],
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'ward' => 'nullable|string|max:100',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'is_headquarters' => 'boolean',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        // Nếu đánh dấu là headquarters, bỏ flag của branch khác
        if (($validated['is_headquarters'] ?? false) && !$branch->is_headquarters) {
            Branch::where('is_headquarters', true)->update(['is_headquarters' => false]);
        }

        $branch->update($validated);
        $branch->load('manager:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật chi nhánh thành công',
            'data' => $branch
        ]);
    }

    /**
     * Xóa branch
     */
    public function destroy(string $id)
    {
        $branch = Branch::findOrFail($id);

        // Kiểm tra có users không
        if ($branch->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa chi nhánh đang có nhân sự'
            ], 400);
        }

        // Không cho xóa headquarters
        if ($branch->is_headquarters) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa trụ sở chính'
            ], 400);
        }

        $branch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa chi nhánh thành công'
        ]);
    }

    /**
     * Lấy danh sách users của branch
     */
    public function users(string $id)
    {
        $branch = Branch::findOrFail($id);
        
        $users = $branch->users()
            ->with('roles:id,name,display_name')
            ->select('id', 'name', 'email', 'branch_id', 'created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Lấy danh sách branches (dropdown - không phân trang)
     */
    public function list(Request $request)
    {
        $branches = Branch::active()
            ->select('id', 'code', 'name', 'city')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $branches
        ]);
    }

    /**
     * Thống kê theo branch
     */
    public function statistics(string $id)
    {
        $branch = Branch::findOrFail($id);

        $stats = [
            'total_users' => $branch->users()->count(),
            'active_users' => $branch->users()->whereHas('roles', function ($query) {
                $query->where('is_active', true);
            })->count(),
            // Có thể thêm thống kê khác như: students, staff, etc.
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
