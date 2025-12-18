<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionMaterial extends Model
{
    use HasFactory;

    protected $table = 'session_materials';

    protected $fillable = [
        'lesson_plan_session_id',
        'branch_id',
        'title',
        'description',
        'content',
        'material_type',
        'order',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the session that owns the material
     */
    public function session()
    {
        return $this->belongsTo(LessonPlanSession::class, 'lesson_plan_session_id');
    }

    /**
     * Get the user who created this material
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this material
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the branch this material belongs to
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
