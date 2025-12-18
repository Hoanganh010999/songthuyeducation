# Multi-Branch Zalo Implementation Plan

## Overview
Implement Option B - Advanced: Single Zalo login, route messages by branch based on conversation assignment.

## Current Status
✅ Database ready:
- `zalo_accounts`: Has composite unique key (zalo_id, branch_id)
- `zalo_conversations`: Already has branch_id and department_id columns
- `zalo_groups`: Already has branch_id and department_id columns
- Permissions: zalo.view_groups, zalo.all_conversation_management, zalo.assign_groups exist

## Phase 1: Multi-Branch Message Broadcasting

### 1.1 Update ZaloController::receiveMessage()

**Current Logic (Line 3860-3875):**
```php
// Find account by account_id (preferred) or zalo_id (backward compatibility)
if ($accountId) {
    $account = ZaloAccount::find($accountId);
}
if (!$account && $zaloId) {
    $account = ZaloAccount::where('zalo_id', $zaloId)->first(); // ❌ PROBLEM: Only gets first
}
```

**New Logic:**
```php
// 1. If account_id provided: Use that specific account
if ($accountId) {
    $account = ZaloAccount::find($accountId);
    $accounts = [$account]; // Single account
}

// 2. If only zalo_id: Get ALL accounts for this zalo_id (all branches)
else if ($zaloId) {
    $accounts = ZaloAccount::where('zalo_id', $zaloId)->get();
    if ($accounts->isEmpty()) {
        return error('Account not found');
    }
    $account = $accounts->first(); // Primary account for backward compat
}

// 3. Find/create conversations for EACH account
foreach ($accounts as $acc) {
    $conversation = ZaloConversation::firstOrCreate([
        'zalo_account_id' => $acc->id,
        'recipient_id' => $recipientId,
        'recipient_type' => $recipientType,
    ], [
        'recipient_name' => $recipientName,
        'branch_id' => null, // NULL = global/unassigned
        'department_id' => null,
    ]);

    // Save message for this account
    $message = ZaloMessage::create([
        'zalo_account_id' => $acc->id,
        'zalo_conversation_id' => $conversation->id,
        // ... other fields
    ]);

    // Broadcast to this account's room
    $this->broadcastNewMessage($acc->id, $message, $conversation);
}
```

### 1.2 Broadcast Filtering Logic

**Rules:**
1. **New conversation (branch_id = NULL):**
   - Broadcast to ALL branches of same zalo_id
   - Only users with `zalo.all_conversation_management` OR their own branch can see

2. **Assigned conversation (branch_id != NULL):**
   - Only broadcast to that specific branch
   - Filter by user's branch access

3. **Frontend filtering:**
   - Users without `zalo.all_conversation_management`:
     - Only see conversations for their branches
   - Users with `zalo.all_conversation_management`:
     - See all conversations (global + assigned)

### 1.3 New Helper Method

Create: `app/Services/ZaloMultiBranchService.php`

```php
class ZaloMultiBranchService
{
    /**
     * Get all accounts for a zalo_id
     */
    public static function getAccountsForZaloId(string $zaloId): Collection
    {
        return ZaloAccount::where('zalo_id', $zaloId)->get();
    }

    /**
     * Broadcast message to relevant accounts based on conversation assignment
     */
    public static function broadcastMessage(
        ZaloMessage $message,
        ZaloConversation $conversation
    ): void {
        // If conversation assigned to branch: Only broadcast to that branch's account
        if ($conversation->branch_id) {
            $account = $conversation->zaloAccount;
            self::broadcastToAccount($account->id, $message, $conversation);
            return;
        }

        // If conversation is global: Broadcast to ALL branches
        $zaloId = $conversation->zaloAccount->zalo_id;
        $accounts = self::getAccountsForZaloId($zaloId);

        foreach ($accounts as $account) {
            self::broadcastToAccount($account->id, $message, $conversation);
        }
    }

    private static function broadcastToAccount(
        int $accountId,
        ZaloMessage $message,
        ZaloConversation $conversation
    ): void {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->post(
                env('ZALO_SERVICE_URL') . '/api/socket/broadcast',
                [
                    'event' => 'new_message',
                    'account_id' => $accountId, // Target this account's room
                    'data' => [
                        'account_id' => $accountId,
                        'conversation_id' => $conversation->id,
                        'message' => $message->toArray(),
                    ],
                ]
            );

            if ($response->successful()) {
                Log::info('[ZaloMultiBranch] Message broadcasted', [
                    'account_id' => $accountId,
                    'message_id' => $message->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[ZaloMultiBranch] Broadcast failed', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

## Phase 2: Group Assignment UI & API

### 2.1 Backend API Endpoint

**Route:**
```php
// routes/api.php
Route::middleware(['auth:sanctum'])->prefix('zalo')->group(function () {
    Route::put('/groups/{group}/assign', [ZaloController::class, 'assignGroup'])
        ->middleware('permission:zalo.assign_groups');

    Route::get('/groups/list-for-assignment', [ZaloController::class, 'listGroupsForAssignment'])
        ->middleware('permission:zalo.assign_groups');
});
```

**Controller Methods:**
```php
// app/Http/Controllers/Api/ZaloController.php

/**
 * List all groups for assignment (with current assignments)
 */
