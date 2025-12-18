<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class VocabularyAudioRecording extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vocabulary_entry_id',
        'file_path',
        'duration_seconds',
        'check_status',
        'overall_score',
        'accuracy_score',
        'fluency_score',
        'completeness_score',
        'transcribed_text',
        'phoneme_results',
        'feedback',
        'checked_at'
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'overall_score' => 'decimal:2',
        'accuracy_score' => 'decimal:2',
        'fluency_score' => 'decimal:2',
        'completeness_score' => 'decimal:2',
        'phoneme_results' => 'array',
        'checked_at' => 'datetime'
    ];

    /**
     * User relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vocabulary entry relationship
     */
    public function vocabularyEntry()
    {
        return $this->belongsTo(VocabularyEntry::class);
    }

    /**
     * Delete audio file from storage
     */
    public function deleteFile()
    {
        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            Storage::disk('public')->delete($this->file_path);
        }
    }

    /**
     * Boot method - delete file when model is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($recording) {
            $recording->deleteFile();
        });
    }
}

