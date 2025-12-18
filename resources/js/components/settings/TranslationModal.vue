<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click.self="$emit('close')">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <!-- Header -->
        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
          {{ isEdit ? 'Edit Translation' : 'Add Translation' }}
        </h3>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Language -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Language *</label>
            <select
              v-model="form.language_id"
              required
              :disabled="isEdit"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
            >
              <option value="">Select Language</option>
              <option v-for="lang in languages" :key="lang.id" :value="lang.id">
                {{ lang.flag }} {{ lang.name }}
              </option>
            </select>
          </div>

          <!-- Group -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Group *</label>
            <input
              v-model="form.group"
              type="text"
              required
              list="groups-list"
              :disabled="isEdit"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
              placeholder="common, auth, users, etc."
            />
            <datalist id="groups-list">
              <option v-for="group in groups" :key="group" :value="group" />
            </datalist>
            <p class="text-xs text-gray-500 mt-1">Group name (e.g., common, auth, users)</p>
          </div>

          <!-- Key -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Key *</label>
            <input
              v-model="form.key"
              type="text"
              required
              :disabled="isEdit"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
              placeholder="welcome_message"
            />
            <p class="text-xs text-gray-500 mt-1">Translation key (e.g., welcome_message)</p>
          </div>

          <!-- Value -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Value *</label>
            <textarea
              v-model="form.value"
              required
              rows="4"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter translation text..."
            ></textarea>
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
              Cancel
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50"
            >
              {{ loading ? 'Saving...' : 'Save' }}
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

const props = defineProps({
  translation: {
    type: Object,
    default: null,
  },
  languages: {
    type: Array,
    required: true,
  },
  groups: {
    type: Array,
    default: () => [],
  },
  isEdit: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close', 'saved']);

const { loadTranslations, currentLanguageCode } = useI18n();
const swal = useSwal();

const form = ref({
  language_id: '',
  group: '',
  key: '',
  value: '',
});

const loading = ref(false);
const error = ref('');

const handleSubmit = async () => {
  loading.value = true;
  error.value = '';

  try {
    let response;
    if (props.isEdit) {
      response = await api.put(`/api/settings/translations/${props.translation.id}`, {
        value: form.value.value, // Only update value in edit mode
      });
    } else {
      response = await api.post('/api/settings/translations', form.value);
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

onMounted(() => {
  if (props.isEdit && props.translation) {
    form.value = {
      language_id: props.translation.language_id,
      group: props.translation.group,
      key: props.translation.key,
      value: props.translation.value,
    };
  }
});
</script>

