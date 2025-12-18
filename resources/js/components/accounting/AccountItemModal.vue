<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click.self="$emit('close')">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-semibold text-gray-900">
          {{ item ? t('accounting.edit_item') : t('accounting.add_item') }}
        </h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form @submit.prevent="handleSubmit">
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.code') }} <span class="text-red-500">*</span>
              </label>
              <input
                v-model="formData.code"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :placeholder="t('accounting.code_placeholder')"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.type') }} <span class="text-red-500">*</span>
              </label>
              <select
                v-model="formData.type"
                required
                @change="filterCategories"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="income">{{ t('accounting.income') }}</option>
                <option value="expense">{{ t('accounting.expense') }}</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('accounting.name') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="formData.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :placeholder="t('accounting.name_placeholder')"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('accounting.category') }} <span class="text-red-500">*</span>
            </label>
            <select
              v-model="formData.category_id"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">{{ t('accounting.select_category') }}</option>
              <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">
                {{ cat.name }} ({{ cat.code }})
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('accounting.description') }}
            </label>
            <textarea
              v-model="formData.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :placeholder="t('accounting.description_placeholder')"
            ></textarea>
          </div>

          <div class="flex items-center">
            <input
              v-model="formData.is_active"
              type="checkbox"
              class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label class="ml-2 text-sm text-gray-700">{{ t('accounting.active') }}</label>
          </div>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
          >
            {{ t('accounting.cancel') }}
          </button>
          <button
            type="submit"
            :disabled="saving"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            {{ saving ? t('accounting.saving') : t('accounting.save') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';

const { t } = useI18n();

const props = defineProps({
  item: {
    type: Object,
    default: null
  },
  categories: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['close', 'saved']);

const saving = ref(false);
const formData = ref({
  code: '',
  name: '',
  type: 'expense',
  category_id: '',
  description: '',
  is_active: true,
  sort_order: 0
});

const filteredCategories = computed(() => {
  return props.categories.filter(cat => cat.type === formData.value.type && cat.is_active);
});

const filterCategories = () => {
  // Reset category if type changes
  const currentCategory = props.categories.find(cat => cat.id === formData.value.category_id);
  if (currentCategory && currentCategory.type !== formData.value.type) {
    formData.value.category_id = '';
  }
};

const handleSubmit = async () => {
  saving.value = true;
  try {
    if (props.item) {
      await axios.put(`/api/accounting/account-items/${props.item.id}`, formData.value);
    } else {
      await axios.post('/api/accounting/account-items', formData.value);
    }
    emit('saved');
  } catch (error) {
    console.error('Error saving item:', error);
    alert(error.response?.data?.message || t('accounting.save_error'));
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  if (props.item) {
    formData.value = {
      code: props.item.code,
      name: props.item.name,
      type: props.item.type,
      category_id: props.item.category_id,
      description: props.item.description || '',
      is_active: props.item.is_active,
      sort_order: props.item.sort_order || 0
    };
  }
});
</script>

