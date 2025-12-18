<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        // Authorization: Require explicit permission OR teacher accessing their students
        $user = $request->user();
        
        // Super Admin and Admin can view all
        $canViewAll = $user->hasRole('admin') || $user->hasRole('super-admin');
        
        // Or must have explicit permission
        $hasPermission = $user->hasPermission('students.view_all');
        
        // Teachers can ONLY view students from classes they teach (not all students)
        $isTeacher = $user->hasRole('teacher');
        
        if (!$canViewAll && !$hasPermission && !$isTeacher) {
            return response()->json([
                'success' => false,
                'message' => __('errors.unauthorized_view_students')
            ], 403);
        }
        
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $status = $request->input('status');
        $branchId = $request->input('branch_id');
        $sortBy = $request->input('sort_by', 'name');
        $sortDir = $request->input('sort_dir', 'asc');

        $query = Student::with([
            'user', 
            'branch', 
            'parents.user', 
            'wallet',
            'classes' => function($q) {
                $q->where('classes.status', 'active')
                    ->select('classes.id', 'classes.name', 'classes.code', 'classes.status')
                    ->orderBy('classes.name');
            }
        ]);
        
        // If teacher without permission, only show students from their classes
        if ($isTeacher && !$hasPermission && !$canViewAll) {
            $teacherClassIds = \App\Models\ClassModel::where(function($q) use ($user) {
                $q->where('homeroom_teacher_id', $user->id)
                  ->orWhereHas('schedules', function($sq) use ($user) {
                      $sq->where('teacher_id', $user->id);
                  });
            })->pluck('id')->toArray();
            
            $query->whereHas('classes', function($q) use ($teacherClassIds) {
                $q->whereIn('classes.id', $teacherClassIds)
                  ->where('class_students.status', 'active');
            });
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('student_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        // Apply sorting
        $allowedSortColumns = ['name', 'email', 'google_email', 'is_active', 'student_code', 'created_at'];
        $sortDir = in_array(strtolower($sortDir), ['asc', 'desc']) ? strtolower($sortDir) : 'asc';

        if ($sortBy === 'name') {
            // Sort by last word of Vietnamese name (e.g., "Điền Thái Bảo" -> sort by "Bảo")
            $query->join('users', 'students.user_id', '=', 'users.id')
                  ->orderByRaw("SUBSTRING_INDEX(users.name, ' ', -1) {$sortDir}")
                  ->select('students.*');
        } elseif (in_array($sortBy, ['email', 'google_email'])) {
            // Sort by other user fields
            $query->join('users', 'students.user_id', '=', 'users.id')
                  ->orderBy("users.{$sortBy}", $sortDir)
                  ->select('students.*');
        } elseif ($sortBy === 'is_active') {
            $query->orderBy('students.is_active', $sortDir);
        } elseif ($sortBy === 'student_code') {
            $query->orderBy('students.student_code', $sortDir);
        } else {
            $query->latest();
        }

        $students = $query->paginate($perPage);

        // Add effective balance for each student
        $students->getCollection()->transform(function ($student) {
            $student->effective_balance = $student->getEffectiveBalance();
            return $student;
        });

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }

    /**
     * Search student by code (for add to class - bypasses permission checks)
     */
    public function searchByCode(Request $request)
    {
        $studentCode = $request->input('student_code');
        
        if (!$studentCode) {
            return response()->json([
                'success' => false,
                'message' => 'Student code is required'
            ], 400);
        }
        
        $student = Student::with([
            'user', 
            'branch', 
            'classes' => function($q) {
                $q->where('classes.status', 'active')
                    ->select('classes.id', 'classes.name', 'classes.code', 'classes.status')
                    ->orderBy('classes.name');
            }
        ])->where('student_code', 'like', "%{$studentCode}%")
          ->first();
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }
        
        $student->effective_balance = $student->getEffectiveBalance();
        
        return response()->json([
            'success' => true,
            'data' => $student
        ]);
    }

    public function show($id)
    {
        $user = request()->user();
        $student = Student::with(['user', 'branch', 'parents.user', 'wallet'])->findOrFail($id);
        
        // Authorization: Admin, the student themselves, their parents, or their teachers
        $hasAccess = false;
        
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            $hasAccess = true;
        } elseif ($user->hasPermission('students.view_all')) {
            $hasAccess = true;
        } elseif ($user->id === $student->user_id) {
            // Student viewing themselves
            $hasAccess = true;
        } elseif ($parent = \App\Models\ParentModel::where('user_id', $user->id)->first()) {
            // Check if this student is their child
            $hasAccess = $parent->students()->where('students.id', $id)->exists();
        } elseif ($user->hasRole('teacher')) {
            // Check if teacher teaches this student
            $studentClassIds = $student->classes()->pluck('classes.id')->toArray();
            $teacherClassIds = \App\Models\ClassModel::where(function($q) use ($user) {
                $q->where('homeroom_teacher_id', $user->id)
                  ->orWhereHas('schedules', function($sq) use ($user) {
                      $sq->where('teacher_id', $user->id);
                  });
            })->pluck('id')->toArray();
            $hasAccess = count(array_intersect($studentClassIds, $teacherClassIds)) > 0;
        }
        
        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => __('errors.unauthorized_view_student')
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $student
        ]);
    }

    // Get current user's student record
    public function me(Request $request)
    {
        $userId = $request->user()->id;
        
        $student = Student::with(['user', 'branch', 'classes', 'wallet'])
            ->where('user_id', $userId)
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => __('errors.student_not_found')
            ], 404);
        }

        $student->effective_balance = $student->getEffectiveBalance();

        return response()->json([
            'success' => true,
            'data' => $student
        ]);
    }

    // Get children for current parent user
    public function myChildren(Request $request)
    {
        $userId = $request->user()->id;
        
        $parent = \App\Models\ParentModel::where('user_id', $userId)->first();

        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => __('errors.parent_not_found'),
                'data' => []
            ]);
        }

        $students = $parent->students()
            ->with(['user', 'branch', 'classes', 'wallet'])
            ->get()
            ->map(function ($student) {
                $student->effective_balance = $student->getEffectiveBalance();
                $student->full_name = $student->user->name ?? $student->name;
                return $student;
            });

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }

    // Get children for current parent user in a specific class
    public function myChildrenInClass(Request $request, $classId)
    {
        $userId = $request->user()->id;

        $parent = \App\Models\ParentModel::where('user_id', $userId)->first();

        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => __('errors.parent_not_found'),
                'data' => []
            ]);
        }

        // Get parent's children who are enrolled in this specific class
        $students = $parent->students()
            ->with(['user'])
            ->whereHas('classes', function($q) use ($classId) {
                $q->where('classes.id', $classId)
                  ->where('class_students.status', 'active');
            })
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'user_id' => $student->user_id,
                    'student_code' => $student->student_code,
                    'name' => $student->user->name ?? 'Unknown',
                    'full_name' => $student->user->name ?? 'Unknown',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }

    // Get classes for a specific student (for parent/teacher/admin)
    public function getStudentClasses(Request $request, $studentId)
    {
        $user = $request->user();
        $student = Student::findOrFail($studentId);
        
        // Check if user has permission to view this student's classes
        // 1. Admin/Super-admin can view all
        // 2. Parent can view their children's classes
        // 3. Student can view their own classes
        // 4. Teacher can view their students' classes
        
        $hasAccess = false;
        
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            $hasAccess = true;
        } elseif ($user->id === $student->user_id) {
            // Student viewing their own classes
            $hasAccess = true;
        } elseif ($parent = \App\Models\ParentModel::where('user_id', $user->id)->first()) {
            // Check if this student is their child
            $hasAccess = $parent->students()->where('students.id', $studentId)->exists();
        } elseif ($user->hasRole('teacher')) {
            // Check if teacher teaches any of this student's classes
            $studentClassIds = $student->classes()->pluck('classes.id')->toArray();
            $teacherClassIds = \App\Models\ClassModel::where(function($q) use ($user) {
                $q->where('homeroom_teacher_id', $user->id)
                  ->orWhereHas('teachers', function($tq) use ($user) {
                      $tq->where('user_id', $user->id);
                  });
            })->pluck('id')->toArray();
            $hasAccess = count(array_intersect($studentClassIds, $teacherClassIds)) > 0;
        }
        
        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => __('errors.unauthorized_view_student_classes')
            ], 403);
        }
        
        $classes = $student->classes()
            ->where('class_students.status', 'active')
            ->select('classes.id', 'classes.name', 'classes.code')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $classes
        ]);
    }

    /**
     * Update student status (active/inactive)
     */
    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();
        
        // Authorization: Only admin, super-admin, or users with explicit permission
        $canUpdate = $user->hasRole('admin') 
                     || $user->hasRole('super-admin') 
                     || $user->hasPermission('students.edit');
        
        if (!$canUpdate) {
            return response()->json([
                'success' => false,
                'message' => __('errors.unauthorized_edit_student')
            ], 403);
        }
        
        $request->validate([
            'is_active' => 'required|boolean'
        ]);
        
        $student = Student::findOrFail($id);
        $student->is_active = $request->input('is_active');
        $student->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Student status updated successfully',
            'data' => $student->load('user', 'branch', 'parents.user', 'wallet', 'classes')
        ]);
    }

    /**
     * Assign a parent to a student
     */
    public function assignParent(Request $request, $id)
    {
        $user = $request->user();
        
        // Authorization check
        $canAssign = $user->hasRole('admin') 
                     || $user->hasRole('super-admin') 
                     || $user->hasPermission('students.edit')
                     || $user->hasPermission('quality.view');
        
        if (!$canAssign) {
            return response()->json([
                'success' => false,
                'message' => __('errors.unauthorized_edit_student')
            ], 403);
        }
        
        $request->validate([
            'parent_id' => 'required|exists:parents,id'
        ]);
        
        $student = Student::findOrFail($id);
        $parent = \App\Models\ParentModel::findOrFail($request->input('parent_id'));
        
        // Check if relationship already exists
        $exists = \DB::table('parent_student')
            ->where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->exists();
        
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This parent is already assigned to this student'
            ], 400);
        }
        
        // Create relationship
        \DB::table('parent_student')->insert([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Parent assigned to student successfully',
            'data' => $student->load('user', 'branch', 'parents.user', 'wallet', 'classes')
        ]);
    }
}
