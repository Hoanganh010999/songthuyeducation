<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Translation extends Model
{
    protected $fillable = [
        'language_id',
        'group',
        'key',
        'value',
    ];

    /**
     * Get the language that owns this translation
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * Get translation value by language code, group, and key
     */
    public static function get(string $languageCode, string $group, string $key, ?string $default = null): ?string
    {
        $language = Language::where('code', $languageCode)->first();
        
        if (!$language) {
            return $default;
        }

        $translation = static::where('language_id', $language->id)
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        return $translation?->value ?? $default;
    }

    /**
     * Get all translations for a language and group
     */
    public static function getGroup(string $languageCode, string $group): array
    {
        $language = Language::where('code', $languageCode)->first();
        
        if (!$language) {
            return [];
        }

        return static::where('language_id', $language->id)
            ->where('group', $group)
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Set or update a translation
     */
    public static function set(string $languageCode, string $group, string $key, string $value): self
    {
        $language = Language::where('code', $languageCode)->firstOrFail();

        return static::updateOrCreate(
            [
                'language_id' => $language->id,
                'group' => $group,
                'key' => $key,
            ],
            [
                'value' => $value,
            ]
        );
    }

    /**
     * Get all available groups
     */
    public static function getGroups(): array
    {
        return static::distinct()
            ->pluck('group')
            ->toArray();
    }

    /**
     * Scope: Filter by group
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope: Filter by language
     */
    public function scopeByLanguage($query, $languageId)
    {
        return $query->where('language_id', $languageId);
    }
}
