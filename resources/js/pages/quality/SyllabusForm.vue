<template>
  <form @submit.prevent="save" class="space-y-6">
    <!-- Basic Info -->
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('syllabus.name') }} *</label>
        <input v-model="form.name" type="text" required class="w-full px-3 py-2 border rounded-lg" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('syllabus.code') }} *</label>
        <input v-model="form.code" type="text" required class="w-full px-3 py-2 border rounded-lg" />
      </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('syllabus.subject') }} *</label>
        <select v-model="form.subject_id" required class="w-full px-3 py-2 border rounded-lg">
          <option value="">-- {{ t('common.select') }} --</option>
          <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('syllabus.total_units') }} *</label>
        <input v-model.number="form.total_sessions" type="number" min="1" required class="w-full px-3 py-2 border rounded-lg" />
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('syllabus.description') }}</label>
      <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 border rounded-lg"></textarea>
    </div>

    <!-- Status -->
    <div v-if="props.syllabus">
      <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('syllabus.status') }} *</label>
      <select v-model="form.status" required class="w-full px-3 py-2 border rounded-lg">
        <option value="draft">{{ t('syllabus.status_draft') }}</option>
        <option value="approved">{{ t('syllabus.status_approved') }}</option>
        <option value="in_use">{{ t('syllabus.status_in_use') }}</option>
        <option value="archived">{{ t('syllabus.status_archived') }}</option>
      </select>
      <p class="text-sm text-gray-500 mt-1">{{ statusDescription(form.status) }}</p>
    </div>

    <!-- Actions -->
    <div class="flex justify-end space-x-3 pt-4 border-t">
      <button type="button" @click="$emit('cancel')" class="px-4 py-2 border rounded-lg hover:bg-gray-50">{{ t('common.cancel') }}</button>
      <button type="submit" :disabled="saving" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
        {{ saving ? t('common.saving') : t('common.save') }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useI18n } from '../../composables/useI18n';

const { t } = useI18n();
const props = defineProps({
  syllabus: { type: Object, default: null }
});
const emit = defineEmits(['saved', 'cancel']);

const saving = ref(false);
const subjects = ref([]);
const form = ref({
  name: '',
  code: '',
  subject_id: '',
  total_sessions: 30,
  description: '',
  status: 'draft',
  branch_id: localStorage.getItem('current_branch_id')
});

const statusDescription = (status) => {
  const descriptions = {
    draft: t('syllabus.status_draft_desc'),
    approved: t('syllabus.status_approved_desc'),
    in_use: t('syllabus.status_in_use_desc'),
    archived: t('syllabus.status_archived_desc')
  };
  return descriptions[status] || '';
};

const loadSubjects = async () => {
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const response = await axios.get('/api/lesson-plans/subjects-list', { params: { branch_id: branchId } });
    subjects.value = response.data.data || response.data;
  } catch (error) {
    console.error('Load subjects error:', error);
  }
};

const ensureSyllabusFolder = async () => {
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const response = await axios.post('/api/google-drive/ensure-syllabus-folder', {
      branch_id: branchId
    });
    
    if (response.data.success) {
      console.log('[Syllabus] Syllabus folder ready:', response.data.data);
      return response.data.data;
    }
    
    return null;
  } catch (error) {
    // If Google Drive is not configured, just log and continue
    if (error.response?.status === 400) {
      console.log('[Syllabus] Google Drive not configured, skipping folder creation');
    } else {
      console.error('[Syllabus] Error ensuring Syllabus folder:', error);
    }
    return null;
  }
};

