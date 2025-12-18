<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleDepartmentSetting extends Model
{
    protected $fillable = [
        'branch_id',
        'module',
        'department_id',
    ];

    /**
     * Get the branch that owns this setting
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the department assigned to this module
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the department for a specific module in a branch
     */
    public static function getDepartmentForModule(string $module, int $branchId): ?Department
    {
        $setting = self::where('module', $module)
            ->where('branch_id', $branchId)
            ->first();

        return $setting ? $setting->department : null;
    }

    /**
     * Set the department for a module in a branch
     */
    public static function setDepartmentForModule(string $module, int $branchId, int $departmentId): self
    {
        return self::updateOrCreate(
            ['module' => $module, 'branch_id' => $branchId],
            ['department_id' => $departmentId]
        );
    }

    /**
     * Check if a user can access a module based on department assignment
     * Returns: 'full' (head/deputy), 'limited' (member), 'none' (not in department)
     */
    public static function getUserAccessLevel(string $module, int $branchId, User $user): string
    {
        // Super admin always has full access
        if ($user->is_super_admin || $user->hasRole('super-admin')) {
            return 'full';
        }

        // Check for view_all permission
        $viewAllPermission = $module . '.view_all';
        if ($user->hasPermission($viewAllPermission)) {
            return 'full';
        }

        // Get the department responsible for this module
        $department = self::getDepartmentForModule($module, $branchId);

        if (!$department) {
            // No department assigned yet - allow access based on basic permission
            return 'full';
        }

        // Check if user is in this department
        $userDepartment = $user->departments()
            ->where('department_id', $department->id)
            ->wherePivot('status', 'active')
            ->first();

        if (!$userDepartment) {
            // User is not in the responsible department
            return 'none';
        }

        // Check if user is head or deputy
        if ($userDepartment->pivot->is_head || $userDepartment->pivot->is_deputy) {
            return 'full';
        }

        // Regular member - limited access
        return 'limited';
    }

    /**
     * Get all user IDs in the department responsible for a module
     */
    public static function getDepartmentUserIds(string $module, int $branchId): array
    {
        $department = self::getDepartmentForModule($module, $branchId);

        if (!$department) {
            return [];
        }

        return $department->users()
            ->wherePivot('status', 'active')
            ->pluck('users.id')
            ->toArray();
    }
}
