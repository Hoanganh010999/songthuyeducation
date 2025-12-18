<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZaloGroupMember extends Model
{
    protected $fillable = [
        'zalo_group_id',
        'zalo_user_id',
        'display_name',
        'avatar_url',
        'avatar_path',
        'is_admin',
        'metadata',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'metadata' => 'array',
    ];

    // Relationships
    public function group()
    {
        return $this->belongsTo(ZaloGroup::class, 'zalo_group_id');
    }

    // Scopes
    public function scopeForGroup($query, $groupId)
    {
        return $query->where('zalo_group_id', $groupId);
    }

    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }
}
