<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualitySetting extends Model
{
    protected $fillable = [
        'branch_id',
        'industry',
        'setting_key',
        'setting_value',
    ];

    protected $casts = [
        'setting_value' => 'array',
    ];

    /**
     * Get the branch that owns the setting
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
