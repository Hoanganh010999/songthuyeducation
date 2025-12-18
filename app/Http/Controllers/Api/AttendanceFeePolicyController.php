<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceFeePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceFeePolicyController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->input('branch_id');
        
        $query = AttendanceFeePolicy::query();
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $policies = $query->orderBy('is_active', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->get();
        
        return response()->json([
            'success' => true,
            'data' => $policies
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
            'absence_unexcused_percent' => 'required|numeric|min:0|max:100',
            'absence_consecutive_threshold' => 'required|integer|min:1',
            'absence_excused_free_limit' => 'required|integer|min:0',
            'absence_excused_percent' => 'required|numeric|min:0|max:100',
            'late_deduct_percent' => 'required|numeric|min:0|max:100',
            'late_grace_minutes' => 'required|integer|min:0',
            'late_penalty_amount' => 'required|numeric|min:0',
            'late_penalty_threshold' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $policy = AttendanceFeePolicy::create($validated);

        if ($validated['is_active'] ?? false) {
            $policy->activate();
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance fee policy created successfully',
            'data' => $policy->load('branch')
        ], 201);
    }

    public function show(AttendanceFeePolicy $attendanceFeePolicy)
    {
        return response()->json([
            'success' => true,
            'data' => $attendanceFeePolicy->load('branch')
        ]);
    }

    public function update(Request $request, AttendanceFeePolicy $attendanceFeePolicy)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
            'absence_unexcused_percent' => 'sometimes|numeric|min:0|max:100',
            'absence_consecutive_threshold' => 'sometimes|integer|min:1',
            'absence_excused_free_limit' => 'sometimes|integer|min:0',
            'absence_excused_percent' => 'sometimes|numeric|min:0|max:100',
            'late_deduct_percent' => 'sometimes|numeric|min:0|max:100',
            'late_grace_minutes' => 'sometimes|integer|min:0',
            'late_penalty_amount' => 'sometimes|numeric|min:0',
            'late_penalty_threshold' => 'sometimes|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $attendanceFeePolicy->update($validated);

        if (isset($validated['is_active']) && $validated['is_active']) {
            $attendanceFeePolicy->activate();
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance fee policy updated successfully',
            'data' => $attendanceFeePolicy->fresh()->load('branch')
        ]);
    }

    public function destroy(AttendanceFeePolicy $attendanceFeePolicy)
    {
        if ($attendanceFeePolicy->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete active policy'
            ], 422);
        }

        $attendanceFeePolicy->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attendance fee policy deleted successfully'
        ]);
    }

    public function activate(AttendanceFeePolicy $attendanceFeePolicy)
    {
        $attendanceFeePolicy->activate();

        return response()->json([
            'success' => true,
            'message' => 'Policy activated successfully',
            'data' => $attendanceFeePolicy->fresh()->load('branch')
        ]);
    }

    public function getActive(Request $request)
    {
        $branchId = $request->input('branch_id');
        $policy = AttendanceFeePolicy::getActive($branchId);

        if (!$policy) {
            return response()->json([
                'success' => false,
                'message' => 'No active policy found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $policy->load('branch')
        ]);
    }
}
