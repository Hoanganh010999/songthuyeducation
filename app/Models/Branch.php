<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = [
        'code',
        'name',
        'phone',
        'email',
        'address',
        'city',
        'district',
        'ward',
        'manager_id',
        'is_active',
        'is_headquarters',
        'description',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_headquarters' => 'boolean',
        'metadata' => 'array',
    ];

    protected $appends = [
        'full_address',
    ];

    /**
     * Relationship: Branch có một manager (User)
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Relationship: Branch có nhiều users (Many-to-Many)
     */
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_user')
            ->withTimestamps();
    }

    /**
     * Relationship: Branch có nhiều Zalo accounts
     */
    public function zaloAccounts(): HasMany
    {
        return $this->hasMany(\App\Models\ZaloAccount::class);
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->ward,
            $this->district,
            $this->city,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get active branches
     */
    public static function active()
    {
        return static::where('is_active', true);
    }

    /**
     * Get headquarters branch
     */
    public static function headquarters()
    {
        return static::where('is_headquarters', true)->first();
    }

    /**
     * Scope: Only active branches
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Search by name or code
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%");
        });
    }
}
