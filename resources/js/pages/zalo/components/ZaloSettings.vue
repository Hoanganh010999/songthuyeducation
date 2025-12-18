<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">{{ t('zalo.settings') }}</h1>
      <p class="mt-1 text-sm text-gray-600">{{ t('zalo.settings_subtitle') }}</p>
    </div>

    <!-- Service Connection -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ t('zalo.service_connection') }}</h2>
      
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('zalo.service_url') }}
          </label>
          <input
            v-model="settings.serviceUrl"
            type="text"
            readonly
            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('zalo.api_key') }}
          </label>
          <div class="flex gap-2">
            <input
              :value="showApiKey ? settings.apiKey : '••••••••••••••••'"
              type="text"
              readonly
              class="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50"
            />
            <button
              @click="showApiKey = !showApiKey"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              <svg v-if="!showApiKey" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
              </svg>
            </button>
          </div>
        </div>

        <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
          <p class="text-sm text-blue-800">
            {{ t('zalo.account_management_note') || 'To add or manage Zalo accounts and check connection status, please go to the Accounts tab.' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Telegram Notification Settings -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h2 class="text-lg font-semibold text-gray-900">{{ t('zalo.telegram_notifications') || 'Telegram Notifications' }}</h2>
          <p class="text-sm text-gray-600">{{ t('zalo.telegram_notifications_desc') || 'Receive instant alerts when Zalo session disconnects' }}</p>
        </div>
        <span v-if="telegramSettings.enabled" class="px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">
          {{ t('common.enabled') || 'Enabled' }}
        </span>
        <span v-else class="px-3 py-1 bg-gray-100 text-gray-600 text-sm font-medium rounded-full">
          {{ t('common.disabled') || 'Disabled' }}
        </span>
      </div>

      <div class="space-y-4">
        <!-- Account Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('zalo.select_account') || 'Zalo Account' }}
          </label>
          <select
            v-model="selectedAccountId"
            @change="loadTelegramSettings"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">{{ t('zalo.select_account_placeholder') || '-- Select account --' }}</option>
            <option v-for="account in accounts" :key="account.id" :value="account.id">
              {{ account.name }} (ID: {{ account.id }})
            </option>
          </select>
        </div>

        <div v-if="selectedAccountId">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('zalo.telegram_bot_token') || 'Bot Token' }}
            </label>
            <div class="flex gap-2">
              <input
                v-model="telegramSettings.botToken"
                :type="showBotToken ? 'text' : 'password'"
                placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz"
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              />
              <button
                @click="showBotToken = !showBotToken"
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                <svg v-if="!showBotToken" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              </button>
            </div>
            <p class="mt-1 text-xs text-gray-500">{{ t('zalo.telegram_bot_token_help') || 'Get from @BotFather on Telegram' }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('zalo.telegram_chat_id') || 'Chat ID' }}
            </label>
            <input
              v-model="telegramSettings.chatId"
              type="text"
              placeholder="123456789"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
            <p class="mt-1 text-xs text-gray-500">{{ t('zalo.telegram_chat_id_help') || 'Get from @userinfobot on Telegram' }}</p>
          </div>

          <div class="flex gap-3 pt-2">
            <button
              @click="saveTelegramSettings"
              :disabled="savingTelegram"
              class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              {{ savingTelegram ? t('common.saving') : t('common.save') }}
            </button>
            <button
              @click="testTelegramNotification"
              :disabled="testingTelegram || !telegramSettings.botToken || !telegramSettings.chatId"
              class="px-4 py-2 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 disabled:opacity-50"
            >
              <span v-if="testingTelegram" class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ t('common.testing') || 'Testing...' }}
              </span>
              <span v-else>{{ t('zalo.test_notification') || 'Test Notification' }}</span>
            </button>
          </div>

          <div class="mt-4 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
            <div class="flex gap-2">
              <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              <div>
                <p class="text-sm font-medium text-yellow-800">{{ t('zalo.telegram_setup_note_title') || 'Important' }}</p>
                <p class="text-sm text-yellow-700">{{ t('zalo.telegram_setup_note') || 'You must start a conversation with your bot first. Search for your bot on Telegram and click "Start".' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Auto Notifications -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ t('zalo.auto_notifications') }}</h2>
      
      <div class="space-y-4">
        <label class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
          <div>
            <p class="font-medium text-gray-900">{{ t('zalo.notify_new_homework') }}</p>
            <p class="text-sm text-gray-600">{{ t('zalo.notify_new_homework_desc') }}</p>
          </div>
          <input
            v-model="settings.notifyNewHomework"
            type="checkbox"
            class="w-5 h-5 text-blue-600 rounded"
          />
        </label>

        <label class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
          <div>
            <p class="font-medium text-gray-900">{{ t('zalo.notify_homework_reminder') }}</p>
            <p class="text-sm text-gray-600">{{ t('zalo.notify_homework_reminder_desc') }}</p>
          </div>
          <input
            v-model="settings.notifyHomeworkReminder"
            type="checkbox"
            class="w-5 h-5 text-blue-600 rounded"
          />
        </label>

        <label class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
          <div>
            <p class="font-medium text-gray-900">{{ t('zalo.notify_score') }}</p>
            <p class="text-sm text-gray-600">{{ t('zalo.notify_score_desc') }}</p>
          </div>
          <input
            v-model="settings.notifyScore"
            type="checkbox"
            class="w-5 h-5 text-blue-600 rounded"
          />
        </label>

        <button
          @click="saveSettings"
          :disabled="saving"
          class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
        >
          {{ saving ? t('common.saving') : t('common.save') }}
        </button>
      </div>
    </div>

    <!-- Documentation -->
    <div class="bg-blue-50 rounded-lg border border-blue-200 p-6">
      <div class="flex items-start gap-3">
        <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
          <h3 class="font-semibold text-blue-900 mb-2">{{ t('zalo.setup_guide') }}</h3>
          <p class="text-sm text-blue-800 mb-3">
            {{ t('zalo.setup_guide_desc') }}
          </p>
          <a
            href="/ZALO_SETUP_GUIDE.md"
            target="_blank"
            class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700"
          >
            {{ t('zalo.view_documentation') }}
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
          </a>
        </div>
      </div>
    </div>

    <!-- QR Code Login Modal -->
    <div v-if="showLoginModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-xl font-semibold text-gray-900">{{ t('zalo.login_with_qr') }}</h3>
          <button
            @click="closeLoginModal"
            class="text-gray-400 hover:text-gray-600"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- QR Code Display -->
        <div v-if="loginLoading" class="text-center py-12">
          <svg class="animate-spin h-12 w-12 mx-auto text-blue-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="mt-4 text-gray-600">{{ t('zalo.generating_qr') }}</p>
        </div>

        <div v-else-if="qrCodeData" class="text-center">
          <div class="bg-white p-4 rounded-lg border-2 border-gray-200 inline-block">
            <img :src="qrCodeData" alt="QR Code" class="w-64 h-64" />
          </div>
          
          <div class="mt-4 space-y-2">
            <p class="text-sm font-medium text-gray-900">{{ t('zalo.scan_with_app') }}</p>
            <ol class="text-sm text-gray-600 text-left space-y-1">
              <li>1. {{ t('zalo.open_zalo_app') }}</li>
              <li>2. {{ t('zalo.tap_settings') }}</li>
              <li>3. {{ t('zalo.select_zalo_web') }}</li>
              <li>4. {{ t('zalo.scan_qr') }}</li>
            </ol>
          </div>

          <!-- Auto-checking status -->
          <div class="mt-4 flex items-center justify-center gap-2 text-sm text-gray-600">
            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ t('zalo.waiting_for_scan') }}</span>
          </div>

          <p class="mt-2 text-xs text-gray-500">{{ t('zalo.qr_expires_60s') }}</p>
        </div>

        <div v-else class="text-center py-8 text-red-600">
          <p>{{ t('zalo.failed_to_generate_qr') }}</p>
          <button
            @click="initializeLogin"
            class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            {{ t('common.retry') }}
          </button>
        </div>

        <button
          @click="closeLoginModal"
          class="mt-6 w-full px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
        >
          {{ t('common.close') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import { useSwal } from '../../../composables/useSwal';
import axios from 'axios';

const { t } = useI18n();
const { Swal } = useSwal();

const showApiKey = ref(false);
const saving = ref(false);

// Telegram settings
const accounts = ref([]);
const selectedAccountId = ref('');
const showBotToken = ref(false);
const savingTelegram = ref(false);
const testingTelegram = ref(false);
const telegramSettings = ref({
  botToken: '',
  chatId: '',
  enabled: false,
});

// QR Login
const showLoginModal = ref(false);
const loginLoading = ref(false);
const qrCodeData = ref(null);
let statusCheckInterval = null;

const settings = ref({
  serviceUrl: import.meta.env.VITE_WS_URL || import.meta.env.VITE_ZALO_SERVICE_URL || window.location.origin,
  apiKey: '••••••••••',
  notifyNewHomework: true,
  notifyHomeworkReminder: true,
  notifyScore: true,
});


const saveSettings = async () => {
  saving.value = true;
  try {
    await axios.post('/api/zalo/settings', settings.value);
    Swal.fire({
      icon: 'success',
      title: t('common.success'),
      text: t('zalo.settings_saved'),
      timer: 2000,
    });
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.message,
    });
  } finally {
    saving.value = false;
  }
};

