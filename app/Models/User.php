<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'google_email',
        'google_drive_folder_id',
        'password',
        'phone',
        'avatar',
        'date_of_birth',
        'gender',
        'address',
        'employee_code',
        'join_date',
        'employment_status',
        'language_id',
        'manager_id',
        'hierarchy_level',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'full_name',
        'primary_branch_id',
        'is_student',
        'is_parent',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'join_date' => 'date',
        ];
    }

    /**
     * Accessor: Get full name
     */
    public function getFullNameAttribute(): string
    {
        return $this->name ?? 'N/A';
    }

    /**
     * Accessor: Get primary branch ID
     * Returns the ID of user's branch based on their role (student > parent > employee)
     */
    public function getPrimaryBranchIdAttribute(): ?int
    {
        // 1. Check if user is a student
        $studentRecord = \DB::table('students')
            ->where('user_id', $this->id)
            ->where('is_active', true)
            ->first();

        if ($studentRecord && $studentRecord->branch_id) {
            return $studentRecord->branch_id;
        }

        // 2. Check if user is a parent
        $parentRecord = \DB::table('parents')
            ->where('user_id', $this->id)
            ->where('is_active', true)
            ->first();

        if ($parentRecord && $parentRecord->branch_id) {
            return $parentRecord->branch_id;
        }

        // 3. Check employee branch assignment
        return $this->branches()->first()?->id;
    }

    /**
     * Accessor: Check if user is a student
     * Returns true if user has an active record in students table
     */
    public function getIsStudentAttribute(): bool
    {
        return \DB::table('students')
            ->where('user_id', $this->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Accessor: Check if user is a parent
     * Returns true if user has an active record in parents table
     */
    public function getIsParentAttribute(): bool
    {
        return \DB::table('parents')
            ->where('user_id', $this->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Relationship: User có nhiều Roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Relationship: User thuộc về một Language
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * Relationship: User thuộc về nhiều Branches (Many-to-Many)
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_user')
            ->withTimestamps();
    }

    /**
     * Relationship: User thuộc về nhiều Departments
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_user')
            ->withPivot(['position_id', 'is_head', 'is_deputy', 'start_date', 'end_date', 'status'])
            ->withTimestamps();
    }

    /**
     * Relationship: User (teacher) can teach many Subjects
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_teacher')
            ->withPivot(['is_head', 'start_date', 'end_date', 'status'])
            ->withTimestamps();
    }

    /**
     * Relationship: User có nhiều Positions
     */
    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'department_user')
            ->withPivot(['department_id', 'is_head', 'is_deputy', 'start_date', 'end_date', 'status'])
            ->withTimestamps();
    }

    /**
     * Relationship: Invitations sent by user
     */
    public function sentInvitations()
    {
        return $this->hasMany(EmployeeInvitation::class, 'invited_by');
    }

    /**
     * Relationship: Invitations received by user
     */
    public function receivedInvitations()
    {
        return $this->hasMany(EmployeeInvitation::class, 'invited_user_id');
    }
    
    // Hierarchy relationships
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
    
    public function subordinates()
    {
        return $this->hasMany(User::class, 'manager_id');
    }
    
    // Get all subordinates recursively
    public function getAllSubordinates()
    {
        $subordinates = collect();
        
        foreach ($this->subordinates as $subordinate) {
            $subordinates->push($subordinate);
            $subordinates = $subordinates->merge($subordinate->getAllSubordinates());
        }
        
        return $subordinates;
    }
    
    // Get subordinates in specific branch/department context
    public function getSubordinatesInBranch($branchId)
    {
        $subordinates = collect();
        
        // Get all users in departments where this user is manager
        $departments = \App\Models\Department::where('branch_id', $branchId)
            ->whereHas('users', function($q) {
                $q->where('user_id', $this->id);
            })
            ->with(['users' => function($q) {
                $q->wherePivot('status', 'active')
                  ->wherePivot('manager_user_id', $this->id);
            }])
            ->get();
        
        foreach ($departments as $dept) {
            foreach ($dept->users as $user) {
                if ($user->id !== $this->id) {
                    $subordinates->push($user);
                    // Recursively get their subordinates
                    $subordinates = $subordinates->merge($user->getSubordinatesInBranch($branchId));
                }
            }
        }
        
        return $subordinates->unique('id');
    }
    
    // Check if user can access another user's data (branch-aware)
    public function canAccessUserData($targetUserId, $branchId = null)
    {
        // Super admin can access everything
        if ($this->is_super_admin) {
            return true;
        }
        
        // Can access own data
        if ($this->id === $targetUserId) {
            return true;
        }
        
        // If branch specified, check branch-specific hierarchy
        if ($branchId) {
            $subordinateIds = $this->getSubordinatesInBranch($branchId)->pluck('id')->toArray();
            return in_array($targetUserId, $subordinateIds);
        }
        
        // Otherwise check global hierarchy
        $subordinateIds = $this->getAllSubordinates()->pluck('id')->toArray();
        return in_array($targetUserId, $subordinateIds);
    }

    /**
     * Relationship: User quản lý nhiều Branches
     */
    public function managedBranches(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Branch::class, 'manager_id');
    }

    /**
     * Get primary branch của user based on their role (student > parent > employee)
     */
    public function getPrimaryBranch()
    {
        $branchId = $this->primary_branch_id; // Uses the accessor

        if ($branchId) {
            return Branch::find($branchId);
        }

        return null;
    }

    /**
     * Assign user vào branch
     */
    public function assignBranch(Branch|int $branch): void
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;
        $this->branches()->syncWithoutDetaching([$branchId]);
    }

    /**
     * Remove user khỏi branch
     */
    public function removeBranch(Branch|int $branch): void
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;
        $this->branches()->detach($branchId);
    }

    /**
     * Get user's preferred language or default
     */
    public function getPreferredLanguage(): Language
    {
        if ($this->language_id && $this->language) {
            return $this->language;
        }

        return Language::getDefault() ?? Language::first();
    }

    /**
     * Gán role cho user
     */
    public function assignRole(Role|string $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->syncWithoutDetaching([$role->id]);
    }

    /**
     * Thu hồi role từ user
     */
    public function removeRole(Role|string $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->detach($role->id);
    }

    /**
     * Kiểm tra user có role hay không
     * Super-admin luôn trả về true
     */
    public function hasRole(string $roleName): bool
    {
        // Super-admin có tất cả roles
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Kiểm tra user có bất kỳ role nào trong danh sách
     * Super-admin luôn trả về true
     */
    public function hasAnyRole(array $roles): bool
    {
        // Super-admin có tất cả roles
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Kiểm tra user có tất cả roles trong danh sách
     * Super-admin luôn trả về true
     */
    public function hasAllRoles(array $roles): bool
    {
        // Super-admin có tất cả roles
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        $userRoles = $this->roles()->pluck('name')->toArray();
        return count(array_intersect($roles, $userRoles)) === count($roles);
    }

    /**
     * Kiểm tra user có quyền hay không
     * Super-admin luôn có tất cả quyền
     * Kiểm tra cả permissions từ roles trực tiếp VÀ từ positions
     */
    public function hasPermission(string $permissionName): bool
    {
        // Super-admin có tất cả permissions
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        // Check permissions from direct roles
        $hasDirectPermission = $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionName) {
                $query->where('name', $permissionName)
                    ->where('is_active', true);
            })
            ->exists();
        
        if ($hasDirectPermission) {
            return true;
        }
        
        // Check permissions from positions (in case roles haven't been synced yet)
        $hasPositionPermission = \DB::table('department_user')
            ->join('position_role', 'department_user.position_id', '=', 'position_role.position_id')
            ->join('permission_role', 'position_role.role_id', '=', 'permission_role.role_id')
            ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
            ->where('department_user.user_id', $this->id)
            ->where('department_user.status', 'active')
            ->where('permissions.name', $permissionName)
            ->where('permissions.is_active', true)
            ->exists();

        if ($hasPositionPermission) {
            return true;
        }

        // Check permissions from department roles
        $hasDepartmentPermission = \DB::table('department_user')
            ->join('department_role', 'department_user.department_id', '=', 'department_role.department_id')
            ->join('permission_role', 'department_role.role_id', '=', 'permission_role.role_id')
            ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
            ->where('department_user.user_id', $this->id)
            ->where('department_user.status', 'active')
            ->where('permissions.name', $permissionName)
            ->where('permissions.is_active', true)
            ->exists();

        if ($hasDepartmentPermission) {
            return true;
        }

        // Check if user is a student (có record trong bảng students)
        // Nếu có, tự động có permissions của student role
        $studentRole = \DB::table('roles')->where('name', 'student')->first();
        if ($studentRole) {
            $isStudent = \DB::table('students')
                ->where('user_id', $this->id)
                ->where('is_active', true)
                ->exists();

            if ($isStudent) {
                $hasStudentPermission = \DB::table('permission_role')
                    ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
                    ->where('permission_role.role_id', $studentRole->id)
                    ->where('permissions.name', $permissionName)
                    ->where('permissions.is_active', true)
                    ->exists();

                if ($hasStudentPermission) {
                    return true;
                }
            }
        }

        // Check if user is a parent (có record trong bảng parents)
        // Nếu có, tự động có permissions của parent role
        $parentRole = \DB::table('roles')->where('name', 'parent')->first();
        if ($parentRole) {
            $isParent = \DB::table('parents')
                ->where('user_id', $this->id)
                ->where('is_active', true)
                ->exists();

            if ($isParent) {
                $hasParentPermission = \DB::table('permission_role')
                    ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
                    ->where('permission_role.role_id', $parentRole->id)
                    ->where('permissions.name', $permissionName)
                    ->where('permissions.is_active', true)
                    ->exists();

                if ($hasParentPermission) {
                    return true;
                }
            }
        }

        return false;
    }
    
    /**
     * Kiểm tra user có quyền trong branch cụ thể
     * Chỉ check permissions từ roles được gán qua department trong branch đó
     */
    public function hasPermissionInBranch(string $permissionName, int $branchId): bool
    {
        // Super-admin có tất cả permissions
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        // Check if user belongs to this branch
        if (!$this->branches()->where('branches.id', $branchId)->exists()) {
            return false;
        }
        
        // First check global roles (admin, teacher, etc.)
        if ($this->hasPermission($permissionName)) {
            return true;
        }
        
        // Get departments in this branch where user is assigned
        $departmentIds = \DB::table('department_user')
            ->join('departments', 'department_user.department_id', '=', 'departments.id')
            ->where('department_user.user_id', $this->id)
            ->where('department_user.status', 'active')
            ->where('departments.branch_id', $branchId)
            ->pluck('departments.id');
        
        if ($departmentIds->isEmpty()) {
            // User in branch but not in any department - already checked global roles above
            return false;
        }
        
        // Get positions from departments
        $positionIds = \DB::table('department_user')
            ->whereIn('department_id', $departmentIds)
            ->where('user_id', $this->id)
            ->where('status', 'active')
            ->pluck('position_id')
            ->filter();
        
        if ($positionIds->isEmpty()) {
            // No positions assigned - already checked global roles above
            return false;
        }
        
        // Check if any position has the required permission
        return \DB::table('position_role')
            ->join('permission_role', 'position_role.role_id', '=', 'permission_role.role_id')
            ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
            ->whereIn('position_role.position_id', $positionIds)
            ->where('permissions.name', $permissionName)
            ->where('permissions.is_active', true)
            ->exists();
    }

    /**
     * Kiểm tra user có quyền trên module
     * Super-admin luôn có quyền trên tất cả modules
     * Kiểm tra cả permissions từ roles trực tiếp VÀ từ positions
     */
    public function hasPermissionToModule(string $module): bool
    {
        // Super-admin có quyền trên tất cả modules
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        // Check from direct roles
        $hasDirectModule = $this->roles()
            ->whereHas('permissions', function ($query) use ($module) {
                $query->where('module', $module)
                    ->where('is_active', true);
            })
            ->exists();
        
        if ($hasDirectModule) {
            return true;
        }
        
        // Check from positions
        $hasPositionModule = \DB::table('department_user')
            ->join('position_role', 'department_user.position_id', '=', 'position_role.position_id')
            ->join('permission_role', 'position_role.role_id', '=', 'permission_role.role_id')
            ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
            ->where('department_user.user_id', $this->id)
            ->where('department_user.status', 'active')
            ->where('permissions.module', $module)
            ->where('permissions.is_active', true)
            ->exists();
        
        return $hasPositionModule;
    }

    /**
     * Lấy tất cả permissions của user
     * Super-admin có tất cả permissions
     * Bao gồm cả permissions từ roles trực tiếp VÀ từ positions
     */
    public function getAllPermissions()
    {
        // Super-admin có tất cả permissions
        if ($this->isSuperAdmin()) {
            return Permission::where('is_active', true)->get();
        }
        
        // Get permissions from direct roles
        $directRoleIds = $this->roles()->pluck('roles.id');
        
        // Get permissions from positions
        $positionRoleIds = \DB::table('department_user')
            ->join('position_role', 'department_user.position_id', '=', 'position_role.position_id')
            ->where('department_user.user_id', $this->id)
            ->where('department_user.status', 'active')
            ->pluck('position_role.role_id');
        
        // Merge all role IDs
        $allRoleIds = $directRoleIds->merge($positionRoleIds)->unique();
        
        if ($allRoleIds->isEmpty()) {
            return collect();
        }
        
        return Permission::whereHas('roles', function ($query) use ($allRoleIds) {
            $query->whereIn('roles.id', $allRoleIds);
        })->where('is_active', true)->get();
    }

    /**
     * Kiểm tra user có phải super-admin không
     */
    public function isSuperAdmin(): bool
    {
        return $this->roles()->where('name', 'super-admin')->exists();
    }

    // ==========================================
    // CLASSES & SCHEDULE RELATIONSHIPS
    // ==========================================

    /**
     * Classes where this user is the homeroom teacher
     */
    public function homeroomClasses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ClassModel::class, 'homeroom_teacher_id');
    }

    /**
     * Classes where this user teaches as a subject teacher
     */
    public function teachingClasses(): BelongsToMany
    {
        return $this->belongsToMany(ClassModel::class, 'class_subject', 'teacher_id', 'class_id')
            ->withPivot(['subject_id', 'periods_per_week', 'room_number', 'notes', 'status'])
            ->withTimestamps();
    }

    /**
     * Get all classes this user can view schedule for
     * - Homeroom classes: full schedule
     * - Teaching classes: their subjects only
     * - Head teacher subjects: all classes teaching those subjects
     */
    public function getViewableClasses($branchId = null)
    {
        $query = ClassModel::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // Get homeroom classes
        $homeroomClassIds = $this->homeroomClasses()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->pluck('id');

        // Get teaching classes
        $teachingClassIds = $this->teachingClasses()
            ->when($branchId, fn($q) => $q->where('classes.branch_id', $branchId))
            ->pluck('classes.id');

        // Get classes teaching subjects where this user is head teacher
        $headSubjectIds = $this->subjects()
            ->wherePivot('is_head', true)
            ->pluck('subjects.id');

        $headSubjectClassIds = collect();
        if ($headSubjectIds->isNotEmpty()) {
            $headSubjectClassIds = ClassModel::whereHas('subjects', function ($q) use ($headSubjectIds) {
                $q->whereIn('subjects.id', $headSubjectIds);
            })
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->pluck('id');
        }

        // Merge all class IDs
        $allClassIds = $homeroomClassIds
            ->merge($teachingClassIds)
            ->merge($headSubjectClassIds)
            ->unique();

        return $query->whereIn('id', $allClassIds)->get();
    }

    /**
     * Check if user can view full schedule of a class
     * (Only homeroom teachers can view full schedule)
     */
    public function canViewFullSchedule(ClassModel $class): bool
    {
        return $class->homeroom_teacher_id === $this->id;
    }

    /**
     * Get subjects this user can view in a specific class
     */
    public function getViewableSubjectsInClass(ClassModel $class)
    {
        // Homeroom teacher sees all
        if ($this->canViewFullSchedule($class)) {
            return $class->subjects;
        }

        // Get subjects where user is the assigned teacher
        $teachingSubjects = $class->subjects()
            ->wherePivot('teacher_id', $this->id)
            ->get();

        // Get subjects where user is head teacher
        $headSubjectIds = $this->subjects()
            ->wherePivot('is_head', true)
            ->pluck('subjects.id');

        $headSubjects = $class->subjects()
            ->whereIn('subjects.id', $headSubjectIds)
            ->get();

        return $teachingSubjects->merge($headSubjects)->unique('id');
    }

    /**
     * Check if user is a head teacher of any subject
     */
    public function isHeadTeacherOfAnySubject(): bool
    {
        return $this->subjects()->wherePivot('is_head', true)->exists();
    }

    /**
     * Get subjects where this user is the head teacher
     */
    public function headSubjects()
    {
        return $this->subjects()->wherePivot('is_head', true)->get();
    }

    /**
     * Relationship: Google Drive permissions for this user
     */
    public function googleDrivePermissions()
    {
        return $this->hasMany(GoogleDrivePermission::class);
    }

    /**
     * Get accessible Google Drive folders for this user
     */
    public function accessibleGoogleDriveFolders()
    {
        return GoogleDriveItem::whereHas('userPermissions', function ($query) {
            $query->where('user_id', $this->id)
                ->where('is_verified', true);
        })->where('type', 'folder');
    }
}
