# 🛣️ ZALO MULTI-BRANCH API ROUTES

**File:** `routes/api.php`

**Location:** Add these routes inside the protected Zalo routes group (before the closing `});` at line ~1403)

---

## ROUTES TO ADD

```php
// Multi-Branch Access Management
Route::prefix('branch-access')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\ZaloBranchAccessController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\ZaloBranchAccessController::class, 'store']);
    Route::put('/{id}', [\App\Http\Controllers\Api\ZaloBranchAccessController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\ZaloBranchAccessController::class, 'destroy']);
    Route::post('/assign-items', [\App\Http\Controllers\Api\ZaloBranchAccessController::class, 'assignItems']);
});
```

---

## API ENDPOINTS DOCUMENTATION

### 1. List Branch Access

**Endpoint:** `GET /api/zalo/branch-access?account_id=16`

**Headers:**
- `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "data": {
    "account": {
      "id": 16,
      "name": "Tuấn Lê",
      "phone": "0123456789",
      "owner_branch_id": 2
    },
    "branches": [
      {
        "id": 1,
        "branch_id": 2,
        "branch_name": "Branch A",
        "role": "owner",
        "permissions": {
          "can_send_to_customers": true,
          "can_send_to_teachers": true,
          "can_send_to_class_groups": true,
          "can_send_to_friends": true,
          "can_send_to_groups": true,
          "view_all_friends": true,
          "view_all_groups": true,
          "view_all_conversations": true
        }
      },
      {
        "id": 2,
        "branch_id": 3,
        "branch_name": "Branch B",
        "role": "shared",
        "permissions": {
          "can_send_to_customers": false,
          "can_send_to_teachers": false,
          "can_send_to_class_groups": false,
          "can_send_to_friends": false,
          "can_send_to_groups": false,
          "view_all_friends": false,
          "view_all_groups": false,
          "view_all_conversations": false
        }
      }
    ]
  }
}
```

---

### 2. Grant Branch Access

**Endpoint:** `POST /api/zalo/branch-access`

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

**Body:**
```json
{
  "account_id": 16,
  "branch_id": 3,
  "permissions": {
    "can_send_to_customers": false,
    "can_send_to_teachers": false,
    "can_send_to_class_groups": false,
    "can_send_to_friends": true,
    "can_send_to_groups": true,
    "view_all_friends": true,
    "view_all_groups": true,
    "view_all_conversations": true
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Branch access granted successfully",
  "data": {
    "id": 2,
    "zalo_account_id": 16,
    "branch_id": 3,
    "role": "shared",
    "can_send_to_customers": false,
    // ... other permissions
  }
}
```

---

### 3. Update Branch Permissions

**Endpoint:** `PUT /api/zalo/branch-access/{id}`

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

**Body:**
```json
{
  "permissions": {
    "can_send_to_customers": true,
    "can_send_to_teachers": true,
    "view_all_friends": true
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Permissions updated successfully",
  "data": {
    "id": 2,
    "can_send_to_customers": true,
    // ... updated permissions
  }
}
```

---

### 4. Revoke Branch Access

**Endpoint:** `DELETE /api/zalo/branch-access/{id}`

**Headers:**
- `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "message": "Branch access revoked successfully"
}
```

---

### 5. Assign Items to Branch

**Endpoint:** `POST /api/zalo/branch-access/assign-items`

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

**Body:**
```json
{
  "account_id": 16,
  "branch_id": 3,
  "item_type": "friends",
  "item_ids": [1, 2, 3, 4, 5]
}
```

**Parameters:**
- `item_type`: One of `friends`, `groups`, `conversations`

**Response:**
```json
{
  "success": true,
  "message": "Successfully assigned 5 friends to branch",
  "data": {
    "updated_count": 5
  }
}
```

---

## PERMISSION REQUIRED

All endpoints require:
- `zalo.manage_multi_branch_access` permission
- OR `super-admin` role

---

**Created:** 27/11/2025
**Related:** ZaloBranchAccessController.php, ZALO_MULTI_BRANCH_PROGRESS.md
