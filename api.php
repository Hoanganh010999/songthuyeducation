<?php

use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CalendarEventController;
use App\Http\Controllers\Api\TrialStudentController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerChildController;
use App\Http\Controllers\Api\CustomerInteractionController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EmployeeInvitationController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\QualityManagementController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\Quality\SessionController;
use App\Http\Controllers\Api\TranslationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ClassSettingsController;
use App\Http\Controllers\Api\ClassManagementController;
use App\Http\Controllers\Api\ClassDetailController;
use App\Http\Controllers\Api\LessonPlanController;
use App\Http\Controllers\Api\ValuationFormsController;
use App\Http\Controllers\Api\HolidaysController;
use App\Http\Controllers\Api\SystemSettingsController;
use App\Http\Controllers\Api\ScheduledTasksController;
use App\Http\Controllers\Api\HomeworkExerciseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Authentication Routes
Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Email hoặc mật khẩu không đúng'
        ], 401);
    }

    $token = $user->createToken('auth-token')->plainTextToken;
    
    // Load user data
    $user->load([
        'roles.permissions',
        'branches',
        'departments' => function($q) {
            $q->with(['branch'])
              ->wherePivot('status', 'active');
        }
    ]);

    // Determine branch_id based on user type (priority order: student > parent > employee)
    $branchId = null;

    // 1. Check if user is a student
    $studentRecord = \DB::table('students')
        ->where('user_id', $user->id)
        ->where('is_active', true)
        ->first();

    if ($studentRecord && $studentRecord->branch_id) {
        $branchId = $studentRecord->branch_id;
    }

    // 2. If not student, check if user is a parent
    if (!$branchId) {
        $parentRecord = \DB::table('parents')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if ($parentRecord && $parentRecord->branch_id) {
            $branchId = $parentRecord->branch_id;
        }
    }

    // 3. If neither student nor parent, check employee branch assignment
    if (!$branchId) {
        $firstBranch = $user->branches()->first();
        $branchId = $firstBranch ? $firstBranch->id : null;
    }
    
    $allPermissions = collect();
    
    // 1. Direct role permissions (global)
    foreach ($user->roles as $role) {
        if ($role->permissions) {
            $allPermissions = $allPermissions->merge($role->permissions);
        }
    }
    
    // 2. Branch-specific permissions from positions
    if ($branchId) {
        // Get positions only from departments in first/primary branch
        $positionIds = \DB::table('department_user')
            ->join('departments', 'department_user.department_id', '=', 'departments.id')
            ->where('department_user.user_id', $user->id)
            ->where('department_user.status', 'active')
            ->where('departments.branch_id', $branchId)
            ->whereNotNull('department_user.position_id')
            ->pluck('department_user.position_id')
            ->unique();
        
        if ($positionIds->isNotEmpty()) {
            // Get permissions from these positions
            $positionPermissions = \DB::table('position_role')
                ->join('permission_role', 'position_role.role_id', '=', 'permission_role.role_id')
                ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
                ->whereIn('position_role.position_id', $positionIds)
                ->where('permissions.is_active', true)
                ->select('permissions.*')
                ->distinct()
                ->get();
            
            $allPermissions = $allPermissions->merge($positionPermissions);
        }
    }
    
    // 3. Permissions from student role (if user is a student)
    $studentRole = \DB::table('roles')->where('name', 'student')->first();
    if ($studentRole) {
        $isStudent = \DB::table('students')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        if ($isStudent) {
            $studentPermissions = \DB::table('permission_role')
                ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
                ->where('permission_role.role_id', $studentRole->id)
                ->where('permissions.is_active', true)
                ->select('permissions.*')
                ->get();

            $allPermissions = $allPermissions->merge($studentPermissions);
        }
    }

    // 4. Permissions from parent role (if user is a parent)
    $parentRole = \DB::table('roles')->where('name', 'parent')->first();
    if ($parentRole) {
        $isParent = \DB::table('parents')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        if ($isParent) {
            $parentPermissions = \DB::table('permission_role')
                ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
                ->where('permission_role.role_id', $parentRole->id)
                ->where('permissions.is_active', true)
                ->select('permissions.*')
                ->get();

            $allPermissions = $allPermissions->merge($parentPermissions);
        }
    }

    // Remove duplicates and only active permissions
    $allPermissions = $allPermissions
        ->where('is_active', true)
        ->unique('id')
        ->values();

    // Add computed permissions to user object
    $userData = $user->toArray();
    $userData['all_permissions'] = $allPermissions->toArray();
    $userData['current_branch_id'] = $branchId;

    return response()->json([
        'success' => true,
        'message' => 'Đăng nhập thành công',
        'token' => $token,
        'user' => $userData
    ]);
});

Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();
    
    return response()->json([
        'success' => true,
        'message' => 'Đăng xuất thành công'
    ]);
});

// WebSocket token verification endpoint
Route::post('/auth/verify-token', function (Request $request) {
    try {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token'
            ], 401);
        }
        
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Token verification failed'
        ], 401);
    }
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    $user = $request->user();

    // Get current branch from request (from axios interceptor)
    $branchId = $request->input('branch_id');

    // If no branch_id provided, determine from user type
    if (!$branchId) {
        // 1. Check if user is a student
        $studentRecord = \DB::table('students')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if ($studentRecord && $studentRecord->branch_id) {
            $branchId = $studentRecord->branch_id;
        }

        // 2. If not student, check if user is a parent
        if (!$branchId) {
            $parentRecord = \DB::table('parents')
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if ($parentRecord && $parentRecord->branch_id) {
                $branchId = $parentRecord->branch_id;
            }
        }

        // 3. If neither student nor parent, check employee branch assignment
        if (!$branchId) {
            $firstBranch = \App\Models\User::find($user->id)->branches()->first();
            $branchId = $firstBranch ? $firstBranch->id : null;
        }
    }

    $user->load([
        'roles.permissions',
        'branches',
        'departments' => function($q) {
            $q->with(['branch'])
              ->wherePivot('status', 'active');
        }
    ]);
    
    $allPermissions = collect();
    
    // 1. Direct role permissions (global)
    foreach ($user->roles as $role) {
        if ($role->permissions) {
            $allPermissions = $allPermissions->merge($role->permissions);
        }
    }
    
    // 2. Branch-specific permissions from positions
    if ($branchId) {
        // Get positions only from departments in current branch
        $positionIds = \DB::table('department_user')
            ->join('departments', 'department_user.department_id', '=', 'departments.id')
            ->where('department_user.user_id', $user->id)
            ->where('department_user.status', 'active')
            ->where('departments.branch_id', $branchId)
            ->whereNotNull('department_user.position_id')
            ->pluck('department_user.position_id')
            ->unique();
        
        if ($positionIds->isNotEmpty()) {
            // Get permissions from these positions
            $positionPermissions = \DB::table('position_role')
                ->join('permission_role', 'position_role.role_id', '=', 'permission_role.role_id')
                ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
                ->whereIn('position_role.position_id', $positionIds)
                ->where('permissions.is_active', true)
                ->select('permissions.*')
                ->distinct()
                ->get();
            
            $allPermissions = $allPermissions->merge($positionPermissions);
        }
    } else {
        // No branch context - load positions from ALL departments
        $user->load(['positions' => function($q) {
            $q->with('roles.permissions')
              ->wherePivot('status', 'active');
        }]);
        
        foreach ($user->positions as $position) {
            if ($position->roles) {
                foreach ($position->roles as $role) {
                    if ($role->permissions) {
                        $allPermissions = $allPermissions->merge($role->permissions);
                    }
                }
            }
        }
    }
    
    // 3. Permissions from student role (if user is a student)
    $studentRole = \DB::table('roles')->where('name', 'student')->first();
    if ($studentRole) {
        $isStudent = \DB::table('students')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        if ($isStudent) {
            $studentPermissions = \DB::table('permission_role')
                ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
                ->where('permission_role.role_id', $studentRole->id)
                ->where('permissions.is_active', true)
                ->select('permissions.*')
                ->get();

            $allPermissions = $allPermissions->merge($studentPermissions);
        }
    }

    // 4. Permissions from parent role (if user is a parent)
    $parentRole = \DB::table('roles')->where('name', 'parent')->first();
    if ($parentRole) {
        $isParent = \DB::table('parents')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        if ($isParent) {
            $parentPermissions = \DB::table('permission_role')
                ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
                ->where('permission_role.role_id', $parentRole->id)
                ->where('permissions.is_active', true)
                ->select('permissions.*')
                ->get();

            $allPermissions = $allPermissions->merge($parentPermissions);
        }
    }

    // Remove duplicates and only active permissions
    $allPermissions = $allPermissions
        ->where('is_active', true)
        ->unique('id')
        ->values();

    // Add computed permissions to user object
    $userData = $user->toArray();
    $userData['all_permissions'] = $allPermissions->toArray();
    $userData['current_branch_id'] = $branchId ? (int)$branchId : null;

    return response()->json([
        'success' => true,
        'data' => $userData
    ]);
});

Route::middleware('auth:sanctum')->get('/user/branch-roles', [UserController::class, 'getBranchRoles']);
Route::middleware('auth:sanctum')->post('/change-password', [UserController::class, 'changePassword']);

// Public routes (không cần authentication)
Route::apiResource('customers', CustomerController::class);

// Languages - Public (for language switcher)
Route::get('/languages', [LanguageController::class, 'index']);
Route::get('/languages/{code}/translations', [LanguageController::class, 'getTranslationsByCode']);

