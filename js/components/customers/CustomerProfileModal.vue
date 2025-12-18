<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 p-4"
    @click.self="close"
  >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto flex flex-col">
      <!-- Cover Photo -->
      <div class="h-48 bg-gradient-to-r from-yellow-400 via-orange-400 to-pink-400 relative">
        <img
          v-if="coverPhoto"
          :src="coverPhoto"
          alt="Cover"
          class="w-full h-full object-cover"
        />
        <button
          @click="close"
          class="absolute top-4 right-4 p-2 bg-white bg-opacity-80 rounded-full hover:bg-opacity-100 transition-colors"
        >
          <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Profile Info -->
      <div class="px-6 pb-6">
        <!-- Avatar -->
        <div class="flex justify-center -mt-16 mb-4">
          <div class="relative">
            <div class="w-32 h-32 rounded-full border-4 border-white bg-white shadow-lg overflow-hidden">
              <img
                v-if="avatar"
                :src="avatar"
                :alt="displayName"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center bg-blue-100">
                <svg class="w-16 h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
            </div>
          </div>
        </div>

        <!-- Name -->
        <div class="text-center mb-6">
          <h2 class="text-2xl font-bold text-gray-900">{{ displayName }}</h2>
          <p v-if="status" class="text-sm text-gray-500 mt-1">{{ status }}</p>
          <div v-if="loading" class="text-sm text-blue-600 mt-2">
            <svg class="animate-spin inline-block h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading profile from Zalo...
          </div>
          <p v-if="error" class="text-sm text-red-600 mt-2">{{ error }}</p>
        </div>

        <!-- Personal Info Section -->
        <div class="mb-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ t('customers.personal_info') || 'Thông tin cá nhân' }}</h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
              <span class="text-gray-600">{{ t('customers.gender') || 'Giới tính' }}</span>
              <span class="text-gray-900 font-medium">{{ gender }}</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
              <span class="text-gray-600">{{ t('customers.date_of_birth') || 'Ngày sinh' }}</span>
              <span class="text-gray-900 font-medium">{{ dateOfBirth || '-' }}</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
              <span class="text-gray-600">{{ t('customers.phone') || 'Điện thoại' }}</span>
              <span class="text-gray-900 font-medium">{{ phone || '-' }}</span>
            </div>
            <div v-if="customer.email" class="flex items-center justify-between py-2 border-b border-gray-100">
              <span class="text-gray-600">{{ t('customers.email') || 'Email' }}</span>
              <span class="text-gray-900 font-medium">{{ customer.email }}</span>
            </div>
            <div v-if="customer.address" class="flex items-center justify-between py-2 border-b border-gray-100">
              <span class="text-gray-600">{{ t('customers.address') || 'Địa chỉ' }}</span>
              <span class="text-gray-900 font-medium text-right">{{ customer.address }}</span>
            </div>
          </div>

          <!-- Info badge showing data source -->
          <div class="mt-3 px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-xs text-blue-700">
              <svg class="inline-block w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
              {{ zaloUserInfo ? 'Thông tin từ tài khoản Zalo' : 'Thông tin từ cơ sở dữ liệu' }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';

const { t } = useI18n();

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  customer: {
    type: Object,
    required: true,
  },
  zaloAvatar: {
    type: String,
    default: null,
  },
  zaloUserId: {
    type: String,
    default: null,
  },
  accountId: {
    type: Number,
    default: null,
  },
});

const emit = defineEmits(['close']);

const loading = ref(false);
const zaloUserInfo = ref(null);
const error = ref(null);

// Fetch Zalo user info
const fetchZaloUserInfo = async () => {
  if (!props.zaloUserId || !props.accountId) {
    console.warn('No Zalo user ID or account ID provided');
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    const response = await axios.get(
      `/api/zalo/user-info/${props.zaloUserId}`,
      {
        params: {
          account_id: props.accountId,
        },
      }
    );

    if (response.data.success) {
      zaloUserInfo.value = response.data.data;
      console.log('Zalo user info loaded:', zaloUserInfo.value);
    } else {
      throw new Error(response.data.message || 'Failed to fetch user info');
    }
  } catch (err) {
    console.error('Error fetching Zalo user info:', err);
    error.value = err.response?.data?.message || err.message || 'Failed to load user profile from Zalo';
  } finally {
    loading.value = false;
  }
};

// Watch for modal open
watch(() => props.show, (newValue) => {
  if (newValue && props.zaloUserId) {
    fetchZaloUserInfo();
  }
});

// Computed values from Zalo or fallback to customer data
const displayName = computed(() => {
  return zaloUserInfo.value?.display_name || props.customer.name || 'Unknown';
});

const avatar = computed(() => {
  return zaloUserInfo.value?.avatar || props.zaloAvatar || props.customer.avatar;
});

const coverPhoto = computed(() => {
  return zaloUserInfo.value?.cover || props.customer.cover_photo;
});

const gender = computed(() => {
  if (zaloUserInfo.value?.gender !== undefined && zaloUserInfo.value?.gender !== null) {
    return getGenderLabel(zaloUserInfo.value.gender);
  }
  return getGenderLabel(props.customer.gender);
});

const dateOfBirth = computed(() => {
  // Zalo returns sdob (string date of birth) or dob (timestamp)
  if (zaloUserInfo.value?.sdob) {
    return zaloUserInfo.value.sdob;
  }
  if (zaloUserInfo.value?.dob) {
    return formatDate(new Date(zaloUserInfo.value.dob * 1000));
  }
  return formatDate(props.customer.date_of_birth);
});

const phone = computed(() => {
  return zaloUserInfo.value?.phone || props.customer.phone;
});

const status = computed(() => {
  return zaloUserInfo.value?.status || null;
});

const getGenderLabel = (gender) => {
  if (gender === undefined || gender === null || gender === '') return '-';

  const genderMap = {
    'male': 'Nam',
    'female': 'Nữ',
    'other': 'Khác',
    0: 'Nam',
    1: 'Nữ',
  };

  return genderMap[gender] !== undefined ? genderMap[gender] : gender;
};

const formatDate = (dateString) => {
  if (!dateString) return null;
  const date = new Date(dateString);
  return date.toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const close = () => {
  emit('close');
};
</script>
