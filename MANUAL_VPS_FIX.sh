#!/bin/bash
# ================================================================
# MANUAL FIX FOR VPS - Chạy trực tiếp trên VPS
# ================================================================
# SSH vào VPS: ssh -i ~/.ssh/vps_key -p 26266 root@103.121.90.143
# Then run: bash < MANUAL_VPS_FIX.sh
# ================================================================

cd /var/www/school

echo "📝 [1/2] Updating Customer.php..."

# Backup
cp app/Models/Customer.php app/Models/Customer.php.before_manual_fix

# Use sed to insert the permission check
sed -i '/if ($user->is_super_admin ||/,/return $query;/{ 
    /return $query;/a\
\
        \/\/ Check if user has '"'"'customers.view_all'"'"' permission\
        if ($user->hasPermission('"'"'customers.view_all'"'"')) {\
            \/\/ User can see all customers, no filter needed\
            return $query;\
        }
}' app/Models/Customer.php

# Verify
php -l app/Models/Customer.php
echo "✅ Customer.php updated!"
echo ""

echo "📝 [2/2] Updating ZaloController.php..."

# Find insertion point (after getCustomerUnreadCounts method ends)
# We'"'"'ll insert the new method right before the syncHistory method

# Create new method file
cat > /tmp/new_zalo_method.php << 'PHPEOF'

    /**
     * Get TOTAL unread count for all accessible customers
     */
    public function getCustomerUnreadTotal(Request $request)
    {
        $user = $request->user();
        $branchId = $request->input('branch_id');

        try {
            $accountsQuery = ZaloAccount::where('is_connected', true);
            if ($branchId) {
                $accountsQuery->where('branch_id', $branchId);
            }
            $accountIds = $accountsQuery->pluck('id');

            if ($accountIds->isEmpty()) {
                return response()->json(['success' => true, 'data' => ['total_unread' => 0]]);
            }

            $customersQuery = \App\Models\Customer::query()
                ->accessibleBy($user)
                ->whereNotNull('phone')
                ->select('id', 'phone');
            $customers = $customersQuery->get();

            if ($customers->isEmpty()) {
                return response()->json(['success' => true, 'data' => ['total_unread' => 0]]);
            }

            $phones = $customers->pluck('phone')->unique()->filter();
            if ($phones->isEmpty()) {
                return response()->json(['success' => true, 'data' => ['total_unread' => 0]]);
            }

            $friends = \App\Models\ZaloFriend::whereIn('zalo_account_id', $accountIds)
                ->whereIn('phone', $phones)
                ->get();

            if ($friends->isEmpty()) {
                return response()->json(['success' => true, 'data' => ['total_unread' => 0]]);
            }

            $totalUnread = 0;
            foreach ($friends as $friend) {
                $unreadCount = ZaloMessage::where('zalo_account_id', $friend->zalo_account_id)
                    ->where('recipient_id', $friend->zalo_user_id)
                    ->where('type', 'received')
                    ->whereNull('read_at')
                    ->count();
                $totalUnread += $unreadCount;
            }

            return response()->json(['success' => true, 'data' => ['total_unread' => $totalUnread]]);
        } catch (\Exception $e) {
            Log::error('[ZaloController] Failed to get customer unread total', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to get unread total'], 500);
        }
    }

PHPEOF

# Insert before syncHistory method (around line 2977)
sed -i '/public function syncHistory(Request $request)/i\
' -f /tmp/new_zalo_method.php app/Http/Controllers/Api/ZaloController.php

php -l app/Http/Controllers/Api/ZaloController.php  
echo "✅ ZaloController.php updated!"
echo ""

echo "🗑️  Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan cache:clear
echo "✅ Caches cleared!"
echo ""

echo "========================================="
echo "  ✅ ALL BACKEND CHANGES COMPLETED!"
echo "========================================="
echo ""
echo "Now run: npm run build"
echo ""

