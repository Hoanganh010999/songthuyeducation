<template>
  <div class="max-w-7xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ t('accounting.financial_plans') }}</h2>
        <p class="text-gray-600 mt-1">{{ t('accounting.plans_subtitle') }}</p>
      </div>
      <button
        @click="openCreateModal"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center space-x-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>{{ t('accounting.add_plan') }}</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('accounting.search') }}</label>
          <input
            v-model="filters.search"
            type="text"
            :placeholder="t('accounting.search_placeholder')"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('accounting.plan_type') }}</label>
          <select
            v-model="filters.plan_type"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">{{ t('accounting.all_types') }}</option>
            <option value="quarterly">{{ t('accounting.quarterly') }}</option>
            <option value="monthly">{{ t('accounting.monthly') }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('accounting.status') }}</label>
          <select
            v-model="filters.status"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">{{ t('accounting.all_status') }}</option>
            <option value="draft">{{ t('accounting.draft') }}</option>
            <option value="pending">{{ t('accounting.pending') }}</option>
            <option value="approved">{{ t('accounting.approved') }}</option>
            <option value="active">{{ t('accounting.active') }}</option>
            <option value="closed">{{ t('accounting.closed') }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('accounting.year') }}</label>
          <input
            v-model.number="filters.year"
            type="number"
            :placeholder="new Date().getFullYear()"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div v-if="loading" class="p-8 text-center text-gray-500">
        {{ t('accounting.loading') }}
      </div>
      <div v-else-if="plans.length === 0" class="p-8 text-center text-gray-500">
        {{ t('accounting.no_plans') }}
      </div>
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.code') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.name') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.period') }}
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.total_income') }}
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.total_expense') }}
            </th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.status') }}
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.actions') }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="plan in plans" :key="plan.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ plan.code }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">
              {{ plan.name }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatPeriod(plan) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">
              {{ formatCurrency(plan.total_income_planned) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-medium">
              {{ formatCurrency(plan.total_expense_planned) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
              <span
                class="px-2 py-1 text-xs font-medium rounded-full"
                :class="getStatusClass(plan.status)"
              >
                {{ t(`accounting.${plan.status}`) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
              <button
                @click="viewPlan(plan)"
                class="text-blue-600 hover:text-blue-900"
              >
                {{ t('accounting.view') }}
              </button>
              <button
                v-if="plan.status === 'draft' || plan.status === 'pending'"
                @click="openEditModal(plan)"
                class="text-green-600 hover:text-green-900"
              >
                {{ t('accounting.edit') }}
              </button>
              <button
                v-if="plan.status === 'draft'"
                @click="submitPlan(plan)"
                class="text-indigo-600 hover:text-indigo-900"
              >
                {{ t('accounting.submit_for_approval') }}
              </button>
              <button
                v-if="plan.status === 'pending'"
                @click="approvePlan(plan)"
                class="text-purple-600 hover:text-purple-900"
              >
                {{ t('accounting.approve') }}
              </button>
              <button
                v-if="plan.status === 'draft'"
                @click="confirmDelete(plan)"
                class="text-red-600 hover:text-red-900"
              >
                {{ t('accounting.delete') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Financial Plan Modal -->
    <FinancialPlanModal
      v-if="showModal"
      :plan="selectedPlan"
      :view-mode="viewMode"
      @close="closeModal"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';
import Swal from 'sweetalert2';
import FinancialPlanModal from './FinancialPlanModal.vue';

const { t } = useI18n();

const loading = ref(false);
const plans = ref([]);
const showModal = ref(false);
const selectedPlan = ref(null);
const viewMode = ref(false);
const filters = ref({
  search: '',
  plan_type: '',
  status: '',
  year: null
});

const fetchPlans = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/accounting/financial-plans', {
      params: filters.value
    });
    plans.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching plans:', error);
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  selectedPlan.value = null;
  viewMode.value = false;
  showModal.value = true;
};

const openEditModal = (plan) => {
  selectedPlan.value = plan;
  viewMode.value = false;
  showModal.value = true;
};

const viewPlan = (plan) => {
  selectedPlan.value = plan;
  viewMode.value = true; // Open in view-only mode
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  selectedPlan.value = null;
  viewMode.value = false;
};

const handleSaved = () => {
  closeModal();
  fetchPlans();
};

const submitPlan = async (plan) => {
  const result = await Swal.fire({
    title: t('accounting.confirm_submit'),
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: t('accounting.submit'),
    cancelButtonText: t('accounting.cancel'),
    confirmButtonColor: '#6366f1'
  });
  
  if (!result.isConfirmed) return;
  
  try {
    // Use a dedicated endpoint to change status
    await axios.post(`/api/accounting/financial-plans/${plan.id}/submit`);
    await fetchPlans();
    
    Swal.fire({
      title: t('accounting.success'),
      text: t('accounting.plan_submitted'),
      icon: 'success',
      timer: 2000,
      showConfirmButton: false
    });
  } catch (error) {
    console.error('Error submitting plan:', error);
    Swal.fire({
      title: t('accounting.error'),
      text: error.response?.data?.message || t('accounting.submit_error'),
      icon: 'error'
    });
  }
};

const approvePlan = async (plan) => {
  const result = await Swal.fire({
    title: t('accounting.confirm_approve'),
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: t('accounting.approve'),
    cancelButtonText: t('accounting.cancel'),
    confirmButtonColor: '#10b981'
  });
  
  if (!result.isConfirmed) return;
  
  try {
    await axios.post(`/api/accounting/financial-plans/${plan.id}/approve`);
    await fetchPlans();
    
    Swal.fire({
      title: t('accounting.success'),
      text: t('accounting.plan_approved'),
      icon: 'success',
      timer: 2000,
      showConfirmButton: false
    });
  } catch (error) {
    console.error('Error approving plan:', error);
    Swal.fire({
      title: t('accounting.error'),
      text: error.response?.data?.message || t('accounting.approve_error'),
      icon: 'error'
    });
  }
};

const confirmDelete = async (plan) => {
  const result = await Swal.fire({
    title: t('accounting.confirm_delete'),
    text: t('accounting.delete_warning'),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: t('accounting.delete'),
    cancelButtonText: t('accounting.cancel'),
    confirmButtonColor: '#ef4444'
  });
  
  if (!result.isConfirmed) return;
  
  try {
    await axios.delete(`/api/accounting/financial-plans/${plan.id}`);
    await fetchPlans();
    
    Swal.fire({
      title: t('accounting.success'),
      text: t('accounting.plan_deleted'),
      icon: 'success',
      timer: 2000,
      showConfirmButton: false
    });
  } catch (error) {
    console.error('Error deleting plan:', error);
    Swal.fire({
      title: t('accounting.error'),
      text: error.response?.data?.message || t('accounting.delete_error'),
      icon: 'error'
    });
  }
};

const formatPeriod = (plan) => {
  if (plan.plan_type === 'quarterly') {
    return `Q${plan.quarter}/${plan.year}`;
  }
  return `${plan.month}/${plan.year}`;
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount || 0);
};

const getStatusClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-blue-100 text-blue-800',
    active: 'bg-green-100 text-green-800',
    closed: 'bg-red-100 text-red-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

watch(filters, () => {
  fetchPlans();
}, { deep: true });

onMounted(() => {
  fetchPlans();
});
</script>

