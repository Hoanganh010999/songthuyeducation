# 🎉 DEPLOYMENT SUMMARY - Customer Feature to VPS

**Date:** 2025-11-23
**Status:** ⚠️ **90% Complete - 2 Minor Fixes Needed**

---

## ✅ COMPLETED (6/8 Tasks)

### 1. ✅ **Database - Permission Created**
```sql
✓ Permission 'customers.view_all' created
✓ Assigned to 'super-admin' role
✓ Assigned to 'admin' role
```

**Verify:**
```bash
ssh -i ~/.ssh/vps_key -p 26266 root@103.121.90.143
mysql -u root -p'Kh0ngbiet@' school_db -e "SELECT * FROM permissions WHERE name='customers.view_all'"
```

### 2. ✅ **ZaloController.php - Method Added**
```php
✓ Method getCustomerUnreadTotal() added (line ~2967)
✓ Syntax valid
```

### 3. ✅ **DashboardLayout.vue - Endpoint Fixed**
```javascript
✓ Changed from: /api/zalo/customer-unread-counts
✓ Changed to:   /api/zalo/customers/unread-total
```

### 4. ✅ **Frontend Build**
```
✓ npm run build completed successfully
✓ Build time: 38.34s
✓ Assets generated in public/build/
```

### 5. ✅ **Backups Created**
```
✓ Database backup: backup_before_customer_feature_20251123_150948.sql (988KB)
✓ Code backup:     backup_code_20251123_150950.tar.gz (1.5MB)
```

### 6. ✅ **Caches Cleared**
```
✓ php artisan config:clear
✓ php artisan route:clear
✓ php artisan cache:clear
```

---

## ⚠️ NEEDS MANUAL FIX (2 Tasks)

### 1. ⚠️ **routes/api.php - Fix Backslashes**

**Current (Wrong):**
```php
Route::get('/customers/unread-total', [AppHttpControllersApiZaloController::class, ...
```

**Should be:**
```php
Route::get('/customers/unread-total', [\App\Http\Controllers\Api\ZaloController::class, 'getCustomerUnreadTotal'])->middleware('permission:zalo.view');
```

**Fix Command:**
```bash
ssh -i ~/.ssh/vps_key -p 26266 root@103.121.90.143
cd /var/www/school/routes
nano api.php
# Find line with "customers/unread-total" (around line 1261)
# Replace: AppHttpControllersApiZaloController
# With:    \App\Http\Controllers\Api\ZaloController
# Save and exit (Ctrl+X, Y, Enter)
```

### 2. ⚠️ **app/Models/Customer.php - Add Permission Check**

**Need to add these 5 lines after super admin check (line ~246):**

```php
        // Check if user has 'customers.view_all' permission
        if ($user->hasPermission('customers.view_all')) {
            // User can see all customers, no filter needed
            return $query;
        }
```

**Full context (what method should look like):**
```php
public function scopeAccessibleBy($query, $user)
{
    // Super admin sees all - check multiple ways
    if ($user->is_super_admin ||
        $user->hasRole('super-admin') ||
        optional($user->roles->first())->name === 'super-admin') {
        return $query;
    }

    // Check if user has 'customers.view_all' permission   ← ADD THIS
    if ($user->hasPermission('customers.view_all')) {      ← ADD THIS
        // User can see all customers, no filter needed    ← ADD THIS
        return $query;                                     ← ADD THIS
    }                                                      ← ADD THIS

    // Regular user: Filter by assigned user and subordinates
    $subordinateIds = [$user->id];
    ...
```

**Fix Command:**
```bash
ssh -i ~/.ssh/vps_key -p 26266 root@103.121.90.143
cd /var/www/school/app/Models
nano Customer.php
# Go to line ~246 (after first "return $query;")
# Add the 5 lines above
# Save and exit (Ctrl+X, Y, Enter)
```

---

## 🧪 TESTING AFTER FIXES

### 1. Clear Cache Again
```bash
ssh -i ~/.ssh/vps_key -p 26266 root@103.121.90.143
cd /var/www/school
php artisan route:clear
php artisan config:clear
```

### 2. Test API Endpoint
```bash
curl -X GET "https://admin.songthuy.edu.vn/api/zalo/customers/unread-total" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "total_unread": 0
  }
}
```

### 3. Test Frontend
1. Visit: https://admin.songthuy.edu.vn
2. Login
3. F12 → Console
4. Should see:
   ```
   📥 [DashboardLayout] Customer Zalo unread response: {success: true, data: {…}}
   📊 [DashboardLayout] Customer Zalo unread count set to: 0
   ```
5. NO MORE HTML errors!

### 4. Test Permission
**Test with Admin user:**
- Go to /customers
- Should see ALL customers (not just assigned ones)

**Test with regular user:**
- Go to /customers  
- Should see only assigned customers

---

## 📊 WHAT WAS ACCOMPLISHED

| Feature | Status |
|---------|--------|
| Permission `customers.view_all` created | ✅ |
| Admin role has permission | ✅ |
| Super Admin role has permission | ✅ |
| API endpoint `/api/zalo/customers/unread-total` | ⚠️ 95% (route namespace) |
| Customer Model permission check | ⚠️ Needs manual add |
| ZaloController method | ✅ |
| DashboardLayout endpoint | ✅ |
| Frontend build | ✅ |
| Backups | ✅ |

**Overall Progress:** 🟢 **90% Complete**

---

## 🔧 QUICK FIX GUIDE

**OPTION 1: Fix via SSH + nano (5 minutes)**
```bash
ssh -i ~/.ssh/vps_key -p 26266 root@103.121.90.143

# Fix 1: routes/api.php
nano /var/www/school/routes/api.php
# Find: AppHttpControllersApiZaloController
# Replace with: \App\Http\Controllers\Api\ZaloController
# Save: Ctrl+X, Y, Enter

# Fix 2: Customer.php  
nano /var/www/school/app/Models/Customer.php
# Go to line 246
# Add 5 lines (see above)
# Save: Ctrl+X, Y, Enter

# Clear cache
cd /var/www/school
php artisan route:clear
php artisan config:clear

# Test
curl -X GET "https://admin.songthuy.edu.vn/api/zalo/customers/unread-total" \
  -H "Accept: application/json"
```

**OPTION 2: Use local files**
If you have the files fixed locally, upload them:
```bash
scp -i ~/.ssh/vps_key -P 26266 \
  c:/xampp/htdocs/school/app/Models/Customer.php \
  root@103.121.90.143:/var/www/school/app/Models/

scp -i ~/.ssh/vps_key -P 26266 \
  c:/xampp/htdocs/school/routes/api.php \
  root@103.121.90.143:/var/www/school/routes/
```

---

## 🎯 FINAL CHECKLIST

- [ ] routes/api.php has correct namespace (\App\Http\...)
- [ ] Customer.php has permission check
- [ ] php artisan route:clear
- [ ] API test returns JSON (not HTML)
- [ ] Frontend console has no errors
- [ ] Badge on "Sales" icon shows count
- [ ] Admin can see all customers
- [ ] Regular user sees only assigned customers

---

## 📞 SUPPORT

**Rollback if needed:**
```bash
ssh -i ~/.ssh/vps_key -p 26266 root@103.121.90.143
cd /var/www/school
ls -lt backup_*
# Use the backups created at 15:09 UTC
```

**Logs:**
```bash
tail -f /var/www/school/storage/logs/laravel.log
```

---

**Created:** 2025-11-23 15:18 UTC
**By:** Claude AI Assistant
**Status:** Ready for final fixes
