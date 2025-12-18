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
     * This migration deduplicates zalo_groups and migrates assignments to pivot table.
     *
     * Steps:
     * 1. Find all duplicate groups (same zalo_group_id)
     * 2. Keep the best one (most members, most recent data)
     * 3. Migrate branch/department assignments to zalo_group_branches
     * 4. Update foreign keys in related tables
     * 5. Delete duplicate groups
     */
    public function up(): void
    {
        Log::info('[Migration] Starting zalo_groups deduplication and data migration');

        // Group all zalo_groups by zalo_group_id
        $groupedByZaloId = DB::table('zalo_groups')
            ->select('zalo_group_id')
            ->groupBy('zalo_group_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('zalo_group_id');

        Log::info('[Migration] Found groups with duplicates', [
            'count' => $groupedByZaloId->count(),
            'zalo_group_ids' => $groupedByZaloId->toArray(),
        ]);

        foreach ($groupedByZaloId as $zaloGroupId) {
            // Get all duplicate records for this zalo_group_id
            $duplicates = DB::table('zalo_groups')
                ->where('zalo_group_id', $zaloGroupId)
                ->orderByDesc('members_count')
                ->orderByDesc('updated_at')
                ->get();

            if ($duplicates->isEmpty()) {
                continue;
            }

            // Keep the first one (highest members_count, most recent)
            $keepGroup = $duplicates->first();
            $deleteGroups = $duplicates->slice(1);

            Log::info('[Migration] Processing group deduplication', [
                'zalo_group_id' => $zaloGroupId,
                'keep_id' => $keepGroup->id,
                'delete_ids' => $deleteGroups->pluck('id')->toArray(),
            ]);

            // Migrate assignments to pivot table for ALL duplicates (including the one we keep)
            foreach ($duplicates as $group) {
                // Get the account's zalo_id for later use
                $account = DB::table('zalo_accounts')->where('id', $group->zalo_account_id)->first();

                if ($group->branch_id || $group->department_id) {
                    DB::table('zalo_group_branches')->insert([
                        'zalo_group_id' => $keepGroup->id, // Always reference the kept group
                        'branch_id' => $group->branch_id,
                        'department_id' => $group->department_id,
                        'assigned_by' => null,
                        'assigned_at' => $group->updated_at ?? now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::info('[Migration] Created assignment record', [
                        'zalo_group_id' => $keepGroup->id,
                        'branch_id' => $group->branch_id,
                        'department_id' => $group->department_id,
                    ]);
                }
            }

            // Update foreign keys in related tables for groups to be deleted
            foreach ($deleteGroups as $deleteGroup) {
                // Update zalo_group_members
                DB::table('zalo_group_members')
                    ->where('zalo_group_id', $deleteGroup->id)
                    ->update(['zalo_group_id' => $keepGroup->id]);

                // Note: We DON'T update zalo_conversations here because they need zalo_account_id for access control
                // Conversations will keep their references but we'll handle them differently

                Log::info('[Migration] Updated foreign keys', [
                    'from_group_id' => $deleteGroup->id,
                    'to_group_id' => $keepGroup->id,
                ]);
            }

            // Delete duplicate groups (soft delete)
            DB::table('zalo_groups')
                ->whereIn('id', $deleteGroups->pluck('id'))
                ->update(['deleted_at' => now()]);

            Log::info('[Migration] Soft deleted duplicate groups', [
                'deleted_ids' => $deleteGroups->pluck('id')->toArray(),
            ]);
        }

        // Handle groups without duplicates (single records) - migrate their assignments
        $singleGroups = DB::table('zalo_groups')
            ->whereNull('deleted_at')
            ->get();

        foreach ($singleGroups as $group) {
            // Check if this group already has assignment (we might have created it above)
            $existingAssignment = DB::table('zalo_group_branches')
                ->where('zalo_group_id', $group->id)
                ->where('branch_id', $group->branch_id)
                ->exists();

            if (!$existingAssignment && ($group->branch_id || $group->department_id)) {
                DB::table('zalo_group_branches')->insert([
                    'zalo_group_id' => $group->id,
                    'branch_id' => $group->branch_id,
                    'department_id' => $group->department_id,
                    'assigned_by' => null,
                    'assigned_at' => $group->updated_at ?? now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('[Migration] Migrated single group assignment', [
                    'zalo_group_id' => $group->id,
                    'branch_id' => $group->branch_id,
                ]);
            }
        }

        Log::info('[Migration] Completed zalo_groups deduplication and data migration');
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
        Log::warning('[Migration] Reversing zalo_groups migration - this will clear pivot table');

        // Clear the pivot table
        DB::table('zalo_group_branches')->truncate();

        // Restore soft-deleted groups
        DB::table('zalo_groups')
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null]);

        Log::info('[Migration] Reversed migration - restored deleted groups and cleared pivot table');
    }
};