const createFolderForSyllabus = async (syllabusId, syllabusName, syllabusCode, totalUnits) => {
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const response = await axios.post('/api/google-drive/create-syllabus-folder', {
      branch_id: branchId,
      syllabus_id: syllabusId,
      syllabus_name: syllabusName,
      syllabus_code: syllabusCode,
      total_units: totalUnits
    });
    
    if (response.data.success) {
      console.log('[Syllabus] Folder created for syllabus:', response.data.data);
      return response.data.data;
    }
    
    return null;
  } catch (error) {
    // Handle specific error codes
    if (error.response?.status === 400) {
      const errorCode = error.response.data.error_code;
      
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
          confirmButtonText: t('common.ok'),
        });
        throw new Error('NO_GOOGLE_EMAIL');
      } else if (errorCode === 'NO_SYLLABUS_FOLDER') {
        await Swal.fire({
          icon: 'error',
          title: t('common.error'),
          html: `
            <div class="text-left">
              <p class="mb-2">${error.response.data.message}</p>
              <p class="text-sm text-gray-600">${t('syllabus.contact_admin_for_syllabus_folder')}</p>
            </div>
          `,
          confirmButtonText: t('common.ok'),
        });
        throw new Error('NO_SYLLABUS_FOLDER');
      } else {
        console.log('[Syllabus] Google Drive not configured, skipping folder creation');
        return null;
      }
    } else if (error.response?.status === 409) {
      // Handle folder conflict
      const result = await Swal.fire({
        icon: 'question',
        title: error.response.data.message,
        html: `
          <div class="text-left space-y-3">
            <p>${error.response.data.question}</p>
            <p class="text-sm text-gray-600"><strong>${t('common.folder')}:</strong> ${error.response.data.folder_name}</p>
            ${!error.response.data.has_permission ? `
              <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mt-2">
                <p class="text-sm text-yellow-800">${t('syllabus.no_permission_warning')}</p>
              </div>
            ` : ''}
          </div>
        `,
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: t('common.use_existing'),
        denyButtonText: t('common.create_new'),
        cancelButtonText: t('common.cancel'),
        confirmButtonColor: '#10b981',
        denyButtonColor: '#3b82f6',
      });

      if (result.isConfirmed) {
        // Use existing folder
        if (!error.response.data.has_permission) {
          await Swal.fire({
            icon: 'error',
            title: t('common.error'),
            text: t('syllabus.contact_admin_for_permission'),
            confirmButtonText: t('common.ok'),
          });
          throw new Error('NO_PERMISSION');
        }

        const useResponse = await axios.post('/api/google-drive/create-syllabus-folder', {
          branch_id: branchId,
          syllabus_id: syllabusId,
          syllabus_name: syllabusName,
          syllabus_code: syllabusCode,
          total_units: totalUnits,
          use_existing: true,
          existing_folder_id: error.response.data.existing_folder_id,
        });

        return useResponse.data.data;
      } else if (result.isDenied) {
        // Create new folder (rename old one)
        const createResponse = await axios.post('/api/google-drive/create-syllabus-folder', {
          branch_id: branchId,
          syllabus_id: syllabusId,
          syllabus_name: syllabusName,
          syllabus_code: syllabusCode,
          total_units: totalUnits,
          use_existing: false,
          existing_folder_id: error.response.data.existing_folder_id,
        });

        return createResponse.data.data;
      } else {
        throw new Error('CANCELLED');
      }
    } else {
      console.error('[Syllabus] Error creating folder for syllabus:', error);
      throw error;
    }
  }
};

