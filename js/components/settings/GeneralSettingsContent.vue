<template>
  <div class="p-6">
    <!-- Header -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900">General Settings</h2>
      <p class="text-gray-600 mt-1">Cài đặt chung cho hệ thống</p>
    </div>

    <!-- Currency Settings Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Thiết lập cơ bản - Tiền tệ</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Currency -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Loại tiền tệ
          </label>
          <select
            v-model="settings.currency"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="VND">Việt Nam Đồng (VNĐ)</option>
            <option value="USD">US Dollar ($)</option>
            <option value="EUR">Euro (€)</option>
            <option value="GBP">British Pound (£)</option>
            <option value="JPY">Japanese Yen (¥)</option>
          </select>
        </div>

        <!-- Currency Symbol -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Ký hiệu tiền tệ
          </label>
          <input
            v-model="settings.currency_symbol"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
            placeholder="VNĐ"
          />
        </div>

        <!-- Decimal Places -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Số chữ số thập phân
          </label>
          <select
            v-model="settings.currency_decimals"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="0">0 (VNĐ, JPY)</option>
            <option value="2">2 (USD, EUR, GBP)</option>
            <option value="3">3</option>
          </select>
        </div>

        <!-- Thousand Separator -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Dấu phân cách hàng nghìn
          </label>
          <select
            v-model="settings.thousand_separator"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
          >
            <option value=",">, (phẩy)</option>
            <option value=".">. (chấm)</option>
            <option value=" "> (khoảng trắng)</option>
            <option value="">Không</option>
          </select>
        </div>

        <!-- Decimal Separator -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Dấu phân cách thập phân
          </label>
          <select
            v-model="settings.decimal_separator"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
          >
            <option value=".">. (chấm)</option>
            <option value=",">, (phẩy)</option>
          </select>
        </div>
      </div>

      <!-- Preview -->
      <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
        <p class="text-sm font-medium text-blue-900 mb-2">Xem trước:</p>
        <p class="text-2xl font-bold text-blue-700">{{ formatPreview(1234567.89) }}</p>
      </div>
    </div>

    <!-- Google Drive API Settings Card -->
    <div v-if="canManageGoogleDrive" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
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

    <!-- Branding Settings Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Cài đặt thương hiệu</h3>

      <div class="grid grid-cols-1 gap-6">
        <!-- Favicon Upload -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Favicon (Icon trình duyệt)
          </label>
          <div class="flex items-start space-x-4">
            <!-- Current favicon preview -->
            <div class="flex-shrink-0">
              <div class="w-16 h-16 border-2 border-gray-300 rounded-lg flex items-center justify-center bg-white overflow-hidden">
                <img
                  v-if="faviconPreview"
                  :src="faviconPreview"
                  alt="Favicon preview"
                  class="w-full h-full object-contain"
                />
                <svg v-else class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
            </div>

            <!-- Upload controls -->
            <div class="flex-1">
              <input
                ref="faviconInput"
                type="file"
                accept="image/x-icon,image/png,image/svg+xml"
                @change="handleFaviconChange"
                class="hidden"
              />
              <button
                @click="$refs.faviconInput.click()"
                type="button"
                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                Chọn file
              </button>
              <p class="mt-2 text-xs text-gray-500">
                Định dạng: .ico, .png, .svg | Kích thước khuyến nghị: 32x32px hoặc 16x16px
              </p>
              <p v-if="settings.favicon_url" class="mt-2 text-xs text-green-600 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Favicon đã được thiết lập
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Date & Time Settings Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Cài đặt ngày giờ</h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Timezone -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Múi giờ
          </label>
          <select
            v-model="settings.timezone"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
          >
            <optgroup label="Châu Á - Đông Nam Á">
              <option value="Asia/Ho_Chi_Minh">Việt Nam (GMT+7)</option>
              <option value="Asia/Bangkok">Thái Lan (GMT+7)</option>
              <option value="Asia/Singapore">Singapore (GMT+8)</option>
              <option value="Asia/Jakarta">Indonesia - Jakarta (GMT+7)</option>
              <option value="Asia/Manila">Philippines (GMT+8)</option>
              <option value="Asia/Kuala_Lumpur">Malaysia (GMT+8)</option>
            </optgroup>
            <optgroup label="Châu Á - Đông Á">
              <option value="Asia/Shanghai">Trung Quốc (GMT+8)</option>
              <option value="Asia/Tokyo">Nhật Bản (GMT+9)</option>
              <option value="Asia/Seoul">Hàn Quốc (GMT+9)</option>
              <option value="Asia/Hong_Kong">Hồng Kông (GMT+8)</option>
              <option value="Asia/Taipei">Đài Loan (GMT+8)</option>
            </optgroup>
            <optgroup label="Châu Á - Nam Á">
              <option value="Asia/Kolkata">Ấn Độ (GMT+5:30)</option>
              <option value="Asia/Dhaka">Bangladesh (GMT+6)</option>
              <option value="Asia/Karachi">Pakistan (GMT+5)</option>
            </optgroup>
            <optgroup label="Châu Á - Tây Á">
              <option value="Asia/Dubai">UAE (GMT+4)</option>
              <option value="Asia/Riyadh">Ả Rập Saudi (GMT+3)</option>
              <option value="Asia/Istanbul">Thổ Nhĩ Kỳ (GMT+3)</option>
            </optgroup>
            <optgroup label="Châu Âu">
              <option value="Europe/London">Anh (GMT+0)</option>
              <option value="Europe/Paris">Pháp (GMT+1)</option>
              <option value="Europe/Berlin">Đức (GMT+1)</option>
              <option value="Europe/Rome">Ý (GMT+1)</option>
              <option value="Europe/Madrid">Tây Ban Nha (GMT+1)</option>
              <option value="Europe/Moscow">Nga - Moscow (GMT+3)</option>
            </optgroup>
            <optgroup label="Châu Mỹ">
              <option value="America/New_York">Mỹ - New York (GMT-5)</option>
              <option value="America/Chicago">Mỹ - Chicago (GMT-6)</option>
              <option value="America/Denver">Mỹ - Denver (GMT-7)</option>
              <option value="America/Los_Angeles">Mỹ - Los Angeles (GMT-8)</option>
              <option value="America/Toronto">Canada - Toronto (GMT-5)</option>
              <option value="America/Vancouver">Canada - Vancouver (GMT-8)</option>
              <option value="America/Mexico_City">Mexico (GMT-6)</option>
              <option value="America/Sao_Paulo">Brazil - São Paulo (GMT-3)</option>
            </optgroup>
            <optgroup label="Châu Đại Dương">
              <option value="Australia/Sydney">Úc - Sydney (GMT+10)</option>
              <option value="Australia/Melbourne">Úc - Melbourne (GMT+10)</option>
              <option value="Australia/Perth">Úc - Perth (GMT+8)</option>
              <option value="Pacific/Auckland">New Zealand (GMT+12)</option>
            </optgroup>
            <optgroup label="Khác">
              <option value="UTC">UTC (GMT+0)</option>
            </optgroup>
          </select>
          <p class="mt-1 text-xs text-gray-500">Múi giờ được sử dụng để hiển thị thời gian trong hệ thống (thông báo, báo cáo, v.v.)</p>
        </div>
      </div>

      <!-- Preview -->
      <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
        <p class="text-sm font-medium text-blue-900 mb-2">Xem trước:</p>
        <p class="text-lg font-medium text-blue-700">
          <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ formatTimezonePreview() }}
        </p>
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
        <span>{{ saving ? 'Đang lưu...' : 'Lưu cài đặt' }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';
import { useAuthStore } from '../../stores/auth';

const { t } = useI18n();
const { showSuccess, showError } = useSwal();
const authStore = useAuthStore();

const settings = ref({
  currency: 'VND',
  currency_symbol: 'VNĐ',
  currency_decimals: '0',
  thousand_separator: ',',
  decimal_separator: '.',
  timezone: 'Asia/Ho_Chi_Minh',
  favicon_url: '',
  google_drive_client_id: '',
  google_drive_client_secret: '',
  google_drive_refresh_token: '',
  google_drive_folder_name: 'School Drive'
});

const saving = ref(false);
const testing = ref(false);
const authorizing = ref(false);
const googleDriveStatus = ref(null);
const faviconPreview = ref('');
const faviconFile = ref(null);

const isGoogleDriveConfigured = computed(() => {
  // Only require client credentials - refresh_token obtained via OAuth
  return settings.value.google_drive_client_id &&
         settings.value.google_drive_client_secret;
});

const canManageGoogleDrive = computed(() => {
  return authStore.hasPermission('google-drive.settings') || authStore.hasPermission('system-settings.edit');
});

// Auto-update symbol and decimals when currency changes
watch(() => settings.value.currency, (newCurrency) => {
  const currencyConfig = {
    'VND': { symbol: 'VNĐ', decimals: '0' },
    'USD': { symbol: '$', decimals: '2' },
    'EUR': { symbol: '€', decimals: '2' },
    'GBP': { symbol: '£', decimals: '2' },
    'JPY': { symbol: '¥', decimals: '0' }
  };
  
  if (currencyConfig[newCurrency]) {
    settings.value.currency_symbol = currencyConfig[newCurrency].symbol;
    settings.value.currency_decimals = currencyConfig[newCurrency].decimals;
  }
});

const formatPreview = (amount) => {
  const decimals = parseInt(settings.value.currency_decimals);
  const thousandSep = settings.value.thousand_separator;
  const decimalSep = settings.value.decimal_separator;

  // Round to decimals
  const rounded = amount.toFixed(decimals);

  // Split into integer and decimal parts
  const [intPart, decPart] = rounded.split('.');

  // Add thousand separator
  const formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSep);

  // Combine parts
  let result = formattedInt;
  if (decimals > 0 && decPart) {
    result += decimalSep + decPart;
  }

  // Add currency symbol
  return result + ' ' + settings.value.currency_symbol;
};

