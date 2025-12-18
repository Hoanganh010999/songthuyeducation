<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Enrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'customer_id',
        'student_id',
        'student_type',
        'product_id',
        'original_price',
        'discount_amount',
        'final_price',
        'paid_amount',
        'remaining_amount',
        'voucher_id',
        'campaign_id',
        'voucher_code',
        'total_sessions',
        'attended_sessions',
        'remaining_sessions',
        'price_per_session',
        'start_date',
        'end_date',
        'completed_at',
        'status',
        'branch_id',
        'assigned_to',
        'notes',
        'cancellation_reason',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'price_per_session' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'completed_at' => 'date',
        'metadata' => 'array',
    ];

    const STATUS_PENDING = 'pending';      // Chờ duyệt (chưa có income report approved)
    const STATUS_APPROVED = 'approved';    // Đã duyệt (income report approved, chờ thanh toán)
    const STATUS_PAID = 'paid';            // Đã thanh toán
    const STATUS_ACTIVE = 'active';        // Đang học
    const STATUS_COMPLETED = 'completed';  // Hoàn thành
    const STATUS_CANCELLED = 'cancelled';  // Đã hủy
    const STATUS_REFUNDED = 'refunded';    // Đã hoàn tiền

    /**
     * Relationships
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function student()
    {
        return $this->morphTo();
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function walletTransactions()
    {
        return $this->morphMany(WalletTransaction::class, 'transactionable');
    }

    public function incomeReports()
    {
        return IncomeReport::where('payer_info->enrollment_id', $this->id)->get();
    }

    public function hasApprovedIncomeReport(): bool
    {
        return IncomeReport::where('payer_info->enrollment_id', $this->id)
            ->where('status', 'approved')
            ->exists();
    }

    /**
     * Scopes
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope: Filter enrollments accessible by user based on organization structure
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\User $user
     * @param int|null $branchId Optional branch context for filtering
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAccessibleBy($query, $user, $branchId = null)
    {
        // Super admin sees all
        if ($user->is_super_admin ||
            $user->hasRole('super-admin') ||
            optional($user->roles->first())->name === 'super-admin') {
            return $query;
        }

        // Check if user has 'enrollments.view_all' permission
        if ($user->hasPermission('enrollments.view_all')) {
            return $query;
        }

        // Determine branch for department check
        if (!$branchId) {
            $primaryBranch = $user->getPrimaryBranch();
            $branchId = $primaryBranch ? $primaryBranch->id : null;
        }

        // Check access level based on department settings
        if ($branchId) {
            $accessLevel = ModuleDepartmentSetting::getUserAccessLevel('enrollments', $branchId, $user);

            if ($accessLevel === 'full') {
                // Head/Deputy of responsible department - see all in branch
                return $query->where('branch_id', $branchId);
            }

            if ($accessLevel === 'limited') {
                // Regular department member - only see assigned enrollments
                return $query->where('branch_id', $branchId)
                    ->where('assigned_to', $user->id);
            }

            if ($accessLevel === 'none') {
                // Not in responsible department - check general hierarchy
                // Fall through to subordinate check below
            }
        }

        // Default: filter by user hierarchy (assigned to user or subordinates)
        $subordinateIds = [$user->id];

        try {
            $allSubordinates = $user->getAllSubordinates();
            if ($allSubordinates && $allSubordinates->count() > 0) {
                $subordinateIds = array_merge($subordinateIds, $allSubordinates->pluck('id')->toArray());
            }
        } catch (\Exception $e) {
            // Ignore errors, just use current user
        }

        return $query->whereIn('assigned_to', $subordinateIds);
    }

    /**
     * Methods
     */
    public static function generateCode(): string
    {
        return DB::transaction(function () {
            $prefix = 'ENR';
            $date = date('Ymd');
            
            // Use DB lock to prevent race condition
            // IMPORTANT: withTrashed() to include soft-deleted records in the search
            // This ensures we don't reuse codes from soft-deleted enrollments
            $lastEnrollment = self::withTrashed()
                ->where('code', 'like', "{$prefix}{$date}%")
                ->orderBy('code', 'desc')
                ->lockForUpdate() // Add DB lock
                ->first();

            if ($lastEnrollment) {
                $lastNumber = (int) substr($lastEnrollment->code, -4);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            $code = $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
            
            // Double check for uniqueness (safety net)
            // Check both active AND soft-deleted records
            $attempts = 0;
            while (self::withTrashed()->where('code', $code)->lockForUpdate()->exists() && $attempts < 100) {
                $newNumber++;
                $code = $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
                $attempts++;
            }
            
            if ($attempts >= 100) {
                throw new \RuntimeException('Unable to generate unique enrollment code after 100 attempts');
            }
            
            return $code;
        });
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_amount' => $this->final_price,
            'remaining_amount' => 0,
        ]);
    }

    public function activate(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function cancel(string $reason = null): void
    {
        DB::transaction(function () use ($reason) {
            $this->update([
                'status' => self::STATUS_CANCELLED,
                'cancellation_reason' => $reason,
            ]);
            
            // Release voucher usage if exists
            if ($this->voucher_id) {
                $this->releaseVoucherUsage();
            }
        });
    }
    
    /**
     * Release voucher usage when enrollment is cancelled/rejected
     */
    public function releaseVoucherUsage(): void
    {
        if (!$this->voucher_id) {
            return;
        }
        
        \Log::info('🔓 ENROLLMENT: Releasing voucher usage', [
            'enrollment_id' => $this->id,
            'enrollment_code' => $this->code,
            'voucher_id' => $this->voucher_id,
        ]);
        
        // Delete voucher usage record
        $voucherUsage = VoucherUsage::where('enrollment_id', $this->id)->first();
        if ($voucherUsage) {
            $voucherUsage->delete();
            \Log::info('✅ ENROLLMENT: Voucher usage deleted', [
                'voucher_usage_id' => $voucherUsage->id,
            ]);
        }
        
        // Decrement voucher usage count
        $voucher = Voucher::find($this->voucher_id);
        if ($voucher && $voucher->usage_count > 0) {
            $usageBefore = $voucher->usage_count;
            $voucher->decrement('usage_count');
            $usageAfter = $voucher->fresh()->usage_count;
            
            \Log::info('📊 ENROLLMENT: Voucher usage decremented', [
                'voucher_id' => $this->voucher_id,
                'usage_before' => $usageBefore,
                'usage_after' => $usageAfter,
            ]);
        }
    }

    /**
     * Boot
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($enrollment) {
            if (empty($enrollment->code)) {
                $enrollment->code = self::generateCode();
            }
            
            // Calculate remaining sessions
            if ($enrollment->total_sessions) {
                $enrollment->remaining_sessions = $enrollment->total_sessions - $enrollment->attended_sessions;
            }
            
            // Calculate remaining amount
            $enrollment->remaining_amount = $enrollment->final_price - $enrollment->paid_amount;
        });

        static::updating(function ($enrollment) {
            if ($enrollment->isDirty(['total_sessions', 'attended_sessions'])) {
                $enrollment->remaining_sessions = $enrollment->total_sessions - $enrollment->attended_sessions;
            }
            
            if ($enrollment->isDirty(['final_price', 'paid_amount'])) {
                $enrollment->remaining_amount = $enrollment->final_price - $enrollment->paid_amount;
            }
        });
    }
}

