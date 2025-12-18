<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'financial_plan_id',
        'financial_plan_item_id',
        'account_item_id',
        'cash_account_id',
        'amount',
        'requested_date',
        'description',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejected_reason',
        'payment_date',
        'payment_method',
        'payment_ref',
        'branch_id',
        'attachments',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_date' => 'date',
        'approved_at' => 'datetime',
        'payment_date' => 'date',
        'attachments' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($proposal) {
            if (empty($proposal->code)) {
                $proposal->code = static::generateCode();
            }
        });
    }

    public static function generateCode()
    {
        $year = date('Y');
        $month = date('m');
        $prefix = 'DC' . $year . $month;
        
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
     * Relationships
     */
    public function financialPlan()
    {
        return $this->belongsTo(FinancialPlan::class);
    }

    public function financialPlanItem()
    {
        return $this->belongsTo(FinancialPlanItem::class);
    }

    public function accountItem()
    {
        return $this->belongsTo(AccountItem::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function cashAccount()
    {
        return $this->belongsTo(CashAccount::class, 'cash_account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function financialTransaction()
    {
        return $this->morphOne(FinancialTransaction::class, 'transactionable');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['approved', 'paid']);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
