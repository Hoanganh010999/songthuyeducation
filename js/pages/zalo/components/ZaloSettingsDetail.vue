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
import { ref, computed, onMounted } from 'vue';
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

const settingsItems = [
  { key: 'connection', label: t('zalo.service_connection') },
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

onMounted(() => {
  loadSettings();
});
</script>

