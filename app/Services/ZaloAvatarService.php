<?php

namespace App\Services;

use App\Models\ZaloFriend;
use App\Models\ZaloGroup;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ZaloAvatarService
{
    protected $avatarPath = 'zalo/avatars';

    /**
     * Download and save friend avatar
     */
    public function downloadFriendAvatar(ZaloFriend $friend): ?string
    {
        if (!$friend->avatar_url) {
            return null;
        }

        try {
            $extension = $this->getImageExtension($friend->avatar_url);
            $filename = "friend_{$friend->zalo_user_id}_{$friend->id}.{$extension}";
            $path = "{$this->avatarPath}/friends/{$filename}";

            // Download image
            $response = Http::timeout(10)->get($friend->avatar_url);
            
            if ($response->successful()) {
                Storage::disk('public')->put($path, $response->body());
                
                $fullPath = Storage::disk('public')->path($path);
                $friend->update(['avatar_path' => $path]);
                
                Log::info('[ZaloAvatar] Downloaded friend avatar', [
                    'friend_id' => $friend->id,
                    'path' => $path,
                ]);

                return $path;
            }
        } catch (\Exception $e) {
            Log::error('[ZaloAvatar] Failed to download friend avatar', [
                'friend_id' => $friend->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Download and save group avatar
     */
    public function downloadGroupAvatar(ZaloGroup $group): ?string
    {
        // If no avatar_url, try to generate default avatar URL from Zalo
        if (!$group->avatar_url) {
            // Zalo default group avatar pattern: https://s120-ava-talk.zadn.vn/{group_id}.jpg
            // Or we can use a placeholder
            $defaultAvatarUrl = $this->generateDefaultGroupAvatarUrl($group);
            if ($defaultAvatarUrl) {
                $group->avatar_url = $defaultAvatarUrl;
                $group->save();
            } else {
                return null;
            }
        }

        try {
            $extension = $this->getImageExtension($group->avatar_url);
            $filename = "group_{$group->zalo_group_id}_{$group->id}.{$extension}";
            $path = "{$this->avatarPath}/groups/{$filename}";

            // Download image
            $response = Http::timeout(10)->get($group->avatar_url);
            
            if ($response->successful()) {
                Storage::disk('public')->put($path, $response->body());
                
                $fullPath = Storage::disk('public')->path($path);
                $group->update(['avatar_path' => $path]);
                
                Log::info('[ZaloAvatar] Downloaded group avatar', [
                    'group_id' => $group->id,
                    'path' => $path,
                ]);

                return $path;
            }
        } catch (\Exception $e) {
            Log::error('[ZaloAvatar] Failed to download group avatar', [
                'group_id' => $group->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
    
    /**
     * Generate default group avatar URL from Zalo
     * Zalo generates default avatars for groups without custom avatars
     */
    protected function generateDefaultGroupAvatarUrl(ZaloGroup $group): ?string
    {
        // Try common Zalo avatar URL patterns
        $patterns = [
            "https://s120-ava-talk.zadn.vn/{$group->zalo_group_id}.jpg",
            "https://s120-ava-talk.zadn.vn/g/{$group->zalo_group_id}.jpg",
            "https://avatar.zalo.me/g/{$group->zalo_group_id}",
        ];
        
        // For now, return null - we'll let frontend handle default avatar display
        // Or we can generate a data URI placeholder
        return null;
    }

    /**
     * Get image extension from URL
     */
    protected function getImageExtension(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        // Default to jpg if no extension
        return $extension ?: 'jpg';
    }

    /**
     * Get avatar URL (local if available, otherwise remote, or default)
     */
    public function getAvatarUrl($model): ?string
    {
        if ($model->avatar_path && Storage::disk('public')->exists($model->avatar_path)) {
            return Storage::disk('public')->url($model->avatar_path);
        }
        
        if ($model->avatar_url) {
            return $model->avatar_url;
        }
        
        // For groups without avatar, return null (frontend will show default)
        // For accounts/friends, return null (frontend will show placeholder)
        return null;
    }
    
    /**
     * Get default group avatar URL pattern
     * This can be used by frontend to generate default avatars
     */
    public function getDefaultGroupAvatarUrl($zaloGroupId): ?string
    {
        // Zalo may have default avatar URLs for groups
        // Common pattern: https://s120-ava-talk.zadn.vn/{group_id}.jpg
        // But we can't verify without making HTTP request
        // Return null and let frontend handle with placeholder
        return null;
    }
}

