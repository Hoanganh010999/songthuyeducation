<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VocabularyEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'word',
        'word_form',
        'definition',
        'synonym',
        'antonym',
        'example',
        'review_count',
        'last_reviewed_at',
        'mastery_level'
    ];

    protected $casts = [
        'last_reviewed_at' => 'datetime',
        'review_count' => 'integer',
        'mastery_level' => 'integer'
    ];

    /**
     * User relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Audio recordings relationship
     */
    public function audioRecordings()
    {
        return $this->hasMany(VocabularyAudioRecording::class);
    }

    /**
     * Increment review count
     */
    public function incrementReview()
    {
        $this->increment('review_count');
        $this->update(['last_reviewed_at' => now()]);
    }

    /**
     * Update mastery level based on performance
     */
    public function updateMastery(bool $correct)
    {
        if ($correct) {
            $this->mastery_level = min(5, $this->mastery_level + 1);
        } else {
            $this->mastery_level = max(0, $this->mastery_level - 1);
        }
        $this->save();
    }
}

