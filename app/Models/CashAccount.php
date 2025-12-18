<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'account_number',
        'bank_name',
        'bank_branch',
        'balance',
        'branch_id',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Boot method for auto-generating code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cashAccount) {
            if (empty($cashAccount->code)) {
                // Generate code: TK + YYYYMM + sequential number
                $prefix = 'TK' . date('Ym');
                $lastAccount = static::where('code', 'like', $prefix . '%')
                    ->orderBy('code', 'desc')
                    ->first();

                if ($lastAccount) {
                    $lastNumber = (int) substr($lastAccount->code, -3);
                    $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
                } else {
                    $newNumber = '001';
                }

                $cashAccount->code = $prefix . $newNumber;
            }
        });
    }

    /**
     * Branch relationship
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCash($query)
    {
        return $query->where('type', 'cash');
    }

    public function scopeBank($query)
    {
        return $query->where('type', 'bank');
    }

    /**
     * Update balance
     */
    public function updateBalance($amount, $type = 'add')
    {
        if ($type === 'add') {
            $this->balance += $amount;
        } else {
            $this->balance -= $amount;
        }
        $this->save();
    }
}