// Calendar Today - Public (xem lịch học nhanh không cần đăng nhập)
Route::get('/calendar/today', [CalendarEventController::class, 'today']);
// Protected routes (cần authentication)
Route::middleware('auth:sanctum')->group(function () {
    
    // Main Dashboard (Statistics)
    Route::get('/dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'index']);

    // Customers Management
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])
            ->middleware(['branch.access', 'permission:customers.view']);
        
        Route::get('/kanban', [CustomerController::class, 'kanban'])
            ->middleware(['branch.access', 'permission:customers.view']);
        
        Route::get('/statistics', [CustomerController::class, 'statistics'])
            ->middleware(['branch.access', 'permission:customers.view']);
        
        Route::post('/', [CustomerController::class, 'store'])
            ->middleware(['branch.access', 'permission:customers.create']);
        
        Route::get('/{id}', [CustomerController::class, 'show'])
            ->middleware(['permission:customers.view']);
        
        Route::put('/{id}', [CustomerController::class, 'update'])
            ->middleware(['permission:customers.edit']);
        
        Route::delete('/{id}', [CustomerController::class, 'destroy'])
            ->middleware(['permission:customers.delete']);
        
        Route::post('/{id}/move-stage', [CustomerController::class, 'moveStage'])
            ->middleware(['permission:customers.edit']);
        
        // Customer Interactions
        Route::get('/{customerId}/interactions', [CustomerInteractionController::class, 'index'])
            ->middleware(['permission:customers.view']);
        
        Route::post('/{customerId}/interactions', [CustomerInteractionController::class, 'store'])
            ->middleware(['permission:customers.create']);
        
        Route::put('/{customerId}/interactions/{interactionId}', [CustomerInteractionController::class, 'update'])
            ->middleware(['permission:customers.edit']);
        
        Route::delete('/{customerId}/interactions/{interactionId}', [CustomerInteractionController::class, 'destroy'])
            ->middleware(['permission:customers.delete']);
        
        // Customer Children
        Route::get('/{customerId}/children', [CustomerChildController::class, 'index'])
            ->middleware(['permission:customers.view']);
        
        Route::post('/{customerId}/children', [CustomerChildController::class, 'store'])
            ->middleware(['permission:customers.create']);
        
        Route::put('/{customerId}/children/{childId}', [CustomerChildController::class, 'update'])
            ->middleware(['permission:customers.edit']);
        
        Route::delete('/{customerId}/children/{childId}', [CustomerChildController::class, 'destroy'])
            ->middleware(['permission:customers.delete']);
    });

    // Work Management Module
    Route::prefix('work')->group(function () {
        // Work Items
        Route::get('/items', [\App\Http\Controllers\Api\WorkItemController::class, 'index'])
            ->middleware(['permission:work_items.view_own']);

        Route::post('/items', [\App\Http\Controllers\Api\WorkItemController::class, 'store'])
            ->middleware(['permission:work_items.create']);

        Route::get('/items/{id}', [\App\Http\Controllers\Api\WorkItemController::class, 'show'])
            ->middleware(['permission:work_items.view_own']);

        Route::put('/items/{id}', [\App\Http\Controllers\Api\WorkItemController::class, 'update'])
            ->middleware(['permission:work_items.edit']);

        Route::delete('/items/{id}', [\App\Http\Controllers\Api\WorkItemController::class, 'destroy'])
            ->middleware(['permission:work_items.delete']);

        Route::post('/items/{id}/assign', [\App\Http\Controllers\Api\WorkItemController::class, 'assign'])
            ->middleware(['permission:work_items.assign']);

        Route::post('/items/{id}/update-status', [\App\Http\Controllers\Api\WorkItemController::class, 'updateStatus'])
            ->middleware(['permission:work_items.edit']);

        Route::get('/items/{id}/timeline', [\App\Http\Controllers\Api\WorkItemController::class, 'timeline'])
            ->middleware(['permission:work_items.view_own']);

        // Assignments
        Route::post('/assignments/{id}/accept', [\App\Http\Controllers\Api\WorkAssignmentController::class, 'accept']);
        Route::post('/assignments/{id}/decline', [\App\Http\Controllers\Api\WorkAssignmentController::class, 'decline']);
        Route::post('/assignments/{id}/request-support', [\App\Http\Controllers\Api\WorkAssignmentController::class, 'requestSupport']);

        // Discussions
        Route::post('/items/{id}/discussions', [\App\Http\Controllers\Api\WorkDiscussionController::class, 'store']);
        Route::put('/discussions/{id}', [\App\Http\Controllers\Api\WorkDiscussionController::class, 'update']);
        Route::delete('/discussions/{id}', [\App\Http\Controllers\Api\WorkDiscussionController::class, 'destroy']);

        // Submissions
        Route::post('/items/{id}/submit', [\App\Http\Controllers\Api\WorkSubmissionController::class, 'submit'])
            ->middleware(['permission:work_items.submit']);

        Route::post('/submissions/{id}/review', [\App\Http\Controllers\Api\WorkSubmissionController::class, 'review'])
            ->middleware(['permission:work_items.review']);

        Route::post('/submissions/{id}/approve', [\App\Http\Controllers\Api\WorkSubmissionController::class, 'approve'])
            ->middleware(['permission:work_items.approve']);

        Route::post('/submissions/{id}/request-revision', [\App\Http\Controllers\Api\WorkSubmissionController::class, 'requestRevision'])
            ->middleware(['permission:work_items.review']);

        // Attachments
        Route::post('/attachments/upload', [\App\Http\Controllers\Api\WorkAttachmentController::class, 'upload']);
        Route::delete('/attachments/{id}', [\App\Http\Controllers\Api\WorkAttachmentController::class, 'destroy']);

        // Dashboard & Statistics
        Route::get('/dashboard', [\App\Http\Controllers\Api\WorkDashboardController::class, 'index'])
            ->middleware(['permission:work_management.dashboard']);

        Route::get('/statistics', [\App\Http\Controllers\Api\WorkDashboardController::class, 'statistics'])
            ->middleware(['permission:work_management.statistics']);

        Route::get('/calendar', [\App\Http\Controllers\Api\WorkCalendarController::class, 'index'])
            ->middleware(['permission:work_management.calendar']);

        // Tags
        Route::get('/tags', [\App\Http\Controllers\Api\WorkTagController::class, 'index']);
        Route::post('/tags', [\App\Http\Controllers\Api\WorkTagController::class, 'store'])
            ->middleware(['permission:work_items.create']);
    });

    // Branches Management
    Route::prefix('branches')->group(function () {
        Route::get('/', [BranchController::class, 'index'])
            ->middleware('permission:branches.view');
        
        // Public endpoint for dropdowns/filters - no permission required
        Route::get('/list', [BranchController::class, 'list']);
        
        Route::post('/', [BranchController::class, 'store'])
            ->middleware('permission:branches.create');
        
        Route::get('/{id}', [BranchController::class, 'show'])
            ->middleware('permission:branches.view');
        
        Route::put('/{id}', [BranchController::class, 'update'])
            ->middleware('permission:branches.edit');
        
        Route::delete('/{id}', [BranchController::class, 'destroy'])
            ->middleware('permission:branches.delete');
        
        Route::get('/{id}/users', [BranchController::class, 'users'])
            ->middleware('permission:branches.view');
        
        Route::get('/{id}/statistics', [BranchController::class, 'statistics'])
            ->middleware('permission:branches.view');
    });

    // Users Management
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])
            ->middleware('permission:users.view');
        
        Route::get('/list', [UserController::class, 'list']); // Dropdown list (no pagination)
        
        Route::get('/branch-employees', [UserController::class, 'branchEmployees']); // Branch employees
        
        Route::get('/search-by-phone', [UserController::class, 'searchByPhone']); // Search by phone

        // Update contact info (email, phone) - No permission required
        Route::patch('/{id}/contact', [UserController::class, 'updateContact']);

        // Update Google Email - No permission required
        Route::patch('/{id}/google-email', [UserController::class, 'updateGoogleEmail']);

        // Update English Name - No permission required
        Route::patch('/{id}/english-name', [UserController::class, 'updateEnglishName']);

        Route::post('/', [UserController::class, 'store'])
            ->middleware('permission:users.create');
        
        Route::get('/{id}', [UserController::class, 'show'])
            ->middleware('permission:users.view');
        
        Route::put('/{id}', [UserController::class, 'update'])
            ->middleware('permission:users.edit');
        
        Route::delete('/{id}', [UserController::class, 'destroy'])
            ->middleware('permission:users.delete');
        
        Route::post('/{id}/assign-role', [UserController::class, 'assignRole'])
            ->middleware('permission:users.assign-role');
        
        Route::post('/{id}/remove-role', [UserController::class, 'removeRole'])
            ->middleware('permission:users.assign-role');
        
        Route::post('/{id}/reset-password', [UserController::class, 'resetPassword'])
            ->middleware('permission:users.edit');
        
        // Google Drive Email Management
        Route::post('/{id}/google-email', [\App\Http\Controllers\Api\UserGoogleDriveController::class, 'assignGoogleEmail'])
            ->middleware('permission:users.edit');
        
        Route::post('/{id}/google-email/force', [\App\Http\Controllers\Api\UserGoogleDriveController::class, 'assignGoogleEmailForce'])
            ->middleware('permission:users.edit');
        
        Route::put('/{id}/google-email', [\App\Http\Controllers\Api\UserGoogleDriveController::class, 'updateGoogleEmail'])
            ->middleware('permission:users.edit');
        
        Route::delete('/{id}/google-email', [\App\Http\Controllers\Api\UserGoogleDriveController::class, 'removeGoogleEmail'])
            ->middleware('permission:users.edit');
    });

    // Roles Management
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index'])
            ->middleware('permission:roles.view');
        
        Route::post('/', [RoleController::class, 'store'])
            ->middleware('permission:roles.create');
        
        Route::get('/{id}', [RoleController::class, 'show'])
            ->middleware('permission:roles.view');
        
        Route::put('/{id}', [RoleController::class, 'update'])
            ->middleware('permission:roles.edit');
        
        Route::delete('/{id}', [RoleController::class, 'destroy'])
            ->middleware('permission:roles.delete');
        
        // Permissions management for role
        Route::get('/{id}/permissions', [RoleController::class, 'getPermissions'])
            ->middleware('permission:roles.view');
        
        Route::post('/{id}/permissions', [RoleController::class, 'syncPermissions'])
            ->middleware('permission:roles.assign-permission');
        
        Route::post('/{id}/assign-permission', [RoleController::class, 'assignPermission'])
            ->middleware('permission:roles.assign-permission');
        
        Route::post('/{id}/revoke-permission', [RoleController::class, 'revokePermission'])
            ->middleware('permission:roles.assign-permission');
    });

    // Permissions Management
    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::get('/modules', [PermissionController::class, 'modules']);
        Route::get('/by-module/{module}', [PermissionController::class, 'byModule']);
        Route::get('/{id}', [PermissionController::class, 'show']);
        
        // Chỉ super-admin mới có thể tạo/sửa/xóa permissions
        Route::post('/', [PermissionController::class, 'store'])
            ->middleware('role:super-admin');
        
        Route::put('/{id}', [PermissionController::class, 'update'])
            ->middleware('role:super-admin');
        
        Route::delete('/{id}', [PermissionController::class, 'destroy'])
            ->middleware('role:super-admin');
    });

    // Settings - Languages Management (Super Admin only)
    Route::prefix('settings')->middleware('role:super-admin')->group(function () {
        
        // Languages Management
        Route::prefix('languages')->group(function () {
            Route::get('/', [LanguageController::class, 'all']);
            Route::post('/', [LanguageController::class, 'store']);
            Route::get('/{language}', [LanguageController::class, 'show']);
            Route::put('/{language}', [LanguageController::class, 'update']);
            Route::delete('/{language}', [LanguageController::class, 'destroy']);
            Route::post('/{language}/set-default', [LanguageController::class, 'setDefault']);
            Route::get('/{language}/translations', [LanguageController::class, 'getTranslations']);
        });

        // Translations Management
        Route::prefix('translations')->group(function () {
            Route::get('/', [TranslationController::class, 'index']);
            Route::get('/groups', [TranslationController::class, 'groups']);
            Route::post('/', [TranslationController::class, 'store']);
            Route::get('/{translation}', [TranslationController::class, 'show']);
            Route::put('/{translation}', [TranslationController::class, 'update']);
            Route::delete('/{translation}', [TranslationController::class, 'destroy']);
            Route::post('/bulk-update', [TranslationController::class, 'bulkUpdate']);
            Route::post('/sync-languages', [TranslationController::class, 'syncLanguages']);
        });
    });
    
    // Customer Settings - Read-only routes (for dropdowns)
    Route::prefix('customers/settings')->group(function () {
        Route::get('/interaction-types', [App\Http\Controllers\Api\CustomerSettingsController::class, 'getInteractionTypes']);
        Route::get('/interaction-results', [App\Http\Controllers\Api\CustomerSettingsController::class, 'getInteractionResults']);
        Route::get('/sources', [App\Http\Controllers\Api\CustomerSettingsController::class, 'getCustomerSources']);
    });
    
    // Customer Settings - Management routes (need permission)
    Route::prefix('customers/settings')->middleware(['permission:customers.settings'])->group(function () {
        // Interaction Types
        Route::post('/interaction-types', [App\Http\Controllers\Api\CustomerSettingsController::class, 'storeInteractionType']);
        Route::put('/interaction-types/{id}', [App\Http\Controllers\Api\CustomerSettingsController::class, 'updateInteractionType']);
        Route::delete('/interaction-types/{id}', [App\Http\Controllers\Api\CustomerSettingsController::class, 'deleteInteractionType']);
        
        // Interaction Results
        Route::post('/interaction-results', [App\Http\Controllers\Api\CustomerSettingsController::class, 'storeInteractionResult']);
        Route::put('/interaction-results/{id}', [App\Http\Controllers\Api\CustomerSettingsController::class, 'updateInteractionResult']);
        Route::delete('/interaction-results/{id}', [App\Http\Controllers\Api\CustomerSettingsController::class, 'deleteInteractionResult']);
        
        // Customer Sources
        Route::post('/sources', [App\Http\Controllers\Api\CustomerSettingsController::class, 'storeCustomerSource']);
        Route::put('/sources/{id}', [App\Http\Controllers\Api\CustomerSettingsController::class, 'updateCustomerSource']);
        Route::delete('/sources/{id}', [App\Http\Controllers\Api\CustomerSettingsController::class, 'deleteCustomerSource']);
    });

    // Department Settings for Customer module (permission checked in controller)
    Route::prefix('customers/settings/department')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\CustomerSettingsController::class, 'getDepartmentSettings']);
        Route::post('/', [App\Http\Controllers\Api\CustomerSettingsController::class, 'setDepartmentSettings']);
        Route::delete('/', [App\Http\Controllers\Api\CustomerSettingsController::class, 'removeDepartmentSettings']);
        Route::get('/access-level', [App\Http\Controllers\Api\CustomerSettingsController::class, 'checkAccessLevel']);
    });

    // Calendar Events Management
    Route::prefix('calendar')->group(function () {
        Route::get('/events', [CalendarEventController::class, 'index'])
            ->middleware('permission:calendar.view');
        
        Route::get('/events/upcoming', [CalendarEventController::class, 'upcoming'])
            ->middleware('permission:calendar.view');
        
        Route::get('/events/overdue', [CalendarEventController::class, 'overdue'])
            ->middleware('permission:calendar.view');
        
        Route::get('/categories', [CalendarEventController::class, 'categories'])
            ->middleware('permission:calendar.view');
        
        Route::post('/events', [CalendarEventController::class, 'store'])
            ->middleware('permission:calendar.create');
        
        Route::get('/events/{id}', [CalendarEventController::class, 'show'])
            ->middleware('permission:calendar.view');
        
        Route::put('/events/{id}', [CalendarEventController::class, 'update'])
            ->middleware('permission:calendar.edit');
        
        Route::delete('/events/{id}', [CalendarEventController::class, 'destroy'])
            ->middleware('permission:calendar.delete');
        
        // Placement Test Routes
        Route::post('/placement-test/customer/{customerId}', [CalendarEventController::class, 'createPlacementTestForCustomer'])
            ->middleware('permission:calendar.create');
        
        Route::post('/placement-test/child/{childId}', [CalendarEventController::class, 'createPlacementTestForChild'])
            ->middleware('permission:calendar.create');
        
        // 🔥 NEW: Delete placement test (with Zalo notification) - MUST be before PUT route to avoid conflict
        Route::delete('/placement-test/{id}/delete', [CalendarEventController::class, 'deletePlacementTest'])
            ->middleware('permission:calendar.delete');
        
        Route::put('/placement-test/{eventId}/result', [CalendarEventController::class, 'updateTestResult'])
            ->middleware('permission:calendar.edit');
        
        // Feedback Routes
        Route::post('/events/{eventId}/submit-test-result', [CalendarEventController::class, 'submitTestResult'])
            ->middleware('permission:calendar.submit_feedback');
        
        Route::post('/events/{eventId}/submit-trial-feedback', [CalendarEventController::class, 'submitTrialFeedback'])
            ->middleware('permission:calendar.submit_feedback');
        
        Route::post('/events/{eventId}/assign-teacher', [CalendarEventController::class, 'assignTeacher'])
            ->middleware('permission:calendar.assign_teacher');
    });

    // Trial Students Routes
    Route::prefix('trial-students')->group(function () {
        Route::get('/available-classes', [TrialStudentController::class, 'getAvailableClasses'])
            ->middleware('permission:calendar.create');
        Route::get('/classes/{classId}/sessions', [TrialStudentController::class, 'getAvailableSessions'])
            ->middleware('permission:calendar.create');
        Route::post('/register', [TrialStudentController::class, 'register'])
            ->middleware('permission:calendar.create');
        Route::get('/sessions/{sessionId}', [TrialStudentController::class, 'getSessionTrialStudents'])
            ->middleware('permission:classes.view');
        Route::put('/{id}/status', [TrialStudentController::class, 'updateStatus'])
            ->middleware('permission:classes.edit');
        Route::delete('/{id}', [TrialStudentController::class, 'cancel'])
            ->middleware('permission:classes.edit');
    });
    
    // HR Module Routes
    Route::prefix('hr')->middleware('permission:hr.view')->group(function () {
        // Departments
        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::get('/departments/tree', [DepartmentController::class, 'getTree']);
        Route::post('/departments', [DepartmentController::class, 'store'])
            ->middleware('permission:departments.create');
        Route::get('/departments/{department}', [DepartmentController::class, 'show']);
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])
            ->middleware('permission:departments.edit');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])
            ->middleware('permission:departments.delete');
        Route::post('/departments/{department}/assign', [DepartmentController::class, 'assignUser'])
            ->middleware('permission:employees.assign');
        Route::post('/departments/{department}/remove', [DepartmentController::class, 'removeUser'])
            ->middleware('permission:employees.assign');
        Route::post('/departments/{department}/set-manager', [DepartmentController::class, 'setManager'])
            ->middleware('permission:employees.assign');
        Route::post('/departments/{department}/update-user', [DepartmentController::class, 'updateUser'])
            ->middleware('permission:employees.assign');
        
        // Positions (Job Titles)
        Route::get('/positions', [PositionController::class, 'index']);
        Route::post('/positions', [PositionController::class, 'store'])
            ->middleware('permission:hr.manage');
        Route::get('/positions/{position}', [PositionController::class, 'show']);
        Route::put('/positions/{position}', [PositionController::class, 'update'])
            ->middleware('permission:hr.manage');
        Route::delete('/positions/{position}', [PositionController::class, 'destroy'])
            ->middleware('permission:hr.manage');
        
        // Employee Invitations (HR Management)
        Route::get('/employee-invitations', [EmployeeInvitationController::class, 'index']);
        Route::post('/employee-invitations', [EmployeeInvitationController::class, 'sendInvitation']);
    });
    
    // Employee Invitation Actions (No HR permission required - user responding to their own invitation)
    Route::prefix('hr')->group(function () {
        Route::post('/employee-invitations/{token}/accept', [EmployeeInvitationController::class, 'acceptInvitation']);
        Route::post('/employee-invitations/{token}/reject', [EmployeeInvitationController::class, 'rejectInvitation']);
    });
    
    // Quality Management Module Routes
    Route::prefix('quality')->middleware('permission:quality.view')->group(function () {
        // Teachers Management
        Route::get('/positions', [QualityManagementController::class, 'getPositions']);
        Route::get('/departments', [QualityManagementController::class, 'getDepartments']);
        Route::get('/teachers', [QualityManagementController::class, 'getTeachers']);
        Route::get('/teachers/settings', [QualityManagementController::class, 'getTeacherSettings']);
        Route::post('/teachers/settings', [QualityManagementController::class, 'saveTeacherSettings'])
            ->middleware('permission:teachers.settings');
        
        // Subjects Management
        Route::get('/subjects', [SubjectController::class, 'index'])
            ->withoutMiddleware('permission:quality.view'); // Teachers can view their subjects
        Route::post('/subjects', [SubjectController::class, 'store'])
            ->middleware('permission:subjects.create');
        Route::get('/subjects/{subject}', [SubjectController::class, 'show'])
            ->middleware('permission:subjects.view');
        Route::put('/subjects/{subject}', [SubjectController::class, 'update'])
            ->middleware('permission:subjects.edit');
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])
            ->middleware('permission:subjects.delete');
        Route::post('/subjects/{subject}/assign-teacher', [SubjectController::class, 'assignTeacher'])
            ->middleware('permission:subjects.assign_teachers');
        Route::post('/subjects/{subject}/remove-teacher', [SubjectController::class, 'removeTeacher'])
            ->middleware('permission:subjects.assign_teachers');
        Route::post('/subjects/{subject}/set-head-teacher', [SubjectController::class, 'setHeadTeacher'])
            ->middleware('permission:subjects.assign_teachers');
        
        // Classes Management (proxy to main classes routes for consistency)
        Route::get('/classes', [ClassManagementController::class, 'index'])
            ->middleware('permission:classes.view');
        Route::get('/classes/{id}', [ClassManagementController::class, 'show'])
            ->middleware('permission:classes.view');
        Route::get('/classes/{id}/students', [ClassDetailController::class, 'getStudents'])
            ->withoutMiddleware('permission:quality.view');
            // Authorization handled in controller - allows teachers/students/parents
        Route::post('/classes/{id}/students', [ClassDetailController::class, 'addStudent'])
            ->withoutMiddleware('permission:quality.view');
            // Authorization handled in controller - allows teachers of this class
        Route::get('/classes/{classId}/sessions/{sessionId}/homework-submissions', [\App\Http\Controllers\Api\ClassLessonSessionController::class, 'getHomeworkSubmissions'])
            ->withoutMiddleware('permission:quality.view');
            // Authorization handled in controller - allows teachers/students/parents

        // Attendance & Evaluation Management
        Route::get('/classes/{classId}/sessions/{sessionId}/attendance', [\App\Http\Controllers\Api\ClassLessonSessionController::class, 'getAttendance'])
            ->withoutMiddleware('permission:quality.view');
            // Authorization handled in controller - allows teachers
        Route::post('/classes/{classId}/sessions/{sessionId}/attendance', [\App\Http\Controllers\Api\ClassLessonSessionController::class, 'saveAttendance'])
            ->withoutMiddleware('permission:quality.view');
            // Authorization handled in controller - allows teachers
        Route::post('/classes/{classId}/sessions/{sessionId}/evaluations', [\App\Http\Controllers\Api\ClassLessonSessionController::class, 'saveEvaluations'])
            ->withoutMiddleware('permission:quality.view');
            // Authorization handled in controller - allows teachers
        
        // Quick Attendance & Comments (with specific permission checks)
        Route::get('/sessions/{sessionId}/quick-attendance', [\App\Http\Controllers\Api\ClassLessonSessionController::class, 'getQuickAttendance'])
            ->withoutMiddleware('permission:quality.view')
            ->middleware('permission:attendance.quick_mark');
        Route::post('/sessions/{sessionId}/quick-attendance', [\App\Http\Controllers\Api\ClassLessonSessionController::class, 'saveQuickAttendance'])
            ->withoutMiddleware('permission:quality.view')
            ->middleware('permission:attendance.quick_mark');
        Route::get('/sessions/{sessionId}/quick-comments', [\App\Http\Controllers\Api\ClassLessonSessionController::class, 'getQuickComments'])
            ->withoutMiddleware('permission:quality.view')
            ->middleware('permission:class_comments.manage');
        Route::post('/sessions/{sessionId}/quick-comments', [\App\Http\Controllers\Api\ClassLessonSessionController::class, 'saveQuickComments'])
            ->withoutMiddleware('permission:quality.view')
            ->middleware('permission:class_comments.manage');
            // Authorization handled in controller - allows teachers
        Route::get('/classes/{classId}/student-stats', [\App\Http\Controllers\Api\ClassLessonSessionController::class, 'getStudentStats'])
            ->withoutMiddleware('permission:quality.view');
            // Authorization handled in controller - allows teachers
        Route::post('/classes/{classId}/sessions/{sessionId}/send-attendance-notification', [\App\Http\Controllers\Api\ClassLessonSessionController::class, 'sendAttendanceNotification'])
            ->withoutMiddleware('permission:quality.view');
            // Authorization handled in controller - allows teachers
        Route::post('/classes/{classId}/sessions/{sessionId}/send-evaluation-notification', [\App\Http\Controllers\Api\ClassLessonSessionController::class, 'sendEvaluationNotification'])
            ->withoutMiddleware('permission:quality.view');
            // Authorization handled in controller - allows teachers

        // Students & Parents Lists
        Route::get('/students/me', [\App\Http\Controllers\Api\StudentController::class, 'me']); // Must be before /{id}
        Route::get('/students/search-by-code', [\App\Http\Controllers\Api\StudentController::class, 'searchByCode']); // Must be before /{id}
        Route::get('/students', [\App\Http\Controllers\Api\StudentController::class, 'index'])
            ->withoutMiddleware('permission:quality.view'); // Allow teachers for mention system
        Route::get('/students/{id}', [\App\Http\Controllers\Api\StudentController::class, 'show']);
        Route::patch('/students/{id}/status', [\App\Http\Controllers\Api\StudentController::class, 'updateStatus'])
            ->middleware('permission:quality.view');
        Route::post('/students/{id}/assign-parent', [\App\Http\Controllers\Api\StudentController::class, 'assignParent'])
            ->middleware('permission:quality.view');
        Route::get('/parents', [\App\Http\Controllers\Api\ParentController::class, 'index']);
        Route::get('/parents/search', [\App\Http\Controllers\Api\ParentController::class, 'search'])
            ->middleware('permission:quality.view');
        Route::post('/parents/create', [\App\Http\Controllers\Api\ParentController::class, 'createAndAssign'])
            ->middleware('permission:quality.view');
        Route::patch('/parents/{id}/status', [\App\Http\Controllers\Api\ParentController::class, 'updateStatus'])
            ->middleware('permission:quality.view');
        Route::get('/parents/{id}', [\App\Http\Controllers\Api\ParentController::class, 'show']);
        
        // Attendance Fee Policy (Settings)
        Route::prefix('attendance-fee-policies')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\AttendanceFeePolicyController::class, 'index']);
            Route::get('/active', [\App\Http\Controllers\Api\AttendanceFeePolicyController::class, 'getActive']);
            Route::post('/', [\App\Http\Controllers\Api\AttendanceFeePolicyController::class, 'store'])
                ->middleware('permission:quality.manage_settings');
            Route::get('/{attendanceFeePolicy}', [\App\Http\Controllers\Api\AttendanceFeePolicyController::class, 'show']);
            Route::put('/{attendanceFeePolicy}', [\App\Http\Controllers\Api\AttendanceFeePolicyController::class, 'update'])
                ->middleware('permission:quality.manage_settings');
            Route::delete('/{attendanceFeePolicy}', [\App\Http\Controllers\Api\AttendanceFeePolicyController::class, 'destroy'])
                ->middleware('permission:quality.manage_settings');
            Route::post('/{attendanceFeePolicy}/activate', [\App\Http\Controllers\Api\AttendanceFeePolicyController::class, 'activate'])
                ->middleware('permission:quality.manage_settings');
        });

        // Lesson Plan Sessions (TECP)
        Route::get('/sessions/{id}', [SessionController::class, 'show'])
            ->middleware('permission:lesson_plans.view');
        Route::post('/sessions/{id}/lesson-plan', [SessionController::class, 'saveLessonPlan'])
            ->middleware('permission:lesson_plans.edit');

        // Materials Management (CRUD)
        Route::get('/sessions/{id}/materials', [SessionController::class, 'getMaterials'])
            ->middleware('permission:lesson_plans.view');
        Route::get('/sessions/{sessionId}/materials/{materialId}', [SessionController::class, 'getMaterial'])
            ->middleware('permission:lesson_plans.view');
        Route::post('/sessions/{id}/materials', [SessionController::class, 'saveMaterials'])
            ->middleware('permission:lesson_plans.edit');
        Route::put('/sessions/{sessionId}/materials/{materialId}', [SessionController::class, 'updateMaterial'])
            ->middleware('permission:lesson_plans.edit');
        Route::delete('/sessions/{sessionId}/materials/{materialId}', [SessionController::class, 'deleteMaterial'])
            ->middleware('permission:lesson_plans.edit');

        // AI Material Editing
        Route::post('/sessions/{sessionId}/edit-material-with-ai', [SessionController::class, 'editMaterialWithAI'])
            ->middleware('permission:lesson_plans.edit');
        Route::post('/sessions/{sessionId}/edit-text-with-ai', [SessionController::class, 'editTextWithAI'])
            ->middleware('permission:lesson_plans.edit');

        // AI Settings
        Route::get('/ai-settings', [\App\Http\Controllers\Api\Quality\QualityAIController::class, 'getAiSettings']);
        Route::post('/ai-settings', [\App\Http\Controllers\Api\Quality\QualityAIController::class, 'saveAiSettings'])
            ->middleware('permission:quality.manage_settings');

        // Material Generation Prompt Settings
        Route::get('/material-generation-prompt', [\App\Http\Controllers\Api\Quality\QualityAIController::class, 'getMaterialPrompt'])
            ->middleware('permission:quality.manage_settings');
        Route::post('/material-generation-prompt', [\App\Http\Controllers\Api\Quality\QualityAIController::class, 'saveMaterialPrompt'])
            ->middleware('permission:quality.manage_settings');

        // AI Material Generation
        Route::post('/sessions/{id}/generate-material', [\App\Http\Controllers\Api\Quality\QualityAIController::class, 'generateMaterial'])
            ->middleware('permission:lesson_plans.edit');
    });
    
    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'getUnreadCount']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
    });
    
    // Accounting Module Routes
    Route::prefix('accounting')->middleware('permission:accounting.view')->group(function () {
        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Api\AccountingController::class, 'dashboard']);
        Route::get('/categories/tree', [\App\Http\Controllers\Api\AccountingController::class, 'getCategoryTree']);
        
        // Account Categories (Danh mục)
        Route::get('/account-categories', [\App\Http\Controllers\Api\AccountCategoryController::class, 'index']);
        Route::get('/account-categories/tree', [\App\Http\Controllers\Api\AccountCategoryController::class, 'tree']);
        Route::post('/account-categories', [\App\Http\Controllers\Api\AccountCategoryController::class, 'store'])
            ->middleware('permission:account_categories.create');
        Route::get('/account-categories/{accountCategory}', [\App\Http\Controllers\Api\AccountCategoryController::class, 'show']);
        Route::put('/account-categories/{accountCategory}', [\App\Http\Controllers\Api\AccountCategoryController::class, 'update'])
            ->middleware('permission:account_categories.edit');
        Route::delete('/account-categories/{accountCategory}', [\App\Http\Controllers\Api\AccountCategoryController::class, 'destroy'])
            ->middleware('permission:account_categories.delete');
        
        // Account Items (Khoản mục Thu Chi)
        Route::apiResource('account-items', \App\Http\Controllers\Api\AccountItemController::class);
        
        // Cash Accounts (Tài khoản tiền)
        Route::get('/cash-accounts', [\App\Http\Controllers\Api\CashAccountController::class, 'index']);
        Route::get('/cash-accounts/summary', [\App\Http\Controllers\Api\CashAccountController::class, 'summary']);
        Route::post('/cash-accounts', [\App\Http\Controllers\Api\CashAccountController::class, 'store']);
        Route::get('/cash-accounts/{id}', [\App\Http\Controllers\Api\CashAccountController::class, 'show']);
        Route::put('/cash-accounts/{id}', [\App\Http\Controllers\Api\CashAccountController::class, 'update']);
        Route::delete('/cash-accounts/{id}', [\App\Http\Controllers\Api\CashAccountController::class, 'destroy']);
        
        // Financial Plans (Kế hoạch Thu Chi)
        Route::get('/financial-plans', [\App\Http\Controllers\Api\FinancialPlanController::class, 'index']);
        Route::get('/financial-plans/available', [\App\Http\Controllers\Api\FinancialPlanController::class, 'available']);
        Route::post('/financial-plans', [\App\Http\Controllers\Api\FinancialPlanController::class, 'store'])
            ->middleware('permission:financial_plans.create');
        Route::get('/financial-plans/{financialPlan}', [\App\Http\Controllers\Api\FinancialPlanController::class, 'show']);
        Route::put('/financial-plans/{financialPlan}', [\App\Http\Controllers\Api\FinancialPlanController::class, 'update'])
            ->middleware('permission:financial_plans.edit');
        Route::post('/financial-plans/{financialPlan}/submit', [\App\Http\Controllers\Api\FinancialPlanController::class, 'submit'])
            ->middleware('permission:financial_plans.create');
        Route::post('/financial-plans/{financialPlan}/approve', [\App\Http\Controllers\Api\FinancialPlanController::class, 'approve'])
            ->middleware('permission:financial_plans.approve');
        Route::post('/financial-plans/{financialPlan}/activate', [\App\Http\Controllers\Api\FinancialPlanController::class, 'activate'])
            ->middleware('permission:financial_plans.approve');
        Route::post('/financial-plans/{financialPlan}/close', [\App\Http\Controllers\Api\FinancialPlanController::class, 'close'])
            ->middleware('permission:financial_plans.approve');
        Route::delete('/financial-plans/{financialPlan}', [\App\Http\Controllers\Api\FinancialPlanController::class, 'destroy'])
            ->middleware('permission:financial_plans.delete');
        
        // Expense Proposals (Đề xuất Chi)
        Route::get('/expense-proposals', [\App\Http\Controllers\Api\ExpenseProposalController::class, 'index']);
        Route::post('/expense-proposals', [\App\Http\Controllers\Api\ExpenseProposalController::class, 'store'])
            ->middleware('permission:expense_proposals.create');
        Route::get('/expense-proposals/{expenseProposal}', [\App\Http\Controllers\Api\ExpenseProposalController::class, 'show']);
        Route::put('/expense-proposals/{expenseProposal}', [\App\Http\Controllers\Api\ExpenseProposalController::class, 'update'])
            ->middleware('permission:expense_proposals.edit');
        Route::post('/expense-proposals/{expenseProposal}/approve', [\App\Http\Controllers\Api\ExpenseProposalController::class, 'approve'])
            ->middleware('permission:expense_proposals.approve');
        Route::post('/expense-proposals/{expenseProposal}/reject', [\App\Http\Controllers\Api\ExpenseProposalController::class, 'reject'])
            ->middleware('permission:expense_proposals.approve');
        Route::post('/expense-proposals/{expenseProposal}/mark-paid', [\App\Http\Controllers\Api\ExpenseProposalController::class, 'markPaid'])
            ->middleware('permission:expense_proposals.approve');
        Route::delete('/expense-proposals/{expenseProposal}', [\App\Http\Controllers\Api\ExpenseProposalController::class, 'destroy'])
            ->middleware('permission:expense_proposals.delete');
        
        // Income Reports (Báo Thu)
        Route::get('/income-reports', [\App\Http\Controllers\Api\IncomeReportController::class, 'index']);
        Route::post('/income-reports', [\App\Http\Controllers\Api\IncomeReportController::class, 'store'])
            ->middleware('permission:income_reports.create');
        Route::get('/income-reports/{incomeReport}', [\App\Http\Controllers\Api\IncomeReportController::class, 'show']);
        Route::put('/income-reports/{incomeReport}', [\App\Http\Controllers\Api\IncomeReportController::class, 'update'])
            ->middleware('permission:income_reports.edit');
        Route::post('/income-reports/{incomeReport}/approve', [\App\Http\Controllers\Api\IncomeReportController::class, 'approve'])
            ->middleware('permission:income_reports.approve');
        Route::post('/income-reports/{incomeReport}/reject', [\App\Http\Controllers\Api\IncomeReportController::class, 'reject'])
            ->middleware('permission:income_reports.approve');
        // ❌ REMOVED: verify moved to FinancialTransactionController
        Route::delete('/income-reports/{incomeReport}', [\App\Http\Controllers\Api\IncomeReportController::class, 'destroy'])
            ->middleware('permission:income_reports.delete');
        
        // Financial Transactions (Giao dịch)
        Route::get('/transactions', [\App\Http\Controllers\Api\FinancialTransactionController::class, 'index']);
        Route::get('/transactions/summary', [\App\Http\Controllers\Api\FinancialTransactionController::class, 'summary']);
        Route::get('/transactions/category-breakdown', [\App\Http\Controllers\Api\FinancialTransactionController::class, 'categoryBreakdown']);
        Route::get('/transactions/cash-flow', [\App\Http\Controllers\Api\FinancialTransactionController::class, 'cashFlow']);
        Route::get('/transactions/export', [\App\Http\Controllers\Api\FinancialTransactionController::class, 'export']);
        Route::get('/transactions/{financialTransaction}', [\App\Http\Controllers\Api\FinancialTransactionController::class, 'show']);
        
        // Transaction Workflow (NEW: Central Hub)
        Route::post('/transactions/{transaction}/approve', [\App\Http\Controllers\Api\FinancialTransactionController::class, 'approve'])
            ->middleware('permission:transactions.approve');
        Route::post('/transactions/{transaction}/verify', [\App\Http\Controllers\Api\FinancialTransactionController::class, 'verify'])
            ->middleware('permission:transactions.verify');
        Route::post('/transactions/{transaction}/reject', [\App\Http\Controllers\Api\FinancialTransactionController::class, 'reject'])
            ->middleware('permission:transactions.approve');
    });
    
    // Class Settings Module Routes
    Route::prefix('class-settings')->group(function () {
        // Academic Years
        Route::get('/academic-years', [ClassSettingsController::class, 'getAcademicYears']);
        Route::post('/academic-years', [ClassSettingsController::class, 'storeAcademicYear'])
            ->middleware('permission:classes.manage_settings');
        Route::put('/academic-years/{id}', [ClassSettingsController::class, 'updateAcademicYear'])
            ->middleware('permission:classes.manage_settings');
        Route::delete('/academic-years/{id}', [ClassSettingsController::class, 'deleteAcademicYear'])
            ->middleware('permission:classes.manage_settings');
        
        // Semesters
        Route::get('/semesters', [ClassSettingsController::class, 'getSemesters']);
        Route::post('/semesters', [ClassSettingsController::class, 'storeSemester'])
            ->middleware('permission:classes.manage_settings');
        Route::put('/semesters/{id}', [ClassSettingsController::class, 'updateSemester'])
            ->middleware('permission:classes.manage_settings');
        Route::delete('/semesters/{id}', [ClassSettingsController::class, 'deleteSemester'])
            ->middleware('permission:classes.manage_settings');
        
        // Study Periods
        Route::get('/study-periods', [ClassSettingsController::class, 'getStudyPeriods']);
        Route::post('/study-periods', [ClassSettingsController::class, 'storeStudyPeriod'])
            ->middleware('permission:classes.manage_settings');
        Route::put('/study-periods/{id}', [ClassSettingsController::class, 'updateStudyPeriod'])
            ->middleware('permission:classes.manage_settings');
        Route::delete('/study-periods/{id}', [ClassSettingsController::class, 'deleteStudyPeriod'])
            ->middleware('permission:classes.manage_settings');
        
        // Rooms
        Route::get('/rooms', [ClassSettingsController::class, 'getRooms']);
        Route::post('/rooms', [ClassSettingsController::class, 'storeRoom'])
            ->middleware('permission:classes.manage_settings');
        Route::put('/rooms/{id}', [ClassSettingsController::class, 'updateRoom'])
            ->middleware('permission:classes.manage_settings');
        Route::delete('/rooms/{id}', [ClassSettingsController::class, 'deleteRoom'])
            ->middleware('permission:classes.manage_settings');
        
        // Holidays
        Route::get('/holidays', [ClassSettingsController::class, 'getHolidays']);
        Route::post('/holidays', [ClassSettingsController::class, 'storeHoliday'])
            ->middleware('permission:classes.manage_settings');
        Route::put('/holidays/{id}', [ClassSettingsController::class, 'updateHoliday'])
            ->middleware('permission:classes.manage_settings');
        Route::delete('/holidays/{id}', [ClassSettingsController::class, 'deleteHoliday'])
            ->middleware('permission:classes.manage_settings');
    });
    
    // Class Management Module Routes
    Route::prefix('classes')->middleware('permission:classes.view')->group(function () {
        // Classes CRUD
        Route::get('/', [ClassManagementController::class, 'index']);
        Route::post('/', [ClassManagementController::class, 'store'])
            ->middleware('permission:classes.create');
        Route::get('/{id}', [ClassManagementController::class, 'show']);
        Route::put('/{id}', [ClassManagementController::class, 'update'])
            ->middleware('permission:classes.edit');
        Route::delete('/{id}', [ClassManagementController::class, 'destroy'])
            ->middleware('permission:classes.delete');
        
        // Class Schedules
        Route::get('/{id}/schedules', [ClassManagementController::class, 'getSchedules']);
        Route::post('/{id}/schedules', [ClassManagementController::class, 'createSchedule'])
            ->middleware('permission:classes.manage_schedule');
        Route::put('/{id}/schedules/{scheduleId}', [ClassManagementController::class, 'updateSchedule'])
            ->middleware('permission:classes.manage_schedule');
        Route::delete('/{id}/schedules/{scheduleId}', [ClassManagementController::class, 'deleteSchedule'])
            ->middleware('permission:classes.manage_schedule');
        
        // Lesson Sessions
        Route::post('/{id}/generate-sessions', [ClassManagementController::class, 'generateLessonSessions'])
            ->middleware('permission:classes.manage_schedule');
        Route::get('/{id}/sessions', [ClassManagementController::class, 'getLessonSessions']);
        Route::get('/{id}/sessions/{sessionId}', [ClassManagementController::class, 'getSessionDetail']);
        Route::put('/{id}/sessions/{sessionId}', [ClassManagementController::class, 'updateLessonSession'])
            ->middleware('permission:classes.update_session');
        Route::post('/{id}/sessions/{sessionId}/change-teacher', [ClassManagementController::class, 'changeSessionTeacher'])
            ->middleware('permission:classes.update_session');
        Route::post('/{id}/sessions/{sessionId}/cancel', [ClassManagementController::class, 'cancelSession'])
            ->middleware('permission:classes.update_session');
        Route::post('/{id}/sync-from-syllabus', [ClassManagementController::class, 'syncFromSyllabus'])
            ->middleware('permission:classes.edit');
        Route::post('/{id}/sync-folder-ids', [ClassManagementController::class, 'syncFolderIds'])
            ->middleware('permission:classes.edit');

        // Update Zalo group for class
        Route::patch('/{id}/zalo-group', [ClassManagementController::class, 'updateZaloGroup'])
            ->middleware('permission:classes.edit');

        // Get Zalo contacts (students + parents) for class
        Route::get('/{id}/zalo-contacts', [ClassManagementController::class, 'getZaloContacts'])
            ->middleware('permission:classes.view');

        // Đồng bộ lịch học lên Calendar
        Route::post('/{id}/sync-to-calendar', [ClassManagementController::class, 'syncClassToCalendar'])
            ->middleware('permission:classes.edit');
        
        // Class Detail Routes
        Route::get('/{id}/detail', [ClassDetailController::class, 'show']);
        Route::get('/{id}/weekly-schedule', [ClassDetailController::class, 'getWeeklySchedule']);
        Route::get('/{id}/lesson-sessions', [ClassDetailController::class, 'getLessonSessions']);
        Route::get('/{id}/students', [ClassDetailController::class, 'getStudents']);
        Route::get('/{id}/overview', [ClassDetailController::class, 'getOverview']);
        
        // Student Management
        Route::post('/{id}/students', [ClassDetailController::class, 'addStudent'])
            ->middleware('permission:classes.edit');
        Route::put('/{id}/students/{studentId}', [ClassDetailController::class, 'updateStudent'])
            ->middleware('permission:classes.edit');
        Route::delete('/{id}/students/{studentId}', [ClassDetailController::class, 'removeStudent'])
            ->middleware('permission:classes.edit');
        
        // Attendance Management
        Route::post('/sessions/{sessionId}/attendance', [ClassDetailController::class, 'markAttendance'])
            ->middleware('permission:classes.update_session');
        Route::post('/evaluations/generate-pdf', [\App\Http\Controllers\Api\EvaluationPdfController::class, 'generatePdf']);
        Route::get('/evaluations/{attendanceId}/pdf', [\App\Http\Controllers\Api\EvaluationPdfController::class, 'viewPdf']);
        
        // Homework Management
        Route::post('/sessions/{sessionId}/homework', [ClassDetailController::class, 'submitHomework'])
            ->middleware('permission:classes.update_session');
        Route::put('/homework/{homeworkId}/grade', [ClassDetailController::class, 'gradeHomework'])
            ->middleware('permission:classes.update_session');
        
        // Session Comments
        Route::post('/sessions/{sessionId}/comments', [ClassDetailController::class, 'addSessionComment'])
            ->middleware('permission:classes.update_session');
        Route::get('/sessions/{sessionId}/detail', [ClassDetailController::class, 'getSessionDetail']);
    });
    
    // Lesson Plans Module Routes
    // Lesson Plans Module Routes
    // NOTE: Authorization is handled in controllers for flexibility
    // Supports both 'lesson_plans.*' and 'syllabus.*' permissions for backwards compatibility
    Route::prefix('lesson-plans')->group(function () {
        // Get subjects list for lesson plan creation (no permission check)
        // IMPORTANT: Must be before /{id} route to avoid route conflict
        Route::get('/subjects-list', [LessonPlanController::class, 'getSubjectsList']);
        
        // Lesson Plans CRUD
        Route::get('/', [LessonPlanController::class, 'index']);
        Route::post('/', [LessonPlanController::class, 'store']);
        Route::get('/{id}', [LessonPlanController::class, 'show']);
        Route::put('/{id}', [LessonPlanController::class, 'update']);
        Route::post('/{id}/check-sessions-impact', [LessonPlanController::class, 'checkSessionsImpact']); // Preview session changes
        Route::patch('/{id}/status', [LessonPlanController::class, 'updateStatus']); // Update status only
        Route::delete('/{id}', [LessonPlanController::class, 'destroy']);
        
        // Lesson Plan Sessions
        Route::get('/{id}/sessions', [LessonPlanController::class, 'getSessions']);
        Route::post('/{id}/sessions', [LessonPlanController::class, 'createSession']);
        Route::put('/{id}/sessions/{sessionId}', [LessonPlanController::class, 'updateSession']);
        Route::delete('/{id}/sessions/{sessionId}', [LessonPlanController::class, 'deleteSession']);
        
        // Google Drive Integration (on-demand folder creation)
        Route::post('/{syllabusId}/upload', [\App\Http\Controllers\Api\SyllabusGoogleDriveController::class, 'uploadToUnit']);
        Route::get('/{syllabusId}/unit/{unitNumber}/files', [\App\Http\Controllers\Api\SyllabusGoogleDriveController::class, 'getUnitFiles']);
    });
    
    // Valuation Forms Routes
    Route::prefix('valuation-forms')->middleware('permission:lesson_plans.view')->group(function () {
        Route::get('/', [ValuationFormsController::class, 'index']);
        Route::post('/', [ValuationFormsController::class, 'store'])
            ->middleware('permission:lesson_plans.create');
        Route::get('/{id}', [ValuationFormsController::class, 'show']);
        Route::put('/{id}', [ValuationFormsController::class, 'update'])
            ->middleware('permission:lesson_plans.edit');
        Route::delete('/{id}', [ValuationFormsController::class, 'destroy'])
            ->middleware('permission:lesson_plans.delete');
    });
    
    // ============================================
    // HOLIDAYS MODULE (Main Module - Branch-wide)
    // ============================================
    Route::prefix('holidays')->middleware('permission:holidays.view')->group(function () {
        Route::get('/', [HolidaysController::class, 'index']);
        Route::post('/', [HolidaysController::class, 'store'])
            ->middleware('permission:holidays.create');
        Route::put('/{id}', [HolidaysController::class, 'update'])
            ->middleware('permission:holidays.edit');
        Route::delete('/{id}', [HolidaysController::class, 'destroy'])
            ->middleware('permission:holidays.delete');
    });

    // System Settings Module Routes
    // ============================================
    Route::prefix('system-settings')->middleware('permission:settings.view')->group(function () {
        Route::get('/', [SystemSettingsController::class, 'index']);
        Route::get('/{key}', [SystemSettingsController::class, 'show']);
        Route::put('/{key}', [SystemSettingsController::class, 'update'])
            ->middleware('permission:settings.edit');
        Route::post('/bulk-update', [SystemSettingsController::class, 'bulkUpdate'])
            ->middleware('permission:settings.edit');
        Route::post('/upload-favicon', [SystemSettingsController::class, 'uploadFavicon'])
            ->middleware('permission:settings.edit');
        Route::delete('/{key}', [SystemSettingsController::class, 'destroy'])
            ->middleware('permission:settings.delete');
    });

    // Scheduled Tasks Management Routes
    // ============================================
    Route::prefix('scheduled-tasks')->middleware('permission:settings.view')->group(function () {
        Route::get('/', [ScheduledTasksController::class, 'index']);
        Route::put('/{key}', [ScheduledTasksController::class, 'update'])
            ->middleware('permission:settings.edit');
        Route::post('/{key}/run', [ScheduledTasksController::class, 'run'])
            ->middleware('permission:settings.edit');
        Route::post('/{key}/reset', [ScheduledTasksController::class, 'reset'])
            ->middleware('permission:settings.edit');
    });

    // ============================================
    // PRODUCTS MODULE (Sản phẩm/Khóa học)
    // ============================================
    Route::prefix('products')->middleware('permission:products.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ProductController::class, 'index']);
        Route::get('/featured', [\App\Http\Controllers\Api\ProductController::class, 'featured']);
        Route::get('/categories', [\App\Http\Controllers\Api\ProductController::class, 'categories']);
        Route::get('/{id}', [\App\Http\Controllers\Api\ProductController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\ProductController::class, 'store'])
            ->middleware('permission:products.create');
        Route::put('/{id}', [\App\Http\Controllers\Api\ProductController::class, 'update'])
            ->middleware('permission:products.edit');
        Route::delete('/{id}', [\App\Http\Controllers\Api\ProductController::class, 'destroy'])
            ->middleware('permission:products.delete');
    });

    // ============================================
    // VOUCHERS MODULE (Mã giảm giá)
    // ============================================
    Route::prefix('vouchers')->middleware('permission:vouchers.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\VoucherController::class, 'index']);
        Route::get('/check', [\App\Http\Controllers\Api\VoucherController::class, 'validate']); // Check voucher validity
        Route::get('/customer/{customerId}/applicable', [\App\Http\Controllers\Api\VoucherController::class, 'applicableForCustomer']);
        Route::post('/validate', [\App\Http\Controllers\Api\VoucherController::class, 'validate']);
        Route::get('/{id}', [\App\Http\Controllers\Api\VoucherController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\VoucherController::class, 'store'])
            ->middleware('permission:vouchers.create');
        Route::put('/{id}', [\App\Http\Controllers\Api\VoucherController::class, 'update'])
            ->middleware('permission:vouchers.edit');
        Route::delete('/{id}', [\App\Http\Controllers\Api\VoucherController::class, 'destroy'])
            ->middleware('permission:vouchers.delete');
    });

    // ============================================
    // CAMPAIGNS MODULE (Chiến dịch khuyến mãi)
    // ============================================
    Route::prefix('campaigns')->middleware('permission:campaigns.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\CampaignController::class, 'index']);
        Route::get('/active', [\App\Http\Controllers\Api\CampaignController::class, 'active']);
        Route::post('/auto-apply', [\App\Http\Controllers\Api\CampaignController::class, 'autoApply']);
        Route::get('/{id}', [\App\Http\Controllers\Api\CampaignController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\CampaignController::class, 'store'])
            ->middleware('permission:campaigns.create');
        Route::put('/{id}', [\App\Http\Controllers\Api\CampaignController::class, 'update'])
            ->middleware('permission:campaigns.edit');
        Route::delete('/{id}', [\App\Http\Controllers\Api\CampaignController::class, 'destroy'])
            ->middleware('permission:campaigns.delete');
    });

    // ============================================
    // ENROLLMENTS MODULE (Đơn đăng ký khóa học)
    // ============================================
    Route::prefix('enrollments')->middleware('permission:enrollments.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\EnrollmentController::class, 'index']);
        Route::get('/statistics', [\App\Http\Controllers\Api\EnrollmentController::class, 'statistics']);
        Route::get('/{id}', [\App\Http\Controllers\Api\EnrollmentController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\EnrollmentController::class, 'store'])
            ->middleware('permission:enrollments.create');
        Route::post('/{id}/confirm-payment', [\App\Http\Controllers\Api\EnrollmentController::class, 'confirmPayment'])
            ->middleware('permission:enrollments.edit');
        Route::delete('/{id}', [\App\Http\Controllers\Api\EnrollmentController::class, 'destroy'])
            ->middleware('permission:enrollments.delete');
        Route::post('/{id}/cancel', [\App\Http\Controllers\Api\EnrollmentController::class, 'cancel'])
            ->middleware('permission:enrollments.delete');
    });

    // ============================================
    // WALLETS MODULE (Ví tiền)
    // ============================================
    
    Route::prefix('wallets')->group(function () {
        // My Wallet - không cần permission (user xem wallet của chính mình)
        Route::get('/my-wallet', [\App\Http\Controllers\Api\WalletController::class, 'myWallet']);
        
        // My Children Wallets - không cần permission (parent xem wallet của các con)
        Route::get('/my-children', [\App\Http\Controllers\Api\WalletController::class, 'myChildrenWallets']);
        
        // Các route khác cần permission
        Route::middleware('permission:wallets.view')->group(function () {
            Route::get('/show', [\App\Http\Controllers\Api\WalletController::class, 'show']);
            Route::get('/transactions', [\App\Http\Controllers\Api\WalletController::class, 'transactions']);
            Route::get('/customer/{customerId}', [\App\Http\Controllers\Api\WalletController::class, 'customerWallets']);
        });
        
        Route::post('/{id}/toggle-lock', [\App\Http\Controllers\Api\WalletController::class, 'toggleLock'])
            ->middleware('permission:wallets.edit');
    });
});

