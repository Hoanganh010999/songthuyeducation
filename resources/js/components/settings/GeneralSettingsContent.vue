<template>
  <div class="p-6">
    <!-- Header -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900">{{ t('settings.general_settings') }}</h2>
      <p class="text-gray-600 mt-1">{{ t('settings.general_settings_description') }}</p>
    </div>

    <!-- Currency Settings Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ t('settings.basic_settings') }}</h3>
      
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
  favicon_url: ''
});

const saving = ref(false);
const faviconPreview = ref('');
const faviconFile = ref(null);

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
  } catch (error) {
    console.error('Error loading settings:', error);
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
    
    showSuccess(t('common.saved_successfully'));
  } catch (error) {
    console.error('Error saving settings:', error);
    showError(error.response?.data?.message || t('common.error_occurred'));
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadSettings();
});
</script>

