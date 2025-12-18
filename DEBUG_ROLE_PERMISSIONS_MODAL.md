# 🐛 DEBUG: Role Permissions Modal

## Vấn Đề
Click nút "Permissions" trên role card → Modal mở nhưng không thấy danh sách permissions.

## ✅ Đã Sửa

### 1. Thêm `immediate: true` vào watch
**File:** `resources/js/components/settings/RolePermissionsModal.vue`

**Vấn đề:**
- Modal được mount với `v-if="showPermissionsModal && selectedRole"`
- Khi modal xuất hiện, `props.show` đã là `true`
- Watch không trigger vì không có sự thay đổi từ `false` → `true`

**Fix:**
```javascript
// BEFORE:
watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      loadPermissions();
    }
  }
);

// AFTER:
watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      loadPermissions();
    }
  },
  { immediate: true } // ← Thêm này
);
```

### 2. Thêm Console Logs để Debug
```javascript
const loadPermissions = async () => {
  console.log('🔄 Loading permissions for role:', props.role);
  loading.value = true;
  try {
    // Load all permissions
    console.log('📡 Fetching all permissions...');
    const permissionsResponse = await api.get('/api/permissions');
    console.log('📡 All permissions response:', permissionsResponse.data);
    
    if (permissionsResponse.data.success) {
      allPermissions.value = permissionsResponse.data.data;
      console.log('✅ Loaded', allPermissions.value.length, 'permissions');
    }

    // Load role's current permissions
    console.log('📡 Fetching role permissions for role ID:', props.role.id);
    const rolePermissionsResponse = await api.get(`/api/roles/${props.role.id}/permissions`);
    console.log('📡 Role permissions response:', rolePermissionsResponse.data);
    
    if (rolePermissionsResponse.data.success) {
      selectedPermissions.value = rolePermissionsResponse.data.data.map(p => p.id);
      console.log('✅ Selected permissions:', selectedPermissions.value);
    }
  } catch (error) {
    console.error('❌ Failed to load permissions:', error);
    alert('Failed to load permissions');
  } finally {
    loading.value = false;
  }
};
```

---

## 🧪 Cách Test

### 1. Hard Reload
```
Ctrl + Shift + R
```

### 2. Mở Console (F12)

### 3. Test Flow
```
System Settings → Access Control → Roles
  ↓
Click "Permissions" button trên bất kỳ role nào
  ↓
Xem Console logs:
```

**Expected Console Output:**
```
🔄 Loading permissions for role: {id: 1, name: "super-admin", ...}
📡 Fetching all permissions...
📡 All permissions response: {success: true, data: [...]}
✅ Loaded 21 permissions
📡 Fetching role permissions for role ID: 1
📡 Role permissions response: {success: true, data: [...]}
✅ Selected permissions: [1, 2, 3, 4, 5, ...]
```

---

## 🔍 Nếu Vẫn Không Thấy Data

### Check 1: API Response Format
Console sẽ hiển thị response. Kiểm tra:

**All Permissions API:**
```javascript
// Expected:
{
  success: true,
  data: [
    {id: 1, module: "users", action: "view", name: "users.view", ...},
    {id: 2, module: "users", action: "create", name: "users.create", ...},
    ...
  ]
}

// NOT paginated object:
{
  success: true,
  data: {
    current_page: 1,
    data: [...],  // ← Wrong!
    ...
  }
}
```

**Role Permissions API:**
```javascript
// Expected:
{
  success: true,
  data: [
    {id: 1, module: "users", action: "view", ...},
    {id: 2, module: "users", action: "create", ...},
    ...
  ]
}
```

### Check 2: API Endpoints
Kiểm tra routes đã được thêm:

```bash
php artisan route:list | grep "roles.*permissions"
```

**Expected:**
```
GET    api/roles/{id}/permissions  
POST   api/roles/{id}/permissions
```

### Check 3: Permissions Middleware
User phải có permission `roles.view` để gọi API:

```javascript
// Check trong Console:
authStore.hasPermission('roles.view')  // Should return true
```

---

## 🚀 Giải Pháp Nếu Vẫn Lỗi

### Lỗi 1: API trả về paginated data
**Triệu chứng:**
```
Console: allPermissions.value.forEach is not a function
```

**Fix:** Đảm bảo API không có `per_page` param:
```javascript
// In RolePermissionsModal.vue
const permissionsResponse = await api.get('/api/permissions'); // ← No per_page
```

### Lỗi 2: Route không tồn tại
**Triệu chứng:**
```
Console: 404 Not Found - /api/roles/1/permissions
```

**Fix:** Chạy lại:
```bash
php artisan route:clear
php artisan route:cache
```

### Lỗi 3: Permission denied
**Triệu chứng:**
```
Console: 403 Forbidden
```

**Fix:** Kiểm tra user có permission:
```bash
php artisan tinker
>>> $user = User::find(1);
>>> $user->hasPermission('roles.view');  // Should be true
>>> $user->hasPermission('roles.assign-permission');  // Should be true
```

---

## 📊 Debug Checklist

- [ ] Hard reload (Ctrl + Shift + R)
- [ ] Console mở (F12)
- [ ] Click "Permissions" button
- [ ] Thấy console logs "🔄 Loading permissions..."
- [ ] API `/api/permissions` trả về array (không phải paginated)
- [ ] API `/api/roles/{id}/permissions` trả về array
- [ ] `allPermissions.value.length` > 0
- [ ] `selectedPermissions.value` có IDs
- [ ] Modal hiển thị checkboxes grouped by module
- [ ] Checkboxes được pre-selected đúng

---

## ✅ Kết Quả Mong Đợi

**Modal hiển thị:**
```
┌─────────────────────────────────────────┐
│ Manage Permissions: Super Admin    [X] │
│ Select which permissions this role has  │
├─────────────────────────────────────────┤
│ 21 / 21 permissions selected            │
│ [Select All] [Deselect All]             │
├─────────────────────────────────────────┤
│                                         │
│ ┌─ users (5/5) ──────────────────┐     │
│ │ [Select All] [Deselect All]    │     │
│ │                                 │     │
│ │ ☑ users.view                    │     │
│ │ ☑ users.create                  │     │
│ │ ☑ users.edit                    │     │
│ │ ☑ users.delete                  │     │
│ │ ☑ users.assign-role             │     │
│ └─────────────────────────────────┘     │
│                                         │
│ ┌─ roles (5/5) ──────────────────┐     │
│ │ ☑ roles.view                    │     │
│ │ ☑ roles.create                  │     │
│ │ ...                             │     │
│ └─────────────────────────────────┘     │
│                                         │
├─────────────────────────────────────────┤
│ Changes not saved until you click Save  │
│                      [Cancel] [Save]    │
└─────────────────────────────────────────┘
```

---

## 🎯 Next Steps

1. **Reload** trang: `Ctrl + Shift + R`
2. **Mở Console**: `F12`
3. **Click** "Permissions" button
4. **Xem** console logs
5. **Report** kết quả nếu vẫn lỗi

**Console logs sẽ cho biết chính xác vấn đề ở đâu!** 🔍

