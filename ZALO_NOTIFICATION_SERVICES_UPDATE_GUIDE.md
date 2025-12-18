# 🔔 ZALO NOTIFICATION SERVICES - PERMISSION CHECK UPDATE GUIDE

**Purpose:** Add multi-branch permission checks to notification services

**Status:** ⏳ PENDING MANUAL UPDATE (due to linter conflicts)

---

## 📋 FILES TO UPDATE

### 1. CustomerZaloNotificationService.php

**File:** `app/Services/CustomerZaloNotificationService.php`

**Changes Required:**

#### a. Add Dependency Injection

```php
<?php

namespace App\Services;

use App\Models\ZaloAccount;
// ... other imports
use Illuminate\Support\Facades\Log;

class CustomerZaloNotificationService
{
    protected $permissionService;

    public function __construct(ZaloBranchPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    // ... rest of class
}
```

#### b. Update `sendZaloMessage` Method (Line ~565)

```php
public function sendZaloMessage(
    ZaloAccount $account,
    string $phone,
    string $message,
    ?int $customerId = null,
    ?int $branchId = null  // ← ADD THIS PARAMETER
): bool {
    try {
        // ===== MULTI-BRANCH PERMISSION CHECK ===== ADD THIS BLOCK
        if ($branchId && $account->branch_id) {
            if (!$this->permissionService->canSendTo($account->id, $branchId, 'customers')) {
                Log::warning('[CustomerZaloNotification] Branch does not have permission to send to customers', [
                    'account_id' => $account->id,
                    'branch_id' => $branchId,
                    'customer_id' => $customerId,
                ]);
                return false;
            }
        }
        // ===== END PERMISSION CHECK =====

        // Step 1: Search for user by phone
        Log::info('[CustomerZaloNotification] Searching user by phone', [
            // ... existing code
```

#### c. Update All Send Methods

Update these methods to accept and pass `$branchId`:
- `sendPlacementTestNotification()`
- `sendPlacementTestNotificationForChild()`
- `sendTrialClassNotification()`
- `sendPlacementTestReminderNotification()`
- `sendTrialClassReminderNotification()`
- `sendPlacementTestResultNotification()`
- `sendTrialClassFeedbackNotification()`

Example:
```php
public function sendPlacementTestNotification(
    CalendarEvent $event,
    Customer $customer,
    ?int $branchId = null  // ← ADD THIS
): bool {
    try {
        $account = $this->getPrimaryZaloAccount();
        if (!$account) {
            Log::warning('[CustomerZaloNotification] No active Zalo account found');
            return false;
        }

        $message = $this->formatPlacementTestMessage($event, $customer, null);

        // Pass branchId to sendZaloMessage
        return $this->sendZaloMessage($account, $customer->phone, $message, $customer->id, $branchId);
    } catch (\Exception $e) {
        // ...
    }
}
```

---

### 2. TeacherZaloNotificationService.php

**File:** `app/Services/TeacherZaloNotificationService.php`

**Same pattern as CustomerZaloNotificationService:**

1. Add `ZaloBranchPermissionService` dependency injection
2. Add permission check in `sendZaloMessage` method
3. Use target type: `'teachers'` instead of `'customers'`

```php
if ($branchId && $account->branch_id) {
    if (!$this->permissionService->canSendTo($account->id, $branchId, 'teachers')) {
        Log::warning('[TeacherZaloNotification] Branch does not have permission to send to teachers', [
            'account_id' => $account->id,
            'branch_id' => $branchId,
        ]);
        return false;
    }
}
```

---

### 3. ZaloGroupNotificationService.php

**File:** `app/Services/ZaloGroupNotificationService.php`

**Same pattern:**

1. Add `ZaloBranchPermissionService` dependency injection
2. Add permission check in send methods
3. Use target type: `'class_groups'`

```php
if ($branchId && $account->branch_id) {
    if (!$this->permissionService->canSendTo($account->id, $branchId, 'class_groups')) {
        Log::warning('[ZaloGroupNotification] Branch does not have permission to send to class groups', [
            'account_id' => $account->id,
            'branch_id' => $branchId,
            'group_id' => $groupId,
        ]);
        return false;
    }
}
```

---

## 🔄 CONTROLLER UPDATES

After updating services, update controllers to pass `branch_id`:

### Example: CustomerController

```php
public function sendZaloNotification(Request $request, $customerId)
{
    $customer = Customer::findOrFail($customerId);
    $event = CalendarEvent::find($request->event_id);

    // Get current user's branch
    $branchId = $request->user()->branches()->first()?->id;

    $notificationService = app(CustomerZaloNotificationService::class);

    // Pass branch_id to service
    $result = $notificationService->sendPlacementTestNotification($event, $customer, $branchId);

    return response()->json(['success' => $result]);
}
```

---

## ✅ VERIFICATION CHECKLIST

After manual updates:

### Code Review:
- [ ] All 3 notification services have `ZaloBranchPermissionService` injected
- [ ] All send methods accept optional `$branchId` parameter
- [ ] Permission checks are in place before sending messages
- [ ] Correct target types used: 'customers', 'teachers', 'class_groups'
- [ ] Log messages include relevant context (account_id, branch_id, etc.)

### Testing:
- [ ] Owner branch can send to customers ✓
- [ ] Shared branch (no permission) cannot send to customers ✓
- [ ] Shared branch (with permission) can send to customers ✓
- [ ] Same tests for teachers and class groups

### Integration:
- [ ] Controllers pass branch_id from current user's context
- [ ] API requests include branch context
- [ ] Log files show permission checks working

---

## 🛠️ IMPLEMENTATION STEPS

1. **Backup current services:**
   ```bash
   cp app/Services/CustomerZaloNotificationService.php app/Services/CustomerZaloNotificationService.php.bak
   cp app/Services/TeacherZaloNotificationService.php app/Services/TeacherZaloNotificationService.php.bak
   cp app/Services/ZaloGroupNotificationService.php app/Services/ZaloGroupNotificationService.php.bak
   ```

2. **Apply changes to each service:**
   - Add dependency injection
   - Update sendZaloMessage method
   - Update all send* methods

3. **Update controllers:**
   - Find all places calling notification services
   - Add branch_id parameter from user context

4. **Test thoroughly:**
   - Unit tests for permission checks
   - Integration tests with real data
   - Manual testing with multiple branches

---

## 📝 EXAMPLE COMPLETE SERVICE

Here's a complete minimal example:

```php
<?php

namespace App\Services;

use App\Models\ZaloAccount;
use App\Services\ZaloBranchPermissionService;
use Illuminate\Support\Facades\Log;

class CustomerZaloNotificationService
{
    protected $permissionService;

    public function __construct(ZaloBranchPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function sendNotification(
        ZaloAccount $account,
        string $phone,
        string $message,
        ?int $branchId = null
    ): bool {
        // Permission check
        if ($branchId && $account->branch_id) {
            if (!$this->permissionService->canSendTo($account->id, $branchId, 'customers')) {
                Log::warning('No permission to send to customers', [
                    'account_id' => $account->id,
                    'branch_id' => $branchId,
                ]);
                return false;
            }
        }

        // Send message...
        return true;
    }
}
```

---

**Created:** 27/11/2025
**Status:** Awaiting manual implementation
**Related:** ZALO_MULTI_BRANCH_PROGRESS.md

**Note:** Due to linter conflicts during automated updates, these changes should be applied manually following this guide.
