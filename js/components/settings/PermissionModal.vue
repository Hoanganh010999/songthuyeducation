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
            {{ isEdit ? t('permissions.edit') : t('permissions.create') }}
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
        <form @submit.prevent="savePermission" class="p-6 space-y-6">
          <!-- Module -->
          <div>
            <label for="module" class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('permissions.module') }} <span class="text-red-500">*</span>
            </label>
            <div class="flex gap-2">
              <select
                v-model="form.module"
                id="module"
                :disabled="isEdit"
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                :class="{ 'bg-gray-100': isEdit }"
                required
              >
                <option value="">{{ t('permissions.select_module') }}</option>
                <option v-for="mod in modules" :key="mod" :value="mod">{{ mod }}</option>
                <option value="__new__">{{ t('permissions.new_module') }}</option>
              </select>
            </div>
          </div>

          <!-- New Module Name -->
          <div v-if="form.module === '__new__'">
            <label for="new_module" class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('permissions.new_module_name') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.new_module"
              type="text"
              id="new_module"
              :placeholder="t('permissions.new_module_placeholder')"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            />
            <p class="text-xs text-gray-500 mt-1">{{ t('permissions.module_hint') }}</p>
          </div>

          <!-- Action -->
          <div>
            <label for="action" class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('permissions.action') }} <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.action"
              id="action"
              :disabled="isEdit"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'bg-gray-100': isEdit }"
              required
            >
              <option value="">{{ t('permissions.select_action') }}</option>
              <option value="view">view</option>
              <option value="create">create</option>
              <option value="edit">edit</option>
              <option value="delete">delete</option>
              <option value="export">export</option>
              <option value="import">import</option>
              <option value="manage">manage</option>
            </select>
          </div>

          <!-- Permission Name (Auto-generated) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('permissions.permission_name') }}
            </label>
            <div class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg">
              <code class="text-sm font-mono text-gray-900">
                {{ generatedName }}
              </code>
            </div>
            <p class="text-xs text-gray-500 mt-1">{{ t('permissions.auto_generated') }}</p>
          </div>

          <!-- Display Name -->
          <div>
            <label for="display_name" class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('permissions.display_name') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.display_name"
              type="text"
              id="display_name"
              :placeholder="t('permissions.display_name_placeholder')"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            />
          </div>

          <!-- Description -->
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('permissions.description_label') }}
            </label>
            <textarea
              v-model="form.description"
              id="description"
              rows="3"
              :placeholder="t('permissions.description_placeholder')"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>

          <!-- Is Active -->
          <div class="flex items-center">
            <input
              v-model="form.is_active"
              type="checkbox"
              id="is_active"
              class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">
              {{ t('permissions.is_active') }}
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
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 flex items-center gap-2"
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
import { ref, computed, watch } from 'vue';
import api from '../../services/api';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  permission: {
    type: Object,
    default: null,
  },
  isEdit: {
    type: Boolean,
    default: false,
  },
  modules: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['close', 'saved']);

const { t } = useI18n();
const swal = useSwal();

const form = ref({
  module: '',
  new_module: '',
  action: '',
  display_name: '',
  description: '',
  is_active: true,
});

const loading = ref(false);
const error = ref('');

const generatedName = computed(() => {
  const module = form.value.module === '__new__' ? form.value.new_module : form.value.module;
  const action = form.value.action;
  if (module && action) {
    return `${module}.${action}`;
  }
  return t('permissions.enter_module_action');
});

const resetForm = () => {
  form.value = {
    module: '',
    new_module: '',
    action: '',
    display_name: '',
    description: '',
    is_active: true,
  };
  error.value = '';
};

const savePermission = async () => {
  loading.value = true;
  error.value = '';

  try {
    const payload = {
      module: form.value.module === '__new__' ? form.value.new_module : form.value.module,
      action: form.value.action,
      display_name: form.value.display_name,
      description: form.value.description,
      is_active: form.value.is_active,
    };

    let response;
    if (props.isEdit) {
      response = await api.put(`/api/permissions/${props.permission.id}`, payload);
    } else {
      response = await api.post('/api/permissions', payload);
    }

    if (response.data.success) {
      swal.success(response.data.message);
      emit('saved');
    } else {
      error.value = response.data.message || 'Failed to save permission.';
    }
  } catch (err) {
    console.error('Permission save error:', err);
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
      console.log('📝 Permission Modal opened');
      console.log('📋 Available modules:', props.modules);
      console.log('🔧 Is Edit:', props.isEdit);
      if (props.isEdit && props.permission) {
        console.log('✏️ Editing permission:', props.permission);
        form.value = {
          module: props.permission.module,
          new_module: '',
          action: props.permission.action,
          display_name: props.permission.display_name || '',
          description: props.permission.description || '',
          is_active: props.permission.is_active ?? true,
        };
      } else {
        console.log('➕ Creating new permission');
        resetForm();
      }
    }
  },
  { immediate: true }
);

// Watch modules prop changes
watch(
  () => props.modules,
  (newModules) => {
    console.log('📋 Modules updated:', newModules);
  }
);
</script>