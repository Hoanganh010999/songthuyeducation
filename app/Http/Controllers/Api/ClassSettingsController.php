<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\StudyPeriod;
use App\Models\Room;
use App\Models\Semester;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClassSettingsController extends Controller
{
    // ==========================================
    // ACADEMIC YEARS
    // ==========================================
    
    public function getAcademicYears(Request $request)
    {
        $branchId = $request->input('branch_id');
        
        $query = AcademicYear::with('semesters')
            ->forBranch($branchId)
            ->orderBy('is_current', 'desc')
            ->orderBy('start_date', 'desc');
        
        $academicYears = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $academicYears
        ]);
    }
    
    public function storeAcademicYear(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:academic_years,code',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
            'description' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // If setting as current, unset other current academic years for this branch
        if ($request->is_current) {
            AcademicYear::where('branch_id', $request->branch_id)
                ->update(['is_current' => false]);
        }
        
        $academicYear = AcademicYear::create($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Đã tạo năm học thành công',
            'data' => $academicYear
        ], 201);
    }
    
    public function updateAcademicYear(Request $request, $id)
    {
        $academicYear = AcademicYear::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:academic_years,code,' . $id,
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
            'description' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // If setting as current, unset other current academic years for this branch
        if ($request->is_current) {
            AcademicYear::where('branch_id', $academicYear->branch_id)
                ->where('id', '!=', $id)
                ->update(['is_current' => false]);
        }
        
        $academicYear->update($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật năm học thành công',
            'data' => $academicYear
        ]);
    }
    
    public function deleteAcademicYear($id)
    {
        $academicYear = AcademicYear::findOrFail($id);
        $academicYear->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Đã xóa năm học thành công'
        ]);
    }
    
    // ==========================================
    // SEMESTERS
    // ==========================================
    
    public function getSemesters(Request $request)
    {
        $academicYearId = $request->input('academic_year_id');
        
        $query = Semester::with('academicYear');
        
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        
        $semesters = $query->orderBy('sort_order')->get();
        
        return response()->json([
            'success' => true,
            'data' => $semesters
        ]);
    }
    
    public function storeSemester(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'total_weeks' => 'nullable|integer|min:1',
            'is_current' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // If setting as current, unset other current semesters for this academic year
        if ($request->is_current) {
            Semester::where('academic_year_id', $request->academic_year_id)
                ->update(['is_current' => false]);
        }
        
        $semester = Semester::create($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Đã tạo học kỳ thành công',
            'data' => $semester->load('academicYear')
        ], 201);
    }
    
    public function updateSemester(Request $request, $id)
    {
        $semester = Semester::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'total_weeks' => 'nullable|integer|min:1',
            'is_current' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // If setting as current, unset other current semesters for this academic year
        if ($request->is_current) {
            Semester::where('academic_year_id', $semester->academic_year_id)
                ->where('id', '!=', $id)
                ->update(['is_current' => false]);
        }
        
        $semester->update($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật học kỳ thành công',
            'data' => $semester->load('academicYear')
        ]);
    }
    
    public function deleteSemester($id)
    {
        $semester = Semester::findOrFail($id);
        $semester->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Đã xóa học kỳ thành công'
        ]);
    }
    
    // ==========================================
    // STUDY PERIODS
    // ==========================================
    
    public function getStudyPeriods(Request $request)
    {
        $branchId = $request->input('branch_id');
        
        $studyPeriods = StudyPeriod::forBranch($branchId)
            ->active()
            ->orderBy('sort_order')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $studyPeriods
        ]);
    }
    
    public function storeStudyPeriod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string',
            'duration_minutes' => 'required|integer|min:1',
            'lesson_duration' => 'required|integer|min:1',
            'break_duration' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $studyPeriod = StudyPeriod::create($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Đã tạo ca học thành công',
            'data' => $studyPeriod
        ], 201);
    }
    
    public function updateStudyPeriod(Request $request, $id)
    {
        $studyPeriod = StudyPeriod::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string',
            'duration_minutes' => 'required|integer|min:1',
            'lesson_duration' => 'required|integer|min:1',
            'break_duration' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $studyPeriod->update($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật ca học thành công',
            'data' => $studyPeriod
        ]);
    }
    
    public function deleteStudyPeriod($id)
    {
        $studyPeriod = StudyPeriod::findOrFail($id);
        $studyPeriod->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Đã xóa ca học thành công'
        ]);
    }
    
    // ==========================================
    // ROOMS
    // ==========================================
    
    public function getRooms(Request $request)
    {
        $branchId = $request->input('branch_id');
        $roomType = $request->input('room_type');
        $isAvailable = $request->input('is_available');
        
        $query = Room::forBranch($branchId)->active();
        
        if ($roomType) {
            $query->where('room_type', $roomType);
        }
        
        if ($isAvailable !== null) {
            $query->where('is_available', $isAvailable);
        }
        
        $rooms = $query->orderBy('building')
            ->orderBy('floor')
            ->orderBy('name')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }
    
    public function storeRoom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:rooms,code',
            'building' => 'nullable|string',
            'floor' => 'nullable|string',
            'capacity' => 'nullable|integer|min:1',
            'room_type' => 'required|in:classroom,lab,computer_lab,library,gym,other',
            'facilities' => 'nullable|array',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $room = Room::create($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Đã tạo phòng học thành công',
            'data' => $room
        ], 201);
    }
    
    public function updateRoom(Request $request, $id)
    {
        $room = Room::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:rooms,code,' . $id,
            'building' => 'nullable|string',
            'floor' => 'nullable|string',
            'capacity' => 'nullable|integer|min:1',
            'room_type' => 'required|in:classroom,lab,computer_lab,library,gym,other',
            'facilities' => 'nullable|array',
            'is_available' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $room->update($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật phòng học thành công',
            'data' => $room
        ]);
    }
    
    public function deleteRoom($id)
    {
        $room = Room::findOrFail($id);
        $room->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Đã xóa phòng học thành công'
        ]);
    }
    
    // ==========================================
    // HOLIDAYS
    // ==========================================
    
    public function getHolidays(Request $request)
    {
        $branchId = $request->input('branch_id');
        $academicYearId = $request->input('academic_year_id');
        $type = $request->input('type');
        
        $query = Holiday::with('academicYear')->forBranch($branchId)->active();
        
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        
        if ($type) {
            $query->where('type', $type);
        }
        
        $holidays = $query->orderBy('start_date')->get();
        
        return response()->json([
            'success' => true,
            'data' => $holidays
        ]);
    }
    
    public function storeHoliday(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:national,school,semester_break,other',
            'affects_schedule' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Calculate total days
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = \Carbon\Carbon::parse($request->end_date);
        $totalDays = $startDate->diffInDays($endDate) + 1;
        
        $holiday = Holiday::create(array_merge($request->all(), [
            'total_days' => $totalDays
        ]));
        
        return response()->json([
            'success' => true,
            'message' => 'Đã tạo lịch nghỉ thành công',
            'data' => $holiday->load('academicYear')
        ], 201);
    }
    
    public function updateHoliday(Request $request, $id)
    {
        $holiday = Holiday::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:national,school,semester_break,other',
            'affects_schedule' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Recalculate total days
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = \Carbon\Carbon::parse($request->end_date);
        $totalDays = $startDate->diffInDays($endDate) + 1;
        
        $holiday->update(array_merge($request->all(), [
            'total_days' => $totalDays
        ]));
        
        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật lịch nghỉ thành công',
            'data' => $holiday->load('academicYear')
        ]);
    }
    
    public function deleteHoliday($id)
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Đã xóa lịch nghỉ thành công'
        ]);
    }
}
