<template>
  <div class="fixed inset-0 z-[60] overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="$emit('close')"></div>

      <!-- Modal -->
      <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">
              {{ isEdit ? t('settings.edit_translation') : t('settings.add_translation') }}
            </h3>
            <button
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-600 transition"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Body -->
        <div class="px-6 py-4">
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <!-- Language (Read-only) -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ t('settings.language') }}
              </label>
              <div class="flex items-center space-x-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg">
                <span class="text-2xl">{{ language.flag }}</span>
                <span class="font-medium">{{ language.name }}</span>
                <span class="text-xs text-gray-500">({{ language.code }})</span>
              </div>
            </div>

            <!-- Group -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ t('settings.group') }} <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.group"
                :disabled="isEdit"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
              >
                <option value="">{{ t('settings.select_group') }}</option>
                <option v-for="group in groups" :key="group" :value="group">
                  {{ group }}
                </option>
                <option value="__new__">+ {{ t('settings.new_group') }}</option>
              </select>
            </div>

            <!-- New Group Name (if selected) -->
            <div v-if="form.group === '__new__'">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ t('settings.new_group_name') }} <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.newGroupName"
                type="text"
                required
                placeholder="e.g., navigation, forms, etc."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Key -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ t('settings.key') }} <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.key"
                type="text"
                :disabled="isEdit"
                required
                placeholder="e.g., welcome_message, button_save"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm disabled:bg-gray-100"
              />
              <p class="mt-1 text-xs text-gray-500">{{ t('settings.key_hint') }}</p>
            </div>

            <!-- Value -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ t('settings.value') }} <span class="text-red-500">*</span>
              </label>
              <textarea
                v-model="form.value"
                required
                rows="3"
                :placeholder="t('settings.value_placeholder')"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              ></textarea>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg">
              <p class="text-sm text-red-600">{{ error }}</p>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3 pt-4">
              <button
                type="button"
                @click="$emit('close')"
                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
              >
                {{ t('common.cancel') }}
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="loading">{{ t('common.saving') }}...</span>
                <span v-else>{{ t('common.save') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import api from '../../services/api';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';

const props = defineProps({
  language: {
    type: Object,
    required: true
  },
  translation: {
    type: Object,
    default: null
  },
  isEdit: {
    type: Boolean,
    default: false
  },
  groups: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['close', 'saved']);

const { t, loadTranslations, currentLanguageCode } = useI18n();
const swal = useSwal();

const form = ref({
  language_id: props.language.id,
  group: '',
  newGroupName: '',
  key: '',
  value: ''
});

const loading = ref(false);
const error = ref('');

// Initialize form if editing
watch(() => props.translation, (translation) => {
  if (translation && props.isEdit) {
    form.value = {
      language_id: props.language.id,
      group: translation.group,
      newGroupName: '',
      key: translation.key,
      value: translation.value
    };
  }
}, { immediate: true });

const handleSubmit = async () => {
  loading.value = true;
  error.value = '';

  try {
    // Handle new group
    let group = form.value.group;
    if (group === '__new__') {
      if (!form.value.newGroupName) {
        error.value = 'Please enter a group name';
        loading.value = false;
        return;
      }
      group = form.value.newGroupName.toLowerCase().replace(/\s+/g, '_');
    }

    const data = {
      language_id: form.value.language_id,
      group: group,
      key: form.value.key,
      value: form.value.value
    };

    let response;
    if (props.isEdit) {
      response = await api.put(`/api/settings/translations/${props.translation.id}`, {
        value: data.value
      });
    } else {
      response = await api.post('/api/settings/translations', data);
    }

    if (response.data.success) {
      swal.success(response.data.message);
      
      // Clear localStorage cache and reload translations
      localStorage.removeItem('app_translations');
      await loadTranslations(currentLanguageCode.value);
      
      emit('saved');
    }
  } catch (err) {
    console.error('Failed to save translation:', err);
    error.value = err.response?.data?.message || 'Failed to save translation';
    swal.error(error.value);
  } finally {
    loading.value = false;
  }
};
</script>

