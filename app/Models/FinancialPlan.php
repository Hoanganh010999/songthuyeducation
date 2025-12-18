<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'plan_type',
        'year',
        'quarter',
        'month',
        'total_income_planned',
        'total_expense_planned',
        'status',
        'approved_by',
        'approved_at',
        'branch_id',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'quarter' => 'integer',
        'month' => 'integer',
        'total_income_planned' => 'decimal:2',
        'total_expense_planned' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * Boot method to auto-generate code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->code)) {
                $plan->code = static::generateCode($plan);
            }
        });
    }

    /**
     * Generate unique code: KH202501Q1, KH202501M01
     */
    public static function generateCode($plan)
    {
        $prefix = 'KH' . $plan->year;
        
        if ($plan->plan_type === 'quarterly') {
            $suffix = 'Q' . $plan->quarter;
        } else {
            $suffix = 'M' . str_pad($plan->month, 2, '0', STR_PAD_LEFT);
        }
        
        $code = $prefix . $suffix;
        
        // Check uniqueness
        $count = static::where('code', 'like', $code . '%')->count();
        if ($count > 0) {
            $code .= '_' . ($count + 1);
        }
        
        return $code;
    }

    /**
     * Relationships
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function planItems()
    {
        return $this->hasMany(FinancialPlanItem::class)->orderBy('type');
    }

    public function expenseProposals()
    {
        return $this->hasMany(ExpenseProposal::class);
    }

    public function incomeReports()
    {
        return $this->hasMany(IncomeReport::class);
    }

    public function financialTransactions()
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['approved', 'active']);
    }

    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeQuarterly($query)
    {
        return $query->where('plan_type', 'quarterly');
    }

    public function scopeMonthly($query)
    {
        return $query->where('plan_type', 'monthly');
    }

    /**
     * Accessors
     */
    public function getPeriodNameAttribute()
    {
        if ($this->plan_type === 'quarterly') {
            return "Quý {$this->quarter}/{$this->year}";
        }
        return "Tháng {$this->month}/{$this->year}";
    }

    public function getTotalPlannedAttribute()
    {
        return $this->total_income_planned + $this->total_expense_planned;
    }
}
