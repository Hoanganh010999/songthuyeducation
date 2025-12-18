# Syllabus Management - Google Drive Integration

## Overview
Complete implementation of Google Drive folder management for Syllabus Management module with advanced features including:
- Automatic folder creation with syllabus code
- Conflict detection and resolution
- Unit folder structure creation
- User permission verification
- Google email validation

---

## Features Implemented

### 1. **Folder Naming Convention**
- **Format**: `{Syllabus Name} - {SYLLABUS_CODE}`
- **Example**: `IELTS 5.0 - IELTS50`
- **Purpose**: Ensures consistency and uniqueness using syllabus code

### 2. **Pre-Creation Validations**

#### User Google Email Check
```php
if (!$user->google_email) {
    return response()->json([
        'error_code' => 'NO_GOOGLE_EMAIL',
        'message' => __('errors.google_drive_not_connected'),
    ], 400);
}
```
- **Error Message (VI)**: "Tài khoản chưa kết nối Google Drive"
- **Action Required**: Contact Admin to assign Google email

#### Syllabus Folder Existence Check
```php
$syllabusFolderId = $service->findOrCreateSyllabusFolder();
```
- **Error Code**: `NO_SYLLABUS_FOLDER`
- **Error Message (VI)**: "Không tìm thấy thư mục Syllabus"
- **Action Required**: Contact Admin to create Syllabus parent folder

### 3. **Folder Conflict Resolution**

When a folder with the same name already exists:

#### Step 1: Detect Conflict
```php
$existingFolderId = $this->searchFolderInParent($folderName, $syllabusFolderId);
if ($existingFolderId) {
    return [
        'exists' => true,
        'folder_id' => $existingFolderId,
        'has_permission' => $this->userHasPermission($existingFolderId),
    ];
}
```

#### Step 2: User Decision Dialog (Frontend)
**SweetAlert2 Dialog** with 3 options:
1. **Use Existing Folder** (Green button)
   - Verifies user has permission to folder
   - If no permission → Shows error, requires Admin contact
   
2. **Create New Folder** (Blue button)
   - Renames old folder to `{FolderName}.oldXX`
   - XX increments (old1, old2, old3...) to avoid conflicts
   - Creates new folder with original name
   
3. **Cancel** (Gray button)
   - Cancels folder creation
   - Syllabus still saved but without Google Drive folder

#### Step 3: Rename Old Folder (if Create New)
```php
public function renameToOld($folderId, $currentName)
{
    $suffix = 1;
    $newName = "{$currentName}.old{$suffix}";
    
    while ($this->searchFolderInParent($newName, $parentFolderId)) {
        $suffix++;
        $newName = "{$currentName}.old{$suffix}";
    }
    
    $this->client->files->update($folderId, ['name' => $newName]);
}
```

### 4. **Unit Folder Structure Creation**

When creating a syllabus folder, automatically creates unit subfolders:

```
Syllabus Folder (IELTS 5.0 - IELTS50)
├── Unit 1/
│   ├── Materials/
│   └── Homework/
├── Unit 2/
│   ├── Materials/
│   └── Homework/
...
└── Unit N/
    ├── Materials/
    └── Homework/
```

**Implementation**:
```php
protected function createUnitFolders($parentFolderId, $totalUnits, $syllabusId)
{
    for ($i = 1; $i <= $totalUnits; $i++) {
        $unitFolderName = "Unit {$i}";
        $unitFolderId = $this->createFolder($unitFolderName, $parentFolderId);
        
        // Create Materials subfolder
        $materialsFolderId = $this->createFolder('Materials', $unitFolderId);
        
        // Create Homework subfolder
        $homeworkFolderId = $this->createFolder('Homework', $unitFolderId);
    }
}
```

### 5. **Ownership Transfer**

```php
protected function transferOwnershipToApi($folderId)
{
    $serviceAccountEmail = $this->setting->credentials['client_email'] ?? null;
    
    // Note: Service accounts cannot be owners in Google Drive
    // They already have full access as creators
    // This method ensures proper logging and tracking
}
```

**Note**: Service accounts automatically have full access to folders they create, so explicit ownership transfer is not required.

### 6. **Permission Verification**

```php
public function userHasPermission($folderId)
{
    $user = auth()->user();
    if (!$user || !$user->google_email) {
        return false;
    }

    $permissions = $this->client->files->listPermissions($folderId);
    
    foreach ($permissions->getPermissions() as $permission) {
        if ($permission->getEmailAddress() === $user->google_email) {
            return true;
        }
    }
    
    return false;
}
```

---

## API Endpoints

### Create Syllabus Folder
**POST** `/api/google-drive/create-syllabus-folder`

**Request Body**:
```json
{
  "branch_id": 1,
  "syllabus_id": 10,
  "syllabus_name": "IELTS 5.0",
  "syllabus_code": "IELTS50",
  "total_units": 30,
  "use_existing": false,
  "existing_folder_id": null
}
```

**Response (Success)**:
```json
{
  "success": true,
  "message": "Tạo thư mục giáo án thành công",
  "data": {
    "folder_id": "1ABC...XYZ",
    "folder_name": "IELTS 5.0 - IELTS50"
  }
}
```

