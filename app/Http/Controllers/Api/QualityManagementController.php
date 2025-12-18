<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Position;
use App\Models\QualitySetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QualityManagementController extends Controller
{
    /**
     * Get all positions with codes for settings
     */
    public function getPositions(Request $request)
    {
        $positions = Position::select('id', 'name', 'code', 'level')
            ->where('is_active', true)
            ->whereNotNull('code')
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $positions
        ]);
    }

    /**
     * Get all departments for current branch (for teacher settings)
     */
    public function getDepartments(Request $request)
    {
        $branchId = $request->input('branch_id');

        if (!$branchId) {
            return response()->json([
                'success' => false,
                'message' => 'Branch ID là bắt buộc'
            ], 400);
        }

        $departments = DB::table('departments')
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->select('id', 'name', 'code', 'description', 'parent_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $departments
        ]);
    }

    /**
     * Get teachers based on department IDs
     */
    public function getTeachers(Request $request)
    {
        $branchId = $request->input('branch_id');
        $departmentIds = $request->input('department_ids', []);

        if (!empty($departmentIds)) {
            return $this->getTeachersByDepartments($branchId, $departmentIds);
        }

        return response()->json([
            'success' => true,
            'data' => []
        ]);
    }

    /**
     * Get teachers by department IDs (NEW METHOD)
     */
    private function getTeachersByDepartments($branchId, $departmentIds)
    {
        $query = DB::table('department_user')
            ->join('users', 'department_user.user_id', '=', 'users.id')
            ->join('positions', 'department_user.position_id', '=', 'positions.id')
            ->join('departments', 'department_user.department_id', '=', 'departments.id')
            ->whereIn('department_user.department_id', $departmentIds)
            ->where('department_user.status', 'active')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.phone',
                'users.google_email',
                'positions.name as position_name',
                'positions.code as position_code',
                'departments.name as department_name',
                'departments.id as department_id',
                'department_user.is_head',
                'department_user.is_deputy',
                'department_user.start_date'
            );

        // Filter by branch if provided
        if ($branchId) {
            $query->where('departments.branch_id', $branchId);
        }

        $teachers = $query->get();

        // Load subjects for each teacher
        foreach ($teachers as $teacher) {
            $subjects = DB::table('subject_teacher')
                ->join('subjects', 'subject_teacher.subject_id', '=', 'subjects.id')
                ->where('subject_teacher.user_id', $teacher->id)
                ->where('subject_teacher.status', 'active')
                ->select(
                    'subjects.id',
                    'subjects.name',
                    'subjects.code',
                    'subject_teacher.is_head'
                )
                ->get();

            $teacher->subjects = $subjects;
        }

        return response()->json([
            'success' => true,
            'data' => $teachers
        ]);
    }

    /**
     * Get teacher settings for current branch
     * Returns department_ids configuration
     */
    public function getTeacherSettings(Request $request)
    {
        $branchId = $request->input('branch_id');

        if (!$branchId) {
            return response()->json([
                'success' => false,
                'message' => 'Branch ID là bắt buộc'
            ], 400);
        }

        $deptSetting = QualitySetting::where('branch_id', $branchId)
            ->where('industry', 'education')
            ->where('setting_key', 'teacher_department_ids')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'department_ids' => $deptSetting ? $deptSetting->setting_value : []
            ]
        ]);
    }

    /**
     * Save teacher settings for current branch
     */
    public function saveTeacherSettings(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'department_ids' => 'required|array',
            'department_ids.*' => 'integer'
        ]);

        QualitySetting::updateOrCreate(
            [
                'branch_id' => $request->branch_id,
                'industry' => 'education',
                'setting_key' => 'teacher_department_ids'
            ],
            [
                'setting_value' => $request->department_ids
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu thiết lập phòng ban giáo viên cho chi nhánh',
            'data' => [
                'department_ids' => $request->department_ids
            ]
        ]);
    }
}
