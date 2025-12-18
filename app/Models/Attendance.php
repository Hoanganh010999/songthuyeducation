<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'student_id',
        'status',
        'check_in_time',
        'homework_score',
        'participation_score',
        'notes',
        'evaluation_data',
        'evaluation_pdf_url',
        'marked_by',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'evaluation_data' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassLessonSession::class, 'session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(AttendanceFeeDeduction::class);
    }
}
