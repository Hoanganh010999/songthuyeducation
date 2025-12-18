<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'class_id',
        'session_id',
        'created_by',
        'title',
        'description',
        'instructions',
        'attachment_path',
        'due_date',
        'max_score',
        'status',
        'submissions_count',
        'branch_id',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'max_score' => 'integer',
        'submissions_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassLessonSession::class, 'session_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(CourseSubmission::class, 'assignment_id');
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast();
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
