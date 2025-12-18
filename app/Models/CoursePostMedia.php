<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoursePostMedia extends Model
{
    protected $fillable = [
        'post_id',
        'media_type',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'url',
        'sort_order',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CoursePost::class, 'post_id');
    }

    public function getFullUrlAttribute(): string
    {
        if ($this->url) {
            return $this->url;
        }
        return asset('storage/' . $this->file_path);
    }
}
