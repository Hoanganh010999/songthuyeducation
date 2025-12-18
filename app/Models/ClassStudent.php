<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassStudent extends Pivot
{
    use HasFactory;

    protected $table = 'class_students';

    protected $fillable = [
        'class_id',
        'student_id',
        'enrollment_date',
        'status',
        'discount_percent',
        'notes',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'discount_percent' => 'decimal:2',
    ];

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function student(): BelongsTo
    {
        // student_id in class_students is actually user_id, not students.id
        return $this->belongsTo(Student::class, 'student_id', 'user_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id', 'student_id');
    }

    public function homeworkSubmissions(): HasMany
    {
        return $this->hasMany(HomeworkSubmission::class, 'student_id', 'student_id');
    }

    public function sessionComments(): HasMany
    {
        return $this->hasMany(SessionComment::class, 'student_id', 'student_id');
    }
}