// =====================================
// COURSE MODULE ROUTES
// =====================================
Route::middleware(['auth:sanctum'])->prefix('course')->group(function () {
    // Student Info - Accessible by anyone logged in (no specific permission needed)
    Route::get('/my-student-info', [\App\Http\Controllers\Api\StudentController::class, 'me']);
    
    // Parent's Children - Accessible by anyone logged in (no specific permission needed)
    Route::get('/my-children', [\App\Http\Controllers\Api\StudentController::class, 'myChildren']);

    // Parent's Children in a specific class - Accessible by anyone logged in (no specific permission needed)
    Route::get('/my-children-in-class/{classId}', [\App\Http\Controllers\Api\StudentController::class, 'myChildrenInClass']);

    // Student's Classes - Accessible by parent/teacher/admin/student themselves
    Route::get('/students/{studentId}/classes', [\App\Http\Controllers\Api\StudentController::class, 'getStudentClasses']);

    // My Classes - No permission needed, logic inside checks enrollment/role
    Route::get('/my-classes', [\App\Http\Controllers\Api\CoursePostController::class, 'getMyClasses']);

    // Upcoming Events - No permission needed, filtered by user in logic
    Route::get('/upcoming-events', [\App\Http\Controllers\Api\CoursePostController::class, 'getUpcomingEvents']);

    // Homework Assignments
    Route::prefix('homework')->group(function () {
        // Assignment routes (with /assignments path)
        Route::get('/assignments', [\App\Http\Controllers\Api\HomeworkAssignmentController::class, 'index'])
            ->middleware('permission:course.view');
        Route::get('/assignments/{id}', [\App\Http\Controllers\Api\HomeworkAssignmentController::class, 'show'])
            ->middleware('permission:course.view');
        Route::post('/assignments', [\App\Http\Controllers\Api\HomeworkAssignmentController::class, 'store'])
            ->middleware('permission:course.post');
        Route::patch('/assignments/{id}/status', [\App\Http\Controllers\Api\HomeworkAssignmentController::class, 'updateStatus'])
            ->middleware('permission:course.post');
        Route::delete('/assignments/{id}', [\App\Http\Controllers\Api\HomeworkAssignmentController::class, 'destroy'])
            ->middleware('permission:course.post');

        // Upcoming homework with role-based access (Teacher, Subject Head, Department Head)
        Route::get('/upcoming', [\App\Http\Controllers\Api\HomeworkAssignmentController::class, 'getUpcomingHomework'])
            ->middleware('permission:course.view');

        // Legacy routes (backward compatibility)
        Route::get('/', [\App\Http\Controllers\Api\HomeworkAssignmentController::class, 'index'])
            ->middleware('permission:course.view');
        Route::post('/', [\App\Http\Controllers\Api\HomeworkAssignmentController::class, 'store'])
            ->middleware('permission:course.post');
        Route::get('/classes/{classId}/sessions', [\App\Http\Controllers\Api\HomeworkAssignmentController::class, 'getSessions'])
            ->middleware('permission:course.view');
        Route::get('/sessions/{sessionId}/files', [\App\Http\Controllers\Api\HomeworkAssignmentController::class, 'getSessionFiles'])
            ->middleware('permission:course.view');
        Route::get('/{homeworkId}/file/{fileId}', [\App\Http\Controllers\Api\HomeworkAssignmentController::class, 'downloadFile']);
        Route::post('/{homeworkId}/submit', [\App\Http\Controllers\Api\HomeworkSubmissionController::class, 'submit']);
        Route::get('/{homeworkId}/submissions', [\App\Http\Controllers\Api\HomeworkSubmissionController::class, 'getSubmissions']);
        Route::get('/{homeworkId}/my-submission', [\App\Http\Controllers\Api\HomeworkSubmissionController::class, 'getMySubmission']);
        Route::get('/submissions/{submissionId}/answers', [\App\Http\Controllers\Api\HomeworkSubmissionController::class, 'getSubmissionAnswers']);
        Route::post('/submissions/{submissionId}/answers/{answerId}/grade', [\App\Http\Controllers\Api\HomeworkSubmissionController::class, 'gradeAnswer']);
        Route::post('/submissions/{submissionId}/grade', [\App\Http\Controllers\Api\HomeworkSubmissionController::class, 'gradeSubmission']);
        Route::post('/grade-essay-with-ai', [\App\Http\Controllers\Api\HomeworkSubmissionController::class, 'gradeEssayWithAI']);
        Route::patch('/{id}/status', [\App\Http\Controllers\Api\HomeworkAssignmentController::class, 'updateStatus'])
            ->middleware('permission:course.post');
        Route::delete('/{id}', [\App\Http\Controllers\Api\HomeworkAssignmentController::class, 'destroy'])
            ->middleware('permission:course.post');

        // Homework Exercises (Question Bank)
        Route::prefix('exercises')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\HomeworkExerciseController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\HomeworkExerciseController::class, 'store']);
            Route::get('/statistics', [\App\Http\Controllers\Api\HomeworkExerciseController::class, 'statistics']);
            Route::get('/{id}', [\App\Http\Controllers\Api\HomeworkExerciseController::class, 'show']);
            Route::put('/{id}', [\App\Http\Controllers\Api\HomeworkExerciseController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\HomeworkExerciseController::class, 'destroy']);
        });
    });

    // Learning Journals
    Route::prefix('learning-journals')->group(function () {
        // Student routes
        Route::get('/class/{classId}/my-journals', [\App\Http\Controllers\Api\LearningJournalController::class, 'getMyJournals']);
        Route::get('/class/{classId}/date/{date}', [\App\Http\Controllers\Api\LearningJournalController::class, 'getJournalByDate']);
        Route::post('/class/{classId}/save', [\App\Http\Controllers\Api\LearningJournalController::class, 'saveJournal']);

        // Teacher routes
        Route::get('/class/{classId}/student/{studentId}', [\App\Http\Controllers\Api\LearningJournalController::class, 'getStudentJournals']);
        Route::post('/{journalId}/grade-with-ai', [\App\Http\Controllers\Api\LearningJournalController::class, 'gradeWithAI']);
        Route::post('/{journalId}/save-grading', [\App\Http\Controllers\Api\LearningJournalController::class, 'saveGrading']);
    });

    // Classroom Board - Posts
    Route::prefix('classes/{classId}/posts')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\CoursePostController::class, 'index'])
            ->middleware('permission:course.view');
        Route::post('/', [\App\Http\Controllers\Api\CoursePostController::class, 'store'])
            ->middleware('permission:course.post');
        Route::get('/{postId}/comments', [\App\Http\Controllers\Api\CoursePostController::class, 'getComments'])
            ->middleware('permission:course.view');
        Route::post('/{postId}/comments', [\App\Http\Controllers\Api\CoursePostController::class, 'addComment'])
            ->middleware('permission:course.view');
        Route::delete('/{postId}/comments/{commentId}', [\App\Http\Controllers\Api\CoursePostController::class, 'deleteComment'])
            ->middleware('permission:course.view');
        Route::delete('/{id}', [\App\Http\Controllers\Api\CoursePostController::class, 'destroy'])
            ->middleware('permission:course.post');
    });

    // Post likes
    Route::post('/posts/like', [\App\Http\Controllers\Api\CoursePostController::class, 'toggleLike'])
        ->middleware('permission:course.view');

    // Assignments
    Route::prefix('classes/{classId}/assignments')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\CourseAssignmentController::class, 'index'])
            ->middleware('permission:course.view');
        Route::post('/', [\App\Http\Controllers\Api\CourseAssignmentController::class, 'store'])
            ->middleware('permission:course.manage_assignments');
    });

    Route::post('/assignments/{assignmentId}/submit', [\App\Http\Controllers\Api\CourseAssignmentController::class, 'submitWork'])
        ->middleware('permission:course.view');
    Route::post('/submissions/{submissionId}/grade', [\App\Http\Controllers\Api\CourseAssignmentController::class, 'gradeSubmission'])
        ->middleware('permission:course.manage_assignments');

    // Learning History
    Route::prefix('learning-history')->group(function () {
        Route::get('/students/{studentId}', [\App\Http\Controllers\Api\LearningHistoryController::class, 'getStudentHistory'])
            ->middleware('permission:course.view');
        Route::get('/classes/{classId}', [\App\Http\Controllers\Api\LearningHistoryController::class, 'getClassHistory'])
            ->middleware('permission:course.view');
        Route::get('/classes/{classId}/stats', [\App\Http\Controllers\Api\LearningHistoryController::class, 'getClassStats'])
            ->middleware('permission:course.view');
    });
});

