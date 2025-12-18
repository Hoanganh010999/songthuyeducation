# ✅ HỆ THỐNG TRỪ HỌC PHÍ V2 - LOGIC MỚI HOÀN CHỈNH

## 🎯 LOGIC MỚI (ĐÃ TRIỂN KHAI):

### **1. Phí Buổi Học (Fee Charge)**

| Status | Logic | % Phí |
|--------|-------|-------|
| **Present** (Đi học đúng giờ) | Tự động trừ phí buổi học | 100% |
| **Unexcused Absence** (Vắng không phép) | Trừ phí + track consecutive | 100% |
| **Excused Absence ≤ limit** (Vắng có phép trong giới hạn) | MIỄN PHÍ | 0% |
| **Excused Absence > limit** (Vắng có phép vượt giới hạn) | Trừ phí | 100% |
| **Late** (Đi trễ) | Trừ phí (vì vẫn đi học) | 100% |

### **2. Phạt (Penalty)**

- **Late Penalty**: Khi số buổi trễ/tháng > `late_penalty_threshold`
  - → Phạt **1 lần** số tiền cố định `late_penalty_amount`
  - VD: Threshold = 3, trễ 5 lần → chỉ phạt 1 lần 50,000đ

### **3. Hoàn Phí (Refund)**

- **Unexcused Consecutive Absence**: 
  - Khi vắng không phép liên tiếp > `absence_consecutive_threshold` buổi
  - → Hệ thống **đánh dấu TẤT CẢ các buổi liên tiếp** để hoàn phí
  - → Status: `refund_pending` (chờ admin review)
  - → Admin duyệt thủ công qua UI (sẽ triển khai sau)

---

## 🗄️ DATABASE CHANGES:

### **Table: `attendance_fee_policies`**
**Thêm 2 fields:**
```sql
- late_penalty_amount (decimal 10,2, default 0) - Số tiền phạt cố định
- late_penalty_threshold (int, default 3) - Số buổi trễ/tháng trước khi phạt
```

### **Table: `attendance_fee_deductions`**
**Thay đổi lớn:**
```sql
Removed:
- deduction_type (enum: unexcused_absence, excused_over_limit, late)

Added:
- transaction_type (enum: charge, penalty, refund_pending)
- consecutive_absence_count (int, nullable) - Track consecutive absences
- refund_status (enum: pending, approved, rejected, nullable)
- refund_approved_by (foreign key -> users, nullable)
- refund_approved_at (timestamp, nullable)
- refund_reason (text, nullable)
```

---

## 💻 CODE CHANGES:

### **1. Models**

#### `AttendanceFeePolicy.php`
```php
✅ Added fillable: late_penalty_amount, late_penalty_threshold
✅ Added casts: late_penalty_amount (decimal:2), late_penalty_threshold (integer)
```

#### `AttendanceFeeDeduction.php`
```php
✅ Added fillable: transaction_type, consecutive_absence_count, refund_status, 
   refund_approved_by, refund_approved_at, refund_reason
✅ Added casts: consecutive_absence_count (integer), refund_approved_at (datetime)
✅ Added relationship: refundApprovedBy() -> belongsTo(User)
```

### **2. Services**

#### `AttendanceFeeService.php` - **VIẾT LẠI HOÀN TOÀN**

**Main Method:**
```php
processAttendanceFee(Attendance $attendance): array
```

**Logic Flow:**
```
1. Get active policy
2. Get class hourly_rate
3. Process by status:
   - Present       → chargeFee(100%)
   - Unexcused     → chargeFee(100%) + markForRefundIfNeeded()
   - Excused       → check monthly limit → chargeFee(0% or 100%)
   - Late          → chargeFee(100%) + applyPenaltyIfNeeded()
4. Return array of deductions created
```

