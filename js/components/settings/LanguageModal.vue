<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click.self="$emit('close')">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <!-- Header -->
        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
          {{ isEdit ? t('settings.edit_language') : t('settings.add_language') }}
        </h3>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('settings.language_name') }} *
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="English"
            />
          </div>

          <!-- Code -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('settings.language_code') }} *
            </label>
            <input
              v-model="form.code"
              type="text"
              required
              maxlength="10"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="en"
            />
            <p class="text-xs text-gray-500 mt-1">ISO 639-1 code (e.g., en, vi, fr)</p>
          </div>

          <!-- Flag -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('settings.language_flag') }}
            </label>
            <input
              v-model="form.flag"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="🇬🇧"
            />
            <p class="text-xs text-gray-500 mt-1">Emoji flag or URL to flag icon</p>
          </div>

          <!-- Direction -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('settings.language_direction') }}
            </label>
            <select
              v-model="form.direction"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="ltr">Left to Right (LTR)</option>
              <option value="rtl">Right to Left (RTL)</option>
            </select>
          </div>

          <!-- Sort Order -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('settings.sort_order') }}
            </label>
            <input
              v-model.number="form.sort_order"
              type="number"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="0"
            />
          </div>

          <!-- Checkboxes -->
          <div class="space-y-2">
            <label class="flex items-center">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700">{{ t('settings.is_active') }}</span>
            </label>

            <label class="flex items-center">
              <input
                v-model="form.is_default"
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700">{{ t('settings.is_default') }}</span>
            </label>
          </div>

          <!-- Error Message -->
          <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-md">
            <p class="text-sm text-red-600">{{ error }}</p>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-2 pt-4">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50"
            >
              {{ loading ? t('common.loading') : t('common.save') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../services/api';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';

const { t } = useI18n();
const swal = useSwal();

const props = defineProps({
  language: {
    type: Object,
    default: null,
  },
  isEdit: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close', 'saved']);

const form = ref({
  name: '',
  code: '',
  flag: '',
  direction: 'ltr',
  is_active: true,
  is_default: false,
  sort_order: 0,
});

const loading = ref(false);
const error = ref('');

const handleSubmit = async () => {
  loading.value = true;
  error.value = '';

  try {
    let response;
    if (props.isEdit) {
      response = await api.put(`/api/settings/languages/${props.language.id}`, form.value);
    } else {
      response = await api.post('/api/settings/languages', form.value);
    }

    if (response.data.success) {
      swal.success(response.data.message);
      emit('saved');
    }
  } catch (err) {
    console.error('Failed to save language:', err);
    error.value = err.response?.data?.message || 'Failed to save language';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  if (props.isEdit && props.language) {
    form.value = { ...props.language };
  }
});
</script>