// =====================================
// GOOGLE DRIVE MODULE ROUTES
// =====================================
// Google Drive OAuth callback (no auth required for callback)
Route::get('/google-drive/callback', [\App\Http\Controllers\Api\GoogleDriveController::class, 'handleCallback']);

Route::middleware(['auth:sanctum'])->prefix('google-drive')->group(function () {
    // OAuth Flow
    Route::post('/auth-url', [\App\Http\Controllers\Api\GoogleDriveController::class, 'getAuthUrl'])
        ->middleware('permission:google-drive.settings');

    // Settings
    Route::get('/settings', [\App\Http\Controllers\Api\GoogleDriveController::class, 'getSettings'])
        ->middleware('permission:google-drive.settings');
    Route::post('/settings', [\App\Http\Controllers\Api\GoogleDriveController::class, 'saveSettings'])
        ->middleware('permission:google-drive.settings');
    Route::post('/test-connection', [\App\Http\Controllers\Api\GoogleDriveController::class, 'testConnection'])
        ->middleware('permission:google-drive.settings');
    
    // Folder Management
    Route::post('/ensure-syllabus-folder', [\App\Http\Controllers\Api\GoogleDriveController::class, 'ensureSyllabusFolder'])
        ->middleware('permission:google-drive.view');
    Route::post('/create-syllabus-folder', [\App\Http\Controllers\Api\GoogleDriveController::class, 'createFolderForSyllabus'])
        ->middleware('permission:google-drive.view');
    // DEPRECATED: Use /api/lesson-plans/{syllabusId}/upload instead
    // Keeping for backward compatibility with old frontend builds
    Route::post('/upload-session-file', [\App\Http\Controllers\Api\GoogleDriveController::class, 'uploadSessionFile'])
        ->middleware('permission:google-drive.manage');
    Route::post('/folder-files', [\App\Http\Controllers\Api\GoogleDriveController::class, 'getFolderFiles'])
        ->middleware('permission:google-drive.view');
    Route::post('/folder-files-count', [\App\Http\Controllers\Api\GoogleDriveController::class, 'getFolderFilesCount'])
        ->middleware('permission:google-drive.view');

    // File Management
    Route::get('/files', [\App\Http\Controllers\Api\GoogleDriveController::class, 'listFiles'])
        ->middleware('permission:google-drive.view');
    Route::post('/sync', [\App\Http\Controllers\Api\GoogleDriveController::class, 'sync'])
        ->middleware('permission:google-drive.manage');
    Route::get('/sync-status', [\App\Http\Controllers\Api\GoogleDriveController::class, 'getSyncStatus'])
        ->middleware('permission:google-drive.view');
    Route::post('/folders', [\App\Http\Controllers\Api\GoogleDriveController::class, 'createFolder'])
        ->middleware('permission:google-drive.manage');
    Route::post('/upload', [\App\Http\Controllers\Api\GoogleDriveController::class, 'uploadFile'])
        ->middleware('permission:google-drive.manage');
    Route::delete('/files/{id}', [\App\Http\Controllers\Api\GoogleDriveController::class, 'deleteFile'])
        ->middleware('permission:google-drive.manage');
    Route::patch('/files/{id}/rename', [\App\Http\Controllers\Api\GoogleDriveController::class, 'renameFile'])
        ->middleware('permission:google-drive.manage');
    Route::patch('/files/{id}/move', [\App\Http\Controllers\Api\GoogleDriveController::class, 'moveFile'])
        ->middleware('permission:google-drive.manage');
    
    // Permissions Management
    Route::get('/files/{id}/permissions', [\App\Http\Controllers\Api\GoogleDriveController::class, 'getPermissions'])
        ->middleware('permission:google-drive.view');
    Route::post('/files/{id}/share', [\App\Http\Controllers\Api\GoogleDriveController::class, 'shareFile'])
        ->middleware('permission:google-drive.manage');
    
    // Permission Sync & Verification
    Route::post('/files/{id}/sync-permissions', [\App\Http\Controllers\Api\GoogleDriveController::class, 'syncFolderPermissions'])
        ->middleware('permission:google-drive.manage');
    Route::get('/files/{id}/verify-permission', [\App\Http\Controllers\Api\GoogleDriveController::class, 'verifyUserPermission'])
        ->middleware('permission:google-drive.view');
    
    // Accessible Folder Tree (based on user permissions)
    Route::get('/accessible-folders', [\App\Http\Controllers\Api\GoogleDriveController::class, 'getAccessibleFolderTree'])
        ->middleware('permission:google-drive.view');
    
    // Class History Folder Management
    Route::get('/check-class-history-folder', [\App\Http\Controllers\Api\GoogleDriveController::class, 'checkClassHistoryFolder'])
        ->middleware('permission:google-drive.view');
    Route::post('/create-class-history-folder', [\App\Http\Controllers\Api\GoogleDriveController::class, 'createClassHistoryFolder'])
        ->middleware('permission:google-drive.manage');
});

