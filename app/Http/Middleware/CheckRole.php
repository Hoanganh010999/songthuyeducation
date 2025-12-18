<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Danh sách roles được phép (vd: admin, manager)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Kiểm tra user đã đăng nhập chưa
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để tiếp tục'
            ], 401);
        }

        $user = auth()->user();

        // Kiểm tra user có bất kỳ role nào trong danh sách
        if (!$user->hasAnyRole($roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập chức năng này',
                'required_roles' => $roles
            ], 403);
        }

        return $next($request);
    }
}
