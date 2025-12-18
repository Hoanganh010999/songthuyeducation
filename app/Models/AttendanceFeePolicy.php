<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceFeePolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'branch_id',
        'is_active',
        'absence_unexcused_percent',
        'absence_consecutive_threshold',
        'absence_excused_free_limit',
        'absence_excused_percent',
        'late_deduct_percent',
        'late_grace_minutes',
        'late_penalty_amount',
        'late_penalty_threshold',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'absence_unexcused_percent' => 'decimal:2',
        'absence_consecutive_threshold' => 'integer',
        'absence_excused_free_limit' => 'integer',
        'absence_excused_percent' => 'decimal:2',
        'late_deduct_percent' => 'decimal:2',
        'late_grace_minutes' => 'integer',
        'late_penalty_amount' => 'decimal:2',
        'late_penalty_threshold' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Activate this policy and deactivate others
     */
    public function activate(): void
    {
        // Deactivate all other policies in the same branch
        static::where('branch_id', $this->branch_id)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);
        
        // Activate this one
        $this->update(['is_active' => true]);
    }

    /**
     * Get the active policy for a branch
     */
    public static function getActive(?int $branchId = null): ?self
    {
        return static::where('is_active', true)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->first();
    }
}