**Helper Methods:**
```php
✅ chargeFee() - Charge fee + create deduction + withdraw from wallet
✅ applyPenalty() - Apply fixed penalty amount
✅ markConsecutiveAbsencesForRefund() - Mark deductions for refund (pending)
✅ withdrawFromWallet() - Create wallet transaction
✅ getConsecutiveUnexcusedAbsences() - Count consecutive absences
✅ getMonthlyExcusedAbsencesCount() - Count monthly excused absences
✅ getMonthlyLateCount() - Count monthly late attendance
```

### **3. Observer**

#### `AttendanceObserver.php` - **VIẾT LẠI**
```php
✅ created() → processAttendanceFee()
✅ updated() → processAttendanceFee() (if status/is_excused/check_in_time changed)
✅ Extensive logging
```

### **4. Controller**

#### `AttendanceFeePolicyController.php`
```php
✅ Added validation: late_penalty_amount, late_penalty_threshold
✅ store() - validate new fields
✅ update() - validate new fields
```

### **5. Frontend**

#### `PolicyModal.vue`
```php
✅ Added form fields:
   - late_penalty_threshold (input number, min=1)
   - late_penalty_amount (input number, min=0, step=1000)
✅ Updated form defaults:
   late_penalty_threshold: 3
   late_penalty_amount: 50000
✅ Added helper text
```

### **6. Translations**
```
✅ attendance_fee.late_penalty_threshold
   - VI: "Số buổi trễ/tháng"
   - EN: "Late count/month"
✅ attendance_fee.late_penalty_amount
   - VI: "Số tiền phạt"
   - EN: "Penalty amount"
```

---

## 📊 WORKFLOW TỰ ĐỘNG:

### **Khi Teacher Điểm Danh:**

```
Teacher marks attendance
   ↓
AttendanceObserver::created() triggered
   ↓
AttendanceFeeService::processAttendanceFee($attendance)
   ↓
┌─────────────────────────────────────────┐
│ Get policy & hourly_rate                │
└─────────────────────────────────────────┘
   ↓
┌─────────────────────────────────────────┐
│ Check status:                           │
│  • present     → charge 100%            │
│  • unexcused   → charge 100% + track    │
│  • excused     → check limit → charge   │
│  • late        → charge 100% + penalty  │
└─────────────────────────────────────────┘
   ↓
┌─────────────────────────────────────────┐
│ Create:                                 │
│  • WalletTransaction (withdrawal)       │
│  • AttendanceFeeDeduction (log)         │
│  • Mark refund if needed (pending)      │
└─────────────────────────────────────────┘
   ↓
✅ DONE
```

---

## 🧪 TEST CASES:

### **Test 1: Present (Đi học đúng giờ)**
```php
$attendance = Attendance::create([
    'student_id' => 1,
    'class_id' => 1,
    'session_id' => 1,
    'status' => 'present',
]);

Expected:
✅ Deduction created (transaction_type: 'charge')
✅ Amount = hourly_rate * 100%
✅ Wallet balance decreased
```

### **Test 2: Unexcused Absence (Vắng không phép)**
```php
// Vắng 1 buổi
$attendance1 = Attendance::create(['status' => 'absent', 'is_excused' => false]);
Expected:
✅ Charge 100%
❌ No refund mark (consecutive = 1 ≤ threshold)

// Vắng buổi 2, 3, 4 liên tiếp
$attendance2 = Attendance::create(['status' => 'absent', 'is_excused' => false]);
...
Expected (buổi 4, threshold = 1):
✅ Charge 100%
✅ Mark ALL 4 absences for refund (refund_status: 'pending')
✅ consecutive_absence_count = 4
```

### **Test 3: Excused Absence (Vắng có phép)**
```php
// Vắng có phép lần 1, 2 (limit = 2)
Expected:
✅ NO CHARGE (0%)

// Vắng có phép lần 3 (over limit)
Expected:
✅ Charge 100%
```

