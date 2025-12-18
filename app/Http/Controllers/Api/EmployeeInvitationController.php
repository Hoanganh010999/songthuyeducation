<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployeeInvitationController extends Controller
{
    /**
     * Danh sách lời mời
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $branchId = $request->input('branch_id');
        
        $query = DB::table('employee_invitations')
            ->leftJoin('users as invited', 'employee_invitations.email', '=', 'invited.email')
            ->leftJoin('users as inviter', 'employee_invitations.invited_by', '=', 'inviter.id')
            ->leftJoin('branches', 'employee_invitations.branch_id', '=', 'branches.id')
            ->select(
                'employee_invitations.*',
                'invited.id as invited_user_id',
                'invited.name as invited_user_name',
                'inviter.name as inviter_name',
                'branches.name as branch_name'
            );
        
        // Always filter by branch if branch_id is provided
        if ($branchId) {
            $query->where('employee_invitations.branch_id', $branchId);
        }
        
        $invitations = $query->orderBy('employee_invitations.created_at', 'desc')->get();
        
        // Format data
        $formatted = $invitations->map(function ($inv) {
            return [
                'id' => $inv->id,
                'email' => $inv->email,
                'message' => $inv->message,
                'status' => $inv->status,
                'created_at' => $inv->created_at,
                'invited_user' => $inv->invited_user_id ? [
                    'id' => $inv->invited_user_id,
                    'name' => $inv->invited_user_name
                ] : null,
                'inviter' => [
                    'name' => $inv->inviter_name
                ],
                'branch' => [
                    'name' => $inv->branch_name
                ]
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $formatted
        ]);
    }
    
    /**
     * Gửi lời mời nhân viên vào branch
     */
    public function sendInvitation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'required|string',
            'branch_id' => 'required|exists:branches,id'
        ]);
        
        $inviter = $request->user();
        $invitedUser = User::find($request->user_id);
        $branchId = $request->branch_id;
        
        // Verify inviter has access to this branch (super-admin can access all)
        $isSuperAdmin = $inviter->roles()->where('name', 'super-admin')->exists();
        if (!$isSuperAdmin && !$inviter->branches()->where('branches.id', $branchId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền gửi thư mời cho chi nhánh này'
            ], 403);
        }
        
        // Check if user already in this branch
        if ($invitedUser->branches()->where('branches.id', $branchId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'User đã là nhân viên của chi nhánh này'
            ], 400);
        }
        
        // Create invitation record in employee_invitations table
        $token = Str::random(32);
        $invitationId = DB::table('employee_invitations')->insertGetId([
            'token' => $token,
            'email' => $invitedUser->email,
            'invited_by' => $inviter->id,
            'invited_user_id' => $invitedUser->id,
            'branch_id' => $branchId,
            'message' => $request->message,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Create notification for invited user
        DB::table('notifications')->insert([
            'user_id' => $invitedUser->id,
            'type' => 'employee_invitation',
            'title' => 'Lời mời tham gia chi nhánh',
            'message' => $request->message,
            'data' => json_encode([
                'invitation_id' => $invitationId,
                'token' => $token,
                'branch_id' => $branchId,
                'inviter_name' => $inviter->name
            ]),
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Đã gửi lời mời thành công'
        ]);
    }
    
    /**
     * Chấp thuận lời mời
     */
    public function acceptInvitation(Request $request, $token)
    {
        $invitation = DB::table('employee_invitations')
            ->where('token', $token)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();
        
        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Lời mời không hợp lệ hoặc đã hết hạn'
            ], 404);
        }
        
        $user = User::find($invitation->invited_user_id);
        
        // Add user to branch
        if (!$user->branches()->where('branches.id', $invitation->branch_id)->exists()) {
            $user->branches()->attach($invitation->branch_id, [
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        // Update invitation status
        DB::table('employee_invitations')
            ->where('id', $invitation->id)
            ->update([
                'status' => 'accepted',
                'responded_at' => now(),
                'updated_at' => now()
            ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Đã chấp thuận lời mời'
        ]);
    }
    
    /**
     * Từ chối lời mời
     */
    public function rejectInvitation(Request $request, $token)
    {
        $invitation = DB::table('employee_invitations')
            ->where('token', $token)
            ->where('status', 'pending')
            ->first();
        
        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Lời mời không hợp lệ'
            ], 404);
        }
        
        // Update invitation status
        DB::table('employee_invitations')
            ->where('id', $invitation->id)
            ->update([
                'status' => 'rejected',
                'responded_at' => now(),
                'updated_at' => now()
            ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Đã từ chối lời mời'
        ]);
    }
}
