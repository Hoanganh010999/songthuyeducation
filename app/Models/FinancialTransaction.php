<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'transaction_type',
        'status',
        'transactionable_type',
        'transactionable_id',
        'financial_plan_id',
        'account_item_id',
        'cash_account_id',
        'amount',
        'transaction_date',
        'description',
        'payment_method',
        'payment_ref',
        'recorded_by',
        'approved_by',
        'approved_at',
        'verified_by',
        'verified_at',
        'rejected_reason',
        'branch_id',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'approved_at' => 'datetime',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->code)) {
                $transaction->code = static::generateCode();
            }
        });
    }

    public static function generateCode()
    {
        $year = date('Y');
        $month = date('m');
        $prefix = 'GD' . $year . $month;
        
        $lastCode = static::where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();
        
        if ($lastCode) {
            $lastNumber = intval(substr($lastCode->code, -3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Polymorphic relationship to source (ExpenseProposal or IncomeReport)
     */
    public function transactionable()
    {
        return $this->morphTo();
    }

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

    public function cashAccount()
    {
        return $this->belongsTo(CashAccount::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scopes
     */
    public function scopeIncome($query)
    {
        return $query->where('transaction_type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('transaction_type', 'expense');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }
}
