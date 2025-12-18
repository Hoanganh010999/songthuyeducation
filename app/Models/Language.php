<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    protected $fillable = [
        'name',
        'code',
        'flag',
        'direction',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get all translations for this language
     */
    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
    }

    /**
     * Get all users using this language
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get translation by group and key
     */
    public function getTranslation(string $group, string $key): ?string
    {
        $translation = $this->translations()
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        return $translation?->value;
    }

    /**
     * Get all translations for a group
     */
    public function getGroupTranslations(string $group): array
    {
        return $this->translations()
            ->where('group', $group)
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Set as default language
     */
    public function setAsDefault(): void
    {
        // Remove default from all other languages
        static::where('id', '!=', $this->id)->update(['is_default' => false]);
        
        // Set this as default
        $this->update(['is_default' => true]);
    }

    /**
     * Get the default language
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get active languages
     */
    public static function getActive()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Scope: Active languages only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
