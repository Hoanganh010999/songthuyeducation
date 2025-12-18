<template>
  <Transition name="modal-fade">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 p-4"
      @click.self="close"
    >
      <div
        class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"
        @click.stop
      >
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white z-10">
          <h3 class="text-xl font-semibold text-gray-800">
            {{ isEdit ? t('roles.edit') : t('roles.create') }}
          </h3>
          <button
            @click="close"
            class="p-2 rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="saveRole" class="p-6 space-y-6">
          <!-- Name -->
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('roles.name') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              id="name"
              :disabled="isEdit && ['super-admin', 'admin'].includes(role?.name)"
              :placeholder="t('roles.name_placeholder')"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
              :class="{ 'bg-gray-100': isEdit && ['super-admin', 'admin'].includes(role?.name) }"
              required
            />
            <p class="text-xs text-gray-500 mt-1">{{ t('roles.name_hint') }}</p>
          </div>

          <!-- Display Name -->
          <div>
            <label for="display_name" class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('roles.display_name') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.display_name"
              type="text"
              id="display_name"
              :placeholder="t('roles.display_name_placeholder')"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
              required
            />
          </div>

          <!-- Description -->
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('roles.description_label') }}
            </label>
            <textarea
              v-model="form.description"
              id="description"
              rows="3"
              :placeholder="t('roles.description_placeholder')"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
            ></textarea>
          </div>

          <!-- Is Active -->
          <div class="flex items-center">
            <input
              v-model="form.is_active"
              type="checkbox"
              id="is_active"
              class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
            />
            <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">
              {{ t('roles.is_active') }}
            </label>
          </div>

          <!-- Error Message -->
          <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600">{{ error }}</p>
          </div>

          <!-- Actions -->
          <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
            <button
              type="button"
              @click="close"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 transition disabled:opacity-50 flex items-center gap-2"
            >
              <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ loading ? t('common.saving') : t('common.save') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch } from 'vue';
import api from '../../services/api';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  role: {
    type: Object,
    default: null,
  },
  isEdit: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close', 'saved']);

const { t } = useI18n();
const swal = useSwal();

const form = ref({
  name: '',
  display_name: '',
  description: '',
  is_active: true,
});

const loading = ref(false);
const error = ref('');

const resetForm = () => {
  form.value = {
    name: '',
    display_name: '',
    description: '',
    is_active: true,
  };
  error.value = '';
};

const saveRole = async () => {
  loading.value = true;
  error.value = '';

  try {
    let response;
    if (props.isEdit) {
      response = await api.put(`/api/roles/${props.role.id}`, form.value);
    } else {
      response = await api.post('/api/roles', form.value);
    }

    if (response.data.success) {
      swal.success(response.data.message);
      emit('saved');
    } else {
      error.value = response.data.message || 'Failed to save role.';
    }
  } catch (err) {
    console.error('Role save error:', err);
    error.value = err.response?.data?.message || 'An unexpected error occurred.';
    if (err.response?.data?.errors) {
      const errors = Object.values(err.response.data.errors).flat();
      error.value += '\n' + errors.join('\n');
    }
  } finally {
    loading.value = false;
  }
};

const close = () => {
  emit('close');
};

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      if (props.isEdit && props.role) {
        form.value = {
          name: props.role.name,
          display_name: props.role.display_name || '',
          description: props.role.description || '',
          is_active: props.role.is_active ?? true,
        };
      } else {
        resetForm();
      }
    }
  },
  { immediate: true }
);
</script>