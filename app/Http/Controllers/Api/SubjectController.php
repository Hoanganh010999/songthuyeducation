<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubjectController extends Controller
{
    /**
     * Get all subjects for current branch
     * Teachers can view subjects they are assigned to
     * Admin/Super Admin can view all subjects
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $branchId = $request->input('branch_id');
        
        // Check permissions
        $canViewAll = $user->hasRole('admin') || 
                      $user->hasRole('super-admin') || 
                      $user->hasPermission('subjects.view');
        
        $isTeacher = $user->hasRole('teacher');
        
        if (!$canViewAll && !$isTeacher) {
            return response()->json([
                'success' => false,
                'message' => __('errors.unauthorized_view_subjects')
            ], 403);
        }
        
        $query = Subject::with(['branch', 'teachers' => function($q) {
            $q->where('subject_teacher.status', 'active');
        }])
        ->withCount(['activeTeachers']);
        
        if ($branchId) {
            $query->forBranch($branchId);
        }
        
        // If teacher without full permission, only show subjects they teach
        if ($isTeacher && !$canViewAll) {
            $query->whereHas('teachers', function($q) use ($user) {
                $q->where('users.id', $user->id)
                  ->where('subject_teacher.status', 'active');
            });
        }
        
        $subjects = $query->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        
        // Add head teacher info to each subject
        $subjects->each(function($subject) {
            $subject->head_teacher = $subject->headTeacher();
        });
        
        return response()->json([
            'success' => true,
            'data' => $subjects
        ]);
    }

    /**
     * Store a new subject
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check duplicate code in same branch
        if ($request->code) {
            $exists = Subject::where('branch_id', $request->branch_id)
                ->where('code', $request->code)
                ->exists();
                
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã môn học đã tồn tại trong chi nhánh này'
                ], 400);
            }
        }

        $subject = Subject::create($request->all());
        $subject->load('branch');

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo môn học thành công',
            'data' => $subject
        ], 201);
    }

    /**
     * Get single subject with teachers
     */
    public function show(Subject $subject)
    {
        $subject->load([
            'branch',
            'teachers' => function($q) {
                $q->where('subject_teacher.status', 'active')
                  ->with('departments');
            }
        ]);
        
        $subject->head_teacher = $subject->headTeacher();
        
        return response()->json([
            'success' => true,
            'data' => $subject
        ]);
    }

    /**
     * Update subject
     */
    public function update(Request $request, Subject $subject)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check duplicate code if code is being updated
        if ($request->has('code') && $request->code) {
            $exists = Subject::where('branch_id', $subject->branch_id)
                ->where('code', $request->code)
                ->where('id', '!=', $subject->id)
                ->exists();
                
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã môn học đã tồn tại trong chi nhánh này'
                ], 400);
            }
        }

        $subject->update($request->all());
        $subject->load('branch', 'teachers');

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật môn học thành công',
            'data' => $subject
        ]);
    }

    /**
     * Delete subject
     */
    public function destroy(Subject $subject)
    {
        $teacherCount = $subject->teachers()->count();
        
        if ($teacherCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Không thể xóa môn học đang có {$teacherCount} giáo viên. Vui lòng gỡ giáo viên trước."
            ], 400);
        }

        $subject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa môn học thành công'
        ]);
    }

    /**
     * Assign teacher to subject
     */
    public function assignTeacher(Request $request, Subject $subject)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'is_head' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get the teacher user
        $teacher = \App\Models\User::find($request->user_id);
        
        // Check if teacher has Google email assigned
        if (!$teacher->google_email) {
            return response()->json([
                'success' => false,
                'message' => __('errors.teacher_no_google_email'),
                'error_code' => 'NO_GOOGLE_EMAIL',
            ], 400);
        }

        // Check if teacher already assigned
        if ($subject->teachers()->where('user_id', $request->user_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Giáo viên đã được gán vào môn học này'
            ], 400);
        }

        // Handle Google Drive Syllabus folder permissions
        try {
            $this->manageSyllabusFolderPermissions($subject, $teacher, 'add');
        } catch (\Exception $e) {
            \Log::error('[SubjectController] Failed to manage Syllabus folder permissions', [
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('errors.google_drive_permission_failed'),
                'details' => $e->getMessage(),
            ], 500);
        }

        // If assigning as head, remove current head
        if ($request->boolean('is_head')) {
            $subject->teachers()->updateExistingPivot(
                $subject->teachers()->wherePivot('is_head', true)->pluck('user_id'),
                ['is_head' => false]
            );
        }

        $subject->teachers()->attach($request->user_id, [
            'is_head' => $request->boolean('is_head'),
            'start_date' => $request->start_date ?? now(),
            'end_date' => $request->end_date,
            'status' => 'active',
        ]);

        $subject->load('teachers');

        return response()->json([
            'success' => true,
            'message' => 'Đã gán giáo viên vào môn học và cấp quyền Google Drive Syllabus',
            'data' => $subject
        ]);
    }

    /**
     * Remove teacher from subject
     */
    public function removeTeacher(Request $request, Subject $subject)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get the teacher user
        $teacher = \App\Models\User::find($request->user_id);
        
        // Handle Google Drive Syllabus folder permissions
        if ($teacher && $teacher->google_email) {
            try {
                $this->manageSyllabusFolderPermissions($subject, $teacher, 'remove');
            } catch (\Exception $e) {
                \Log::warning('[SubjectController] Failed to remove Syllabus folder permissions', [
                    'subject_id' => $subject->id,
                    'teacher_id' => $teacher->id,
                    'error' => $e->getMessage(),
                ]);
                // Continue with removal even if Google Drive fails
            }
        }

        $subject->teachers()->detach($request->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Đã gỡ giáo viên khỏi môn học và thu hồi quyền Google Drive Syllabus'
        ]);
    }

    /**
     * Set head teacher (Trưởng bộ môn)
     */
    public function setHeadTeacher(Request $request, Subject $subject)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user is in subject's teacher list
        if (!$subject->teachers()->where('user_id', $request->user_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Giáo viên chưa được gán vào môn học này'
            ], 400);
        }

        // Remove current head
        $subject->teachers()->updateExistingPivot(
            $subject->teachers()->wherePivot('is_head', true)->pluck('user_id'),
            ['is_head' => false]
        );

        // Set new head
        $subject->teachers()->updateExistingPivot($request->user_id, ['is_head' => true]);

        $subject->load('teachers');

        return response()->json([
            'success' => true,
            'message' => 'Đã đặt trưởng bộ môn',
            'data' => $subject
        ]);
    }

    /**
     * Manage Google Drive Syllabus folder permissions for a teacher
     * 
     * @param Subject $subject
     * @param User $teacher
     * @param string $action 'add' or 'remove'
     * @return void
     * @throws \Exception
     */
    protected function manageSyllabusFolderPermissions(Subject $subject, \App\Models\User $teacher, $action)
    {
        \Log::info('[SubjectController] Managing Syllabus folder permissions', [
            'subject_id' => $subject->id,
            'subject_name' => $subject->name,
            'teacher_id' => $teacher->id,
            'teacher_email' => $teacher->google_email,
            'action' => $action,
        ]);

        // Get Google Drive settings for the branch
        $setting = \App\Models\GoogleDriveSetting::where('branch_id', $subject->branch_id)
            ->where('is_active', true)
            ->first();

        if (!$setting) {
            \Log::warning('[SubjectController] No Google Drive settings for branch', [
                'branch_id' => $subject->branch_id,
            ]);
            // Don't throw error, just skip
            return;
        }

        // Initialize Google Drive service
        $service = new \App\Services\GoogleDriveService($setting);

        // Ensure Syllabus folder exists
        try {
            $syllabusFolderId = $service->findOrCreateSyllabusFolder();
            \Log::info('[SubjectController] Syllabus folder ready', [
                'folder_id' => $syllabusFolderId,
            ]);
        } catch (\Exception $e) {
            \Log::error('[SubjectController] Failed to ensure Syllabus folder exists', [
                'error' => $e->getMessage(),
            ]);
            throw new \Exception(__('errors.syllabus_folder_not_found'));
        }

        // Manage permissions
        if ($action === 'add') {
            // Grant writer permission to teacher
            try {
                $service->shareFile($syllabusFolderId, $teacher->google_email, 'writer');
                
                // Also save to google_drive_permissions table
                $item = \App\Models\GoogleDriveItem::where('google_id', $syllabusFolderId)->first();
                if ($item) {
                    \App\Models\GoogleDrivePermission::updateOrCreate(
                        [
                            'user_id' => $teacher->id,
                            'google_drive_item_id' => $item->id,
                        ],
                        [
                            'role' => 'writer',
                            'is_verified' => true,
                            'verified_at' => now(),
                            'synced_at' => now(),
                        ]
                    );
                }

                \Log::info('[SubjectController] Permission granted for Syllabus folder', [
                    'folder_id' => $syllabusFolderId,
                    'teacher_email' => $teacher->google_email,
                ]);
            } catch (\Exception $e) {
                \Log::error('[SubjectController] Failed to grant permission', [
                    'folder_id' => $syllabusFolderId,
                    'teacher_email' => $teacher->google_email,
                    'error' => $e->getMessage(),
                ]);
                throw new \Exception(__('errors.google_drive_permission_failed') . ': ' . $e->getMessage());
            }
        } elseif ($action === 'remove') {
            // Revoke permission from teacher
            try {
                $service->revokePermission($syllabusFolderId, $teacher->google_email);
                
                // Remove from google_drive_permissions table
                $item = \App\Models\GoogleDriveItem::where('google_id', $syllabusFolderId)->first();
                if ($item) {
                    \App\Models\GoogleDrivePermission::where('user_id', $teacher->id)
                        ->where('google_drive_item_id', $item->id)
                        ->delete();
                }

                \Log::info('[SubjectController] Permission revoked from Syllabus folder', [
                    'folder_id' => $syllabusFolderId,
                    'teacher_email' => $teacher->google_email,
                ]);
            } catch (\Exception $e) {
                \Log::warning('[SubjectController] Failed to revoke permission (may not exist)', [
                    'folder_id' => $syllabusFolderId,
                    'teacher_email' => $teacher->google_email,
                    'error' => $e->getMessage(),
                ]);
                // Don't throw error for remove, just log warning
            }
        }
    }
}
