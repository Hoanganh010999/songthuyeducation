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
          {{ t('quality.update_phone') || 'Cập nhật số điện thoại' }}
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
          <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <!-- Phone Field -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('common.phone') || 'Số điện thoại' }}
          </label>
          <input
            v-model="form.phone"
            type="tel"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
            :placeholder="t('common.enter_phone') || 'Nhập số điện thoại'"
          />
          <p v-if="user?.phone" class="text-xs text-gray-500 mt-1">
            {{ t('common.current') || 'Hiện tại' }}: {{ user.phone }}
          </p>
        </div>

        <!-- Search Zalo Button -->
        <div class="pt-4 border-t border-gray-200">
          <button
            @click="searchZaloInfo"
            :disabled="!form.phone || searching"
            class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <span>{{ searching ? (t('common.searching') || 'Đang tìm kiếm...') : (t('quality.search_zalo_info') || 'Tìm thông tin Zalo') }}</span>
          </button>
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
          :disabled="saving || !form.phone"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
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

const emit = defineEmits(['close', 'updated', 'show-zalo-profile']);

const form = ref({
  phone: '',
});

const saving = ref(false);
const searching = ref(false);
const error = ref(null);

// Reset form when modal opens
watch(() => props.show, (newValue) => {
  if (newValue) {
    form.value = {
      phone: props.user?.phone || '',
    };
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
      // Emit event to show Zalo profile modal
      emit('show-zalo-profile', response.data.data);
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
  if (!form.value.phone) {
    await Swal.fire({
      icon: 'warning',
      title: t('common.warning') || 'Cảnh báo',
      text: t('quality.please_enter_phone') || 'Vui lòng nhập số điện thoại',
    });
    return;
  }

  saving.value = true;
  error.value = null;

  try {
    const response = await axios.patch(`/api/users/${props.user.id}/contact`, {
      phone: form.value.phone,
    });

    if (response.data.success) {
      await Swal.fire({
        icon: 'success',
        title: t('common.success') || 'Thành công',
        text: t('quality.phone_updated') || 'Cập nhật số điện thoại thành công',
        timer: 1500,
        showConfirmButton: false,
      });

      emit('updated', response.data.data);
      close();
    }
  } catch (err) {
    console.error('Error updating phone:', err);
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
