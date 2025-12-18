<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialPlanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_plan_id',
        'account_item_id',
        'type',
        'planned_amount',
        'description',
    ];

    protected $casts = [
        'planned_amount' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function financialPlan()
    {
        return $this->belongsTo(FinancialPlan::class);
    }

    public function accountItem()
    {
        return $this->belongsTo(AccountItem::class);
    }

    public function expenseProposals()
    {
        return $this->hasMany(ExpenseProposal::class);
    }

    public function incomeReports()
    {
        return $this->hasMany(IncomeReport::class);
    }

    /**
     * Scopes
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    /**
     * Accessors
     */
    public function getActualAmountAttribute()
    {
        if ($this->type === 'income') {
            return $this->incomeReports()->approved()->sum('amount');
        }
        return $this->expenseProposals()->approved()->sum('amount');
    }

    public function getVarianceAttribute()
    {
        return $this->planned_amount - $this->actual_amount;
    }

    public function getCompletionRateAttribute()
    {
        if ($this->planned_amount == 0) return 0;
        return ($this->actual_amount / $this->planned_amount) * 100;
    }
}
