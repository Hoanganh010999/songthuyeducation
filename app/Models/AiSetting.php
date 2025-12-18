<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AiSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'module',
        'provider',
        'api_key_encrypted',
        'model',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'api_key_encrypted',
    ];

    /**
     * Get settings for a specific branch and module (returns first active provider)
     * @deprecated Use getSettingsByProvider() for better control
     */
    public static function getSettings(?int $branchId, string $module = 'examination'): ?self
    {
        $branchId = $branchId ?? 0;
        return static::where('branch_id', $branchId)
            ->where('module', $module)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get settings for a specific branch, module, and provider
     */
    public static function getSettingsByProvider(?int $branchId, string $module, string $provider): ?self
    {
        $branchId = $branchId ?? 0;
        return static::where('branch_id', $branchId)
            ->where('module', $module)
            ->where('provider', $provider)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get all provider settings for a specific branch and module
     * Returns an array indexed by provider name
     */
    public static function getAllSettingsForModule(?int $branchId, string $module): array
    {
        $branchId = $branchId ?? 0;
        $settings = static::where('branch_id', $branchId)
            ->where('module', $module)
            ->where('is_active', true)
            ->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->provider] = $setting;
        }

        return $result;
    }

    /**
     * Get decrypted API key
     */
    public function getApiKeyAttribute(): ?string
    {
        if (empty($this->api_key_encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->api_key_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted API key
     */
    public function setApiKeyAttribute(string $value): void
    {
        $this->attributes['api_key_encrypted'] = Crypt::encryptString($value);
    }

    /**
     * Check if API key is set
     */
    public function hasApiKey(): bool
    {
        return !empty($this->api_key_encrypted);
    }

    /**
     * Get masked API key for display
     */
    public function getMaskedApiKeyAttribute(): string
    {
        $apiKey = $this->api_key;

        if (empty($apiKey)) {
            return '';
        }

        $length = strlen($apiKey);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($apiKey, 0, 4) . '...' . substr($apiKey, -4);
    }

    /**
     * Save settings for a specific branch, module, and provider
     */
    public static function saveSettings(?int $branchId, string $module, array $data): self
    {
        // Use 0 as default/global branch_id when null
        $branchId = $branchId ?? 0;

        $provider = $data['provider'] ?? 'openai';

        // Find existing setting for this specific provider to merge with
        $existing = static::where('branch_id', $branchId)
            ->where('module', $module)
            ->where('provider', $provider)
            ->first();

        // Merge new settings with existing settings
        $mergedSettings = $existing && $existing->settings
            ? array_merge($existing->settings, $data['settings'] ?? [])
            : ($data['settings'] ?? null);

        // For Azure provider, extract azure_key from settings and use as api_key
        $apiKeyToSave = null;
        if ($provider === 'azure' && !empty($mergedSettings['azure_key'])) {
            $apiKeyToSave = $mergedSettings['azure_key'];
            // Remove azure_key from settings to avoid duplication
            unset($mergedSettings['azure_key']);
        } elseif (!empty($data['api_key'])) {
            $apiKeyToSave = $data['api_key'];
        }

        // Set appropriate model based on provider
        $defaultModel = $provider === 'azure' ? '' : 'gpt-5.1';

        $setting = static::updateOrCreate(
            [
                'branch_id' => $branchId,
                'module' => $module,
                'provider' => $provider,  // Now part of the unique constraint
            ],
            [
                'model' => $data['model'] ?? $defaultModel,
                'settings' => $mergedSettings,
                'is_active' => true,
            ]
        );

        // Update API key if provided
        if (!empty($apiKeyToSave)) {
            $setting->api_key = $apiKeyToSave;
            $setting->save();
        }

        return $setting;
    }

    /**
     * Relationship with Branch
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
