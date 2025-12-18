<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <!-- Backdrop -->
      <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="$emit('close')"></div>

      <!-- Modal -->
      <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full z-10">
        <!-- Header -->
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">
              {{ t('users.reset_password') }}
            </h3>
            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-500">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Body -->
        <div class="bg-white px-6 py-4">
          <div v-if="user" class="mb-4">
            <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
              <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center">
                <span class="text-white font-semibold">
                  {{ getUserInitials(user.name) }}
                </span>
              </div>
              <div>
                <div class="font-medium text-gray-900">{{ user.name }}</div>
                <div class="text-sm text-gray-500">{{ user.email }}</div>
              </div>
            </div>
          </div>

          <!-- Reset Type -->
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('users.reset_type') }}
              </label>
              <div class="space-y-2">
                <label class="flex items-center cursor-pointer">
                  <input
                    type="radio"
                    v-model="form.reset_type"
                    value="default"
                    class="w-4 h-4 text-blue-600 focus:ring-blue-500"
                  />
                  <span class="ml-2 text-sm text-gray-700">
                    {{ t('users.reset_to_default') }}
                  </span>
                </label>
                <label class="flex items-center cursor-pointer">
                  <input
                    type="radio"
                    v-model="form.reset_type"
                    value="custom"
                    class="w-4 h-4 text-blue-600 focus:ring-blue-500"
                  />
                  <span class="ml-2 text-sm text-gray-700">
                    {{ t('users.set_custom_password') }}
                  </span>
                </label>
              </div>
            </div>

            <!-- Custom Password Input -->
            <div v-if="form.reset_type === 'custom'" class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ t('users.new_password') }}
              </label>
              <input
                type="text"
                v-model="form.new_password"
                :placeholder="t('users.enter_new_password')"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <p class="text-xs text-gray-500">
                {{ t('users.password_min_length') }}
              </p>
            </div>

            <!-- Default Password Info -->
            <div v-if="form.reset_type === 'default'" class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
              <div class="flex items-start space-x-2">
                <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-blue-800">
                  <p class="font-medium mb-1">{{ t('users.default_password_info') }}:</p>
                  <ul class="list-disc ml-4 space-y-1 text-xs">
                    <li>{{ t('users.default_password_rule_1') }}</li>
                    <li>{{ t('users.default_password_rule_2') }}</li>
                    <li>{{ t('users.default_password_rule_3') }}</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3 border-t border-gray-200">
          <button
            @click="$emit('close')"
            type="button"
            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
          >
            {{ t('common.cancel') }}
          </button>
          <button
            @click="handleReset"
            :disabled="loading || (form.reset_type === 'custom' && !form.new_password)"
            type="button"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="loading" class="flex items-center space-x-2">
              <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ t('common.processing') }}</span>
            </span>
            <span v-else>{{ t('users.reset_password') }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useI18n } from '../../composables/useI18n';
import api from '../../services/api';
import Swal from 'sweetalert2';

const { t } = useI18n();

const props = defineProps({
  show: Boolean,
  user: Object,
});

const emit = defineEmits(['close', 'success']);

const form = ref({
  reset_type: 'default',
  new_password: '',
});

const loading = ref(false);

watch(() => props.show, (newVal) => {
  console.log('🔍 ResetPasswordModal - show changed:', newVal);
  console.log('👤 ResetPasswordModal - user prop:', props.user);
  if (newVal) {
    // Reset form when modal opens
    form.value = {
      reset_type: 'default',
      new_password: '',
    };
    console.log('✅ ResetPasswordModal - Form reset:', form.value);
  }
});

const getUserInitials = (name) => {
  if (!name) return '?';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const handleReset = async () => {
  if (form.value.reset_type === 'custom' && !form.value.new_password) {
    Swal.fire({
      icon: 'warning',
      title: t('common.warning'),
      text: t('users.please_enter_password'),
    });
    return;
  }

  if (form.value.reset_type === 'custom' && form.value.new_password.length < 6) {
    Swal.fire({
      icon: 'warning',
      title: t('common.warning'),
      text: t('users.password_min_length'),
    });
    return;
  }

  loading.value = true;

  try {
    const response = await api.post(`/api/users/${props.user.id}/reset-password`, form.value);
    
    const message = response.data.message;
    const defaultPassword = response.data.data.default_password;

    await Swal.fire({
      icon: 'success',
      title: t('common.success'),
      html: defaultPassword 
        ? `${message}<br><br><strong>${t('users.new_password')}:</strong> <code style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-size: 16px;">${defaultPassword}</code>`
        : message,
      confirmButtonText: t('common.ok'),
    });

    emit('success');
    emit('close');
  } catch (error) {
    console.error('Error resetting password:', error);
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || t('common.error_occurred'),
    });
  } finally {
    loading.value = false;
  }
};
</script>

