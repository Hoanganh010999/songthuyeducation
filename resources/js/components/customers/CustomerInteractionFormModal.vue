<template>
  <Transition name="modal">
    <div
      v-if="show"
      class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900 bg-opacity-50 p-4"
      @click.self="close"
    >
      <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <!-- Header -->
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
          <h3 class="text-lg font-bold text-gray-900">
            {{ t('customers.add_interaction') }}
          </h3>
          <button @click="close" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="save" class="p-6 space-y-4">
          <!-- Customer Info (readonly) -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm text-blue-800">
              <strong>{{ t('customers.customer') }}:</strong> {{ customer?.name }}
            </p>
            <p v-if="customer?.phone" class="text-sm text-blue-700 mt-1">
              <strong>{{ t('customers.phone') }}:</strong> {{ customer.phone }}
            </p>
          </div>

          <!-- Interaction Date & Time -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.interaction_date') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.interaction_date"
              type="datetime-local"
              required
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <!-- Interaction Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.interaction_type') }} <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.interaction_type_id"
              required
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="">{{ t('common.select') }}</option>
              <option v-for="type in interactionTypes" :key="type.id" :value="type.id">
                {{ getIconEmoji(type.icon) }} {{ type.name }}
              </option>
            </select>
          </div>

          <!-- Interaction Result -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.interaction_result') }} <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.interaction_result_id"
              required
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="">{{ t('common.select') }}</option>
              <option v-for="result in interactionResults" :key="result.id" :value="result.id">
                {{ getIconEmoji(result.icon) }} {{ result.name }}
              </option>
            </select>
          </div>

          <!-- Notes -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.notes') }} <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="form.notes"
              rows="5"
              required
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :placeholder="t('customers.notes_placeholder')"
            ></textarea>
          </div>

          <!-- Next Follow Up -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.next_follow_up') }}
            </label>
            <input
              v-model="form.next_follow_up"
              type="datetime-local"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <p class="text-xs text-gray-500 mt-1">{{ t('customers.next_follow_up_hint') }}</p>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <button
              type="button"
              @click="close"
              class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 flex items-center gap-2"
            >
              <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ loading ? t('common.saving') : t('common.save') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';
import api from '../../services/api';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  customer: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'saved']);

const { t } = useI18n();
const swal = useSwal();

const loading = ref(false);
const interactionTypes = ref([]);
const interactionResults = ref([]);

const form = ref({
  interaction_type_id: '',
  interaction_result_id: '',
  notes: '',
  interaction_date: '',
  next_follow_up: '',
});

const iconEmojiMap = {
  phone: '📞',
  envelope: '✉️',
  message: '💬',
  users: '👥',
  comment: '💬',
  facebook: '📘',
  store: '🏪',
  'check-circle': '✅',
  'phone-slash': '📵',
  calendar: '📅',
  'times-circle': '❌',
  clock: '⏰',
  'info-circle': 'ℹ️',
  ban: '🚫',
  google: '🔍',
  'user-friends': '👫',
  walking: '🚶',
  globe: '🌐',
  'calendar-star': '🎉',
  'ellipsis-h': '⋯',
};

const getIconEmoji = (icon) => {
  return iconEmojiMap[icon] || '📌';
};

const loadInteractionTypes = async () => {
  try {
    const response = await api.get('/api/customers/settings/interaction-types');
    if (response.data.success) {
      interactionTypes.value = response.data.data.filter(t => t.is_active);
    }
  } catch (error) {
    console.error('Failed to load interaction types:', error);
  }
};

const loadInteractionResults = async () => {
  try {
    const response = await api.get('/api/customers/settings/interaction-results');
    if (response.data.success) {
      interactionResults.value = response.data.data.filter(r => r.is_active);
    }
  } catch (error) {
    console.error('Failed to load interaction results:', error);
  }
};

const save = async () => {
  if (!props.customer?.id) return;
  
  loading.value = true;
  try {
    // Convert datetime-local to ISO string with timezone
    const payload = {
      ...form.value,
      interaction_date: form.value.interaction_date ? new Date(form.value.interaction_date).toISOString() : null,
      next_follow_up: form.value.next_follow_up ? new Date(form.value.next_follow_up).toISOString() : null,
    };
    
    const response = await api.post(
      `/api/customers/${props.customer.id}/interactions`,
      payload
    );
    
    if (response.data.success) {
      swal.success(response.data.message);
      emit('saved');
    }
  } catch (error) {
    console.error('Failed to save interaction:', error);
    swal.error(error.response?.data?.message || t('customers.error_save_interaction'));
  } finally {
    loading.value = false;
  }
};

const close = () => {
  emit('close');
};

const resetForm = () => {
  // Set default date to now
  const now = new Date();
  const localDate = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
  const formattedDate = localDate.toISOString().slice(0, 16);
  
  form.value = {
    interaction_type_id: '',
    interaction_result_id: '',
    notes: '',
    interaction_date: formattedDate,
    next_follow_up: '',
  };
};

watch(() => props.show, (newVal) => {
  if (newVal) {
    resetForm();
    loadInteractionTypes();
    loadInteractionResults();
  }
});
</script>