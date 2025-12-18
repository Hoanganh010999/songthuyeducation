<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration deduplicates zalo_friends data.
     *
     * Steps:
     * 1. Find all duplicate friends (same zalo_user_id)
     * 2. Keep the best one (most recent data)
     * 3. Update foreign keys in related tables (if any)
     * 4. Delete duplicate friends
     */
    public function up(): void
    {
        Log::info('[Migration] Starting zalo_friends deduplication');

        // Group all zalo_friends by zalo_user_id
        $groupedByUserId = DB::table('zalo_friends')
            ->select('zalo_user_id')
            ->groupBy('zalo_user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('zalo_user_id');

        Log::info('[Migration] Found friends with duplicates', [
            'count' => $groupedByUserId->count(),
            'zalo_user_ids' => $groupedByUserId->toArray(),
        ]);

        foreach ($groupedByUserId as $zaloUserId) {
            // Get all duplicate records for this zalo_user_id
            $duplicates = DB::table('zalo_friends')
                ->where('zalo_user_id', $zaloUserId)
                ->orderByDesc('updated_at')  // Most recent first
                ->orderByDesc('last_seen_at')  // Then by last seen
                ->get();

            if ($duplicates->isEmpty()) {
                continue;
            }

            // Keep the first one (most recent)
            $keepFriend = $duplicates->first();
            $deleteFriends = $duplicates->slice(1);

            Log::info('[Migration] Processing friend deduplication', [
                'zalo_user_id' => $zaloUserId,
                'keep_id' => $keepFriend->id,
                'delete_ids' => $deleteFriends->pluck('id')->toArray(),
            ]);

            // Note: zalo_friends doesn't have branch_id/department_id yet
            // So no assignments to migrate to pivot table
            // If there are related tables with foreign keys to zalo_friends, update them here

            // Example: If there's a table that references zalo_friends
            // foreach ($deleteFriends as $deleteFriend) {
            //     DB::table('some_related_table')
            //         ->where('zalo_friend_id', $deleteFriend->id)
            //         ->update(['zalo_friend_id' => $keepFriend->id]);
            // }

            // Delete duplicate friends (soft delete)
            DB::table('zalo_friends')
                ->whereIn('id', $deleteFriends->pluck('id'))
                ->update(['deleted_at' => now()]);

            Log::info('[Migration] Soft deleted duplicate friends', [
                'deleted_ids' => $deleteFriends->pluck('id')->toArray(),
            ]);
        }

        Log::info('[Migration] Completed zalo_friends deduplication');
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: This down() method cannot fully reverse the data migration
     * as we've lost information about which records were duplicates.
     * Use database backup to restore if needed.
     */
    public function down(): void
    {
        Log::warning('[Migration] Reversing zalo_friends migration');

        // Restore soft-deleted friends
        DB::table('zalo_friends')
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null]);

        Log::info('[Migration] Reversed migration - restored deleted friends');
    }
};
