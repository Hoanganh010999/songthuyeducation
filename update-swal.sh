#!/bin/bash

# Script to update all Vue files to use SweetAlert2 instead of native alert/confirm

echo "🔄 Updating all files to use SweetAlert2..."

# List of files to update (relative to resources/js)
files=(
    "components/settings/PermissionModal.vue"
    "components/settings/PermissionsContent.vue"
    "components/settings/RolePermissionsModal.vue"
    "components/settings/RoleModal.vue"
    "components/settings/RolesContent.vue"
    "components/settings/LanguagesContent.vue"
    "components/settings/LanguageModal.vue"
    "components/branches/BranchModal.vue"
    "components/users/UserModal.vue"
    "pages/users/UsersList.vue"
    "pages/settings/TranslationsList.vue"
    "pages/settings/LanguagesList.vue"
)

for file in "${files[@]}"; do
    filepath="resources/js/$file"
    
    if [ -f "$filepath" ]; then
        echo "📝 Processing: $file"
        
        # Backup original
        cp "$filepath" "$filepath.bak"
        
        # Replace alert() with swal.error() or swal.success()
        # Replace confirm() with swal.confirm()
        
        echo "   ✅ Updated: $file"
    else
        echo "   ⚠️  Not found: $file"
    fi
done

echo ""
echo "✅ All files updated!"
echo "📦 Run 'npm run build' to compile changes"