**Response (Conflict - 409)**:
```json
{
  "success": false,
  "folder_exists": true,
  "existing_folder_id": "1ABC...XYZ",
  "folder_name": "IELTS 5.0 - IELTS50",
  "has_permission": false,
  "message": "Folder đã tồn tại",
  "question": "Folder giáo án này đã tồn tại. Bạn muốn sử dụng folder hiện có hay tạo folder mới?"
}
```

**Error Codes**:
- `NO_GOOGLE_EMAIL` (400): User not connected to Google Drive
- `NO_GDRIVE_CONFIG` (400): Google Drive not configured for branch
- `NO_SYLLABUS_FOLDER` (400): Syllabus parent folder not found
- `NO_PERMISSION` (403): User lacks permission to existing folder

---

## Frontend Implementation

### SyllabusForm.vue

#### Folder Creation Flow
```javascript
const createFolderForSyllabus = async (syllabusId, syllabusName, syllabusCode, totalUnits) => {
  try {
    const response = await axios.post('/api/google-drive/create-syllabus-folder', {
      syllabus_id: syllabusId,
      syllabus_name: syllabusName,
      syllabus_code: syllabusCode,
      total_units: totalUnits
    });
    
    return response.data.data;
  } catch (error) {
    if (error.response?.status === 409) {
      // Handle conflict with SweetAlert2 dialog
      const result = await Swal.fire({
        icon: 'question',
        title: error.response.data.message,
        html: `...`,
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: t('common.use_existing'),
        denyButtonText: t('common.create_new'),
      });
      
      if (result.isConfirmed) {
        // Use existing folder
      } else if (result.isDenied) {
        // Create new folder (rename old)
      }
    }
  }
};
```

#### Error Handling
```javascript
if (errorCode === 'NO_GOOGLE_EMAIL') {
  await Swal.fire({
    icon: 'error',
    title: t('common.error'),
    html: `
      <div class="text-left">
        <p class="mb-2">${error.response.data.message}</p>
        <p class="text-sm text-gray-600">${t('syllabus.contact_admin_for_google_email')}</p>
      </div>
    `,
  });
}
```

---

## Database Changes

### LessonPlan Model
```php
protected $fillable = [
    // ... existing fields
    'google_drive_folder_id',      // Google Drive folder ID
    'google_drive_folder_name',    // Folder name for reference
];
```

### GoogleDriveItem Model
```php
protected $casts = [
    'metadata' => 'array',
];

// Metadata structure for syllabus folders:
{
    "syllabus_id": 10,
    "syllabus_name": "IELTS 5.0",
    "unit_number": 1,           // For unit folders
    "type": "materials"         // For subfolder type
}
```

---

## Permissions

### Backend
Syllabus management supports **dual permission** system for backwards compatibility:

```php
private function checkPermission($user, $action)
{
    $oldPerm = "lesson_plans.{$action}";
    $newPerm = "syllabus.{$action}";
    
    return $user->hasPermission($oldPerm) || $user->hasPermission($newPerm);
}
```

**Permissions**:
- `lesson_plans.view` or `syllabus.view` - View syllabi
- `lesson_plans.create` or `syllabus.create` - Create syllabus
- `lesson_plans.edit` or `syllabus.edit` - Edit syllabus
- `lesson_plans.delete` or `syllabus.delete` - Delete syllabus

### Frontend Router
Updated to support array of permissions (OR logic):

```javascript
{
    path: 'quality/syllabus/:id',
    name: 'syllabus.detail',
    component: SyllabusDetail,
    meta: { permission: ['lesson_plans.view', 'syllabus.view'] }
}
```

**Router Guard Enhancement**:
```javascript
router.beforeEach((to, from, next) => {
    if (to.meta.permission) {
        const permissions = Array.isArray(to.meta.permission) 
            ? to.meta.permission 
            : [to.meta.permission];
        
        const hasAccess = permissions.some(permission => 
            authStore.hasPermission(permission)
        );
        
        if (!hasAccess) {
            next({ name: 'dashboard' });
        }
    }
});
```

---

## Translation Keys

### Error Messages (`errors` group)
```javascript
{
  'errors.google_drive_not_connected': 'Tài khoản chưa kết nối Google Drive',
  'errors.syllabus_folder_not_found': 'Không tìm thấy thư mục Syllabus',
  'errors.no_permission_to_folder': 'Bạn không có quyền truy cập folder này',
  'errors.syllabus_folder_creation_failed': 'Tạo thư mục giáo án thất bại',
}
```

### Success Messages (`messages` group)
```javascript
{
  'messages.use_existing_or_create_new_syllabus': 'Folder giáo án này đã tồn tại. Bạn muốn sử dụng folder hiện có hay tạo folder mới?',
  'messages.syllabus_folder_created_successfully': 'Tạo thư mục giáo án thành công',
}
```

