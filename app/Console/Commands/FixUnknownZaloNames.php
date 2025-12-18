<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ZaloMessage;
use App\Models\ZaloFriend;
use App\Models\ZaloGroupMember;
use App\Models\ZaloGroup;
use Illuminate\Support\Facades\DB;

class FixUnknownZaloNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zalo:fix-unknown-names {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix "Unknown" names in Zalo messages by matching with friends/group members';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 Running in DRY RUN mode - no changes will be made');
        } else {
            $this->warn('⚠️  Running in LIVE mode - changes will be saved to database');
            if (!$this->confirm('Do you want to continue?')) {
                $this->info('Aborted.');
                return;
            }
        }

        $this->info('');
        $this->info('========================================');
        $this->info('  Fix Unknown Zalo Names');
        $this->info('========================================');
        $this->info('');

        // Fix 1-on-1 messages (received from friends)
        $this->fixFriendMessages($dryRun);

        // Fix group messages (received in groups)
        $this->fixGroupMessages($dryRun);

        $this->info('');
        $this->info('✅ Done!');
    }

    /**
     * Fix Unknown names in 1-on-1 friend messages
     */
    private function fixFriendMessages($dryRun)
    {
        $this->info('');
        $this->info('📱 Fixing 1-on-1 friend messages...');
        $this->info('-----------------------------------');

        // Find received messages with Unknown recipient_name (user type)
        $unknownMessages = ZaloMessage::where('type', 'received')
            ->where('recipient_type', 'user')
            ->where(function($query) {
                $query->where('recipient_name', 'Unknown')
                      ->orWhereNull('recipient_name');
            })
            ->get();

        $this->info("Found {$unknownMessages->count()} messages with Unknown friend names");

        $fixed = 0;
        $notFound = 0;

        foreach ($unknownMessages as $message) {
            // Find friend by recipient_id and account_id
            $friend = ZaloFriend::where('zalo_account_id', $message->zalo_account_id)
                ->where('zalo_user_id', $message->recipient_id)
                ->first();

            if ($friend && $friend->name && $friend->name !== 'Unknown') {
                if (!$dryRun) {
                    $message->recipient_name = $friend->name;
                    $message->save();
                }

                $this->line("  ✅ Message #{$message->id}: '{$message->recipient_name}' → '{$friend->name}'");
                $fixed++;
            } else {
                $this->line("  ⚠️  Message #{$message->id}: Friend not found (ID: {$message->recipient_id})");
                $notFound++;
            }
        }

        $this->info('');
        $this->info("✅ Fixed: {$fixed} messages");
        $this->info("⚠️  Not found: {$notFound} messages");
    }

    /**
     * Fix Unknown names in group messages
     */
    private function fixGroupMessages($dryRun)
    {
        $this->info('');
        $this->info('👥 Fixing group messages...');
        $this->info('---------------------------');

        // Find received group messages with Unknown sender_name
        $unknownMessages = ZaloMessage::where('type', 'received')
            ->where('recipient_type', 'group')
            ->where(function($query) {
                $query->where('sender_name', 'Unknown')
                      ->orWhereNull('sender_name');
            })
            ->whereNotNull('sender_id')
            ->get();

        $this->info("Found {$unknownMessages->count()} group messages with Unknown sender names");

        $fixed = 0;
        $notFound = 0;

        foreach ($unknownMessages as $message) {
            // Find group
            $group = ZaloGroup::where('zalo_account_id', $message->zalo_account_id)
                ->where('zalo_group_id', $message->recipient_id)
                ->first();

            if (!$group) {
                $this->line("  ⚠️  Message #{$message->id}: Group not found (ID: {$message->recipient_id})");
                $notFound++;
                continue;
            }

            // Find group member
            $member = ZaloGroupMember::where('zalo_group_id', $group->id)
                ->where('zalo_user_id', $message->sender_id)
                ->first();

            if ($member && $member->display_name && $member->display_name !== 'Unknown') {
                if (!$dryRun) {
                    $message->sender_name = $member->display_name;
                    $message->save();
                }

                $this->line("  ✅ Message #{$message->id}: '{$message->sender_name}' → '{$member->display_name}' (Group: {$group->name})");
                $fixed++;
            } else {
                $this->line("  ⚠️  Message #{$message->id}: Member not found in group '{$group->name}' (User ID: {$message->sender_id})");
                $notFound++;
            }
        }

        $this->info('');
        $this->info("✅ Fixed: {$fixed} messages");
        $this->info("⚠️  Not found: {$notFound} messages");
    }
}