const loadSettings = async () => {
  try {
    const response = await axios.get('/api/zalo/settings');
    settings.value = response.data;
  } catch (error) {
    console.error('Failed to load settings:', error);
  }
};

// Load Zalo accounts for Telegram settings
const loadAccounts = async () => {
  try {
    const response = await axios.get('/api/zalo/accounts');
    accounts.value = response.data.data || response.data || [];

    // Auto-select first account if only one
    if (accounts.value.length === 1) {
      selectedAccountId.value = accounts.value[0].id;
      await loadTelegramSettings();
    }
  } catch (error) {
    console.error('Failed to load accounts:', error);
  }
};

// Load Telegram settings for selected account
const loadTelegramSettings = async () => {
  if (!selectedAccountId.value) {
    telegramSettings.value = { botToken: '', chatId: '', enabled: false };
    return;
  }

  try {
    const response = await axios.get(`/api/zalo/accounts/${selectedAccountId.value}/telegram`);
    if (response.data.success) {
      telegramSettings.value = {
        botToken: response.data.data.telegram_bot_token || '',
        chatId: response.data.data.telegram_chat_id || '',
        enabled: response.data.data.telegram_enabled || false,
      };
    }
  } catch (error) {
    console.error('Failed to load Telegram settings:', error);
    telegramSettings.value = { botToken: '', chatId: '', enabled: false };
  }
};

