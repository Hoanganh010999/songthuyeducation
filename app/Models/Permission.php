<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'module',
        'action',
        'name',
        'display_name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Relationship: Permission thuộc nhiều Roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role')
            ->withTimestamps();
    }

    /**
     * Lấy tất cả permissions theo module
     */
    public static function getByModule(string $module)
    {
        return static::where('module', $module)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Lấy danh sách modules (sorted alphabetically)
     */
    public static function getModules(): array
    {
        return static::where('is_active', true)
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->toArray();
    }

    /**
     * Tạo permission name từ module và action
     */
    public static function makeName(string $module, string $action): string
    {
        return "{$module}.{$action}";
    }
}
