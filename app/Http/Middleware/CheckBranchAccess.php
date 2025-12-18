<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBranchAccess
{
    /**
     * Handle an incoming request.
     * 
     * Middleware này để kiểm tra quyền truy cập tài nguyên theo chi nhánh.
     * Super-admin và user không có branch_id có thể truy cập tất cả.
     * User có branch_id chỉ truy cập được tài nguyên của chi nhánh mình.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Kiểm tra user đã đăng nhập chưa
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để tiếp tục'
            ], 401);
        }

        // Super-admin có thể truy cập tất cả
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // User không có branches có thể truy cập tất cả (HQ users)
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        if (empty($userBranches)) {
            return $next($request);
        }

        // Attach branch_ids vào request để controller có thể filter
        $request->merge(['user_branch_ids' => $userBranches]);

        return $next($request);
    }
}