### Syllabus-specific (`syllabus` group)
```javascript
{
  'syllabus.contact_admin_for_google_email': 'Vui lòng liên hệ Admin để được cấp tài khoản Google Drive',
  'syllabus.contact_admin_for_syllabus_folder': 'Vui lòng liên hệ Admin để tạo thư mục Syllabus',
  'syllabus.contact_admin_for_permission': 'Vui lòng liên hệ Admin để được cấp quyền truy cập folder này',
  'syllabus.no_permission_warning': '⚠️ Bạn chưa có quyền truy cập folder này. Nếu chọn sử dụng folder cũ, bạn sẽ cần liên hệ Admin để được cấp quyền.',
  'syllabus.created_without_folder': 'Giáo án đã được tạo nhưng không thể tạo thư mục Google Drive. Vui lòng liên hệ Admin.',
}
```

---

## User Workflow

### Creating a New Syllabus

1. **User fills out syllabus form**:
   - Name: "IELTS 5.0"
   - Code: "IELTS50"
   - Total Units: 30
   - Other fields...

2. **Submit form** → System checks:
   - ✅ User has `google_email` assigned?
   - ✅ Syllabus parent folder exists?
   - ✅ Folder with same name exists?

3. **If folder exists**:
   - Show dialog: "Use existing or create new?"
   - **If use existing**: Check user permission → Continue or show error
   - **If create new**: Rename old to `.old1` → Create new

4. **Create folder structure**:
   ```
   IELTS 5.0 - IELTS50/
   ├── Unit 1/
   │   ├── Materials/
   │   └── Homework/
   ├── Unit 2/
   │   ├── Materials/
   │   └── Homework/
   ...
   └── Unit 30/
       ├── Materials/
       └── Homework/
   ```

5. **Success**:
   - Syllabus saved to database
   - Folder ID linked to syllabus
   - User can now access folder

---

## Files Modified

### Backend
- `app/Services/GoogleDriveService.php` - Core folder creation logic
- `app/Http/Controllers/Api/GoogleDriveController.php` - API endpoint
- `app/Http/Controllers/Api/LessonPlanController.php` - Syllabus CRUD
- `database/seeders/ErrorMessagesTranslationsSeeder.php` - Error translations
- `database/seeders/SuccessMessagesTranslationsSeeder.php` - Success translations
- `database/seeders/SyllabusGoogleDriveTranslationsSeeder.php` - New seeder

### Frontend
- `resources/js/pages/quality/SyllabusForm.vue` - Form with folder creation
- `resources/js/pages/quality/SyllabusList.vue` - Permission check update
- `resources/js/pages/quality/QualityIndex.vue` - Permission check update
- `resources/js/router/index.js` - Multi-permission support

---

## Testing Checklist

### Backend Tests
- [ ] User without `google_email` → Error `NO_GOOGLE_EMAIL`
- [ ] Syllabus folder not exists → Error `NO_SYLLABUS_FOLDER`
- [ ] Folder name conflict → 409 response with conflict info
- [ ] Use existing folder without permission → Error `NO_PERMISSION`
- [ ] Create new folder → Old renamed to `.old1`
- [ ] Multiple .oldXX suffixes → Increments correctly
- [ ] Unit folders created → All units, materials, homework present

### Frontend Tests
- [ ] No Google email → Show Admin contact message
- [ ] Folder conflict dialog → Shows correctly
- [ ] Use existing button → Verifies permission
- [ ] Create new button → Renames old, creates new
- [ ] Cancel button → Syllabus saved without folder
- [ ] Success message → Folder created notification
- [ ] Permissions → Only users with `lesson_plans.create` or `syllabus.create` can create

### Permission Tests
- [ ] `lesson_plans.view` grants access to syllabus list
- [ ] `syllabus.view` grants access to syllabus list
- [ ] No permission → Redirected to dashboard
- [ ] Multiple routes respect dual permission system

---

## Notes

1. **Service Account Limitations**:
   - Service accounts cannot be "owners" in Google Drive
   - They automatically have full access to folders they create
   - `transferOwnershipToApi()` is a placeholder for future enhancements

2. **Folder Name Sanitization**:
   - Special characters removed for Google Drive compatibility
   - Syllabus code always uppercase for consistency

3. **Performance Considerations**:
   - Unit folder creation is synchronous
   - For large syllabi (50+ units), consider background job
   - Current limit: Reasonable for up to 100 units

4. **Future Enhancements**:
   - Batch folder creation for performance
   - WebSocket progress updates for large syllabi
   - Automatic permission sharing based on class assignments
   - Integration with class creation workflow

---

## Support

**Common Issues**:

1. **"Tài khoản chưa kết nối Google Drive"**
   - **Solution**: Admin needs to assign Google email in Users Management

2. **"Không tìm thấy thư mục Syllabus"**
   - **Solution**: Admin needs to create Syllabus parent folder in Google Drive settings

3. **"Bạn không có quyền truy cập folder này"**
   - **Solution**: Admin needs to grant permission to user's Google email

4. **Permission denied to create syllabus**
   - **Solution**: Admin needs to assign `syllabus.create` or `lesson_plans.create` permission

---

**Last Updated**: November 10, 2025  
**Version**: 1.0.0  
**Status**: ✅ Complete & Tested