const formatTimezonePreview = () => {
  try {
    // Get current time in selected timezone
    const now = new Date();
    const formatter = new Intl.DateTimeFormat('vi-VN', {
      timeZone: settings.value.timezone,
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: false
    });

    return formatter.format(now);
  } catch (error) {
    console.error('Error formatting timezone preview:', error);
    return 'Invalid timezone';
  }
};

const handleFaviconChange = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  // Validate file type
  const validTypes = ['image/x-icon', 'image/png', 'image/svg+xml'];
  if (!validTypes.includes(file.type)) {
    showError('Vui lòng chọn file định dạng .ico, .png hoặc .svg');
    return;
  }

  // Validate file size (max 1MB)
  if (file.size > 1024 * 1024) {
    showError('Kích thước file không được vượt quá 1MB');
    return;
  }

  faviconFile.value = file;

  // Create preview
  const reader = new FileReader();
  reader.onload = (e) => {
    faviconPreview.value = e.target.result;
  };
  reader.readAsDataURL(file);
};

const loadSettings = async () => {
  try {
    const response = await axios.get('/api/system-settings', {
      params: { group: 'general' }
    });

    const data = response.data.data;

    // Map currency settings
    if (data.currency) settings.value.currency = data.currency.value;
    if (data.currency_symbol) settings.value.currency_symbol = data.currency_symbol.value;
    if (data.currency_decimals) settings.value.currency_decimals = data.currency_decimals.value;
    if (data.thousand_separator) settings.value.thousand_separator = data.thousand_separator.value;
    if (data.decimal_separator) settings.value.decimal_separator = data.decimal_separator.value;

    // Map timezone setting
    if (data.timezone) settings.value.timezone = data.timezone.value;

    // Map favicon setting
    if (data.favicon_url) {
      settings.value.favicon_url = data.favicon_url.value;
      faviconPreview.value = data.favicon_url.value;
    }

    // Note: Google Drive settings are loaded separately from google_drive_settings table
  } catch (error) {
    console.error('Error loading settings:', error);
  }
};

