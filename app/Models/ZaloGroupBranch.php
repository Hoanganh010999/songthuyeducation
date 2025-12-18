<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ZaloGroupBranch extends Pivot
{
    protected $table = 'zalo_group_branches';

    protected $fillable = [
        'zalo_group_id',
        'branch_id',
        'department_id',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    // Relationships
    public function group()
    {
        return $this->belongsTo(ZaloGroup::class, 'zalo_group_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
