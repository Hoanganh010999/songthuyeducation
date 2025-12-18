<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ModuleDepartmentSetting;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'phone',
        'email',
        'date_of_birth',
        'gender',
        'address',
        'city',
        'district',
        'ward',
        'stage',
        'stage_order',
        'source',
        'branch_id',
        'assigned_to',
        'user_id', // For linking to users table
        'notes',
        'estimated_value',
        'expected_close_date',
        'closed_at',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'expected_close_date' => 'date',
        'closed_at' => 'date',
        'estimated_value' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'stage_order' => 'integer',
    ];

    protected $appends = [
        'full_address',
        'stage_label',
    ];

    const STAGE_LEAD = 'lead';
    const STAGE_CONTACTED = 'contacted';
    const STAGE_QUALIFIED = 'qualified';
    const STAGE_PROPOSAL = 'proposal';
    const STAGE_NEGOTIATION = 'negotiation';
    const STAGE_CLOSED_WON = 'closed_won';
    const STAGE_CLOSED_LOST = 'closed_lost';

    public static function getStages(): array
    {
        return [
            self::STAGE_LEAD => 'Khách Tiềm Năng',
            self::STAGE_CONTACTED => 'Đã Liên Hệ',
            self::STAGE_QUALIFIED => 'Đủ Điều Kiện',
            self::STAGE_PROPOSAL => 'Đã Gửi Đề Xuất',
            self::STAGE_NEGOTIATION => 'Đang Đàm Phán',
            self::STAGE_CLOSED_WON => 'Chốt Thành Công',
            self::STAGE_CLOSED_LOST => 'Mất Khách',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function interactions()
    {
        return $this->hasMany(CustomerInteraction::class)->latest();
    }

    public function latestInteraction()
    {
        return $this->hasOne(CustomerInteraction::class)->latestOfMany('interaction_date');
    }

    public function children()
    {
        return $this->hasMany(CustomerChild::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function calendarEvents()
    {
        return $this->morphMany(CalendarEvent::class, 'eventable');
    }

    public function placementTestEvent()
    {
        return $this->morphOne(CalendarEvent::class, 'eventable')
            ->where('category', 'placement_test')
            ->latest();
    }

    /**
     * Trial class registrations
     */
    public function trialClasses()
    {
        return $this->morphMany(TrialStudent::class, 'trialable');
    }

    /**
     * Active trial registrations
     */
    public function activeTrials()
    {
        return $this->trialClasses()->active();
    }

    /**
     * Wallet (ví tiền của khách hàng)
     */
    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'owner');
    }

    /**
     * Enrollments (đơn đăng ký khóa học)
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Student enrollments (khi customer là người học)
     */
    public function studentEnrollments()
    {
        return $this->morphMany(Enrollment::class, 'student');
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->ward,
            $this->district,
            $this->city,
        ]);

        return implode(', ', $parts);
    }

    public function getStageLabelAttribute(): string
    {
        return self::getStages()[$this->stage] ?? $this->stage ?? '';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }

    public function scopeByBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByBranches($query, array $branchIds)
    {
        return $query->whereIn('branch_id', $branchIds);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }


    public static function generateCode(): string
    {
        $prefix = 'CUS';
        $date = date('Ymd');
        
        $lastCustomer = self::where('code', 'like', "{$prefix}{$date}%")
            ->orderBy('code', 'desc')
            ->first();
        
        if ($lastCustomer) {
            $lastNumber = (int) substr($lastCustomer->code, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function moveToStage(string $stage, int $order = 0): void
    {
        $this->update([
            'stage' => $stage,
            'stage_order' => $order,
        ]);

        if (in_array($stage, [self::STAGE_CLOSED_WON, self::STAGE_CLOSED_LOST])) {
            $this->update(['closed_at' => now()]);
        }
    }

    /**
     * Scope: Accessible customers based on department settings and hierarchy
     */
    public function scopeAccessibleBy($query, $user, $branchId = null)
    {
        // Super admin sees all
        if ($user->is_super_admin ||
            $user->hasRole('super-admin') ||
            optional($user->roles->first())->name === 'super-admin') {
            return $query;
        }

        // Check if user has 'customers.view_all' permission
        if ($user->hasPermission('customers.view_all')) {
            return $query;
        }

        // Determine branch for department check
        if (!$branchId) {
            $primaryBranch = $user->getPrimaryBranch();
            $branchId = $primaryBranch ? $primaryBranch->id : null;
        }

        // Check access level based on department settings
        if ($branchId) {
            $accessLevel = ModuleDepartmentSetting::getUserAccessLevel('customers', $branchId, $user);

            if ($accessLevel === 'full') {
                // Head/Deputy of responsible department - see all in branch
                return $query->where('branch_id', $branchId);
            }

            if ($accessLevel === 'limited') {
                // Regular department member - only see assigned customers
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->code)) {
                $customer->code = self::generateCode();
            }
        });
    }
}
