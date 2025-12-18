<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'owner_type',
        'code',
        'balance',
        'total_deposited',
        'total_spent',
        'branch_id',
        'currency',
        'is_active',
        'is_locked',
        'lock_reason',
        'metadata',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_deposited' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'balance' => 0,
        'total_deposited' => 0,
        'total_spent' => 0,
        'is_active' => true,
    ];

    /**
     * Relationships
     */
    public function owner()
    {
        return $this->morphTo();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_locked', false);
    }

    /**
     * Methods
     */
    public static function generateCode(): string
    {
        $prefix = 'WAL';
        $lastWallet = self::where('code', 'like', "{$prefix}%")
            ->orderBy('code', 'desc')
            ->first();

        if ($lastWallet) {
            $lastNumber = (int) substr($lastWallet->code, 3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    public function deposit(float $amount, $transactionable = null, string $description = null): WalletTransaction
    {
        \Log::info('💰 Wallet::deposit() called', [
            'wallet_id' => $this->id,
            'amount' => $amount,
            'current_balance' => $this->balance,
            'transactionable_type' => $transactionable ? get_class($transactionable) : null,
            'transactionable_id' => $transactionable?->id,
            'description' => $description,
            'auth_user_id' => auth()->id(),
        ]);

        if ($this->is_locked) {
            \Log::error('❌ Wallet is locked', ['wallet_id' => $this->id]);
            throw new \Exception('Ví đã bị khóa, không thể thực hiện giao dịch.');
        }

        // ⚠️ FIX: Ensure balance is not NULL
        $balanceBefore = $this->balance ?? 0;
        $this->balance = ($this->balance ?? 0) + $amount;
        $this->total_deposited = ($this->total_deposited ?? 0) + $amount;
        $this->save();

        \Log::info('✅ Wallet balance updated', [
            'wallet_id' => $this->id,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
        ]);

        try {
            $transaction = $this->transactions()->create([
                'transaction_code' => WalletTransaction::generateCode(),
                'type' => 'deposit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $this->balance,
                'transactionable_id' => $transactionable?->id,
                'transactionable_type' => $transactionable ? get_class($transactionable) : null,
                'description' => $description ?? 'Nạp tiền vào ví',
                'status' => 'completed',
                'completed_at' => now(),
                'created_by' => auth()->id(),
            ]);

            \Log::info('✅ WalletTransaction created', [
                'wallet_transaction_id' => $transaction->id,
                'transaction_code' => $transaction->transaction_code,
                'amount' => $transaction->amount,
            ]);

            return $transaction;
        } catch (\Exception $e) {
            \Log::error('❌ Failed to create WalletTransaction', [
                'wallet_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function withdraw(float $amount, $transactionable = null, string $description = null): WalletTransaction
    {
        if ($this->is_locked) {
            throw new \Exception('Ví đã bị khóa, không thể thực hiện giao dịch.');
        }

        if ($this->balance < $amount) {
            throw new \Exception('Số dư không đủ để thực hiện giao dịch.');
        }

        $balanceBefore = $this->balance ?? 0;
        $this->balance = ($this->balance ?? 0) - $amount;
        $this->total_spent = ($this->total_spent ?? 0) + $amount;
        $this->save();

        return $this->transactions()->create([
            'transaction_code' => WalletTransaction::generateCode(),
            'type' => 'withdraw',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'transactionable_id' => $transactionable?->id,
            'transactionable_type' => $transactionable ? get_class($transactionable) : null,
            'description' => $description ?? 'Rút tiền từ ví',
            'status' => 'completed',
            'completed_at' => now(),
            'created_by' => auth()->id(),
        ]);
    }

    public function refund(float $amount, $transactionable = null, string $description = null): WalletTransaction
    {
        $balanceBefore = $this->balance ?? 0;
        $this->balance = ($this->balance ?? 0) + $amount;
        $this->save();

        return $this->transactions()->create([
            'transaction_code' => WalletTransaction::generateCode(),
            'type' => 'refund',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'transactionable_id' => $transactionable?->id,
            'transactionable_type' => $transactionable ? get_class($transactionable) : null,
            'description' => $description ?? 'Hoàn tiền vào ví',
            'status' => 'completed',
            'completed_at' => now(),
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Boot
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($wallet) {
            if (empty($wallet->code)) {
                $wallet->code = self::generateCode();
            }
        });
    }
}

