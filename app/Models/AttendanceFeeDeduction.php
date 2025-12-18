<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceFeeDeduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'student_id',
        'class_id',
        'session_id',
        'policy_id',
        'transaction_type',
        'consecutive_absence_count',
        'refund_status',
        'refund_approved_by',
        'refund_approved_at',
        'refund_reason',
        'hourly_rate',
        'deduction_percent',
        'deduction_amount',
        'wallet_transaction_id',
        'notes',
        'applied_at',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'deduction_percent' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'applied_at' => 'datetime',
        'refund_approved_at' => 'datetime',
        'consecutive_absence_count' => 'integer',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassLessonSession::class, 'session_id');
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(AttendanceFeePolicy::class, 'policy_id');
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    public function refundApprovedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'refund_approved_by');
    }
}
