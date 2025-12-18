<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 p-4"
    @click.self="close"
  >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto flex flex-col">
      <!-- Cover Photo -->
      <div class="h-48 bg-gradient-to-r from-blue-400 via-blue-500 to-blue-600 relative">
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
        </div>

        <!-- Personal Info Section -->
        <div class="mb-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ t('quality.zalo_info') || 'Thông tin Zalo' }}</h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
              <span class="text-gray-600">{{ t('common.name') || 'Tên hiển thị' }}</span>
              <span class="text-gray-900 font-medium">{{ displayName || '-' }}</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
              <span class="text-gray-600">{{ t('customers.gender') || 'Giới tính' }}</span>
              <span class="text-gray-900 font-medium">{{ gender || '-' }}</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
              <span class="text-gray-600">{{ t('customers.date_of_birth') || 'Ngày sinh' }}</span>
              <span class="text-gray-900 font-medium">{{ dateOfBirth || '-' }}</span>
            </div>
            <div v-if="zaloUser?.uid" class="flex items-center justify-between py-2 border-b border-gray-100">
              <span class="text-gray-600">User ID</span>
              <span class="text-gray-900 font-medium text-xs">{{ zaloUser.uid }}</span>
            </div>
          </div>

          <!-- Info badge showing data source -->
          <div class="mt-3 px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-xs text-blue-700">
              <svg class="inline-block w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
              Thông tin từ tài khoản Zalo
            </p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3">
          <button
            @click="close"
            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition"
          >
            {{ t('common.close') || 'Đóng' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from '../../composables/useI18n';

const { t } = useI18n();

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  zaloUser: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close']);

const displayName = computed(() => {
  return props.zaloUser?.display_name || 'Unknown';
});

const avatar = computed(() => {
  return props.zaloUser?.avatar;
});

const coverPhoto = computed(() => {
  return props.zaloUser?.cover || null;
});

const status = computed(() => {
  return props.zaloUser?.status || null;
});

const gender = computed(() => {
  if (props.zaloUser?.gender === undefined || props.zaloUser?.gender === null) return '-';

  // Gender mapping similar to CustomerProfileModal
  const genderMap = {
    'male': 'Nam',
    'female': 'Nữ',
    'other': 'Khác',
    0: 'Nam',
    1: 'Nữ',
  };

  // Return mapped value or raw value
  return genderMap[props.zaloUser.gender] !== undefined ? genderMap[props.zaloUser.gender] : props.zaloUser.gender;
});

const dateOfBirth = computed(() => {
  return props.zaloUser?.sdob || null;
});

const close = () => {
  emit('close');
};
</script>
