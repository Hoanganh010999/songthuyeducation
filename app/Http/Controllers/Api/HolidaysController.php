<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HolidaysController extends Controller
{
    /**
     * Get all holidays for a branch
     */
    public function index(Request $request)
    {
        $branchId = $request->input('branch_id');
        
        if (!$branchId) {
            return response()->json([
                'success' => false,
                'message' => 'Branch ID is required'
            ], 400);
        }

        $holidays = Holiday::where('branch_id', $branchId)
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $holidays
        ]);
    }

    /**
     * Store a new holiday
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $holiday = Holiday::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo lịch nghỉ thành công',
            'data' => $holiday
        ], 201);
    }

    /**
     * Update a holiday
     */
    public function update(Request $request, $id)
    {
        $holiday = Holiday::find($id);
        
        if (!$holiday) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy lịch nghỉ'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $holiday->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật lịch nghỉ thành công',
            'data' => $holiday
        ]);
    }

    /**
     * Delete a holiday
     */
    public function destroy($id)
    {
        $holiday = Holiday::find($id);
        
        if (!$holiday) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy lịch nghỉ'
            ], 404);
        }

        $holiday->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa lịch nghỉ thành công'
        ]);
    }
}
