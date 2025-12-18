<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ZaloFriendBranch extends Pivot
{
    protected $table = 'zalo_friend_branches';

    protected $fillable = [
        'zalo_friend_id',
        'branch_id',
        'department_id',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    // Relationships
    public function friend()
    {
        return $this->belongsTo(ZaloFriend::class, 'zalo_friend_id');
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
