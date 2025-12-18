<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'student_id',
        'teacher_id',
        'comment',
        'rating',
        'behavior',
        'participation',
    ];

    protected $casts = [
        'rating' => 'float',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassLessonSession::class, 'session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
