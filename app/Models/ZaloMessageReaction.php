<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ZaloMessageReaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'zalo_message_id',
        'zalo_message_id_external',
        'zalo_user_id',
        'reaction_icon',
        'reaction_type',
        'reaction_source',
        'reaction_data',
        'reacted_at',
    ];

    protected $casts = [
        'reaction_data' => 'array',
        'reacted_at' => 'datetime',
    ];

    // Relationships
    public function message()
    {
        return $this->belongsTo(ZaloMessage::class);
    }
}
