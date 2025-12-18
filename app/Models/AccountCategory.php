<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'cost_type',
        'parent_id',
        'branch_id',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Branch
     */
    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    /**
     * Parent category
     */
    public function parent()
    {
        return $this->belongsTo(AccountCategory::class, 'parent_id');
    }

    /**
     * Child categories
     */
    public function children()
    {
        return $this->hasMany(AccountCategory::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Account items under this category
     */
    public function accountItems()
    {
        return $this->hasMany(AccountItem::class, 'category_id')->orderBy('sort_order');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for current user's branch
     */
    public function scopeForCurrentBranch($query)
    {
        $user = auth()->user();
        if (!$user) {
            return $query;
        }

        // If user has branch, show branch-specific + global (branch_id = null)
        if ($user->branch_id) {
            return $query->where(function($q) use ($user) {
                $q->where('branch_id', $user->branch_id)
                  ->orWhereNull('branch_id');
            });
        }

        // Admin without branch sees all
        return $query;
    }

    /**
     * Scope for specific branch
     */
    public function scopeForBranch($query, $branchId)
    {
        if (!$branchId) {
            return $query;
        }

        return $query->where(function($q) use ($branchId) {
            $q->where('branch_id', $branchId)
              ->orWhereNull('branch_id');
        });
    }
}