// Class Google Drive Integration
Route::prefix('classes/{classId}/google-drive')->middleware('auth:sanctum')->group(function () {
    Route::get('/unit-folders', [\App\Http\Controllers\Api\ClassGoogleDriveController::class, 'getClassUnitFolders']);
    Route::post('/lesson-plans/upload', [\App\Http\Controllers\Api\ClassGoogleDriveController::class, 'uploadLessonPlan']);
    Route::get('/lesson-plans/unit/{unitNumber}', [\App\Http\Controllers\Api\ClassGoogleDriveController::class, 'getLessonPlans']);
});

// Zalo Module Routes - với phân quyền và branch access
// NOTE: /messages/receive and /messages/receive-reaction are outside auth group - called by zalo-service with API key
Route::prefix('zalo')->group(function () {
    // Public endpoints for zalo-service to send incoming messages/reactions (no auth required, uses API key)
    Route::post('/messages/receive', [\App\Http\Controllers\Api\ZaloController::class, 'receiveMessage']);
    Route::post('/messages/receive-reaction', [\App\Http\Controllers\Api\ZaloController::class, 'receiveReaction']);
    // Public endpoint for zalo-service to trigger auto-sync after login (uses API key)
    Route::post('/messages/sync-history', [\App\Http\Controllers\Api\ZaloController::class, 'syncHistory']);
    // Public endpoint for zalo-service to notify session status (expired/disconnected/connected)
    Route::post('/session-status', [\App\Http\Controllers\Api\ZaloController::class, 'sessionStatus']);
    // Internal API for zalo-service to get Telegram settings (protected by API key in controller)
    Route::get('/telegram-settings', [\App\Http\Controllers\Api\ZaloController::class, 'getAllTelegramSettings']);
    // Public endpoint to proxy media files from Zalo CDN (no auth required - validates URL only)
    Route::get('/messages/proxy-media', [\App\Http\Controllers\Api\ZaloController::class, 'proxyMedia']);
    // Internal API for zalo-service to mark message as recalled when undo event received (protected by API key in controller)
    Route::patch('/messages/recall-by-msgid', [\App\Http\Controllers\Api\ZaloController::class, 'recallMessageByMsgId']);
});

