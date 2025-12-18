<template>
  <div class="h-full flex flex-col overflow-hidden">
    <div class="p-6 border-b border-gray-200 flex-shrink-0">
      <h2 class="text-xl font-bold text-gray-900">{{ currentItem?.label }}</h2>
      <p v-if="currentItem?.description" class="mt-1 text-sm text-gray-600">{{ currentItem.description }}</p>
    </div>
    
    <div class="flex-1 overflow-y-auto px-6 py-4">
      <!-- Service Connection -->
      <div v-if="selectedKey === 'connection'" class="space-y-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
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
              <h3 class="text-lg font-semibold text-gray-900">{{ t('zalo.telegram_notifications') || 'Telegram Notifications' }}</h3>
              <p class="text-sm text-gray-600">{{ t('zalo.telegram_notifications_desc') || 'Receive instant alerts when Zalo session disconnects or login succeeds' }}</p>
            </div>
            <span v-if="telegramSettings.enabled" class="px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">{{ t('common.enabled') || 'Enabled' }}</span>
            <span v-else class="px-3 py-1 bg-gray-100 text-gray-600 text-sm font-medium rounded-full">{{ t('common.disabled') || 'Disabled' }}</span>
          </div>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('zalo.select_account') || 'Zalo Account' }}</label>
              <select v-model="selectedTelegramAccountId" @change="loadTelegramSettings" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">{{ t('zalo.select_account_placeholder') || '-- Select account --' }}</option>
                <option v-for="account in telegramAccounts" :key="account.id" :value="account.id">{{ account.name }} (ID: {{ account.id }})</option>
              </select>
            </div>
            <div v-if="selectedTelegramAccountId">
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('zalo.telegram_bot_token') || 'Bot Token' }}</label>
                <div class="flex gap-2">
                  <input v-model="telegramSettings.botToken" :type="showBotToken ? 'text' : 'password'" placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
                  <button @click="showBotToken = !showBotToken" type="button" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">{{ showBotToken ? 'Hide' : 'Show' }}</button>
                </div>
                <p class="mt-1 text-xs text-gray-500">{{ t('zalo.telegram_bot_token_help') || 'Get from @BotFather on Telegram' }}</p>
              </div>
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('zalo.telegram_chat_id') || 'Chat ID' }}</label>
                <input v-model="telegramSettings.chatId" type="text" placeholder="123456789" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
                <p class="mt-1 text-xs text-gray-500">{{ t('zalo.telegram_chat_id_help') || 'Get from @userinfobot on Telegram' }}</p>
              </div>
              <div class="flex gap-3">
                <button @click="saveTelegramSettings" :disabled="savingTelegram" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">{{ savingTelegram ? t('common.saving') : t('common.save') }}</button>
                <button @click="testTelegramNotification" :disabled="testingTelegram || !telegramSettings.botToken || !telegramSettings.chatId" class="px-4 py-2 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 disabled:opacity-50">{{ testingTelegram ? t('common.testing') || 'Testing...' : t('zalo.test_notification') || 'Test' }}</button>
              </div>
              <div class="mt-4 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                <p class="text-sm font-medium text-yellow-800">{{ t('zalo.telegram_setup_note_title') || 'Important' }}</p>
                <p class="text-sm text-yellow-700">{{ t('zalo.telegram_setup_note') || 'You must start a conversation with your bot first. Search for your bot on Telegram and click "Start".' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Auto Notifications -->
      <div v-else-if="selectedKey === 'notifications'" class="space-y-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
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
      </div>

      <!-- Branch Access Management -->
      <div v-else-if="selectedKey === 'branch_access'" class="space-y-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-lg font-semibold text-gray-900">{{ t('zalo.branch_access_management') }}</h3>
              <p class="mt-1 text-sm text-gray-600">{{ t('zalo.branch_access_desc') || 'Manage which branches can access each Zalo account.' }}</p>
            </div>
            <button
              @click="loadBranchAccess"
              :disabled="loadingBranchAccess"
              class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              <span v-if="loadingBranchAccess">Loading...</span>
              <span v-else>Refresh</span>
            </button>
          </div>

          <!-- Info box -->
          <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-start gap-3">
              <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <p class="text-sm text-blue-800">
                {{ t('zalo.auto_branch_detection_desc') || 'The system automatically detects when you login with the same Zalo account from different branches and links them together.' }}
              </p>
            </div>
          </div>

          <!-- Accounts list -->
          <div v-if="loadingBranchAccess" class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          </div>

          <div v-else-if="branchAccessAccounts.length === 0" class="text-center py-8 text-gray-500">
            {{ t('zalo.no_accounts') || 'No Zalo accounts found.' }}
          </div>

          <div v-else class="space-y-4">
            <!-- Account card -->
            <div v-for="account in branchAccessAccounts" :key="account.id" class="border border-gray-200 rounded-lg overflow-hidden">
              <!-- Account header -->
              <div class="px-4 py-3 bg-gray-50 flex items-center justify-between">
                <div class="flex items-center">
                  <img
                    v-if="account.avatar_url"
                    :src="account.avatar_url"
                    :alt="account.name"
                    class="w-10 h-10 rounded-full mr-3"
                  />
                  <div v-else class="w-10 h-10 rounded-full bg-gray-200 mr-3 flex items-center justify-center">
                    <span class="text-gray-500">{{ account.name?.charAt(0) || '?' }}</span>
                  </div>
                  <div>
                    <div class="font-medium text-gray-900">{{ account.name }}</div>
                    <div class="text-sm text-gray-500">{{ account.phone || '-' }}</div>
                  </div>
                </div>
                <span v-if="account.is_connected" class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Connected</span>
                <span v-else class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">Disconnected</span>
              </div>

              <!-- Branches with permissions -->
              <div class="divide-y divide-gray-100">
                <div v-for="branch in account.branches" :key="branch.id" class="px-4 py-3">
                  <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                      <span class="font-medium text-gray-900">{{ branch.name }}</span>
                      <span v-if="branch.is_primary" class="px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded">Primary</span>
                      <span v-else class="px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded">Shared</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <!-- Edit Permissions Button -->
                      <button
                        @click="toggleBranchPermissions(account.id, branch.id)"
                        class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors"
                        :title="t('zalo.edit_permissions') || 'Edit Permissions'"
                      >
                        <svg v-if="expandedBranch !== `${account.id}-${branch.id}`" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                        </svg>
                      </button>
                      <!-- Remove Branch Button (only for shared branches, not primary) -->
                      <button
                        v-if="!branch.is_primary"
                        @click="removeBranchAccess(account, branch)"
                        class="p-1.5 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors"
                        :title="t('zalo.remove_branch_access') || 'Remove from this branch'"
                      >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </div>

                  <!-- Expanded permissions -->
                  <div v-if="expandedBranch === `${account.id}-${branch.id}`" class="mt-3 p-3 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                      <label class="flex items-center gap-2 text-sm">
                        <input
                          type="checkbox"
                          :checked="getBranchPermission(account.id, branch.id, 'can_send_message')"
                          @change="updateBranchPermission(account.id, branch.id, 'can_send_message', $event.target.checked)"
                          class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span>{{ t('zalo.can_send_message') || 'Send Messages' }}</span>
                      </label>
                      <label class="flex items-center gap-2 text-sm">
                        <input
                          type="checkbox"
                          :checked="getBranchPermission(account.id, branch.id, 'view_all_friends')"
                          @change="updateBranchPermission(account.id, branch.id, 'view_all_friends', $event.target.checked)"
                          class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span>{{ t('zalo.view_all_friends') || 'View Friends' }}</span>
                      </label>
                      <label class="flex items-center gap-2 text-sm">
                        <input
                          type="checkbox"
                          :checked="getBranchPermission(account.id, branch.id, 'view_all_groups')"
                          @change="updateBranchPermission(account.id, branch.id, 'view_all_groups', $event.target.checked)"
                          class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span>{{ t('zalo.view_all_groups') || 'View Groups' }}</span>
                      </label>
                      <label class="flex items-center gap-2 text-sm">
                        <input
                          type="checkbox"
                          :checked="getBranchPermission(account.id, branch.id, 'view_all_conversations')"
                          @change="updateBranchPermission(account.id, branch.id, 'view_all_conversations', $event.target.checked)"
                          class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span>{{ t('zalo.view_all_conversations') || 'View Conversations' }}</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Documentation -->
      <div v-else-if="selectedKey === 'documentation'" class="space-y-6">
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
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import { useSwal } from '../../../composables/useSwal';
import axios from 'axios';

const props = defineProps({
  selectedKey: {
    type: String,
    default: 'connection'
  }
});

const { t } = useI18n();
const { Swal } = useSwal();

const showApiKey = ref(false);
const saving = ref(false);

const settings = ref({
  serviceUrl: import.meta.env.VITE_WS_URL || import.meta.env.VITE_ZALO_SERVICE_URL || window.location.origin,
  apiKey: '••••••••••',
  notifyNewHomework: true,
  notifyHomeworkReminder: true,
  notifyScore: true,
});

// Branch access management
const loadingBranchAccess = ref(false);
const branchAccessAccounts = ref([]);
const availableBranches = ref([]);
const expandedBranch = ref(null);
const branchPermissions = ref({}); // { 'accountId-branchId': { permissions... } }

// Telegram settings
const telegramAccounts = ref([]);
const selectedTelegramAccountId = ref('');
const showBotToken = ref(false);
const savingTelegram = ref(false);
const testingTelegram = ref(false);
const telegramSettings = ref({
  botToken: '',
  chatId: '',
  enabled: false,
});

const settingsItems = [
  { key: 'connection', label: t('zalo.service_connection') },
  { key: 'branch_access', label: t('zalo.branch_access_management') },
  { key: 'notifications', label: t('zalo.auto_notifications') },
  { key: 'documentation', label: t('zalo.setup_guide') },
];

const currentItem = computed(() => {
  return settingsItems.find(item => item.key === props.selectedKey);
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

// Telegram settings functions
const loadTelegramAccounts = async () => {
  try {
    const response = await axios.get('/api/zalo/accounts');
    telegramAccounts.value = response.data.data || response.data || [];
    if (telegramAccounts.value.length === 1) {
      selectedTelegramAccountId.value = telegramAccounts.value[0].id;
      await loadTelegramSettings();
    }
  } catch (error) {
    console.error('Failed to load accounts:', error);
  }
};

const loadTelegramSettings = async () => {
  if (!selectedTelegramAccountId.value) {
    telegramSettings.value = { botToken: '', chatId: '', enabled: false };
    return;
  }
  try {
    const response = await axios.get(`/api/zalo/accounts/${selectedTelegramAccountId.value}/telegram`);
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

const saveTelegramSettings = async () => {
  if (!selectedTelegramAccountId.value) return;
  savingTelegram.value = true;
  try {
    const response = await axios.post(`/api/zalo/accounts/${selectedTelegramAccountId.value}/telegram`, {
      telegram_bot_token: telegramSettings.value.botToken,
      telegram_chat_id: telegramSettings.value.chatId,
    });
    if (response.data.success) {
      telegramSettings.value.enabled = !!(telegramSettings.value.botToken && telegramSettings.value.chatId);
      Swal.fire({ icon: 'success', title: t('common.success'), text: t('zalo.telegram_settings_saved') || 'Telegram settings saved successfully', timer: 2000 });
    }
  } catch (error) {
    Swal.fire({ icon: 'error', title: t('common.error'), text: error.response?.data?.message || error.message });
  } finally {
    savingTelegram.value = false;
  }
};

const testTelegramNotification = async () => {
  if (!selectedTelegramAccountId.value) return;
  testingTelegram.value = true;
  try {
    await axios.post(`/api/zalo/accounts/${selectedTelegramAccountId.value}/telegram`, {
      telegram_bot_token: telegramSettings.value.botToken,
      telegram_chat_id: telegramSettings.value.chatId,
    });
    const response = await axios.post(`/api/zalo/accounts/${selectedTelegramAccountId.value}/telegram/test`);
    if (response.data.success) {
      Swal.fire({ icon: 'success', title: t('common.success'), text: t('zalo.telegram_test_sent') || 'Test notification sent! Check your Telegram.', timer: 3000 });
    }
  } catch (error) {
    Swal.fire({ icon: 'error', title: t('common.error'), text: error.response?.data?.message || error.response?.data?.error || error.message });
  } finally {
    testingTelegram.value = false;
  }
};

// Branch access management functions
const loadBranchAccess = async () => {
  loadingBranchAccess.value = true;
  try {
    const response = await axios.get('/api/zalo/accounts/branch-access');
    if (response.data.success) {
      branchAccessAccounts.value = response.data.data.accounts || [];
      availableBranches.value = response.data.data.available_branches || [];
    }
  } catch (error) {
    console.error('Failed to load branch access:', error);
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: t('zalo.cannot_load_branches') || 'Failed to load branch access data',
    });
  } finally {
    loadingBranchAccess.value = false;
  }
};

// Toggle branch permissions panel
const toggleBranchPermissions = async (accountId, branchId) => {
  const key = `${accountId}-${branchId}`;
  if (expandedBranch.value === key) {
    expandedBranch.value = null;
  } else {
    expandedBranch.value = key;
    // Load permissions if not already loaded
    if (!branchPermissions.value[key]) {
      await loadBranchPermissions(accountId, branchId);
    }
  }
};

// Load permissions for a specific branch
const loadBranchPermissions = async (accountId, branchId) => {
  try {
    const response = await axios.get(`/api/zalo/accounts/${accountId}/branch-access/${branchId}`);
    if (response.data.success) {
      const key = `${accountId}-${branchId}`;
      branchPermissions.value[key] = response.data.data.permissions || {};
    }
  } catch (error) {
    console.error('Failed to load branch permissions:', error);
  }
};

// Get permission value
const getBranchPermission = (accountId, branchId, permissionKey) => {
  const key = `${accountId}-${branchId}`;
  return branchPermissions.value[key]?.[permissionKey] || false;
};

// Update single permission
const updateBranchPermission = async (accountId, branchId, permissionKey, value) => {
  try {
    const response = await axios.post(`/api/zalo/accounts/${accountId}/branch-access/${branchId}/permission`, {
      permission: permissionKey,
      value: value,
    });

    if (response.data.success) {
      // Update local state
      const key = `${accountId}-${branchId}`;
      if (!branchPermissions.value[key]) {
        branchPermissions.value[key] = {};
      }
      branchPermissions.value[key][permissionKey] = value;
    }
  } catch (error) {
    console.error('Failed to update permission:', error);
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || 'Failed to update permission',
    });
  }
};

// Remove branch access (delete from zalo_account_branches)
const removeBranchAccess = async (account, branch) => {
  const result = await Swal.fire({
    title: t('zalo.confirm_remove_branch_access') || 'Remove Branch Access?',
    text: `${t('zalo.confirm_remove_branch_access_desc') || 'Are you sure you want to remove access for'} "${branch.name}" ${t('common.from') || 'from'} "${account.name}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    confirmButtonText: t('common.delete') || 'Remove',
    cancelButtonText: t('common.cancel') || 'Cancel',
  });

  if (result.isConfirmed) {
    try {
      const response = await axios.delete(`/api/zalo/accounts/${account.id}/branch-access/${branch.id}`);
      if (response.data.success) {
        // Remove branch from local state
        const accountIndex = branchAccessAccounts.value.findIndex(a => a.id === account.id);
        if (accountIndex !== -1) {
          branchAccessAccounts.value[accountIndex].branches =
            branchAccessAccounts.value[accountIndex].branches.filter(b => b.id !== branch.id);
        }

        // Clear expanded state if this branch was expanded
        if (expandedBranch.value === `${account.id}-${branch.id}`) {
          expandedBranch.value = null;
        }

        Swal.fire({
          icon: 'success',
          title: t('common.success'),
          text: t('zalo.branch_access_removed') || 'Branch access removed successfully',
          timer: 2000,
        });
      }
    } catch (error) {
      console.error('Failed to remove branch access:', error);
      Swal.fire({
        icon: 'error',
        title: t('common.error'),
        text: error.response?.data?.message || 'Failed to remove branch access',
      });
    }
  }
};

// Watch for tab changes to load data
watch(() => props.selectedKey, (newKey) => {
  if (newKey === 'branch_access' && branchAccessAccounts.value.length === 0) {
    loadBranchAccess();
  }
  if (newKey === 'connection' && telegramAccounts.value.length === 0) {
    loadTelegramAccounts();
  }
}, { immediate: true });

onMounted(() => {
  loadSettings();
  loadTelegramAccounts();
});
</script>

