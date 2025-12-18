<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category_id',
        'branch_id',
        'type',
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
     * Category
     */
    public function category()
    {
        return $this->belongsTo(AccountCategory::class);
    }

    /**
     * Financial plan items
     */
    public function financialPlanItems()
    {
        return $this->hasMany(FinancialPlanItem::class);
    }

    /**
     * Expense proposals
     */
    public function expenseProposals()
    {
        return $this->hasMany(ExpenseProposal::class);
    }

    /**
     * Income reports
     */
    public function incomeReports()
    {
        return $this->hasMany(IncomeReport::class);
    }

    /**
     * Financial transactions
     */
    public function financialTransactions()
    {
        return $this->hasMany(FinancialTransaction::class);
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

    // Accessor to get cost_type from category
    public function getCostTypeAttribute()
    {
        return $this->category ? $this->category->cost_type : null;
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
