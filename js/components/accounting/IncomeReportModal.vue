<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click.self="$emit('close')">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-lg bg-white">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-semibold text-gray-900">
          {{ report ? t('accounting.edit_income_report') : t('accounting.create_income_report') }}
        </h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form @submit.prevent="handleSubmit">
        <div class="space-y-4">
          <!-- Financial Plan (Optional) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('accounting.financial_plan') }}
            </label>
            <select
              v-model="formData.financial_plan_id"
              @change="loadPlanItems"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option :value="null">{{ t('accounting.unplanned') }}</option>
              <option v-for="plan in availablePlans" :key="plan.id" :value="plan.id">
                {{ plan.name }} ({{ formatPeriod(plan) }})
              </option>
            </select>
            <p class="text-xs text-gray-500 mt-1">{{ t('accounting.plan_optional_hint') }}</p>
          </div>

          <!-- Plan Item (if plan selected) -->
          <div v-if="formData.financial_plan_id">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('accounting.plan_item') }}
            </label>
            <select
              v-model="formData.financial_plan_item_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option :value="null">{{ t('accounting.select_plan_item') }}</option>
              <option v-for="item in planItems" :key="item.id" :value="item.id">
                {{ item.account_item?.name }} - {{ formatCurrency(item.planned_amount) }}
              </option>
            </select>
          </div>

          <!-- Account Item -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('accounting.account_item') }} <span class="text-red-500">*</span>
            </label>
            <select
              v-model="formData.account_item_id"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">{{ t('accounting.select_item') }}</option>
              <option v-for="item in incomeItems" :key="item.id" :value="item.id">
                {{ item.name }}
              </option>
            </select>
          </div>

          <!-- Title & Amount -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.title') }} <span class="text-red-500">*</span>
              </label>
              <input
                v-model="formData.title"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :placeholder="t('accounting.title_placeholder')"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.amount') }} <span class="text-red-500">*</span>
              </label>
              <input
                v-model.number="formData.amount"
                type="number"
                required
                min="0"
                step="1000"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
          </div>

          <!-- Received Date -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('accounting.received_date') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="formData.received_date"
              type="date"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <!-- Payer Information -->
          <div class="border-t pt-4">
            <h4 class="font-medium text-gray-900 mb-3">{{ t('accounting.payer_info') }}</h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  {{ t('accounting.payer_name') }}
                </label>
                <input
                  v-model="formData.payer_name"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  :placeholder="t('accounting.payer_name_placeholder')"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  {{ t('accounting.payer_phone') }}
                </label>
                <input
                  v-model="formData.payer_phone"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  :placeholder="t('accounting.payer_phone_placeholder')"
                />
              </div>
            </div>
            <div class="mt-3">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.payer_additional_info') }}
              </label>
              <input
                v-model="formData.payer_info"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :placeholder="t('accounting.payer_additional_info_placeholder')"
              />
            </div>
          </div>

          <!-- Payment Method -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.payment_method') }}
              </label>
              <select
                v-model="formData.payment_method"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">{{ t('accounting.select_method') }}</option>
                <option value="cash">{{ t('accounting.cash') }}</option>
                <option value="bank_transfer">{{ t('accounting.bank_transfer') }}</option>
                <option value="card">{{ t('accounting.card') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ t('accounting.payment_ref') }}
              </label>
              <input
                v-model="formData.payment_ref"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :placeholder="t('accounting.payment_ref_placeholder')"
              />
            </div>
          </div>

          <!-- Description -->
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
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
          >
            {{ saving ? t('accounting.saving') : t('accounting.save') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';

const { t } = useI18n();

const props = defineProps({
  report: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'saved']);

const saving = ref(false);
const availablePlans = ref([]);
const planItems = ref([]);
const incomeItems = ref([]);
const formData = ref({
  financial_plan_id: null,
  financial_plan_item_id: null,
  account_item_id: '',
  title: '',
  amount: 0,
  received_date: new Date().toISOString().split('T')[0],
  payer_name: '',
  payer_phone: '',
  payer_info: '',
  description: '',
  payment_method: '',
  payment_ref: ''
});

const fetchAvailablePlans = async () => {
  try {
    const response = await axios.get('/api/accounting/financial-plans/available');
    availablePlans.value = response.data;
  } catch (error) {
    console.error('Error fetching plans:', error);
  }
};

const loadPlanItems = async () => {
  if (!formData.value.financial_plan_id) {
    planItems.value = [];
    return;
  }
  
  try {
    const response = await axios.get(`/api/accounting/financial-plans/${formData.value.financial_plan_id}`);
    planItems.value = response.data.plan_items?.filter(item => item.type === 'income') || [];
  } catch (error) {
    console.error('Error loading plan items:', error);
  }
};

const fetchIncomeItems = async () => {
  try {
    const response = await axios.get('/api/accounting/account-items', {
      params: { type: 'income', is_active: 1 }
    });
    incomeItems.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching income items:', error);
  }
};

const handleSubmit = async () => {
  saving.value = true;
  try {
    if (props.report) {
      await axios.put(`/api/accounting/income-reports/${props.report.id}`, formData.value);
    } else {
      await axios.post('/api/accounting/income-reports', formData.value);
    }
    emit('saved');
  } catch (error) {
    console.error('Error saving report:', error);
    alert(error.response?.data?.message || t('accounting.save_error'));
  } finally {
    saving.value = false;
  }
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount || 0);
};

const formatPeriod = (plan) => {
  if (plan.plan_type === 'quarterly') {
    return `Q${plan.quarter}/${plan.year}`;
  }
  return `${plan.month}/${plan.year}`;
};

onMounted(async () => {
  await Promise.all([
    fetchAvailablePlans(),
    fetchIncomeItems()
  ]);
  
  if (props.report) {
    formData.value = {
      financial_plan_id: props.report.financial_plan_id,
      financial_plan_item_id: props.report.financial_plan_item_id,
      account_item_id: props.report.account_item_id,
      title: props.report.title,
      amount: props.report.amount,
      received_date: props.report.received_date,
      payer_name: props.report.payer_name || '',
      payer_phone: props.report.payer_phone || '',
      payer_info: props.report.payer_info || '',
      description: props.report.description || '',
      payment_method: props.report.payment_method || '',
      payment_ref: props.report.payment_ref || ''
    };
    
    if (formData.value.financial_plan_id) {
      await loadPlanItems();
    }
  }
});
</script>

