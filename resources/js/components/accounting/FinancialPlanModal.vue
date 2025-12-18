<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click.self="$emit('close')">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-5xl shadow-lg rounded-lg bg-white mb-10">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-xl font-semibold text-gray-900">
            {{ viewMode ? t('accounting.view_plan') : (plan ? t('accounting.edit_plan') : t('accounting.create_plan')) }}
          </h3>
          <p v-if="plan && viewMode" class="text-sm text-gray-500 mt-1">
            {{ t('accounting.status') }}: 
            <span :class="getStatusBadgeClass(plan.status)">{{ t(`accounting.${plan.status}`) }}</span>
          </p>
        </div>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- View Mode - Report Style -->
      <div v-if="viewMode" class="space-y-6">
        <!-- Header Info Card -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6 border border-blue-200">
          <div class="grid grid-cols-2 gap-6">
            <div>
              <h4 class="text-sm font-medium text-gray-500 mb-2">{{ t('accounting.code') }}</h4>
              <p class="text-lg font-semibold text-gray-900">{{ plan?.code || '-' }}</p>
            </div>
            <div>
              <h4 class="text-sm font-medium text-gray-500 mb-2">{{ t('accounting.status') }}</h4>
              <span :class="getStatusBadgeClass(plan?.status)">{{ t(`accounting.${plan?.status}`) }}</span>
            </div>
            <div>
              <h4 class="text-sm font-medium text-gray-500 mb-2">{{ t('accounting.name') }}</h4>
              <p class="text-lg font-semibold text-gray-900">{{ formData.name }}</p>
            </div>
            <div>
              <h4 class="text-sm font-medium text-gray-500 mb-2">{{ t('accounting.period') }}</h4>
              <p class="text-lg font-semibold text-gray-900">
                {{ formData.plan_type === 'quarterly' ? `Q${formData.quarter}/${formData.year}` : `${t(`accounting.month_${formData.month}`)}/${formData.year}` }}
              </p>
            </div>
          </div>
          
          <div v-if="formData.notes" class="mt-4 pt-4 border-t border-blue-200">
            <h4 class="text-sm font-medium text-gray-500 mb-2">{{ t('accounting.notes') }}</h4>
            <p class="text-gray-700">{{ formData.notes }}</p>
          </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-3 gap-4">
          <div class="bg-white rounded-lg border-2 border-green-200 p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">{{ t('accounting.total_income_planned') }}</p>
                <p class="text-2xl font-bold text-green-600 mt-1">{{ formatCurrency(totalIncome) }}</p>
              </div>
              <div class="p-3 bg-green-100 rounded-full">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                </svg>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-lg border-2 border-red-200 p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">{{ t('accounting.total_expense_planned') }}</p>
                <p class="text-2xl font-bold text-red-600 mt-1">{{ formatCurrency(totalExpense) }}</p>
              </div>
              <div class="p-3 bg-red-100 rounded-full">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                </svg>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-lg border-2 border-blue-200 p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">{{ t('accounting.balance') }}</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">{{ formatCurrency(totalIncome - totalExpense) }}</p>
              </div>
              <div class="p-3 bg-blue-100 rounded-full">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
          </div>
        </div>

        <!-- Plan Items Table -->
        <div class="bg-white rounded-lg border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-900">{{ t('accounting.plan_items') }}</h3>
          </div>
          
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('accounting.type') }}</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('accounting.account_item') }}</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('accounting.description') }}</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('accounting.planned_amount') }}</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="(item, index) in formData.plan_items" :key="index" 
                    :class="item.type === 'income' ? 'bg-green-50' : 'bg-red-50'">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-medium rounded-full"
                          :class="item.type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                      {{ t(`accounting.${item.type}`) }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    {{ accountItems.find(ai => ai.id == item.account_item_id)?.name || '-' }}
                  </td>
                  <td class="px-6 py-4 text-gray-600">
                    {{ item.description || '-' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right font-semibold"
                      :class="item.type === 'income' ? 'text-green-600' : 'text-red-600'">
                    {{ formatCurrency(item.planned_amount) }}
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-gray-50 font-bold">
                <tr>
                  <td colspan="3" class="px-6 py-4 text-right text-gray-900">{{ t('accounting.total') }}</td>
                  <td class="px-6 py-4 text-right text-lg text-blue-600">{{ formatCurrency(totalIncome + totalExpense) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Approval Info (if approved) -->
        <div v-if="plan?.approved_by" class="bg-green-50 rounded-lg border border-green-200 p-4">
          <div class="flex items-start space-x-3">
            <div class="flex-shrink-0">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <h4 class="text-sm font-medium text-green-900">{{ t('accounting.approved_info') }}</h4>
              <p class="text-sm text-green-700 mt-1">
                {{ t('accounting.approved_by') }}: <span class="font-semibold">{{ plan?.approved_by_name || '-' }}</span>
              </p>
              <p class="text-sm text-green-700">
                {{ t('accounting.approved_at') }}: <span class="font-semibold">{{ plan?.approved_at ? new Date(plan.approved_at).toLocaleString('vi-VN') : '-' }}</span>
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit Mode - Form Style -->
      <form v-else @submit.prevent="handleSubmit">
        <fieldset>
        <!-- Basic Info -->
        <div class="space-y-4 mb-6">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.name') }} <span class="text-red-500">*</span>
              </label>
              <input
                v-model="formData.name"
                type="text"
                required
                :disabled="viewMode"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                :placeholder="t('accounting.plan_name_placeholder')"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.plan_type') }} <span class="text-red-500">*</span>
              </label>
              <select
                v-model="formData.plan_type"
                required
                @change="resetPeriod"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="quarterly">{{ t('accounting.quarterly') }}</option>
                <option value="monthly">{{ t('accounting.monthly') }}</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.year') }} <span class="text-red-500">*</span>
              </label>
              <input
                v-model.number="formData.year"
                type="number"
                required
                min="2020"
                max="2100"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
            <div v-if="formData.plan_type === 'quarterly'">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.quarter') }} <span class="text-red-500">*</span>
              </label>
              <select
                v-model.number="formData.quarter"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option :value="1">Q1</option>
                <option :value="2">Q2</option>
                <option :value="3">Q3</option>
                <option :value="4">Q4</option>
              </select>
            </div>
            <div v-if="formData.plan_type === 'monthly'">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.month') }} <span class="text-red-500">*</span>
              </label>
              <select
                v-model.number="formData.month"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option v-for="m in 12" :key="m" :value="m">{{ t(`accounting.month_${m}`) }}</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('accounting.notes') }}
            </label>
            <textarea
              v-model="formData.notes"
              rows="2"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :placeholder="t('accounting.notes_placeholder')"
            ></textarea>
          </div>
        </div>

        <!-- Plan Items -->
        <div class="border-t pt-4">
          <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-semibold text-gray-900">{{ t('accounting.plan_items') }}</h4>
            <button
              type="button"
              @click="addPlanItem"
              class="px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm"
            >
              + {{ t('accounting.add_item') }}
            </button>
          </div>

          <div v-if="formData.plan_items.length === 0" class="text-center text-gray-500 py-4">
            {{ t('accounting.no_plan_items') }}
          </div>

          <div v-else class="space-y-3 max-h-96 overflow-y-auto">
            <div
              v-for="(item, index) in formData.plan_items"
              :key="index"
              class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg"
            >
              <div class="flex-1 grid grid-cols-3 gap-3">
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">
                    {{ t('accounting.type') }}
                  </label>
                  <select
                    v-model="item.type"
                    @change="() => filterItemsByType(index)"
                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="income">{{ t('accounting.income') }}</option>
                    <option value="expense">{{ t('accounting.expense') }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">
                    {{ t('accounting.account_item') }}
                  </label>
                  <select
                    v-model="item.account_item_id"
                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">{{ t('accounting.select_item') }}</option>
                    <option
                      v-for="accItem in getFilteredAccountItems(item.type)"
                      :key="accItem.id"
                      :value="accItem.id"
                    >
                      {{ accItem.name }}
                    </option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">
                    {{ t('accounting.planned_amount') }}
                  </label>
                  <input
                    v-model.number="item.planned_amount"
                    type="number"
                    min="0"
                    step="1000"
                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>
              <button
                type="button"
                @click="removePlanItem(index)"
                class="mt-5 p-1 text-red-600 hover:bg-red-50 rounded"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Totals -->
          <div v-if="formData.plan_items.length > 0" class="mt-4 pt-4 border-t">
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div class="flex justify-between">
                <span class="font-medium text-gray-700">{{ t('accounting.total_income_planned') }}:</span>
                <span class="text-green-600 font-bold">{{ formatCurrency(totalIncome) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="font-medium text-gray-700">{{ t('accounting.total_expense_planned') }}:</span>
                <span class="text-red-600 font-bold">{{ formatCurrency(totalExpense) }}</span>
              </div>
            </div>
          </div>
        </div>
        </fieldset>

        <div class="mt-6 flex justify-end space-x-3">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
          >
            {{ viewMode ? t('accounting.close') : t('accounting.cancel') }}
          </button>
          <button
            v-if="!viewMode"
            type="submit"
            :disabled="saving || formData.plan_items.length === 0"
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
  plan: {
    type: Object,
    default: null
  },
  viewMode: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'saved']);

const saving = ref(false);
const accountItems = ref([]);
const formData = ref({
  name: '',
  plan_type: 'quarterly',
  year: new Date().getFullYear(),
  quarter: 1,
  month: 1,
  notes: '',
  plan_items: []
});

const totalIncome = computed(() => {
  return formData.value.plan_items
    .filter(item => item.type === 'income')
    .reduce((sum, item) => sum + (Number(item.planned_amount) || 0), 0);
});

const totalExpense = computed(() => {
  return formData.value.plan_items
    .filter(item => item.type === 'expense')
    .reduce((sum, item) => sum + (Number(item.planned_amount) || 0), 0);
});

const getFilteredAccountItems = (type) => {
  return accountItems.value.filter(item => item.type === type && item.is_active);
};

const filterItemsByType = (index) => {
  // Reset account_item_id when type changes
  formData.value.plan_items[index].account_item_id = '';
};

const addPlanItem = () => {
  formData.value.plan_items.push({
    type: 'expense',
    account_item_id: '',
    planned_amount: 0,
    description: ''
  });
};

const removePlanItem = (index) => {
  formData.value.plan_items.splice(index, 1);
};

const resetPeriod = () => {
  if (formData.value.plan_type === 'quarterly') {
    formData.value.month = null;
    formData.value.quarter = 1;
  } else {
    formData.value.quarter = null;
    formData.value.month = 1;
  }
};

const fetchAccountItems = async () => {
  try {
    const response = await axios.get('/api/accounting/account-items', {
      params: { is_active: 1 }
    });
    accountItems.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching account items:', error);
  }
};

const handleSubmit = async () => {
  saving.value = true;
  try {
    // Backend expects 'items' not 'plan_items'
    const { plan_items, ...rest } = formData.value;
    const payload = {
      ...rest,
      items: plan_items,
      total_income_planned: totalIncome.value,
      total_expense_planned: totalExpense.value
    };

    console.log('Submitting payload:', payload);

    if (props.plan) {
      await axios.put(`/api/accounting/financial-plans/${props.plan.id}`, payload);
    } else {
      await axios.post('/api/accounting/financial-plans', payload);
    }
    emit('saved');
  } catch (error) {
    console.error('Error saving plan:', error);
    console.error('Validation errors:', error.response?.data?.errors);
    alert(error.response?.data?.message || t('accounting.save_error'));
  } finally {
    saving.value = false;
  }
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount || 0);
};

const getStatusBadgeClass = (status) => {
  const classes = {
    draft: 'px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800',
    pending: 'px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800',
    approved: 'px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800',
    active: 'px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800',
    closed: 'px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800'
  };
  return classes[status] || classes.draft;
};

onMounted(async () => {
  await fetchAccountItems();
  
  if (props.plan) {
    formData.value = {
      name: props.plan.name,
      plan_type: props.plan.plan_type,
      year: props.plan.year,
      quarter: props.plan.quarter,
      month: props.plan.month,
      notes: props.plan.notes || '',
      // Backend returns 'plan_items' relation but we use it internally as 'plan_items'
      plan_items: props.plan.plan_items || []
    };
  }
});
</script>

