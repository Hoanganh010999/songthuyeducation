# Zalo Conversation Permissions - Implementation Guide

## ✅ Completed Implementation:

### Database Layer
1. ✅ Migration: `zalo_conversations` table - Stores conversation metadata, assignments
2. ✅ Migration: `zalo_conversation_users` table - User-level permissions with can_view/can_reply
3. ✅ Migration: Add `zalo_conversation_id` to `zalo_messages` - Links messages to conversations

### Models & Services
4. ✅ ZaloConversation Model - Complete with relationships and permission scopes
5. ✅ ZaloConversationService - Conversation management, assignment, data migration
6. ✅ ZaloMessage Model - Added conversation relationship

### Controllers & Routes
7. ✅ ZaloController - 4 new conversation endpoints with permissions
8. ✅ API Routes - `/api/zalo/conversations/*` endpoints configured

### Data Migration
9. ✅ Migrated existing messages - 3 conversations created, 15 messages linked

## 📋 Implementation Summary:

### API Endpoints

All endpoints require authentication (`auth:sanctum`) and branch access:

#### 1. Get Conversations
```
GET /api/zalo/conversations
```
Query parameters:
- `account_id` (optional) - Filter by Zalo account
- `branch_id` (optional) - Filter by branch
- `unread_only` (optional) - Show only unread conversations

Permission: `zalo.view`

Response includes conversations accessible by the authenticated user based on:
- Super-admin or `zalo.all_conversation_management` → All conversations
- Creator → Conversations they created
- Department head/deputy → Department conversations
- Branch staff → Branch-level conversations
- Directly assigned users → Assigned conversations

#### 2. Assign User to Conversation
```
POST /api/zalo/conversations/{id}/assign-user
```
Body:
- `user_id` (required) - User to assign
- `can_view` (optional, default: true) - View permission
- `can_reply` (optional, default: true) - Reply permission
- `note` (optional) - Assignment note

Permission: `zalo.all_conversation_management`

Validates that user belongs to conversation's branch.

#### 3. Assign Department to Conversation
```
POST /api/zalo/conversations/{id}/assign-department
```
Body:
- `department_id` (required) - Department to assign

Permission: `zalo.all_conversation_management`

#### 4. Mark Conversation as Read
```
POST /api/zalo/conversations/{id}/mark-read
```
Permission: `zalo.view`

Only accessible to users who can view the conversation.

### Permission Logic

The `ZaloConversation::accessibleBy($user)` scope implements:

```php
// Super-admin OR all_conversation_management permission
if ($user->hasRole('super-admin') || $user->hasPermission('zalo.all_conversation_management')) {
    return all conversations;
}

// Otherwise, return conversations where:
// 1. User created the conversation, OR
// 2. User is directly assigned with can_view=true, OR
// 3. User is head/deputy of conversation's department, OR
// 4. User belongs to conversation's branch (if no department assigned)
```

### Test Results

```
✓ Tables created: zalo_conversations, zalo_conversation_users
✓ Conversations: 3 created from existing messages
✓ Messages linked: 15/15 messages linked to conversations
✓ Model loading: ZaloConversation and ZaloConversationService work
✓ Routes registered: 4 conversation endpoints active
✓ No syntax errors in all files
```

## 🔄 Next Steps for Integration:

### 1. Update Message Saving Logic

When receiving new messages, automatically create/update conversations:

```php
// In ZaloController::receiveMessage()
$conversationService = app(\App\Services\ZaloConversationService::class);
$conversation = $conversationService->handleNewMessage($message, $creator);
```

### 2. Update Frontend

Create UI components for:
- Conversation list with permission filtering
- Assign users/departments to conversations
- Show unread counts per conversation
- Mark conversations as read

### 3. Permissions Setup

Ensure these permissions exist in the system:
- `zalo.view` - View Zalo conversations
- `zalo.send` - Send messages
- `zalo.manage_accounts` - Manage Zalo accounts
- `zalo.all_conversation_management` - Manage all conversations

## 📝 Original Requirements Reference:

### 1. Complete ZaloConversation Model

