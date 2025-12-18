<template>
  <div v-if="show && user" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="close">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white" @click.stop>
      <!-- Modal Header -->
      <div class="flex items-center justify-between mb-4 pb-3 border-b">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ user.google_email ? t('users.update_google_email') : t('users.assign_google_email') }}
        </h3>
        <button @click="close" class="text-gray-400 hover:text-gray-600 transition">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- User Info -->
      <div class="mb-4 p-3 bg-blue-50 rounded-lg">
        <div class="text-sm text-gray-700">
          <strong>{{ t('users.user') }}:</strong> {{ user.name }}
        </div>
        <div class="text-sm text-gray-700 mt-1">
          <strong>{{ t('users.phone') }}:</strong> {{ user.phone || t('common.not_set') }}
        </div>
        <div v-if="user.google_email" class="text-sm text-gray-700 mt-1">
          <strong>{{ t('users.current_google_email') }}:</strong> {{ user.google_email }}
        </div>
        <div v-if="user.google_drive_folder_id" class="text-sm text-green-600 mt-1 flex items-center">
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ t('users.folder_already_created') }}
        </div>
      </div>

      <!-- Warning if no phone (only for non-students) -->
      <div v-if="phoneRequired" class="mb-4 p-3 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
        <div class="flex">
          <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <span>{{ t('users.phone_required_warning') }}</span>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit">
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-medium mb-2">
            {{ t('users.google_email') }} <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.google_email"
            type="email"
            required
            :disabled="phoneRequired || submitting"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
            :placeholder="t('users.enter_google_email')"
          />
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3 mt-6">
          <button
            type="button"
            @click="close"
            :disabled="submitting"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 disabled:opacity-50 transition"
          >
            {{ t('common.cancel') }}
          </button>
          <button
            v-if="user.google_email"
            type="button"
            @click="handleRemove"
            :disabled="submitting"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 transition"
          >
            <svg v-if="submitting && removing" class="animate-spin inline h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ t('common.remove') }}
          </button>
          <button
            type="submit"
            :disabled="phoneRequired || !form.google_email || submitting"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            <svg v-if="submitting && !removing" class="animate-spin inline h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ user.google_email ? t('common.update') : t('common.assign') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';
import Swal from 'sweetalert2';

const { t } = useI18n();

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  user: {
    type: Object,
    default: () => null
  }
});

const emit = defineEmits(['close', 'updated']);

const form = ref({
  google_email: ''
});

const submitting = ref(false);
const removing = ref(false);

// Check if user is a student (students don't need phone number)
const isStudent = computed(() => {
  return props.user?.is_student || false;
});

// Phone is required for non-students only
const phoneRequired = computed(() => {
  return !isStudent.value && !props.user?.phone;
});

// Watch for changes in user prop to update form
watch(() => props.user, (newUser) => {
  if (newUser) {
    form.value.google_email = newUser.google_email || '';
  }
}, { immediate: true });

const close = () => {
  if (!submitting.value) {
    emit('close');
  }
};

const handleSubmit = async () => {
  if (phoneRequired.value) {
    await Swal.fire({
      icon: 'warning',
      title: t('common.warning'),
      text: t('users.phone_required_warning'),
    });
    return;
  }

  submitting.value = true;
  removing.value = false;

  try {
    let response;
    
    if (props.user.google_email) {
      // Update existing
      response = await axios.put(`/api/users/${props.user.id}/google-email`, form.value);
    } else {
      // Assign new
      response = await axios.post(`/api/users/${props.user.id}/google-email`, form.value);
    }

    if (response.data.success) {
      await Swal.fire({
        icon: 'success',
        title: t('common.success'),
        text: response.data.message,
        timer: 2000,
        showConfirmButton: false,
      });

      emit('updated', response.data.data);
      emit('close');
    }
  } catch (error) {
    console.error('Error assigning/updating Google email:', error);
    
    // Check if folder already exists (409 Conflict)
    if (error.response?.status === 409 && error.response?.data?.folder_exists) {
      const result = await Swal.fire({
        icon: 'question',
        title: error.response.data.message,
        text: error.response.data.question,
        html: `
          <p class="mb-4">${error.response.data.question}</p>
          <p class="text-sm text-gray-600"><strong>Folder:</strong> ${error.response.data.folder_name}</p>
        `,
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: t('common.use_existing') || 'Sử dụng folder cũ',
        denyButtonText: t('common.create_new') || 'Tạo folder mới',
        cancelButtonText: t('common.cancel'),
        confirmButtonColor: '#10b981',
        denyButtonColor: '#3b82f6',
      });
      
      if (result.isConfirmed || result.isDenied) {
        // Call force endpoint
        submitting.value = true;
        try {
          const forceResponse = await axios.post(`/api/users/${props.user.id}/google-email/force`, {
            google_email: form.value.google_email,
            use_existing: result.isConfirmed,
            existing_folder_id: result.isConfirmed ? error.response.data.existing_folder_id : null,
          });
          
          if (forceResponse.data.success) {
            await Swal.fire({
              icon: 'success',
              title: t('common.success'),
              text: forceResponse.data.message,
              timer: 2000,
              showConfirmButton: false,
            });
            
            emit('updated', forceResponse.data.data);
            emit('close');
          }
        } catch (forceError) {
          await Swal.fire({
            icon: 'error',
            title: t('common.error'),
            text: forceError.response?.data?.message || t('common.error_occurred'),
          });
        } finally {
          submitting.value = false;
        }
      } else {
        submitting.value = false;
      }
      return;
    }
    
    const errorMessage = error.response?.data?.message || t('errors.google_email_assignment_failed');
    
    await Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: errorMessage,
    });
  } finally {
    submitting.value = false;
  }
};

const handleRemove = async () => {
  const result = await Swal.fire({
    icon: 'warning',
    title: t('common.confirm'),
    text: t('users.confirm_remove_google_email'),
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: t('common.yes_remove'),
    cancelButtonText: t('common.cancel'),
  });

  if (!result.isConfirmed) {
    return;
  }

  submitting.value = true;
  removing.value = true;

  try {
    const response = await axios.delete(`/api/users/${props.user.id}/google-email`);

    if (response.data.success) {
      await Swal.fire({
        icon: 'success',
        title: t('common.success'),
        text: response.data.message,
        timer: 2000,
        showConfirmButton: false,
      });

      emit('updated', { ...props.user, google_email: null });
      emit('close');
    }
  } catch (error) {
    console.error('Error removing Google email:', error);
    
    const errorMessage = error.response?.data?.message || t('errors.google_email_removal_failed');
    
    await Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: errorMessage,
    });
  } finally {
    submitting.value = false;
    removing.value = false;
  }
};
</script>

