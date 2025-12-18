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
        'assigned_branch_id',
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

    // ===== Relationships =====

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

    public function assigned_branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'assigned_branch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ZaloMessage::class, 'zalo_conversation_id');
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'zalo_conversation_users')
            ->withPivot(['can_view', 'can_reply', 'assigned_by', 'assigned_at', 'assignment_note'])
            ->withTimestamps();
    }

    // ===== Scopes =====

    public function scopeAccessibleBy($query, $user)
    {
        // Super-admin or all_conversation_management can see everything
        if ($user->hasRole('super-admin') || $user->hasPermission('zalo.all_conversation_management')) {
            return $query;
        }

        // Users with view_all_branches_conversations can see ALL conversations (no branch filtering)
        if ($user->hasPermission('zalo.view_all_branches_conversations')) {
            return $query;
        }

        // Normal users: Apply branch-based filtering
        // assigned_branch_id = NULL → global (visible to all shared branches)
        // assigned_branch_id = specific → only that branch can see
        $userDepartmentIds = $user->departments()
            ->where(function ($q) {
                $q->where('department_user.is_head', true)
                  ->orWhere('department_user.is_deputy', true);
            })
            ->pluck('departments.id')
            ->toArray();

        $userBranchIds = $user->branches()->pluck('branches.id')->toArray();

        // Only show conversations that user has explicit access to:
        // 1. Created by user
        // 2. Assigned directly to user
        // 3. Assigned to departments where user is head or deputy
        // 4. Assigned to user's branches via assigned_branch_id
        // 5. Global conversations with no assigned_branch_id (visible to all)
        return $query->where(function ($q) use ($user, $userDepartmentIds, $userBranchIds) {
            $q->where('created_by', $user->id)
              ->orWhereHas('assignedUsers', function ($query) use ($user) {
                  $query->where('user_id', $user->id)->where('can_view', true);
              })
              ->orWhere(function ($query) use ($userDepartmentIds) {
                  if (!empty($userDepartmentIds)) {
                      $query->whereIn('department_id', $userDepartmentIds);
                  }
              })
              ->orWhere(function ($query) use ($userBranchIds) {
                  // Show global conversations (assigned_branch_id NULL) OR conversations assigned to user's branches
                  $query->whereNull('assigned_branch_id');

                  if (!empty($userBranchIds)) {
                      $query->orWhereIn('assigned_branch_id', $userBranchIds);
                  }
              });
        });
    }

    public function scopeForAccount($query, $accountId)
    {
        return $query->where('zalo_account_id', $accountId);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderBy('last_message_at', 'desc');
    }

    public function scopeUnread($query)
    {
        return $query->where('unread_count', '>', 0);
    }

    /**
     * Scope: Conversations assigned to a specific branch
     */
    public function scopeAssignedToBranch($query, $branchId)
    {
        return $query->where('assigned_branch_id', $branchId);
    }

    /**
     * Scope: Conversations accessible by a branch (considering permissions)
     * - If view_all_conversations: all conversations of the account
     * - Otherwise: only conversations assigned to this branch
     */
    public function scopeAccessibleByBranch($query, $accountId, $branchId, $viewAll = false)
    {
        $query->where('zalo_account_id', $accountId);

        if (!$viewAll) {
            $query->where(function($q) use ($branchId) {
                $q->where('assigned_branch_id', $branchId)
                  ->orWhereNull('assigned_branch_id');
            });
        }

        return $query;
    }

    // ===== Dynamic Relationships (Polymorphic-like) =====

    /**
     * Get the friend recipient if recipient_type is 'user'
     */
    public function friend()
    {
        if ($this->recipient_type !== 'user') {
            return null;
        }

        return ZaloFriend::where('zalo_account_id', $this->zalo_account_id)
            ->where('id', $this->recipient_id)
            ->first();
    }

    /**
     * Get the group recipient if recipient_type is 'group'
     */
    public function group()
    {
        if ($this->recipient_type !== 'group') {
            return null;
        }

        return ZaloGroup::where('zalo_account_id', $this->zalo_account_id)
            ->where('id', $this->recipient_id)
            ->first();
    }

    /**
     * Get the recipient (friend or group) based on recipient_type
     */
    public function getRecipient()
    {
        return $this->recipient_type === 'user' ? $this->friend() : $this->group();
    }

    // ===== Helper Methods =====

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
        // ✅ FIX: Parse rich text JSON to get plain text for preview
        $preview = $message->content;

        // Try to parse as JSON (Zalo rich text format: {"title":"...","params":"..."})
        try {
            $decoded = json_decode($message->content, true);
            if ($decoded && isset($decoded['title'])) {
                // Extract title from rich text JSON
                $preview = $decoded['title'];

                // If title is empty, use content_type as placeholder
                if (empty($preview)) {
                    $preview = match($message->content_type) {
                        'image' => '[Hình ảnh]',
                        'video' => '[Video]',
                        'file' => '[File]',
                        'sticker' => '[Sticker]',
                        'link' => $decoded['href'] ?? '[Link]',
                        default => '[Media]',
                    };
                }
            }
        } catch (\Exception $e) {
            // Not JSON or parsing failed, use original content
        }

        $this->update([
            'last_message_at' => $message->sent_at ?? now(),
            'last_message_preview' => \Str::limit($preview, 100),
        ]);
    }

    /**
     * Fix old JSON previews in database
     */
    public static function fixOldJsonPreviews(): int
    {
        $fixed = 0;

        // Find all conversations with JSON in preview (starts with {")
        $conversations = self::whereNotNull('last_message_preview')
            ->where('last_message_preview', 'like', '{"%')
            ->get();

        foreach ($conversations as $conversation) {
            $preview = $conversation->last_message_preview;

            try {
                $decoded = json_decode($preview, true);
                if ($decoded && isset($decoded['title'])) {
                    // Extract title from JSON
                    $newPreview = \Str::limit($decoded['title'], 100);

                    $conversation->update([
                        'last_message_preview' => $newPreview
                    ]);

                    $fixed++;
                }
            } catch (\Exception $e) {
                // Skip if parsing fails
                continue;
            }
        }

        return $fixed;
    }
}
