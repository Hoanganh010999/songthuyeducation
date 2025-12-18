<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 p-4"
    @click.self="close"
  >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900">
          {{ t('quality.update_google_email') || 'Cập nhật Google Email' }}
        </h2>
        <button
          @click="close"
          class="p-1 hover:bg-gray-100 rounded-full transition"
        >
          <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- User Info -->
      <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <div class="flex items-center space-x-4">
          <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-medium text-gray-900">{{ user?.name || 'N/A' }}</h3>
            <p class="text-sm text-gray-500">{{ userType }}</p>
          </div>
        </div>
      </div>

      <!-- Form -->
      <div class="px-6 py-6 space-y-6">
        <!-- Google Email Field -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Google Email
          </label>
          <input
            v-model="form.google_email"
            type="email"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            :placeholder="t('quality.enter_google_email') || 'Nhập Google Email'"
          />
          <p v-if="user?.google_email" class="text-xs text-gray-500 mt-1">
            {{ t('common.current') || 'Hiện tại' }}: {{ user.google_email }}
          </p>
        </div>

        <!-- Phone Field (for Zalo search) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('common.phone') || 'Số điện thoại' }}
          </label>
          <input
            v-model="form.phone"
            type="tel"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            :placeholder="t('quality.phone_for_zalo_search') || 'Số điện thoại để tìm Zalo'"
          />
          <p class="text-xs text-gray-500 mt-1">
            {{ t('quality.phone_search_hint') || 'Nhập số điện thoại để tìm và lấy thông tin từ Zalo' }}
          </p>
        </div>

        <!-- Search Zalo Button -->
        <div class="pt-4 border-t border-gray-200">
          <button
            @click="searchZaloInfo"
            :disabled="!form.phone || searching"
            class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <span>{{ searching ? (t('common.searching') || 'Đang tìm kiếm...') : (t('quality.search_zalo_info') || 'Tìm thông tin Zalo') }}</span>
          </button>
        </div>

        <!-- Zalo Info Display (if found) -->
        <div v-if="zaloInfo" class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div class="flex items-center space-x-2 mb-3">
            <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <span class="font-medium text-blue-900">{{ t('quality.zalo_account_found') || 'Tìm thấy tài khoản Zalo' }}</span>
          </div>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600">{{ t('common.name') || 'Tên' }}:</span>
              <span class="text-gray-900 font-medium">{{ zaloInfo.displayName || '-' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">{{ t('common.phone') || 'SĐT' }}:</span>
              <span class="text-gray-900 font-medium">{{ zaloInfo.phone || '-' }}</span>
            </div>
            <div v-if="zaloInfo.avatar" class="mt-3">
              <img :src="zaloInfo.avatar" alt="Avatar" class="w-16 h-16 rounded-full border-2 border-white shadow" />
            </div>
          </div>
        </div>

        <!-- Error message -->
        <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
          {{ error }}
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
        <button
          @click="close"
          class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition"
        >
          {{ t('common.cancel') || 'Hủy' }}
        </button>
        <button
          @click="save"
          :disabled="saving || !form.google_email"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ saving ? (t('common.saving') || 'Đang lưu...') : (t('common.save') || 'Lưu') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';
import Swal from 'sweetalert2';

const { t } = useI18n();

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  user: {
    type: Object,
    required: true,
  },
  userType: {
    type: String,
    default: 'User',
  },
});

const emit = defineEmits(['close', 'updated']);

const form = ref({
  google_email: '',
  phone: '',
});

const saving = ref(false);
const searching = ref(false);
const zaloInfo = ref(null);
const error = ref(null);

// Reset form when modal opens
watch(() => props.show, (newValue) => {
  if (newValue) {
    form.value = {
      google_email: props.user?.google_email || '',
      phone: props.user?.phone || '',
    };
    zaloInfo.value = null;
    error.value = null;
  }
});

// Search for Zalo info by phone
const searchZaloInfo = async () => {
  if (!form.value.phone) {
    return;
  }

  searching.value = true;
  error.value = null;
  zaloInfo.value = null;

  try {
    // Get first active Zalo account for this user
    const accountsResponse = await axios.get('/api/zalo/accounts');
    const accounts = accountsResponse.data.data || [];

    if (accounts.length === 0) {
      error.value = t('quality.no_zalo_account') || 'Không có tài khoản Zalo nào được kết nối';
      return;
    }

    const activeAccount = accounts.find(acc => acc.status === 'active') || accounts[0];

    // Search user by phone using Zalo API
    const response = await axios.post('/api/zalo/users/search', {
      account_id: activeAccount.id,
      phone_number: form.value.phone,
    });

    if (response.data.success && response.data.data) {
      zaloInfo.value = response.data.data;
    } else {
      error.value = t('quality.zalo_not_found') || 'Không tìm thấy tài khoản Zalo với số điện thoại này';
    }
  } catch (err) {
    console.error('Error searching Zalo info:', err);
    error.value = err.response?.data?.message || (t('common.error_occurred') || 'Đã xảy ra lỗi');
  } finally {
    searching.value = false;
  }
};

// Save changes
const save = async () => {
  if (!form.value.google_email) {
    await Swal.fire({
      icon: 'warning',
      title: t('common.warning') || 'Cảnh báo',
      text: t('quality.please_enter_google_email') || 'Vui lòng nhập Google Email',
    });
    return;
  }

  saving.value = true;
  error.value = null;

  try {
    const response = await axios.patch(`/api/users/${props.user.id}/google-email`, {
      google_email: form.value.google_email,
    });

    if (response.data.success) {
      await Swal.fire({
        icon: 'success',
        title: t('common.success') || 'Thành công',
        text: t('quality.google_email_updated') || 'Cập nhật Google Email thành công',
        timer: 1500,
        showConfirmButton: false,
      });

      emit('updated', response.data.data);
      close();
    }
  } catch (err) {
    console.error('Error updating Google Email:', err);
    error.value = err.response?.data?.message || (t('common.error_occurred') || 'Đã xảy ra lỗi');

    await Swal.fire({
      icon: 'error',
      title: t('common.error') || 'Lỗi',
      text: error.value,
    });
  } finally {
    saving.value = false;
  }
};

const close = () => {
  emit('close');
};
</script>
