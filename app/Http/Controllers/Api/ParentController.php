<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use Illuminate\Http\Request;

class ParentController extends Controller
{
    public function index(Request $request)
    {
        // Authorization: Require explicit permission OR teacher accessing parents of their students
        $user = $request->user();
        
        // Super Admin and Admin can view all
        $canViewAll = $user->hasRole('admin') || $user->hasRole('super-admin');
        
        // Or must have explicit permission
        $hasPermission = $user->hasPermission('parents.view_all');
        
        // Teachers can ONLY view parents of students from classes they teach
        $isTeacher = $user->hasRole('teacher');
        
        if (!$canViewAll && !$hasPermission && !$isTeacher) {
            return response()->json([
                'success' => false,
                'message' => __('errors.unauthorized_view_parents')
            ], 403);
        }
        
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $status = $request->input('status');
        $branchId = $request->input('branch_id');
        $sortBy = $request->input('sort_by', 'name');
        $sortDir = $request->input('sort_dir', 'asc');

        $query = ParentModel::with([
            'user',
            'branch',
            'students.user',
            'students.wallet',
            'students.classes' => function($q) {
                $q->where('classes.status', 'active')
                    ->select('classes.id', 'classes.name', 'classes.code', 'classes.status')
                    ->orderBy('classes.name');
            }
        ]);

        // Filter by branch if provided
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // If teacher without permission, only show parents of their students
        if ($isTeacher && !$hasPermission && !$canViewAll) {
            $teacherClassIds = \App\Models\ClassModel::where(function($q) use ($user) {
                $q->where('homeroom_teacher_id', $user->id)
                  ->orWhereHas('schedules', function($sq) use ($user) {
                      $sq->where('teacher_id', $user->id);
                  });
            })->pluck('id')->toArray();
            
            $query->whereHas('students.classes', function($q) use ($teacherClassIds) {
                $q->whereIn('classes.id', $teacherClassIds)
                  ->where('class_students.status', 'active');
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
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
        $sortDir = in_array(strtolower($sortDir), ['asc', 'desc']) ? strtolower($sortDir) : 'asc';

        if ($sortBy === 'name') {
            // Sort by last word of Vietnamese name (e.g., "Nguyễn Văn An" -> sort by "An")
            $query->join('users', 'parents.user_id', '=', 'users.id')
                  ->orderByRaw("SUBSTRING_INDEX(users.name, ' ', -1) {$sortDir}")
                  ->select('parents.*');
        } elseif (in_array($sortBy, ['email', 'google_email'])) {
            // Sort by other user fields
            $query->join('users', 'parents.user_id', '=', 'users.id')
                  ->orderBy("users.{$sortBy}", $sortDir)
                  ->select('parents.*');
        } elseif ($sortBy === 'is_active') {
            $query->orderBy('parents.is_active', $sortDir);
        } else {
            $query->latest();
        }

        $parents = $query->paginate($perPage);

        // Add effective balance for each student
        $parents->getCollection()->transform(function ($parent) {
            if ($parent->students) {
                $parent->students->transform(function ($student) {
                    $student->effective_balance = $student->getEffectiveBalance();
                    return $student;
                });
            }
            return $parent;
        });

        return response()->json([
            'success' => true,
            'data' => $parents
        ]);
    }

    public function show($id)
    {
        $user = request()->user();
        $parent = ParentModel::with([
            'user',
            'branch',
            'students.user',
            'students.wallet',
            'students.classes' => function($q) {
                $q->where('classes.status', 'active')
                    ->select('classes.id', 'classes.name', 'classes.code', 'classes.status')
                    ->orderBy('classes.name');
            }
        ])->findOrFail($id);
        
        // Authorization: Admin, the parent themselves, or staff with permission
        $hasAccess = false;
        
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            $hasAccess = true;
        } elseif ($user->hasPermission('parents.view_all')) {
            $hasAccess = true;
        } elseif ($user->id === $parent->user_id) {
            // Parent viewing themselves
            $hasAccess = true;
        } elseif ($user->hasRole('teacher')) {
            // Teacher can view if they teach any of the parent's children
            $childrenClassIds = $parent->students()
                ->with('classes')
                ->get()
                ->pluck('classes')
                ->flatten()
                ->pluck('id')
                ->unique()
                ->toArray();
                
            $teacherClassIds = \App\Models\ClassModel::where(function($q) use ($user) {
                $q->where('homeroom_teacher_id', $user->id)
                  ->orWhereHas('schedules', function($sq) use ($user) {
                      $sq->where('teacher_id', $user->id);
                  });
            })->pluck('id')->toArray();
            
            $hasAccess = count(array_intersect($childrenClassIds, $teacherClassIds)) > 0;
        }
        
        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => __('errors.unauthorized_view_parent')
            ], 403);
        }