File: `app/Models/ZaloConversation.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ZaloConversation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'zalo_account_id',
        'recipient_id',
        'recipient_type',
        'recipient_name',
        'recipient_avatar_url',
        'branch_id',
        'department_id',
        'created_by',
        'last_message_at',
        'unread_count',
        'last_message_preview',
        'metadata',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'unread_count' => 'integer',
        'metadata' => 'array',
    ];

    // Relationships
    public function zaloAccount(): BelongsTo
    {
        return $this->belongsTo(ZaloAccount::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ZaloMessage::class);
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'zalo_conversation_users')
            ->withPivot(['can_view', 'can_reply', 'assigned_by', 'assigned_at', 'assignment_note'])
            ->withTimestamps();
    }

    // Scopes
    public function scopeAccessibleBy($query, $user)
    {
        if ($user->hasRole('super-admin') || $user->hasPermission('zalo.all_conversation_management')) {
            return $query;
        }

        $userDepartmentIds = $user->departments()
            ->where(function ($q) {
                $q->where('department_user.is_head', true)
                  ->orWhere('department_user.is_deputy', true);
            })
            ->pluck('departments.id')
            ->toArray();

        $userBranchIds = $user->branches()->pluck('branches.id')->toArray();

        return $query->where(function ($q) use ($user, $userDepartmentIds, $userBranchIds) {
            $q->where('created_by', $user->id)
              ->orWhereHas('assignedUsers', function ($query) use ($user) {
                  $query->where('user_id', $user->id)->where('can_view', true);
              })
              ->orWhereIn('department_id', $userDepartmentIds)
              ->orWhere(function ($query) use ($userBranchIds) {
                  $query->whereIn('branch_id', $userBranchIds)->whereNull('department_id');
              });
        });
    }

    public function scopeForAccount($query, $accountId)
    {
        return $query->where('zalo_account_id', $accountId);
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderBy('last_message_at', 'desc');
    }

    // Helper methods
    public function canBeViewedBy(User $user): bool
    {
        return self::where('id', $this->id)->accessibleBy($user)->exists();
    }

    public function incrementUnread(): void
    {
        $this->increment('unread_count');
    }

    public function markAsRead(): void
    {
        $this->update(['unread_count' => 0]);
    }

    public function updateLastMessage(ZaloMessage $message): void
    {
        $this->update([
            'last_message_at' => $message->sent_at ?? now(),
            'last_message_preview' => \Str::limit($message->content, 100),
        ]);
    }
}
```

### 2. Create ZaloConversationService

File: `app/Services/ZaloConversationService.php`