const loadGoogleDriveStatus = async () => {
  try {
    const response = await axios.get('/api/google-drive/settings');
    if (response.data.success && response.data.data) {
      const gdData = response.data.data;
      
      // Load credentials vào form
      if (gdData.client_id) {
        settings.value.google_drive_client_id = gdData.client_id;
      }
      if (gdData.client_secret) {
        settings.value.google_drive_client_secret = gdData.client_secret;
      }
      if (gdData.refresh_token) {
        settings.value.google_drive_refresh_token = gdData.refresh_token;
      }
      if (gdData.school_drive_folder_name) {
        settings.value.google_drive_folder_name = gdData.school_drive_folder_name;
      }
      
      // Load status
      googleDriveStatus.value = {
        is_connected: gdData.is_active || false,
        folder_id: gdData.school_drive_folder_id,
        last_synced_at: gdData.last_synced_at
      };
      
      console.log('Google Drive settings loaded:', {
        has_client_id: !!gdData.client_id,
        has_client_secret: !!gdData.client_secret,
        has_refresh_token: !!gdData.refresh_token,
        folder_name: gdData.school_drive_folder_name,
        is_active: gdData.is_active
      });
    } else {
      console.log('No Google Drive settings found in database');
    }
  } catch (error) {
    console.error('Error loading Google Drive status:', error);
  }
};