public function listGroupsForAssignment(Request $request)
{
    $user = $request->user();

    // Get groups accessible by user
    $query = ZaloGroup::with(['zaloAccount', 'branch', 'department']);

    // Apply access control
    if (!$user->hasPermission('zalo.view_groups')) {
        // Filter by user's branches/departments
        $userBranchIds = $user->branches()->pluck('branches.id')->toArray();
        $userDepartmentIds = $user->departments()->pluck('departments.id')->toArray();

        $query->where(function($q) use ($userBranchIds, $userDepartmentIds) {
            $q->whereNull('branch_id')
              ->orWhereIn('branch_id', $userBranchIds)
              ->orWhereIn('department_id', $userDepartmentIds);
        });
    }

    $groups = $query->orderBy('name')->get();

    return response()->json([
        'success' => true,
        'data' => $groups,
    ]);
}

/**
 * Assign group to branch/department
 */
public function assignGroup(Request $request, ZaloGroup $group)
{
    $validated = $request->validate([
        'branch_id' => 'nullable|exists:branches,id',
        'department_id' => 'nullable|exists:departments,id',
    ]);

    $group->update([
        'branch_id' => $validated['branch_id'],
        'department_id' => $validated['department_id'],
    ]);

    Log::info('[ZaloController] Group assigned', [
        'group_id' => $group->id,
        'branch_id' => $validated['branch_id'],
        'department_id' => $validated['department_id'],
        'user_id' => $request->user()->id,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Group assigned successfully',
        'data' => $group->load(['branch', 'department']),
    ]);
}
```

### 2.2 Frontend UI Component

**Location:** `resources/js/pages/Zalo/GroupAssignment.vue`

**Features:**
- Table showing all groups with:
  - Group name, avatar
  - Current branch assignment (dropdown)
  - Current department assignment (dropdown)
  - Save button
- Filter by:
  - Unassigned groups
  - Branch
  - Department
- Bulk assign capability

**Component Structure:**
```vue
<template>
  <div class="group-assignment-page">
    <h1>Group Assignment</h1>

    <!-- Filters -->
    <div class="filters">
      <select v-model="filterBranch">
        <option value="">All Branches</option>
        <option v-for="branch in branches" :value="branch.id">
          {{ branch.name }}
        </option>
      </select>

      <checkbox v-model="filterUnassigned">Show only unassigned</checkbox>
    </div>

    <!-- Groups Table -->
    <table>
      <thead>
        <tr>
          <th>Group</th>
          <th>Branch</th>
          <th>Department</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="group in filteredGroups" :key="group.id">
          <td>
            <img :src="group.avatar_url" />
            {{ group.name }}
          </td>
          <td>
            <select v-model="group.branch_id" @change="markDirty(group)">
              <option :value="null">Unassigned</option>
              <option v-for="branch in branches" :value="branch.id">
                {{ branch.name }}
              </option>
            </select>
          </td>
          <td>
            <select v-model="group.department_id" @change="markDirty(group)">
              <option :value="null">None</option>
              <option v-for="dept in departments" :value="dept.id">
                {{ dept.name }}
              </option>
            </select>
          </td>
          <td>
            <button
              @click="saveAssignment(group)"
              :disabled="!isDirty(group)"
            >
              Save
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  data() {
    return {
      groups: [],
      branches: [],
      departments: [],
      dirtyGroups: new Set(),
      filterBranch: '',
      filterUnassigned: false,
    }
  },

  computed: {
    filteredGroups() {
      return this.groups.filter(group => {
        if (this.filterUnassigned && group.branch_id) return false
        if (this.filterBranch && group.branch_id !== this.filterBranch) return false
        return true
      })
    }
  },

  methods: {
    async fetchData() {
      const response = await axios.get('/api/zalo/groups/list-for-assignment')
      this.groups = response.data.data

      const branchesRes = await axios.get('/api/branches')
      this.branches = branchesRes.data.data

      const deptsRes = await axios.get('/api/departments')
      this.departments = deptsRes.data.data
    },

    markDirty(group) {
      this.dirtyGroups.add(group.id)
    },

    isDirty(group) {
      return this.dirtyGroups.has(group.id)
    },

    async saveAssignment(group) {
      try {
        await axios.put(`/api/zalo/groups/${group.id}/assign`, {
          branch_id: group.branch_id,
          department_id: group.department_id,
        })

        this.dirtyGroups.delete(group.id)
        this.$toast.success('Group assigned successfully')
      } catch (error) {
        this.$toast.error('Failed to assign group')
      }
    }
  },

  mounted() {
    this.fetchData()
  }
}
</script>
```

## Implementation Steps

1. ✅ Review existing code
2. ⏳ Create ZaloMultiBranchService
3. ⏳ Update ZaloController::receiveMessage()
4. ⏳ Add API endpoints for group assignment
5. ⏳ Create frontend GroupAssignment component
6. ⏳ Add route to Zalo section
7. ⏳ Test multi-branch message routing
8. ⏳ Test group assignment UI
9. ⏳ Deploy to VPS

## Testing Checklist

- [ ] Single zalo_id with 2 branches receives message → Both should get it
- [ ] Assign conversation to branch → Only that branch receives future messages
- [ ] User without `zalo.all_conversation_management` → Only sees their branch conversations
- [ ] User with permission → Sees all conversations
- [ ] Assign group to branch → Only that branch users see it
- [ ] Unassigned group → All users with view_groups see it

## Notes
- Backward compatibility: If account_id provided, works as before
- Only when zalo_id provided (no account_id): Multi-branch logic kicks in
- Frontend needs to handle branch filtering in conversation list
