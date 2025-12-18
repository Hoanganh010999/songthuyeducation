<template>
  <div>
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ t('customers.kanban') }}</h1>
        <div class="flex items-center space-x-2 mt-2">
          <router-link to="/customers" class="text-gray-600 hover:text-blue-600 transition">
            {{ t('customers.list_view') }}
          </router-link>
          <span class="text-gray-400">|</span>
          <router-link to="/customers/kanban" class="text-blue-600 font-medium">
            {{ t('customers.kanban_view') }}
          </router-link>
        </div>
      </div>
      <button
        v-if="authStore.hasPermission('customers.create')"
        @click="showCreateModal = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center space-x-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>{{ t('customers.create') }}</span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Kanban Board -->
    <div v-else class="flex space-x-4 overflow-x-auto pb-4">
      <div
        v-for="(stageData, stageKey) in kanbanData"
        :key="stageKey"
        class="flex-shrink-0 w-80"
      >
        <!-- Stage Column -->
        <div class="bg-gray-50 rounded-lg p-4 h-full">
          <!-- Stage Header -->
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">
              {{ t(`customers.stage_${stageKey}`) }}
            </h3>
            <span class="px-2 py-1 text-xs font-semibold bg-gray-200 text-gray-700 rounded-full">
              {{ stageData.count }}
            </span>
          </div>

          <!-- Customer Cards -->
          <div class="space-y-3 max-h-[calc(100vh-250px)] overflow-y-auto">
            <div
              v-for="customer in stageData.customers"
              :key="customer.id"
              class="bg-white rounded-lg p-4 shadow-sm border border-gray-200 cursor-pointer hover:shadow-md transition"
              @click="viewCustomer(customer)"
            >
              <!-- Customer Name -->
              <h4 class="font-medium text-gray-900 mb-2">{{ customer.name }}</h4>
              
              <!-- Contact Info -->
              <div class="space-y-1 text-sm text-gray-600 mb-3">
                <div v-if="customer.phone" class="flex items-center">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                  {{ customer.phone }}
                </div>
                <div v-if="customer.email" class="flex items-center">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  {{ customer.email }}
                </div>
              </div>

              <!-- Footer -->
              <div class="flex items-center justify-between pt-3 border-t">
                <span class="text-xs text-gray-500">{{ customer.branch?.name }}</span>
                <span v-if="customer.estimated_value" class="text-sm font-semibold text-green-600">
                  {{ formatCurrency(customer.estimated_value) }}
                </span>
              </div>
            </div>

            <!-- Empty State -->
            <div v-if="stageData.customers.length === 0" class="text-center py-8 text-gray-400">
              <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
              </svg>
              <p class="text-sm">{{ t('common.no_data') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Customer Modal -->
    <CustomerModal
      :show="showCreateModal || showEditModal"
      :customer="selectedCustomer"
      :is-edit="showEditModal"
      @close="closeModal"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';
import api from '../../services/api';
import CustomerModal from '../../components/customers/CustomerModal.vue';

const authStore = useAuthStore();
const { t } = useI18n();
const swal = useSwal();

const kanbanData = ref({});
const loading = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedCustomer = ref(null);

const formatCurrency = (value) => {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND'
  }).format(value);
};

const loadKanban = async () => {
  loading.value = true;
  try {
    const response = await api.get('/api/customers/kanban');
    if (response.data.success) {
      kanbanData.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load kanban:', error);
    swal.error(t('common.error_occurred'));
  } finally {
    loading.value = false;
  }
};

const viewCustomer = (customer) => {
  selectedCustomer.value = customer;
  showEditModal.value = true;
};

const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  selectedCustomer.value = null;
};

const handleSaved = () => {
  closeModal();
  loadKanban();
};

onMounted(() => {
  loadKanban();
});
</script>