### **Test 4: Late (Đi trễ)**
```php
// Trễ lần 1, 2, 3 (threshold = 3)
Expected:
✅ Each: Charge 100%
❌ No penalty yet

// Trễ lần 4 (over threshold)
Expected:
✅ Charge 100%
✅ Penalty = 50,000đ (transaction_type: 'penalty')
✅ Only penalized ONCE per month
```

---

## 🚀 HOW TO USE:

### **1. Admin - Quản lý Policy**
```
Quality Management → Settings → Attendance Fee Policy
→ Create/Edit policy
→ Set:
   - Unexcused %: 100
   - Consecutive threshold: 1
   - Excused free limit: 2
   - Excused %: 50 (không dùng nữa)
   - Late %: 100 (không dùng nữa)
   - Late grace: 15 minutes
   - Late penalty threshold: 3 buổi/tháng
   - Late penalty amount: 50,000đ
→ Activate
```

### **2. Teacher - Điểm Danh**
```
Classes → [Select Class] → Attendance Tab
→ Select session + date
→ Mark students:
   ✅ Present (đúng giờ)
   ❌ Absent (checkbox "Có lý do" if excused)
   ⏰ Late (enter check-in time)
→ Save
→ Hệ thống TỰ ĐỘNG trừ tiền
```

### **3. Admin - Review Refunds (Chưa có UI)**
```sql
-- Xem danh sách chờ refund
SELECT * FROM attendance_fee_deductions
WHERE refund_status = 'pending'
ORDER BY applied_at DESC;

-- Approve refund (manual query for now)
UPDATE attendance_fee_deductions
SET refund_status = 'approved',
    refund_approved_by = 1, -- admin user_id
    refund_approved_at = NOW()
WHERE id = 123;

-- TODO: Build UI for this
```

---

## ⚠️ PENDING TASKS:

### **P1 - High Priority:**
1. ❌ **Refund Approval UI**: Admin page để duyệt/từ chối refunds
2. ❌ **Refund Processing**: Service để thực hiện refund vào wallet khi approved
3. ❌ **Notification**: Thông báo cho admin khi có refund pending

### **P2 - Medium Priority:**
4. ❌ **Reports**: Báo cáo thống kê refunds, penalties
5. ❌ **Audit Log**: Log chi tiết actions của admin

### **P3 - Low Priority:**
6. ❌ **Bulk Operations**: Approve/reject multiple refunds at once

---

## 📁 FILES CHANGED:

### **Database:**
```
✅ database/migrations/2025_11_08_214630_add_late_penalty_and_refund_fields_to_attendance_fee_system.php
```

### **Backend:**
```
✅ app/Models/AttendanceFeePolicy.php (updated)
✅ app/Models/AttendanceFeeDeduction.php (updated)
✅ app/Services/AttendanceFeeService.php (REWRITTEN)
✅ app/Observers/AttendanceObserver.php (REWRITTEN)
✅ app/Http/Controllers/Api/AttendanceFeePolicyController.php (updated validation)
```

### **Frontend:**
```
✅ resources/js/pages/quality/settings/PolicyModal.vue (added fields)
```

### **Database Data:**
```
✅ Default policy updated with late_penalty values
✅ Translations added
```

---

## ✅ SUMMARY:

**Đã hoàn thành:**
- ✅ Database schema v2
- ✅ Backend logic hoàn chỉnh (charge, penalty, refund tracking)
- ✅ Frontend form với fields mới
- ✅ Translations
- ✅ Migration & seeding
- ✅ Build successful

**Logic hoạt động:**
- ✅ Present → Trừ phí
- ✅ Unexcused → Trừ phí + mark refund nếu quá threshold
- ✅ Excused → Check limit → trừ hoặc miễn phí
- ✅ Late → Trừ phí + phạt nếu quá threshold

**Chưa có:**
- ❌ Refund approval UI
- ❌ Refund processing service (deposit back to wallet)

**Ready for testing!** 🎉

---

🚀 **Hệ thống đã sẵn sàng để test workflow tự động!**

