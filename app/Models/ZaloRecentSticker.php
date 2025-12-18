<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZaloRecentSticker extends Model
{
    protected $fillable = [
        'zalo_account_id',
        'sticker_id',
        'cate_id',
        'type',
        'text',
        'sticker_url',
        'sticker_webp_url',
        'sticker_sprite_url',
        'uri',
        'last_used_at',
        'use_count',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'type' => 'integer',
        'use_count' => 'integer',
    ];

    /**
     * Get the Zalo account that owns this recent sticker
     */
    public function zaloAccount()
    {
        return $this->belongsTo(ZaloAccount::class);
    }

    /**
     * Record sticker usage (create or update)
     */
    public static function recordUsage($accountId, $stickerData)
    {
        return static::updateOrCreate(
            [
                'zalo_account_id' => $accountId,
                'sticker_id' => $stickerData['id'] ?? $stickerData['stickerId'],
            ],
            [
                'cate_id' => $stickerData['cateId'] ?? $stickerData['catId'] ?? null,
                'type' => $stickerData['type'] ?? null,
                'text' => $stickerData['text'] ?? '',
                'sticker_url' => $stickerData['stickerUrl'] ?? null,
                'sticker_webp_url' => $stickerData['stickerWebpUrl'] ?? null,
                'sticker_sprite_url' => $stickerData['stickerSpriteUrl'] ?? null,
                'uri' => $stickerData['uri'] ?? null,
                'last_used_at' => now(),
                'use_count' => \DB::raw('use_count + 1'),
            ]
        );
    }

    /**
     * Get recent stickers for an account (limit 30)
     */
    public static function getRecent($accountId, $limit = 30)
    {
        return static::where('zalo_account_id', $accountId)
            ->orderBy('last_used_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($sticker) {
                return [
                    'id' => $sticker->sticker_id,
                    'cateId' => $sticker->cate_id,
                    'type' => $sticker->type,
                    'text' => $sticker->text,
                    'stickerUrl' => $sticker->sticker_url,
                    'stickerWebpUrl' => $sticker->sticker_webp_url,
                    'stickerSpriteUrl' => $sticker->sticker_sprite_url,
                    'uri' => $sticker->uri,
                ];
            });
    }
}