const saveSettings = async () => {
  try {
    saving.value = true;

    // Upload favicon if a new file was selected
    if (faviconFile.value) {
      const formData = new FormData();
      formData.append('favicon', faviconFile.value);

      try {
        const uploadResponse = await axios.post('/api/system-settings/upload-favicon', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });

        if (uploadResponse.data.success) {
          settings.value.favicon_url = uploadResponse.data.url;
          faviconFile.value = null; // Reset file after successful upload
        }
      } catch (error) {
        console.error('Error uploading favicon:', error);
        showError('Lỗi khi upload favicon: ' + (error.response?.data?.message || error.message));
        saving.value = false;
        return;
      }
    }

    // Save currency and timezone settings to system_settings table
    const settingsArray = [
      { key: 'currency', value: settings.value.currency },
      { key: 'currency_symbol', value: settings.value.currency_symbol },
      { key: 'currency_decimals', value: settings.value.currency_decimals },
      { key: 'thousand_separator', value: settings.value.thousand_separator },
      { key: 'decimal_separator', value: settings.value.decimal_separator },
      { key: 'timezone', value: settings.value.timezone },
      { key: 'favicon_url', value: settings.value.favicon_url }
    ];

    await axios.post('/api/system-settings/bulk-update', {
      settings: settingsArray
    });
    
    // If Google Drive settings changed, update in Google Drive module
    console.log('isGoogleDriveConfigured:', isGoogleDriveConfigured.value);
    console.log('Google Drive settings:', {
      client_id: settings.value.google_drive_client_id,
      client_secret: settings.value.google_drive_client_secret?.substring(0, 10) + '...',
      refresh_token: settings.value.google_drive_refresh_token?.substring(0, 10) + '...',
      folder_name: settings.value.google_drive_folder_name
    });
    
    if (isGoogleDriveConfigured.value) {
      try {
        console.log('Updating Google Drive folder name...');
        // Only update folder name if client credentials are present
        // The refresh_token is obtained via OAuth, not manual entry
        const gdUpdatePayload = {
          client_id: settings.value.google_drive_client_id,
          client_secret: settings.value.google_drive_client_secret,
          school_drive_folder_name: settings.value.google_drive_folder_name
        };

        // Include refresh_token only if it exists (for backward compatibility)
        if (settings.value.google_drive_refresh_token) {
          gdUpdatePayload.refresh_token = settings.value.google_drive_refresh_token;
        }

        const response = await axios.post('/api/google-drive/settings', gdUpdatePayload);
        console.log('Google Drive settings saved:', response.data);
        await loadGoogleDriveStatus();
      } catch (error) {
        console.error('Error updating Google Drive settings:', error);
        // Show error to user
        showError('Failed to save Google Drive settings: ' + (error.response?.data?.message || error.message));
      }
    } else {
      console.warn('Google Drive settings not configured yet');
    }
    
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
      showSuccess(t('google_drive.connection_success'));
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
  // Validate client credentials
  if (!settings.value.google_drive_client_id || !settings.value.google_drive_client_secret) {
    showError(t('google_drive.enter_credentials_first'));
    return;
  }

  authorizing.value = true;
  try {
    // Get OAuth authorization URL from backend
    const response = await axios.post('/api/google-drive/auth-url', {
      client_id: settings.value.google_drive_client_id,
      client_secret: settings.value.google_drive_client_secret
    });

    if (response.data.success) {
      const authUrl = response.data.auth_url;

      // Open OAuth URL in popup window
      const width = 600;
      const height = 700;
      const left = window.screen.width / 2 - width / 2;
      const top = window.screen.height / 2 - height / 2;

      const popup = window.open(
        authUrl,
        'google_oauth',
        `width=${width},height=${height},left=${left},top=${top},toolbar=no,menubar=no,scrollbars=yes,resizable=yes`
      );

      // Poll for popup close or URL change
      const pollTimer = setInterval(() => {
        try {
          // Check if popup is closed
          if (!popup || popup.closed) {
            clearInterval(pollTimer);
            authorizing.value = false;
            // Reload status to check if authorization succeeded
            loadGoogleDriveStatus();
          }

          // Try to detect if redirected back (will throw error due to CORS if on Google domain)
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
            // Expected error when on different domain (Google OAuth page)
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
  loadSettings();
  loadGoogleDriveStatus();

  // Check for OAuth callback parameters in URL
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has('success') && urlParams.get('success') === 'true') {
    showSuccess(t('google_drive.authorization_success'));
    // Clean up URL
    window.history.replaceState({}, document.title, window.location.pathname);
  } else if (urlParams.has('error')) {
    const error = urlParams.get('error');
    showError(t('google_drive.authorization_failed') + ': ' + decodeURIComponent(error));
    // Clean up URL
    window.history.replaceState({}, document.title, window.location.pathname);
  }
});
</script>

