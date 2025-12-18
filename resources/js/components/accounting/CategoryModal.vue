<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click.self="$emit('close')">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-semibold text-gray-900">
          {{ category ? t('accounting.edit_category') : t('accounting.add_category') }}
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
                @change="resetCostType"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="income">{{ t('accounting.income') }}</option>
                <option value="expense">{{ t('accounting.expense') }}</option>
              </select>
            </div>
          </div>

          <!-- Cost Type - Only for Expense -->
          <div v-if="formData.type === 'expense'">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('accounting.cost_type') }}
            </label>
            <select
              v-model="formData.cost_type"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option :value="null">{{ t('accounting.select_cost_type') }}</option>
              <option value="fixed">{{ t('accounting.fixed') }}</option>
              <option value="variable">{{ t('accounting.variable') }}</option>
              <option value="infrastructure">{{ t('accounting.infrastructure') }}</option>
            </select>
            <p class="text-xs text-gray-500 mt-1">{{ t('accounting.cost_type_hint') }}</p>
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
              {{ t('accounting.parent_category') }}
            </label>
            <select
              v-model="formData.parent_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option :value="null">{{ t('accounting.no_parent') }}</option>
              <option v-for="cat in availableParents" :key="cat.id" :value="cat.id">
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
  category: {
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
  cost_type: null,
  parent_id: null,
  description: '',
  is_active: true,
  sort_order: 0
});

const resetCostType = () => {
  if (formData.value.type === 'income') {
    formData.value.cost_type = null;
  }
};

const availableParents = computed(() => {
  // Exclude self and children (to prevent circular reference)
  return props.categories.filter(cat => {
    if (props.category && cat.id === props.category.id) return false;
    if (props.category && cat.parent_id === props.category.id) return false;
    return true;
  });
});

const handleSubmit = async () => {
  saving.value = true;
  try {
    if (props.category) {
      await axios.put(`/api/accounting/account-categories/${props.category.id}`, formData.value);
    } else {
      await axios.post('/api/accounting/account-categories', formData.value);
    }
    emit('saved');
  } catch (error) {
    console.error('Error saving category:', error);
    alert(error.response?.data?.message || t('accounting.save_error'));
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  if (props.category) {
    formData.value = {
      code: props.category.code,
      name: props.category.name,
      type: props.category.type,
      cost_type: props.category.cost_type || null,
      parent_id: props.category.parent_id || null,
      description: props.category.description || '',
      is_active: props.category.is_active,
      sort_order: props.category.sort_order || 0
    };
  }
});
</script>

