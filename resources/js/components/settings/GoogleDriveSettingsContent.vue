<template>
  <div class="p-6">
    <!-- Header -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900">{{ t('google_drive.settings_title') }}</h2>
      <p class="text-gray-600 mt-1">{{ t('google_drive.settings_description') }}</p>
    </div>

    <!-- Google Drive API Settings Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">{{ t('google_drive.api_settings') }}</h3>
          <p class="text-sm text-gray-600 mt-1">{{ t('google_drive.configure_first') }}</p>
        </div>
        <button
          @click="testGoogleDriveConnection"
          :disabled="testing || !isGoogleDriveConfigured"
          class="px-4 py-2 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center space-x-2"
        >
          <svg class="w-5 h-5" :class="{ 'animate-spin': testing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>{{ testing ? t('common.loading') : t('google_drive.test_connection') }}</span>
        </button>
      </div>
      
      <div class="grid grid-cols-1 gap-6">
        <!-- Client ID -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('google_drive.client_id') }}
            <span class="text-red-500">*</span>
          </label>
          <input
            v-model="settings.google_drive_client_id"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
            :placeholder="t('google_drive.client_id_placeholder')"
          />
          <p class="mt-1 text-xs text-gray-500">{{ t('google_drive.get_from_console') }}</p>
        </div>

        <!-- Client Secret -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('google_drive.client_secret') }}
            <span class="text-red-500">*</span>
          </label>
          <input
            v-model="settings.google_drive_client_secret"
            type="password"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
            :placeholder="t('google_drive.client_secret_placeholder')"
          />
        </div>

        <!-- OAuth Authorization -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('google_drive.authorization') }}
            <span class="text-red-500">*</span>
          </label>

          <!-- Authorization button -->
          <button
            @click="authorizeGoogleDrive"
            :disabled="authorizing || !settings.google_drive_client_id || !settings.google_drive_client_secret"
            class="w-full px-4 py-3 bg-white border-2 border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center space-x-3"
          >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
              <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
              <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
              <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            <span v-if="googleDriveStatus?.is_connected" class="font-semibold">
              ✓ {{ t('google_drive.reauthorize') }}
            </span>
            <span v-else class="font-semibold">
              {{ authorizing ? t('common.loading') : t('google_drive.authorize_with_google') }}
            </span>
          </button>

          <p class="mt-2 text-xs text-gray-500">
            {{ t('google_drive.oauth_description') }}
          </p>

          <!-- Show authorization status -->
          <div v-if="googleDriveStatus?.is_connected" class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg flex items-center space-x-2">
            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span class="text-sm text-green-800 font-medium">{{ t('google_drive.authorized') }}</span>
          </div>
        </div>

        <!-- Folder Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('google_drive.folder_name') }}
          </label>
          <input
            v-model="settings.google_drive_folder_name"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
            placeholder="School Drive"
          />
          <p class="mt-1 text-xs text-gray-500">{{ t('google_drive.folder_auto_create') }}</p>
        </div>

        <!-- Status Display -->
        <div v-if="googleDriveStatus" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">{{ t('google_drive.connection_status') }}:</span>
            <span 
              :class="googleDriveStatus.is_connected ? 'text-green-600' : 'text-red-600'" 
              class="text-sm font-semibold flex items-center space-x-1"
            >
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path v-if="googleDriveStatus.is_connected" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                <path v-else fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
              <span>{{ googleDriveStatus.is_connected ? t('google_drive.connected') : t('google_drive.disconnected') }}</span>
            </span>
          </div>
          <div v-if="googleDriveStatus.folder_id" class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-700">{{ t('google_drive.folder_id') }}:</span>
            <span class="text-sm text-gray-600 font-mono">{{ googleDriveStatus.folder_id }}</span>
          </div>
          <div v-if="googleDriveStatus.last_synced_at" class="flex items-center justify-between mt-2">
            <span class="text-sm font-medium text-gray-700">{{ t('google_drive.last_synced') }}:</span>
            <span class="text-sm text-gray-600">{{ formatDate(googleDriveStatus.last_synced_at) }}</span>
          </div>
        </div>

        <!-- Help Text -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex">
            <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <div class="text-sm text-blue-800">
              <p class="font-semibold mb-1">{{ t('google_drive.setup_guide') }}:</p>
              <ol class="list-decimal ml-4 space-y-1">
                <li>{{ t('google_drive.oauth_step_1') }}</li>
                <li>{{ t('google_drive.oauth_step_2') }}</li>
                <li>{{ t('google_drive.oauth_step_3') }}</li>
                <li>{{ t('google_drive.oauth_step_4') }}</li>
              </ol>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Save Button -->
    <div class="mt-6 flex justify-end">
      <button
        @click="saveSettings"
        :disabled="saving"
        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 flex items-center space-x-2"
      >
        <svg v-if="saving" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>{{ saving ? t('common.saving') : t('common.save') }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';

const { t } = useI18n();
const { showSuccess, showError } = useSwal();

const settings = ref({
  google_drive_client_id: '',
  google_drive_client_secret: '',
  google_drive_folder_name: 'School Drive'
});

const saving = ref(false);
const testing = ref(false);
const authorizing = ref(false);
const googleDriveStatus = ref(null);

const isGoogleDriveConfigured = computed(() => {
  return settings.value.google_drive_client_id &&
         settings.value.google_drive_client_secret;
});

const loadGoogleDriveStatus = async () => {
  try {
    const response = await axios.get('/api/google-drive/settings');
    if (response.data.success && response.data.data) {
      const gdData = response.data.data;
      
      if (gdData.client_id) {
        settings.value.google_drive_client_id = gdData.client_id;
      }
      if (gdData.client_secret) {
        settings.value.google_drive_client_secret = gdData.client_secret;
      }
      if (gdData.school_drive_folder_name) {
        settings.value.google_drive_folder_name = gdData.school_drive_folder_name;
      }
      
      googleDriveStatus.value = {
        is_connected: gdData.is_active || false,
        folder_id: gdData.school_drive_folder_id,
        last_synced_at: gdData.last_synced_at
      };
    }
  } catch (error) {
    console.error('Error loading Google Drive status:', error);
  }
};

const saveSettings = async () => {
  try {
    saving.value = true;
    
    if (!isGoogleDriveConfigured.value) {
      showError(t('google_drive.enter_credentials_first'));
      return;
    }

    const gdUpdatePayload = {
      client_id: settings.value.google_drive_client_id,
      client_secret: settings.value.google_drive_client_secret,
      school_drive_folder_name: settings.value.google_drive_folder_name
    };

    await axios.post('/api/google-drive/settings', gdUpdatePayload);
    await loadGoogleDriveStatus();
    
    showSuccess(t('common.saved_successfully'));
  } catch (error) {
    console.error('Error saving settings:', error);
    showError(error.response?.data?.message || t('common.error_occurred'));
  } finally {
    saving.value = false;
  }
};

const testGoogleDriveConnection = async () => {
  testing.value = true;
  try {
    const response = await axios.post('/api/google-drive/test-connection');
    if (response.data.success) {
      const data = response.data.data;
      
      // Show detailed success message
      let message = response.data.message;
      if (data?.folder_name) {
        message += `\n\nFolder: ${data.folder_name}`;
        if (data.folder_existed) {
          message += ' ✓ (Đã tồn tại và đã được xác minh)';
        } else {
          message += ' ✓ (Vừa được tạo mới)';
        }
      }
      
      showSuccess(message);
      await loadGoogleDriveStatus();
    }
  } catch (error) {
    console.error('Error testing connection:', error);
    showError(error.response?.data?.message || t('google_drive.connection_failed'));
  } finally {
    testing.value = false;
  }
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleString('vi-VN');
};

const authorizeGoogleDrive = async () => {
  if (!settings.value.google_drive_client_id || !settings.value.google_drive_client_secret) {
    showError(t('google_drive.enter_credentials_first'));
    return;
  }

  authorizing.value = true;
  try {
    const response = await axios.post('/api/google-drive/auth-url', {
      client_id: settings.value.google_drive_client_id,
      client_secret: settings.value.google_drive_client_secret
    });

    if (response.data.success) {
      const authUrl = response.data.auth_url;

      const width = 600;
      const height = 700;
      const left = window.screen.width / 2 - width / 2;
      const top = window.screen.height / 2 - height / 2;

      const popup = window.open(
        authUrl,
        'google_oauth',
        `width=${width},height=${height},left=${left},top=${top},toolbar=no,menubar=no,scrollbars=yes,resizable=yes`
      );

      const pollTimer = setInterval(() => {
        try {
          if (!popup || popup.closed) {
            clearInterval(pollTimer);
            authorizing.value = false;
            loadGoogleDriveStatus();
          }

          try {
            const popupUrl = popup.location.href;
            if (popupUrl.includes('success=true')) {
              clearInterval(pollTimer);
              popup.close();
              authorizing.value = false;
              showSuccess(t('google_drive.authorization_success'));
              loadGoogleDriveStatus();
            } else if (popupUrl.includes('error=')) {
              clearInterval(pollTimer);
              popup.close();
              authorizing.value = false;
              const urlParams = new URLSearchParams(popup.location.search);
              const error = urlParams.get('error');
              showError(t('google_drive.authorization_failed') + ': ' + decodeURIComponent(error));
            }
          } catch (e) {
            // Expected error when on different domain
          }
        } catch (e) {
          clearInterval(pollTimer);
          authorizing.value = false;
        }
      }, 500);
    }
  } catch (error) {
    console.error('Error getting auth URL:', error);
    showError(error.response?.data?.message || t('google_drive.authorization_failed'));
    authorizing.value = false;
  }
};

onMounted(() => {
  loadGoogleDriveStatus();

  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has('success') && urlParams.get('success') === 'true') {
    showSuccess(t('google_drive.authorization_success'));
    window.history.replaceState({}, document.title, window.location.pathname);
  } else if (urlParams.has('error')) {
    const error = urlParams.get('error');
    showError(t('google_drive.authorization_failed') + ': ' + decodeURIComponent(error));
    window.history.replaceState({}, document.title, window.location.pathname);
  }
});
</script>