const save = async (confirmDeleteSessions = false) => {
  saving.value = true;

  try {
    if (props.syllabus) {
      // Edit mode - check if reducing sessions
      const payload = { ...form.value };
      if (confirmDeleteSessions) {
        payload.confirm_delete_sessions = true;
      }

      try {
        const response = await axios.put(`/api/lesson-plans/${props.syllabus.id}`, payload);
        await Swal.fire(t('common.success'), t('syllabus.updated'), 'success');
        emit('saved');
      } catch (error) {
        // Check if requires confirmation for deleting sessions
        if (error.response?.data?.requires_confirmation) {
          const sessionsToDelete = error.response.data.sessions_to_delete || [];

          // Build HTML for sessions list
          let sessionsHtml = '<div class="text-left max-h-60 overflow-y-auto">';
          sessionsHtml += '<p class="text-red-600 font-medium mb-3">' + error.response.data.message + '</p>';
          sessionsHtml += '<table class="w-full text-sm">';
          sessionsHtml += '<thead><tr class="border-b"><th class="text-left py-1">Unit</th><th class="text-left py-1">Tiêu đề</th><th class="text-left py-1">Trạng thái</th></tr></thead>';
          sessionsHtml += '<tbody>';

          for (const session of sessionsToDelete) {
            const warnings = [];
            if (session.has_content) warnings.push('Có nội dung');
            if (session.has_files) warnings.push('Có file');
            if (session.has_google_drive) warnings.push('Có Google Drive');
            const warningText = warnings.length > 0
              ? '<span class="text-orange-600">' + warnings.join(', ') + '</span>'
              : '<span class="text-gray-400">Trống</span>';

            sessionsHtml += `<tr class="border-b">
              <td class="py-1">Unit ${session.session_number}</td>
              <td class="py-1">${session.lesson_title || '-'}</td>
              <td class="py-1">${warningText}</td>
            </tr>`;
          }

          sessionsHtml += '</tbody></table></div>';

          const result = await Swal.fire({
            icon: 'warning',
            title: 'Xác nhận xóa sessions',
            html: sessionsHtml,
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Xóa và cập nhật',
            cancelButtonText: 'Hủy',
            width: '500px'
          });

          if (result.isConfirmed) {
            // Retry with confirmation
            saving.value = true;
            await save(true);
          }
          return;
        }
        throw error;
      }
    } else {
      // Create mode - MUST create folder first
      console.log('[Syllabus] Creating new syllabus with Google Drive folder...');
      
      // Show loading for folder creation
      Swal.fire({
        title: t('syllabus.creating_folder'),
        html: t('syllabus.please_wait'),
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
      
      // Step 1: Ensure parent Syllabus folder exists
      await ensureSyllabusFolder();
      
      // Step 2: Create syllabus record in database
      const syllabusResponse = await axios.post('/api/lesson-plans', form.value);
      const syllabusId = syllabusResponse.data.data.id;
      
      console.log('[Syllabus] Syllabus record created, ID:', syllabusId);
      
      // Step 3: Create Google Drive folder (REQUIRED)
      try {
        await createFolderForSyllabus(
          syllabusId,
          form.value.name,
          form.value.code,
          form.value.total_sessions
        );
        
        // Success!
        Swal.close();
        await Swal.fire(t('common.success'), t('syllabus.created'), 'success');
        emit('saved');
        
      } catch (folderError) {
        // Folder creation failed - DELETE the syllabus record
        console.error('[Syllabus] Folder creation failed, rolling back syllabus:', folderError);
        
        try {
          await axios.delete(`/api/lesson-plans/${syllabusId}`);
        } catch (deleteError) {
          console.error('[Syllabus] Failed to delete syllabus:', deleteError);
        }
        
        // Show appropriate error message
        Swal.close();
        
        if (folderError.message === 'NO_GOOGLE_EMAIL') {
          await Swal.fire({
            icon: 'error',
            title: t('common.error'),
            html: `
              <div class="text-left">
                <p class="mb-2">${t('errors.teacher_no_google_email')}</p>
                <p class="text-sm text-gray-600">${t('syllabus.contact_admin_for_google_email')}</p>
              </div>
            `,
            confirmButtonText: t('common.ok'),
          });
        } else if (folderError.message === 'NO_SYLLABUS_FOLDER') {
          await Swal.fire({
            icon: 'error',
            title: t('common.error'),
            html: `
              <div class="text-left">
                <p class="mb-2">${t('errors.syllabus_folder_not_found')}</p>
                <p class="text-sm text-gray-600">${t('syllabus.contact_admin_for_syllabus_folder')}</p>
              </div>
            `,
            confirmButtonText: t('common.ok'),
          });
        } else if (folderError.message === 'CANCELLED') {
          await Swal.fire({
            icon: 'info',
            title: t('common.cancelled'),
            text: t('syllabus.creation_cancelled'),
            confirmButtonText: t('common.ok'),
          });
        } else {
          await Swal.fire({
            icon: 'error',
            title: t('common.error'),
            text: t('syllabus.folder_creation_failed') + ': ' + (folderError.message || 'Unknown error'),
            confirmButtonText: t('common.ok'),
          });
        }
        
        // Don't emit saved - creation failed
        return;
      }
    }
  } catch (error) {
    console.error('Save error:', error);
    Swal.close();
    await Swal.fire(
      t('common.error'), 
      error.response?.data?.message || t('syllabus.error_save'), 
      'error'
    );
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadSubjects();
  if (props.syllabus) {
    form.value = { ...props.syllabus };
  }
});
</script>