// Save Telegram settings
const saveTelegramSettings = async () => {
  if (!selectedAccountId.value) return;

  savingTelegram.value = true;
  try {
    const response = await axios.post(`/api/zalo/accounts/${selectedAccountId.value}/telegram`, {
      telegram_bot_token: telegramSettings.value.botToken,
      telegram_chat_id: telegramSettings.value.chatId,
    });

    if (response.data.success) {
      telegramSettings.value.enabled = !!(telegramSettings.value.botToken && telegramSettings.value.chatId);
      Swal.fire({
        icon: 'success',
        title: t('common.success'),
        text: t('zalo.telegram_settings_saved') || 'Telegram settings saved successfully',
        timer: 2000,
      });
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || error.message,
    });
  } finally {
    savingTelegram.value = false;
  }
};

// Test Telegram notification
const testTelegramNotification = async () => {
  if (!selectedAccountId.value) return;

  testingTelegram.value = true;
  try {
    // First save settings if changed
    await axios.post(`/api/zalo/accounts/${selectedAccountId.value}/telegram`, {
      telegram_bot_token: telegramSettings.value.botToken,
      telegram_chat_id: telegramSettings.value.chatId,
    });

    // Then test
    const response = await axios.post(`/api/zalo/accounts/${selectedAccountId.value}/telegram/test`);

    if (response.data.success) {
      Swal.fire({
        icon: 'success',
        title: t('common.success'),
        text: t('zalo.telegram_test_sent') || 'Test notification sent! Check your Telegram.',
        timer: 3000,
      });
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || error.response?.data?.error || error.message,
    });
  } finally {
    testingTelegram.value = false;
  }
};

const initializeLogin = async () => {
  loginLoading.value = true;
  qrCodeData.value = null;

  try {
    const response = await axios.post('/api/zalo/initialize');
    
    if (response.data.success && response.data.qrCode) {
      qrCodeData.value = response.data.qrCode;
      
      // Start checking status every 3 seconds
      startStatusCheck();
    } else {
      Swal.fire({
        icon: 'error',
        title: t('common.error'),
        text: response.data.message || t('zalo.failed_to_generate_qr'),
      });
    }
  } catch (error) {
    console.error('Initialize login error:', error);
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || error.message,
    });
  } finally {
    loginLoading.value = false;
  }
};

const startStatusCheck = () => {
  // Clear any existing interval
  if (statusCheckInterval) {
    clearInterval(statusCheckInterval);
  }

  // Check every 3 seconds
  statusCheckInterval = setInterval(async () => {
    try {
      const response = await axios.get('/api/zalo/status');
      
      if (response.data.isReady) {
        // Login successful!
        clearInterval(statusCheckInterval);
        
        Swal.fire({
          icon: 'success',
          title: t('common.success'),
          text: t('zalo.login_successful'),
          timer: 2000,
        });

        closeLoginModal();
        connectionStatus.value = 'connected';
      }
    } catch (error) {
      console.error('Status check error:', error);
    }
  }, 3000);

  // Stop after 60 seconds (QR expires)
  setTimeout(() => {
    if (statusCheckInterval) {
      clearInterval(statusCheckInterval);
      if (showLoginModal.value && qrCodeData.value) {
        Swal.fire({
          icon: 'warning',
          title: t('zalo.qr_expired'),
          text: t('zalo.please_try_again'),
        });
      }
    }
  }, 60000);
};

const closeLoginModal = () => {
  showLoginModal.value = false;
  qrCodeData.value = null;
  loginLoading.value = false;
  
  if (statusCheckInterval) {
    clearInterval(statusCheckInterval);
    statusCheckInterval = null;
  }
};

// Watch for modal open
const openLoginModal = () => {
  showLoginModal.value = true;
  initializeLogin();
};

// Update the button click handler
const handleLoginClick = () => {
  openLoginModal();
};

onMounted(() => {
  loadSettings();
  loadAccounts();
});
</script>