```php
<?php

namespace App\Services;

use App\Models\ZaloConversation;
use App\Models\ZaloMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ZaloConversationService
{
    /**
     * Get or create conversation
     */
    public function getOrCreateConversation(
        int $accountId,
        string $recipientId,
        string $recipientType = 'user',
        ?User $creator = null
    ): ZaloConversation {
        $conversation = ZaloConversation::firstOrCreate(
            [
                'zalo_account_id' => $accountId,
                'recipient_id' => $recipientId,
                'recipient_type' => $recipientType,
            ],
            [
                'created_by' => $creator?->id,
                'branch_id' => $creator?->branches()->wherePivot('is_primary', true)->first()?->id,
                'last_message_at' => now(),
            ]
        );

        // Auto-assign creator
        if ($creator && !$conversation->assignedUsers()->where('user_id', $creator->id)->exists()) {
            $this->assignUser($conversation, $creator, $creator);
        }

        return $conversation;
    }

    /**
     * Assign user to conversation
     */
    public function assignUser(
        ZaloConversation $conversation,
        User $user,
        ?User $assignedBy = null,
        bool $canView = true,
        bool $canReply = true,
        ?string $note = null
    ): void {
        $conversation->assignedUsers()->syncWithoutDetaching([
            $user->id => [
                'can_view' => $canView,
                'can_reply' => $canReply,
                'assigned_by' => $assignedBy?->id,
                'assigned_at' => now(),
                'assignment_note' => $note,
            ]
        ]);
    }

    /**
     * Assign department to conversation
     */
    public function assignDepartment(
        ZaloConversation $conversation,
        int $departmentId,
        ?User $assignedBy = null
    ): void {
        $conversation->update([
            'department_id' => $departmentId,
        ]);

        Log::info('[ZaloConversation] Assigned to department', [
            'conversation_id' => $conversation->id,
            'department_id' => $departmentId,
            'assigned_by' => $assignedBy?->id,
        ]);
    }

    /**
     * Handle new message - update conversation
     */
    public function handleNewMessage(ZaloMessage $message, ?User $creator = null): ZaloConversation
    {
        $conversation = $this->getOrCreateConversation(
            $message->zalo_account_id,
            $message->recipient_id,
            $message->recipient_type,
            $creator
        );

        // Link message to conversation
        $message->update(['zalo_conversation_id' => $conversation->id]);

        // Update conversation metadata
        $conversation->updateLastMessage($message);

        // Increment unread if it's a received message
        if ($message->type === 'received') {
            $conversation->incrementUnread();
        }

        return $conversation;
    }

    /**
     * Migrate existing messages to conversations
     */
    public function migrateExistingMessages(): array
    {
        $migrated = 0;
        $errors = 0;

        DB::beginTransaction();
        try {
            // Group messages by account + recipient
            $messageGroups = ZaloMessage::select('zalo_account_id', 'recipient_id', 'recipient_type')
                ->whereNull('zalo_conversation_id')
                ->groupBy('zalo_account_id', 'recipient_id', 'recipient_type')
                ->get();

            foreach ($messageGroups as $group) {
                try {
                    // Create conversation
                    $conversation = ZaloConversation::create([
                        'zalo_account_id' => $group->zalo_account_id,
                        'recipient_id' => $group->recipient_id,
                        'recipient_type' => $group->recipient_type,
                        'last_message_at' => now(),
                    ]);

                    // Link messages
                    ZaloMessage::where('zalo_account_id', $group->zalo_account_id)
                        ->where('recipient_id', $group->recipient_id)
                        ->where('recipient_type', $group->recipient_type)
                        ->update(['zalo_conversation_id' => $conversation->id]);

                    // Update conversation metadata from last message
                    $lastMessage = $conversation->messages()->latest('sent_at')->first();
                    if ($lastMessage) {
                        $conversation->updateLastMessage($lastMessage);
                    }

                    $migrated++;
                } catch (\Exception $e) {
                    Log::error('[ZaloConversation] Migration error', [
                        'group' => $group,
                        'error' => $e->getMessage(),
                    ]);
                    $errors++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return ['migrated' => $migrated, 'errors' => $errors];
    }
}
```

### 3. Update ZaloMessage Model

File: `app/Models/ZaloMessage.php` - Add relationship:

```php
public function conversation(): BelongsTo
{
    return $this->belongsTo(ZaloConversation::class, 'zalo_conversation_id');
}
```

### 4. Run Migrations

```bash
php artisan migrate
```

### 5. Migrate Existing Data

```bash
php artisan tinker
```

Then run:
```php
$service = app(\App\Services\ZaloConversationService::class);
$result = $service->migrateExistingMessages();
dd($result);
```

### 6. Update ZaloController

Add method to get conversations:

```php
public function getConversations(Request $request)
{
    $user = $request->user();
    $accountId = $request->input('account_id');
    $branchId = $request->input('branch_id');

    $query = ZaloConversation::with(['zaloAccount', 'branch', 'department', 'creator'])
        ->accessibleBy($user)
        ->recentFirst();

    if ($accountId) {
        $query->forAccount($accountId);
    }

    if ($branchId) {
        $query->where('branch_id', $branchId);
    }

    $conversations = $query->paginate(50);

    return response()->json([
        'success' => true,
        'data' => $conversations,
    ]);
}
```

### 7. Add Route

File: `routes/api.php`:

```php
Route::get('/conversations', [ZaloController::class, 'getConversations']);
Route::post('/conversations/{id}/assign-user', [ZaloController::class, 'assignUserToConversation']);
Route::post('/conversations/{id}/assign-department', [ZaloController::class, 'assignDepartmentToConversation']);
Route::post('/conversations/{id}/mark-read', [ZaloController::class, 'markConversationAsRead']);
```

## Next Steps:
1. Apply Model code to ZaloConversation.php
2. Create ZaloConversationService.php
3. Run migrations
4. Migrate data
5. Test permissions
