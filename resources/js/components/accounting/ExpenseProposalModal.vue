<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click.self="$emit('close')">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-lg bg-white">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-semibold text-gray-900">
          {{ proposal ? t('accounting.edit_expense_proposal') : t('accounting.create_expense_proposal') }}
        </h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form @submit.prevent="handleSubmit">
        <div class="space-y-4">
          <!-- Financial Plan (Required) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('accounting.financial_plan') }} <span class="text-red-500">*</span>
            </label>
            <select
              v-model="formData.financial_plan_id"
              @change="loadPlanItems"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">{{ t('accounting.select_plan') }}</option>
              <option v-for="plan in availablePlans" :key="plan.id" :value="plan.id">
                {{ plan.name }} ({{ formatPeriod(plan) }})
              </option>
            </select>
            <p class="text-xs text-red-500 mt-1">{{ t('accounting.expense_plan_required_hint') }}</p>
          </div>

          <!-- Account Item - Select from Plan Items -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('accounting.expense_item') }} <span class="text-red-500">*</span>
            </label>
            <select
              v-model="formData.account_item_id"
              @change="onAccountItemChange"
              required
              :disabled="!formData.financial_plan_id || planItems.length === 0"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
            >
              <option value="">
                {{ formData.financial_plan_id ? t('accounting.select_item') : t('accounting.select_plan_first') }}
              </option>
              <option v-for="item in planItems" :key="item.id" :value="item.account_item_id">
                {{ item.account_item?.name }} - {{ t('accounting.planned') }}: {{ formatCurrency(item.planned_amount) }}
              </option>
            </select>
            <p v-if="formData.financial_plan_id && planItems.length > 0" class="text-xs text-green-600 mt-1">
              ✓ {{ planItems.length }} {{ t('accounting.items_available') }}
            </p>
            <p v-else-if="formData.financial_plan_id && planItems.length === 0" class="text-xs text-red-600 mt-1">
              ⚠ {{ t('accounting.no_expense_items_in_plan') }}
            </p>
            <p v-else class="text-xs text-gray-500 mt-1">
              {{ t('accounting.expense_from_plan_hint') }}
            </p>
          </div>

          <!-- Cash Account -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('accounting.cash_account') }} <span class="text-red-500">*</span>
            </label>
            <select
              v-model="formData.cash_account_id"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">{{ t('accounting.select_cash_account') }}</option>
              <option v-for="account in cashAccounts" :key="account.id" :value="account.id">
                {{ account.name }} ({{ account.type === 'cash' ? t('accounting.cash') : t('accounting.bank') }}) - {{ formatCurrency(account.balance) }}
              </option>
            </select>
            <p v-if="cashAccounts.length === 0" class="text-xs text-red-600 mt-1">
              ⚠ {{ t('accounting.no_cash_accounts') }} 
              <a href="#/accounting?tab=cash-accounts" class="underline hover:text-red-800">{{ t('accounting.create_now') }}</a>
            </p>
            <p v-else class="text-xs text-gray-500 mt-1">
              {{ t('accounting.cash_account_hint') }}
            </p>
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
              <!-- Budget Warning -->
              <div v-if="formData.account_item_id && formData.amount" class="mt-2">
                <div v-if="remainingBudget !== null" 
                     class="p-3 rounded-lg"
                     :class="remainingBudget >= 0 ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                  <div class="flex items-start space-x-2">
                    <svg v-if="remainingBudget >= 0" class="w-5 h-5 text-green-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg v-else class="w-5 h-5 text-red-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div class="flex-1">
                      <p class="text-sm font-medium" :class="remainingBudget >= 0 ? 'text-green-900' : 'text-red-900'">
                        {{ t('accounting.budget_status') }}
                      </p>
                      <p class="text-xs mt-1" :class="remainingBudget >= 0 ? 'text-green-700' : 'text-red-700'">
                        {{ t('accounting.planned') }}: <strong>{{ formatCurrency(selectedPlanItem?.planned_amount || 0) }}</strong>
                      </p>
                      <p class="text-xs" :class="remainingBudget >= 0 ? 'text-green-700' : 'text-red-700'">
                        {{ t('accounting.remaining') }}: <strong>{{ formatCurrency(remainingBudget) }}</strong>
                        <span v-if="remainingBudget < 0"> ({{ t('accounting.over_budget') }})</span>
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Requested Date -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('accounting.requested_date') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="formData.requested_date"
              type="date"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
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

          <!-- Payment Info (if editing and approved) -->
          <div v-if="proposal && (proposal.status === 'approved' || proposal.status === 'paid')" class="border-t pt-4">
            <h4 class="font-medium text-gray-900 mb-3">{{ t('accounting.payment_info') }}</h4>
            <div class="grid grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  {{ t('accounting.payment_date') }}
                </label>
                <input
                  v-model="formData.payment_date"
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
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
import { ref, computed, watch, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';
import { useAccountingSwal } from './useAccountingSwal';

const { t } = useI18n();
const { showSuccess, showError } = useAccountingSwal();

const props = defineProps({
  proposal: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'saved']);

const saving = ref(false);
const availablePlans = ref([]);
const planItems = ref([]);
const cashAccounts = ref([]);

const formData = ref({
  financial_plan_id: '',
  financial_plan_item_id: '',
  account_item_id: '',
  cash_account_id: '',
  title: '',
  amount: 0,
  requested_date: new Date().toISOString().split('T')[0],
  description: '',
  payment_date: null,
  payment_method: '',
  payment_ref: ''
});

// Debug: Watch cash_account_id changes
watch(() => formData.value.cash_account_id, (newVal, oldVal) => {
  console.log('💰 Cash account ID changed:', { old: oldVal, new: newVal });
  console.log('💰 Current formData:', formData.value);
});

// Get selected plan item
const selectedPlanItem = computed(() => {
  if (!formData.value.account_item_id) return null;
  return planItems.value.find(item => item.account_item_id == formData.value.account_item_id);
});

// Calculate remaining budget
const remainingBudget = computed(() => {
  if (!selectedPlanItem.value || !formData.value.amount) return null;
  return selectedPlanItem.value.planned_amount - formData.value.amount;
});

const fetchAvailablePlans = async () => {
  try {
    const response = await axios.get('/api/accounting/financial-plans/available');
    console.log('Available plans full response:', response);
    console.log('Available plans response.data:', response.data);
    
    // Backend returns array directly (not wrapped in {data: ...})
    availablePlans.value = Array.isArray(response.data) ? response.data : (response.data.data || []);
    
    console.log('Available plans:', availablePlans.value);
    console.log('Plans count:', availablePlans.value.length);
    
    if (availablePlans.value.length === 0) {
      console.warn('No plans found. Check if plans have status "approved" or "active"');
    }
  } catch (error) {
    console.error('Error fetching plans:', error);
    console.error('Error response:', error.response?.data);
  }
};

const loadPlanItems = async () => {
  if (!formData.value.financial_plan_id) {
    planItems.value = [];
    return;
  }
  
  try {
    const response = await axios.get(`/api/accounting/financial-plans/${formData.value.financial_plan_id}`);
    const plan = response.data.data || response.data;
    
    console.log('Full plan response:', plan);
    console.log('Plan items before filter:', plan.plan_items);
    
    // Backend returns 'plan_items' not 'items'
    // Filter only expense items (type = 'expense')
    // NOTE: 'type' is in plan_items table, not account_items table
    planItems.value = (plan.plan_items || []).filter(item => {
      console.log('Item:', item.account_item?.name, 'Type:', item.type);
      return item.type === 'expense';
    });
    
    console.log('Loaded plan items (filtered):', planItems.value);
    console.log('Plan items count:', planItems.value.length);
    
    // Reset account_item_id when plan changes to avoid selecting invalid item
    formData.value.account_item_id = '';
    formData.value.financial_plan_item_id = '';
  } catch (error) {
    console.error('Error loading plan items:', error);
  }
};

const onAccountItemChange = () => {
  // Auto-fill title from account item name and set financial_plan_item_id
  if (formData.value.account_item_id && selectedPlanItem.value) {
    // Set the financial_plan_item_id (required by backend)
    formData.value.financial_plan_item_id = selectedPlanItem.value.id;
    
    const accountItem = selectedPlanItem.value.account_item;
    if (accountItem && !formData.value.title) {
      formData.value.title = `Chi tiêu: ${accountItem.name}`;
    }
  }
};

const fetchCashAccounts = async () => {
  try {
    const response = await axios.get('/api/accounting/cash-accounts', {
      params: { is_active: 1 }
    });
    console.log('Cash accounts response:', response.data);
    cashAccounts.value = response.data.data || response.data;
    console.log('Cash accounts:', cashAccounts.value);
    console.log('Cash accounts count:', cashAccounts.value.length);
    
    if (cashAccounts.value.length === 0) {
      console.warn('No cash accounts found. Please create cash accounts first.');
    }
  } catch (error) {
    console.error('Error fetching cash accounts:', error);
    console.error('Error response:', error.response?.data);
  }
};


const handleSubmit = async () => {
  saving.value = true;
  try {
    console.log('🚀 Submitting expense proposal with data:', formData.value);
    console.log('🔍 Cash account ID being sent:', formData.value.cash_account_id);
    
    if (props.proposal) {
      await axios.put(`/api/accounting/expense-proposals/${props.proposal.id}`, formData.value);
    } else {
      const response = await axios.post('/api/accounting/expense-proposals', formData.value);
      console.log('✅ Response from server:', response.data);
      console.log('✅ Saved cash_account_id:', response.data.data?.cash_account_id);
    }
    
    await showSuccess(t('accounting.proposal_saved'));
    emit('saved');
  } catch (error) {
    console.error('❌ Error saving proposal:', error);
    console.error('❌ Error response:', error.response?.data);
    await showError(error.response?.data?.message || t('accounting.save_error'));
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
    fetchCashAccounts()
  ]);
  
  if (props.proposal) {
    formData.value = {
      financial_plan_id: props.proposal.financial_plan_id,
      account_item_id: props.proposal.account_item_id,
      cash_account_id: props.proposal.cash_account_id || '',
      title: props.proposal.title,
      amount: props.proposal.amount,
      requested_date: props.proposal.requested_date,
      description: props.proposal.description || '',
      payment_date: props.proposal.payment_date,
      payment_method: props.proposal.payment_method || '',
      payment_ref: props.proposal.payment_ref || ''
    };
    
    if (formData.value.financial_plan_id) {
      await loadPlanItems();
    }
  }
});
</script>