// Protected Zalo routes (require authentication)
Route::prefix('zalo')->middleware(['auth:sanctum', 'branch.access'])->group(function () {
    Route::get('/status', [\App\Http\Controllers\Api\ZaloController::class, 'status'])->middleware('permission:zalo.view');
    Route::get('/sync-progress', [\App\Http\Controllers\Api\ZaloController::class, 'getSyncProgress'])->middleware('permission:zalo.view');
    Route::get('/unread-counts', [\App\Http\Controllers\Api\ZaloController::class, 'getUnreadCounts'])->middleware('permission:zalo.view');
    Route::post('/customer-unread-counts', [\App\Http\Controllers\Api\ZaloController::class, 'getCustomerUnreadCounts'])->middleware('permission:zalo.view');
    Route::get('/customers/unread-total', [\App\Http\Controllers\Api\ZaloController::class, 'getCustomerUnreadTotal'])->middleware('permission:zalo.view');
    Route::post('/mark-as-read', [\App\Http\Controllers\Api\ZaloController::class, 'markAsRead'])->middleware('permission:zalo.send');
    Route::post('/initialize', [\App\Http\Controllers\Api\ZaloController::class, 'initialize'])->middleware('permission:zalo.manage_accounts');
    Route::post('/cancel-login', [\App\Http\Controllers\Api\ZaloController::class, 'cancelLogin'])->middleware('permission:zalo.manage_accounts');
    Route::get('/stats', [\App\Http\Controllers\Api\ZaloController::class, 'stats'])->middleware('permission:zalo.view');
    Route::get('/friends', [\App\Http\Controllers\Api\ZaloController::class, 'getFriends'])->middleware('permission:zalo.view');
    Route::get('/groups', [\App\Http\Controllers\Api\ZaloController::class, 'getGroups'])->middleware('permission:zalo.view');
    Route::get('/groups/{groupId}/members', [\App\Http\Controllers\Api\ZaloController::class, 'getGroupMembers'])->middleware('permission:zalo.view');
    Route::get('/history', [\App\Http\Controllers\Api\ZaloController::class, 'history'])->middleware('permission:zalo.view');
    Route::get('/settings', [\App\Http\Controllers\Api\ZaloController::class, 'getSettings'])->middleware('permission:zalo.view');
    Route::post('/settings', [\App\Http\Controllers\Api\ZaloController::class, 'saveSettings'])->middleware('permission:zalo.manage_settings');
    
    // Account management
    Route::prefix('accounts')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ZaloController::class, 'getAccounts'])->middleware('permission:zalo.view');
        Route::get('/active', [\App\Http\Controllers\Api\ZaloController::class, 'getActiveAccount'])->middleware('permission:zalo.view');
        Route::post('/active', [\App\Http\Controllers\Api\ZaloController::class, 'setActiveAccount'])->middleware('permission:zalo.manage_accounts');
        Route::post('/save', [\App\Http\Controllers\Api\ZaloController::class, 'saveAccount'])->middleware('permission:zalo.manage_accounts');
        Route::post('/relogin', [\App\Http\Controllers\Api\ZaloController::class, 'reloginAccount'])->middleware('permission:zalo.manage_accounts');
        Route::get('{accountId}/zalo-id', [\App\Http\Controllers\Api\ZaloController::class, 'getZaloIdForAccount']); // Internal: for zalo-service
        Route::post('/refresh', [\App\Http\Controllers\Api\ZaloController::class, 'refreshAccountInfo'])->middleware('permission:zalo.manage_accounts');
        Route::post('/assign', [\App\Http\Controllers\Api\ZaloController::class, 'assignAccount'])->middleware('permission:zalo.manage_accounts');
        Route::post('/primary', [\App\Http\Controllers\Api\ZaloController::class, 'setPrimaryAccount'])->middleware('permission:zalo.manage_accounts');
        Route::delete('/{id}', [\App\Http\Controllers\Api\ZaloController::class, 'deleteAccount'])->middleware('permission:zalo.manage_accounts');
        // Branch access management
        Route::get('/branch-access', [\App\Http\Controllers\Api\ZaloController::class, 'getAccountsBranchAccess'])->middleware('permission:zalo.manage_branch_access');
        Route::post('/{id}/branch-access', [\App\Http\Controllers\Api\ZaloController::class, 'updateAccountBranchAccess'])->middleware('permission:zalo.manage_branch_access');
        Route::get('/{id}/branch-access/{branchId}', [\App\Http\Controllers\Api\ZaloController::class, 'getBranchPermissions'])->middleware('permission:zalo.manage_branch_access');
        Route::post('/{id}/branch-access/{branchId}/permission', [\App\Http\Controllers\Api\ZaloController::class, 'updateBranchPermission'])->middleware('permission:zalo.manage_branch_access');
        Route::delete('/{id}/branch-access/{branchId}', [\App\Http\Controllers\Api\ZaloController::class, 'removeBranchAccess'])->middleware('permission:zalo.manage_branch_access');

        // Telegram notification settings
        Route::get('/{id}/telegram', [\App\Http\Controllers\Api\ZaloController::class, 'getTelegramSettings'])->middleware('permission:zalo.manage_accounts');
        Route::post('/{id}/telegram', [\App\Http\Controllers\Api\ZaloController::class, 'saveTelegramSettings'])->middleware('permission:zalo.manage_accounts');
        Route::post('/{id}/telegram/test', [\App\Http\Controllers\Api\ZaloController::class, 'testTelegramNotification'])->middleware('permission:zalo.manage_accounts');
    });

    // Messages
    Route::prefix('messages')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ZaloController::class, 'getMessages'])->middleware('permission:zalo.view');
        Route::post('/send', [\App\Http\Controllers\Api\ZaloController::class, 'sendMessage'])->middleware('permission:zalo.send');
        Route::post('/create-reminder', [\App\Http\Controllers\Api\ZaloController::class, 'createReminder'])->middleware('permission:zalo.send');
        Route::post('/send-to-customer', [\App\Http\Controllers\Api\ZaloController::class, 'sendMessageToCustomer'])->middleware('permission:zalo.send');
        Route::post('/reply', [\App\Http\Controllers\Api\ZaloController::class, 'replyMessage'])->middleware('permission:zalo.send');
        Route::post('/reaction', [\App\Http\Controllers\Api\ZaloController::class, 'addReaction'])->middleware('permission:zalo.send');
        Route::post('/upload-image', [\App\Http\Controllers\Api\ZaloController::class, 'uploadImage'])->middleware('permission:zalo.send');
        Route::post('/upload-file', [\App\Http\Controllers\Api\ZaloController::class, 'uploadFile'])->middleware('permission:zalo.send');
        Route::post('/upload-video', [\App\Http\Controllers\Api\ZaloController::class, 'uploadVideo'])->middleware('permission:zalo.send');
        Route::post('/upload-audio', [\App\Http\Controllers\Api\ZaloController::class, 'uploadAudio'])->middleware('permission:zalo.send');
        Route::post('/upload-folder', [\App\Http\Controllers\Api\ZaloController::class, 'uploadFolder'])->middleware('permission:zalo.send');
        // Note: sync-history is also available at /api/zalo/messages/sync-history (public, for zalo-service with API key)
        // Specific routes (no parameters) should be placed before parameterized routes
        Route::get('/media', [\App\Http\Controllers\Api\ZaloController::class, 'getMedia'])->middleware('permission:zalo.view');
        Route::get('/recent-stickers', [\App\Http\Controllers\Api\ZaloController::class, 'getRecentStickers'])->middleware('permission:zalo.view');
        Route::post('/record-sticker', [\App\Http\Controllers\Api\ZaloController::class, 'recordRecentSticker'])->middleware('permission:zalo.view');
        // Parameterized routes (with {id}) should be placed after specific routes
        Route::get('/{id}/reactions', [\App\Http\Controllers\Api\ZaloController::class, 'getReactions'])->middleware('permission:zalo.view');
        // Recall (undo) a sent message via Zalo API
        Route::post('/{id}/recall', [\App\Http\Controllers\Api\ZaloController::class, 'recallMessage'])->middleware('permission:zalo.send');
    });

    // Conversation management - with permission-based access control
    Route::prefix('conversations')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ZaloController::class, 'getConversations'])->middleware('permission:zalo.view');
        // No middleware - permission check in controller (allows department heads to assign users)
        Route::post('/{id}/assign-user', [\App\Http\Controllers\Api\ZaloController::class, 'assignUserToConversation']);
        Route::delete('/{conversationId}/assign-user/{userId}', [\App\Http\Controllers\Api\ZaloController::class, 'removeUserFromConversation'])->middleware('permission:zalo.all_conversation_management');
        Route::post('/{id}/assign-department', [\App\Http\Controllers\Api\ZaloController::class, 'assignDepartmentToConversation'])->middleware('permission:zalo.all_conversation_management');
        Route::get('/{id}/available-branches', [\App\Http\Controllers\Api\ZaloController::class, 'getAvailableBranchesForConversation'])->middleware('permission:zalo.view_all_branches_conversations');
        Route::put('/{id}/assign-branch', [\App\Http\Controllers\Api\ZaloController::class, 'assignBranchToConversation'])->middleware('permission:zalo.view_all_branches_conversations');
        Route::post('/{id}/assign-branch', [\App\Http\Controllers\Api\ZaloController::class, 'assignBranchToConversation'])->middleware('permission:zalo.view_all_branches_conversations');
        Route::post('/{id}/mark-read', [\App\Http\Controllers\Api\ZaloController::class, 'markConversationAsRead'])->middleware('permission:zalo.view');
        // Delete conversation (database only)
        Route::delete('/{id}', [\App\Http\Controllers\Api\ZaloController::class, 'deleteConversation'])->middleware('permission:zalo.view');
    });

    // Friend operations
    Route::post('/check-phone', [\App\Http\Controllers\Api\ZaloController::class, 'checkPhone'])->middleware('permission:zalo.view');
    Route::post('/users/search', [\App\Http\Controllers\Api\ZaloController::class, 'searchUser']);
    Route::get('/user-info/{userId}', [\App\Http\Controllers\Api\ZaloController::class, 'getUserInfo'])->middleware('permission:zalo.view');
    Route::post('/friends/send-request', [\App\Http\Controllers\Api\ZaloController::class, 'sendFriendRequest'])->middleware('permission:zalo.send');
    
    // Group operations
    Route::post('/groups/validate', [\App\Http\Controllers\Api\ZaloController::class, 'validateGroup'])->middleware('permission:zalo.view');
    Route::post('/groups/create', [\App\Http\Controllers\Api\ZaloController::class, 'createGroup'])->middleware('permission:zalo.send');
    Route::post('/groups/create-with-auto-friend', [\App\Http\Controllers\Api\ZaloController::class, 'createGroupWithAutoFriend'])->middleware('permission:zalo.send');
    Route::post('/groups/{groupId}/add-members', [\App\Http\Controllers\Api\ZaloController::class, 'addMembersToGroup'])->middleware('permission:zalo.send');
    Route::post('/groups/add-member-by-phone', [\App\Http\Controllers\Api\ZaloController::class, 'addMemberByPhone'])->middleware('permission:zalo.send');
    Route::post('/groups/{groupId}/change-avatar', [\App\Http\Controllers\Api\ZaloController::class, 'changeGroupAvatar'])->middleware('permission:zalo.send');

    // Group assignment (multi-branch support)
    Route::get('/groups/list-for-assignment', [\App\Http\Controllers\Api\ZaloController::class, 'listGroupsForAssignment'])->middleware('permission:zalo.assign_groups');
    Route::get('/groups/{group}/available-branches', [\App\Http\Controllers\Api\ZaloController::class, 'getAvailableBranches']);
    Route::put('/groups/{group}/assign', [\App\Http\Controllers\Api\ZaloController::class, 'assignGroup'])->middleware('permission:zalo.assign_groups');

    // Session validation
    Route::post('/validate-session', [\App\Http\Controllers\Api\ZaloController::class, 'validateSession'])->middleware('permission:zalo.view');
    Route::post('/validate-all-sessions', [\App\Http\Controllers\Api\ZaloController::class, 'validateAllSessions'])->middleware('permission:zalo.view');
});


// Include Examination module routes
require __DIR__.'/examination.php';

// Include Quality Management AI routes
if (file_exists(__DIR__.'/quality.php')) {
    require __DIR__.'/quality.php';
}

// Include Homework Exercise module routes
if (file_exists(__DIR__.'/homework.php')) {
    require __DIR__.'/homework.php';
}
