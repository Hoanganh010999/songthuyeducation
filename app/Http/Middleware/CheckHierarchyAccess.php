<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckHierarchyAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $resourceType = 'customer'): Response
    {
        $user = $request->user();
        
        // Super admin bypasses all checks
        if ($user->is_super_admin) {
            return $next($request);
        }
        
        // Get resource ID from route
        $resourceId = $request->route('id') ?? $request->route('customerId') ?? $request->route('customer');
        
        if (!$resourceId) {
            return $next($request);
        }
        
        // Check based on resource type
        switch ($resourceType) {
            case 'customer':
                return $this->checkCustomerAccess($request, $next, $resourceId, $user);
            case 'user':
                return $this->checkUserAccess($request, $next, $resourceId, $user);
            default:
                return $next($request);
        }
    }
    
    private function checkCustomerAccess(Request $request, Closure $next, $customerId, $user)
    {
        $customer = \App\Models\Customer::find($customerId);
        
        if (!$customer) {
            return $next($request);
        }
        
        // Check if customer's assigned user is in hierarchy
        if ($customer->assigned_to) {
            if ($user->canAccessUserData($customer->assigned_to)) {
                return $next($request);
            }
            
            return response()->json([
                'message' => 'Bạn không có quyền truy cập dữ liệu này'
            ], 403);
        }
        
        return $next($request);
    }
    
    private function checkUserAccess(Request $request, Closure $next, $userId, $user)
    {
        if ($user->canAccessUserData($userId)) {
            return $next($request);
        }
        
        return response()->json([
            'message' => 'Bạn không có quyền truy cập dữ liệu người dùng này'
        ], 403);
    }
}
