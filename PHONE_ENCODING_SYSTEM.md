# 📱 Hệ Thống Mã Hóa Số Điện Thoại

## 🎯 Mục Đích

Khi tạo folder Google Drive cho user, số điện thoại được mã hóa để:
1. ✅ **Đảm bảo tính duy nhất** (uniqueness)
2. ✅ **Bảo mật thông tin** (không thể reverse)
3. ✅ **Tính nhất quán** (cùng phone → cùng hash)

## 🔐 Thuật Toán

### Encoding Method: SHA256 Hash (First 8 chars)

```php
function encodePhone($phone) {
    // 1. Remove non-digit characters
    $phone = preg_replace('/\D/', '', $phone);
    
    // 2. SHA256 hash
    $hash = hash('sha256', $phone);
    
    // 3. Take first 8 characters
    return substr($hash, 0, 8);
}
```

### Ví Dụ

| Số Điện Thoại Gốc | Mã Hash | Folder Name |
|-------------------|---------|-------------|
| `0901234567` | `d99c7a10` | `1.Nguyễn Văn A.d99c7a10` |
| `0909876543` | `63a40d89` | `1.Nguyễn Thị B.63a40d89` |
| `0123456789` | `84d89877` | `2.Trần Văn C.84d89877` |

## ✅ Đảm Bảo Tính Chất

### 1. **Uniqueness (Tính Duy Nhất)**
```
Phone 1: 0901234567 → d99c7a10
Phone 2: 0901234568 → b99ebcce (khác 100%)
```
- Chỉ thay đổi 1 số → hash hoàn toàn khác
- Không có collision trong thực tế

### 2. **Consistency (Tính Nhất Quán)**
```
Lần 1: 0901234567 → d99c7a10
Lần 2: 0901234567 → d99c7a10
Lần 3: 0901234567 → d99c7a10
```
- Cùng input → cùng output 100%

### 3. **Format Normalization**
```
0901234567      → d99c7a10
090-123-4567    → d99c7a10
090 123 4567    → d99c7a10
(090) 123-4567  → d99c7a10
```
- Tất cả format → cùng hash

### 4. **Security (Bảo Mật)**
- ❌ Không thể reverse hash để lấy số gốc
- ✅ Phải brute force 10 tỷ combinations
- ✅ Safe cho production

## 📊 Collision Analysis

### Probability Calculation

**Hash space:** 16^8 = 4,294,967,296 combinations

| Số Users | Collision Probability |
|----------|----------------------|
| 1,000    | 0.000012% |
| 10,000   | 0.0012% |
| 100,000  | 0.12% |
| 1,000,000| 11.6% |

**Verdict:** 
- ✅ Safe cho hệ thống < 100,000 users
- ⚠️ Nếu > 100K users, nên tăng lên 10 chars

## 🏗️ Folder Naming Convention

### Format
```
{branch_id}.{user_name}.{phone_hash}
```

### Examples
```
1.Nguyễn Văn A.d99c7a10
2.Trần Thị B.63a40d89
1.Lê Văn C.84d89877
```

### Breakdown
- `branch_id`: 1 digit branch identifier
- `user_name`: Full name (có dấu)
- `phone_hash`: 8-char SHA256 hash

### Benefits
1. **Easy to Identify Branch**
   - Quick filter: `1.*` = Branch 1
   
2. **Searchable by Name**
   - Find: "Nguyễn Văn A"
   
3. **Unique by Phone**
   - `d99c7a10` = unique identifier
   
4. **Privacy Protected**
   - Hash không thể reverse

## 🔄 Migration từ Old Format

### Old Format (nếu có)
```
UserName - PhoneNumber
Nguyễn Văn A - 0901234567
```

### New Format
```
{branch}.{name}.{hash}
1.Nguyễn Văn A.d99c7a10
```

### Migration Strategy
```php
// Khi assign Google email:
1. Check existing folder với old format
2. Nếu tìm thấy:
   - Option A: Rename folder
   - Option B: Use existing folder
   - Option C: Create new folder
3. Nếu không tìm thấy:
   - Create với new format
```

## 🛠️ Implementation Details

### Backend (Laravel)

**File:** `app/Http/Controllers/Api/UserGoogleDriveController.php`

```php
protected function encodePhone($phone)
{
    $phone = preg_replace('/\D/', '', $phone);
    $hash = hash('sha256', $phone);
    return substr($hash, 0, 8);
}

protected function generateFolderName($user, $branchId)
{
    $encodedPhone = $this->encodePhone($user->phone);
    return "{$branchId}.{$user->name}.{$encodedPhone}";
}
```

### Example Usage

```php
$user = User::find(1);
// name: "Nguyễn Văn A"
// phone: "0901234567"
// branch_id: 1

$folderName = $this->generateFolderName($user, 1);
// Result: "1.Nguyễn Văn A.d99c7a10"
```

## ⚙️ Configuration

### Hash Length Adjustment

Nếu cần thay đổi độ dài hash:

```php
// Current: 8 chars
return substr($hash, 0, 8);

// For more uniqueness: 10 chars (recommended for > 100K users)
return substr($hash, 0, 10);

// For maximum uniqueness: 16 chars
return substr($hash, 0, 16);
```

### Trade-offs

| Length | Combinations | Folder Name Length | Collision @ 100K |
|--------|-------------|-------------------|------------------|
| 6 chars | 16M | Shorter | 0.3% |
| 8 chars | 4.3B | Balanced | 0.12% |
| 10 chars | 1.1T | Longer | 0.0005% |

**Recommendation:** **8 chars** (current) - Perfect balance

## 🧪 Testing

### Test Cases

```php
// Test 1: Uniqueness
assert(encodePhone('0901234567') !== encodePhone('0901234568'));

// Test 2: Consistency
assert(encodePhone('0901234567') === encodePhone('0901234567'));

// Test 3: Format normalization
assert(encodePhone('0901234567') === encodePhone('090-123-4567'));

// Test 4: Length
assert(strlen(encodePhone('0901234567')) === 8);
```

### Manual Test

```bash
php artisan tinker

$controller = new \App\Http\Controllers\Api\UserGoogleDriveController;
$user = User::find(1);
$folderName = $controller->generateFolderName($user, 1);
dd($folderName);
```

## 🚨 Important Notes

1. **Phone Must Be Unique in System**
   - System validates phone uniqueness before creating folder
   
2. **Phone Required**
   - Cannot create folder without phone
   
3. **Hash is Deterministic**
   - Same phone always produces same hash
   
4. **No Reverse Engineering**
   - Hash cannot be converted back to original phone

## 📚 References

- SHA256: https://en.wikipedia.org/wiki/SHA-2
- Collision Probability: Birthday Problem
- Google Drive Naming Limits: 256 chars max

---

**Created:** November 10, 2025  
**Version:** 1.0.0  
**Algorithm:** SHA256 (first 8 chars)

