<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomeReport extends Model
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
        'received_date',
        'payer_name',
        'payer_phone',
        'payer_info',
        'description',
        'status',
        'reported_by',
        'approved_by',
        'approved_at',
        'verified_by',
        'verified_at',
        'rejected_reason',
        'payment_method',
        'payment_ref',
        'branch_id',
        'attachments',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'received_date' => 'date',
        'approved_at' => 'datetime',
        'verified_at' => 'datetime',
        'payer_info' => 'array',
        'attachments' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($report) {
            if (empty($report->code)) {
                $report->code = static::generateCode();
            }
        });
    }

    public static function generateCode()
    {
        $year = date('Y');
        $month = date('m');
        $prefix = 'BT' . $year . $month;
        
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

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function cashAccount()
    {
        return $this->belongsTo(CashAccount::class);
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
        return $query->where('status', 'approved');
    }

    public function scopeUnplanned($query)
    {
        return $query->whereNull('financial_plan_id');
    }
}
