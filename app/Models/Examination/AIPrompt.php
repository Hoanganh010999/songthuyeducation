<?php

namespace App\Models\Examination;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class AIPrompt extends Model
{
    use HasFactory;

    protected $table = 'ai_prompts';

    protected $fillable = [
        'module',
        'prompt',
        'created_by',
    ];

    /**
     * Get the user who created this prompt.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