        // Add effective balance for each student
        if ($parent->students) {
            $parent->students->transform(function ($student) {
                $student->effective_balance = $student->getEffectiveBalance();
                return $student;
            });
        }

        return response()->json([
            'success' => true,
            'data' => $parent
        ]);
    }

    /**
     * Search parents by name or phone
     */
    public function search(Request $request)
    {
        $user = $request->user();
        
        // Authorization check
        $canSearch = $user->hasRole('admin') 
                     || $user->hasRole('super-admin') 
                     || $user->hasPermission('parents.view_all')
                     || $user->hasPermission('quality.view');
        
        if (!$canSearch) {
            return response()->json([
                'success' => false,
                'message' => __('errors.unauthorized_search_parents')
            ], 403);
        }
        
        $query = $request->input('query');
        
        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Query parameter is required'
            ], 400);
        }
        
        $parents = ParentModel::with(['user', 'students.user'])
            ->whereHas('user', function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->where('is_active', true)
            ->limit(20)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $parents
        ]);
    }

    /**
     * Update parent status (active/inactive)
     */
    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();
        
        // Authorization: Only admin, super-admin, or users with explicit permission
        $canUpdate = $user->hasRole('admin') 
                     || $user->hasRole('super-admin') 
                     || $user->hasPermission('parents.edit');
        
        if (!$canUpdate) {
            return response()->json([
                'success' => false,
                'message' => __('errors.unauthorized_edit_parent')
            ], 403);
        }
        
        $request->validate([
            'is_active' => 'required|boolean'
        ]);
        
        $parent = ParentModel::findOrFail($id);
        $parent->is_active = $request->input('is_active');
        $parent->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Parent status updated successfully',
            'data' => $parent->load('user', 'branch', 'students.user', 'students.wallet', 'students.classes')
        ]);
    }

    /**
     * Create a new parent and optionally assign to student
     */
    public function createAndAssign(Request $request)
    {
        $user = $request->user();
        
        // Authorization check
        $canCreate = $user->hasRole('admin') 
                     || $user->hasRole('super-admin') 
                     || $user->hasPermission('parents.create')
                     || $user->hasPermission('quality.view');
        
        if (!$canCreate) {
            return response()->json([
                'success' => false,
                'message' => __('errors.unauthorized_create_parent')
            ], 403);
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'student_id' => 'nullable|exists:students,id'
        ]);
        
        \DB::beginTransaction();
        
        try {
            // Create user
            $userModel = \App\Models\User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'password' => \Hash::make('password123'), // Default password
                'branch_id' => $user->branch_id,
            ]);
            
            // Assign parent role
            $parentRole = \DB::table('roles')->where('name', 'parent')->first();
            if ($parentRole) {
                \DB::table('user_roles')->insert([
                    'user_id' => $userModel->id,
                    'role_id' => $parentRole->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            // Create parent record
            $parent = ParentModel::create([
                'user_id' => $userModel->id,
                'branch_id' => $user->branch_id,
                'is_active' => true
            ]);
            
            // Assign to student if provided
            if ($request->has('student_id')) {
                \DB::table('parent_student')->insert([
                    'parent_id' => $parent->id,
                    'student_id' => $request->input('student_id'),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            \DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Parent created and assigned successfully',
                'data' => $parent->load('user', 'students.user')
            ]);
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error creating parent: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create parent: ' . $e->getMessage()
            ], 500);
        }
    }
}
