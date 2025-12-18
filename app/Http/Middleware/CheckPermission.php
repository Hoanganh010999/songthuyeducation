<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  Tên quyền cần kiểm tra (vd: users.view, products.create)
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Kiểm tra user đã đăng nhập chưa
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để tiếp tục'
            ], 401);
        }

        $user = auth()->user();
        
        // Get branch context from request
        $branchId = $request->input('branch_id') ?? $request->header('X-Branch-Id');
        
        // If branch context exists, check permission in that branch
        if ($branchId) {
            if (!$user->hasPermissionInBranch($permission, $branchId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập chức năng này trong chi nhánh hiện tại',
                    'required_permission' => $permission
                ], 403);
            }
        } else {
            // No branch context - use global permission check
            if (!$user->hasPermission($permission)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập chức năng này',
                    'required_permission' => $permission
                ], 403);
            }
        }

        return $next($request);
    }
}
