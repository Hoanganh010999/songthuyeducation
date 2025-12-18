<template>
  <Transition name="modal">
    <div
      v-if="show"
      class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900 bg-opacity-50 p-4"
      @click.self="close"
    >
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
        <!-- Header -->
        <div class="border-b px-6 py-4 flex items-center justify-between">
          <h3 class="text-lg font-bold text-gray-900">
            {{ isEdit ? t('common.edit') : t('common.add') }} {{ getTypeLabel() }}
          </h3>
          <button @click="close" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="save" class="p-6 space-y-4">
          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('common.name') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :placeholder="t('common.name_placeholder')"
            />
          </div>

          <!-- Code -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('common.code') }}
            </label>
            <input
              v-model="form.code"
              type="text"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :placeholder="t('common.code_placeholder')"
            />
            <p class="text-xs text-gray-500 mt-1">{{ t('common.code_hint') }}</p>
          </div>

          <!-- Color -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('common.color') }}
            </label>
            <div class="flex gap-2">
              <input
                v-model="form.color"
                type="color"
                class="h-10 w-20 border rounded cursor-pointer"
              />
              <input
                v-model="form.color"
                type="text"
                class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                placeholder="#3B82F6"
              />
            </div>
          </div>

          <!-- Icon -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('common.icon') }}
            </label>
            <select
              v-model="form.icon"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="">{{ t('common.select_icon') }}</option>
              <option v-for="icon in availableIcons" :key="icon.value" :value="icon.value">
                {{ icon.emoji }} {{ icon.label }}
              </option>
            </select>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('common.description') }}
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :placeholder="t('common.description_placeholder')"
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
            <label for="is_active" class="ml-2 text-sm text-gray-700">
              {{ t('common.is_active') }}
            </label>
          </div>

          <!-- Sort Order -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('common.sort_order') }}
            </label>
            <input
              v-model.number="form.sort_order"
              type="number"
              min="0"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="0"
            />
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
  type: {
    type: String,
    required: true, // 'interaction-type', 'interaction-result', 'source'
  },
  item: {
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

const loading = ref(false);
const form = ref({
  name: '',
  code: '',
  icon: '',
  color: '#3B82F6',
  description: '',
  is_active: true,
  sort_order: 0,
});

const availableIcons = [
  { value: 'phone', emoji: '📞', label: 'Phone' },
  { value: 'envelope', emoji: '✉️', label: 'Email' },
  { value: 'message', emoji: '💬', label: 'Message' },
  { value: 'users', emoji: '👥', label: 'Users' },
  { value: 'comment', emoji: '💬', label: 'Comment' },
  { value: 'facebook', emoji: '📘', label: 'Facebook' },
  { value: 'store', emoji: '🏪', label: 'Store' },
  { value: 'check-circle', emoji: '✅', label: 'Check' },
  { value: 'phone-slash', emoji: '📵', label: 'No Phone' },
  { value: 'calendar', emoji: '📅', label: 'Calendar' },
  { value: 'times-circle', emoji: '❌', label: 'Times' },
  { value: 'clock', emoji: '⏰', label: 'Clock' },
  { value: 'info-circle', emoji: 'ℹ️', label: 'Info' },
  { value: 'ban', emoji: '🚫', label: 'Ban' },
  { value: 'google', emoji: '🔍', label: 'Google' },
  { value: 'user-friends', emoji: '👫', label: 'Friends' },
  { value: 'walking', emoji: '🚶', label: 'Walking' },
  { value: 'globe', emoji: '🌐', label: 'Globe' },
  { value: 'calendar-star', emoji: '🎉', label: 'Event' },
  { value: 'ellipsis-h', emoji: '⋯', label: 'More' },
];

const getTypeLabel = () => {
  if (props.type === 'interaction-type') return t('customers.interaction_type');
  if (props.type === 'interaction-result') return t('customers.interaction_result');
  if (props.type === 'source') return t('customers.source');
  return '';
};

const getEndpoint = () => {
  if (props.type === 'interaction-type') {
    return props.isEdit 
      ? `/api/customers/settings/interaction-types/${props.item.id}`
      : '/api/customers/settings/interaction-types';
  }
  if (props.type === 'interaction-result') {
    return props.isEdit
      ? `/api/customers/settings/interaction-results/${props.item.id}`
      : '/api/customers/settings/interaction-results';
  }
  if (props.type === 'source') {
    return props.isEdit
      ? `/api/customers/settings/sources/${props.item.id}`
      : '/api/customers/settings/sources';
  }
  return '';
};

const save = async () => {
  loading.value = true;
  try {
    const endpoint = getEndpoint();
    const method = props.isEdit ? 'put' : 'post';
    
    const response = await api[method](endpoint, form.value);
    
    if (response.data.success) {
      swal.success(response.data.message);
      emit('saved');
    }
  } catch (error) {
    console.error('Failed to save:', error);
    swal.error(error.response?.data?.message || 'Có lỗi xảy ra khi lưu');
  } finally {
    loading.value = false;
  }
};

const close = () => {
  emit('close');
};

watch(() => props.show, (newVal) => {
  if (newVal) {
    if (props.isEdit && props.item) {
      form.value = {
        name: props.item.name || '',
        code: props.item.code || '',
        icon: props.item.icon || '',
        color: props.item.color || '#3B82F6',
        description: props.item.description || '',
        is_active: props.item.is_active ?? true,
        sort_order: props.item.sort_order || 0,
      };
    } else {
      form.value = {
        name: '',
        code: '',
        icon: '',
        color: '#3B82F6',
        description: '',
        is_active: true,
        sort_order: 0,
      };
    }
  }
});
</script>