<template>
  <div class="space-y-6">
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ t('google_drive.class_history_folder') }}</h3>
      <p class="text-sm text-gray-600 mb-4">
        {{ t('google_drive.class_history_description') }}
      </p>

      <div v-if="loading" class="flex items-center justify-center py-8">
        <div class="animate-spin h-8 w-8 border-4 border-blue-600 border-t-transparent rounded-full"></div>
      </div>

      <div v-else class="space-y-4">
        <!-- Folder Status -->
        <div v-if="folderStatus" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
          <div class="flex items-center space-x-3">
            <svg v-if="folderStatus.exists" class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <svg v-else class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
            <div>
              <p class="text-sm font-medium text-gray-900">
                {{ folderStatus.exists ? t('google_drive.folder_exists') : t('google_drive.folder_not_exists') }}
              </p>
              <p v-if="folderStatus.exists && folderStatus.folder_name" class="text-xs text-gray-500">
                {{ folderStatus.folder_name }}
              </p>
            </div>
          </div>

          <button
            v-if="!folderStatus.exists"
            @click="createClassHistoryFolder"
            :disabled="creating"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition flex items-center space-x-2"
          >
            <svg v-if="creating" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>{{ creating ? t('google_drive.creating') : t('google_drive.create_folder') }}</span>
          </button>

          <span v-else class="px-4 py-2 bg-green-100 text-green-700 rounded-lg text-sm font-medium">
            {{ t('google_drive.folder_ready') }}
          </span>
        </div>

        <!-- Info Box -->
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-blue-700">
                {{ t('google_drive.class_history_info') }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import axios from 'axios';
import Swal from 'sweetalert2';

const { t } = useI18n();
const loading = ref(false);
const creating = ref(false);
const folderStatus = ref(null);

const checkFolderStatus = async () => {
  loading.value = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const response = await axios.get('/api/google-drive/check-class-history-folder', {
      params: { branch_id: branchId }
    });
    
    folderStatus.value = response.data.data;
  } catch (error) {
    console.error('Check folder status error:', error);
    
    // Handle specific error codes
    if (error.response?.data?.error_code === 'NO_ROOT_PERMISSION') {
      folderStatus.value = {
        exists: false,
        can_create: false,
        error: error.response.data.message
      };
    } else {
      folderStatus.value = { exists: false, can_create: true };
    }
  } finally {
    loading.value = false;
  }
};

const createClassHistoryFolder = async () => {
  creating.value = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const response = await axios.post('/api/google-drive/create-class-history-folder', {
      branch_id: branchId
    });

    if (response.data.success) {
      await Swal.fire({
        icon: 'success',
        title: t('common.success'),
        text: response.data.message,
        confirmButtonText: t('common.ok'),
        timer: 2000
      });

      // Refresh folder status
      await checkFolderStatus();
    }
  } catch (error) {
    console.error('Create folder error:', error);
    
    const errorCode = error.response?.data?.error_code;
    
    if (errorCode === 'NO_ROOT_PERMISSION') {
      await Swal.fire({
        icon: 'error',
        title: t('common.error'),
        html: `
          <div class="text-left">
            <p class="mb-3">${error.response.data.message}</p>
            <div class="bg-red-50 border-l-4 border-red-400 p-3 mt-2">
              <p class="text-sm text-red-800">
                <strong>💡 ${t('google_drive.how_to_fix')}:</strong><br>
                ${t('google_drive.contact_admin_for_root_permission')}
              </p>
            </div>
          </div>
        `,
        confirmButtonText: t('common.ok'),
        width: '600px'
      });
    } else if (errorCode === 'FOLDER_EXISTS') {
      await Swal.fire({
        icon: 'info',
        title: t('google_drive.folder_already_exists'),
        text: error.response.data.message,
        confirmButtonText: t('common.ok')
      });
      
      // Refresh to show the existing folder
      await checkFolderStatus();
    } else {
      await Swal.fire({
        icon: 'error',
        title: t('common.error'),
        text: error.response?.data?.message || t('common.error_occurred'),
        confirmButtonText: t('common.ok')
      });
    }
  } finally {
    creating.value = false;
  }
};

onMounted(() => {
  checkFolderStatus();
});
</script>

