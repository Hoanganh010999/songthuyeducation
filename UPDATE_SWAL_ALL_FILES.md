# 🔄 UPDATE ALL FILES TO USE SWEETALERT2

## Files Cần Update

Đã tìm thấy **14 files** còn dùng `alert()` và `confirm()`:

### Settings Components
1. ✅ TranslationsModal.vue - ĐÃ UPDATE
2. ✅ TranslationEditModal.vue - ĐÃ UPDATE  
3. ✅ TranslationModal.vue - ĐÃ UPDATE
4. ⏳ PermissionModal.vue
5. ⏳ PermissionsContent.vue
6. ⏳ RolePermissionsModal.vue
7. ⏳ RoleModal.vue
8. ⏳ RolesContent.vue
9. ⏳ LanguagesContent.vue
10. ⏳ LanguageModal.vue

### Pages
11. ✅ BranchesList.vue - ĐÃ UPDATE
12. ⏳ UsersList.vue
13. ⏳ TranslationsList.vue
14. ⏳ LanguagesList.vue

### Components
15. ⏳ BranchModal.vue
16. ⏳ UserModal.vue
17. ⏳ CustomerManagement.vue

---

## Pattern Update

### Import
```javascript
// ADD
import { useSwal } from '../../composables/useSwal';

// ADD trong setup
const swal = useSwal();
```

### Replace alert()
```javascript
// BEFORE
alert('Message');
alert(response.data.message);

// AFTER
swal.success('Message');
swal.success(response.data.message);

// For errors
swal.error('Error message');
swal.error(error.response?.data?.message);
```

### Replace confirm()
```javascript
// BEFORE
if (!confirm('Are you sure?')) return;

// AFTER
const result = await swal.confirm('Are you sure?');
if (!result.isConfirmed) return;

// For delete
const result = await swal.confirmDelete('Delete this?');
if (!result.isConfirmed) return;
```

---

Đang update từng file...

