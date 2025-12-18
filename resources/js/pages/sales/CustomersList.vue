<template>
  <div class="scale-85-container">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ t('customers.list') }}</h1>
      </div>
      <div class="flex items-center gap-3">
        <!-- Create Button -->
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
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="relative">
          <svg class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            v-model="filters.search"
            @input="debouncedSearch"
            type="text"
            :placeholder="t('common.search') + ' (tên, SĐT, email)'"
            class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
        <select
          v-model="filters.stage"
          @change="loadCustomers(1)"
          class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
        >
          <option value="">{{ t('customers.all_stages') }}</option>
          <option value="lead">{{ t('customers.stage_lead') }}</option>
          <option value="contacted">{{ t('customers.stage_contacted') }}</option>
          <option value="qualified">{{ t('customers.stage_qualified') }}</option>
          <option value="proposal">{{ t('customers.stage_proposal') }}</option>
          <option value="negotiation">{{ t('customers.stage_negotiation') }}</option>
          <option value="closed_won">{{ t('customers.stage_closed_won') }}</option>
          <option value="closed_lost">{{ t('customers.stage_closed_lost') }}</option>
        </select>
        <select
          v-if="authStore.isSuperAdmin"
          v-model="filters.branch_id"
          @change="loadCustomers(1)"
          class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
        >
          <option value="">{{ t('customers.all_branches') }}</option>
          <option v-for="branch in branches" :key="branch.id" :value="branch.id">
            {{ branch.name }}
          </option>
        </select>
        <button
          @click="resetFilters"
          class="px-4 py-2 border rounded-lg hover:bg-gray-50 transition flex items-center justify-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          {{ t('common.reset') }}
        </button>
      </div>

      <!-- Active filters badges -->
      <div v-if="hasActiveFilters" class="flex flex-wrap gap-2 mt-3 pt-3 border-t">
        <span v-if="filters.search" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
          Tìm: "{{ filters.search }}"
          <button @click="filters.search = ''; loadCustomers(1)" class="hover:text-blue-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </span>
        <span v-if="filters.stage" class="inline-flex items-center gap-1 px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">
          {{ t(`customers.stage_${filters.stage}`) }}
          <button @click="filters.stage = ''; loadCustomers(1)" class="hover:text-purple-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </span>
        <span v-if="filters.branch_id" class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
          {{ branches.find(b => b.id == filters.branch_id)?.name }}
          <button @click="filters.branch_id = ''; loadCustomers(1)" class="hover:text-green-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Table -->
    <div v-else class="bg-white rounded-lg shadow-sm border overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              @click="toggleSort('name')"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none"
            >
              <div class="flex items-center gap-1">
                {{ t('customers.name') }}
                <SortIcon :field="'name'" :currentSort="sortBy" :currentDir="sortDir" />
              </div>
            </th>
            <th
              @click="toggleSort('phone')"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none"
            >
              <div class="flex items-center gap-1">
                {{ t('customers.phone') }}
                <SortIcon :field="'phone'" :currentSort="sortBy" :currentDir="sortDir" />
              </div>
            </th>
            <th
              @click="toggleSort('stage')"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none"
            >
              <div class="flex items-center gap-1">
                {{ t('customers.stage') }}
                <SortIcon :field="'stage'" :currentSort="sortBy" :currentDir="sortDir" />
              </div>
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('customers.latest_interaction') }}
            </th>
            <th
              @click="toggleSort('estimated_value')"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none"
            >
              <div class="flex items-center gap-1">
                {{ t('customers.estimated_value') }}
                <SortIcon :field="'estimated_value'" :currentSort="sortBy" :currentDir="sortDir" />
              </div>
            </th>
            <th
              @click="toggleSort('created_at')"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none"
            >
              <div class="flex items-center gap-1">
                {{ t('common.created_at') }}
                <SortIcon :field="'created_at'" :currentSort="sortBy" :currentDir="sortDir" />
              </div>
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('common.actions') }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="customers.length === 0">
            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
              {{ t('common.no_data') }}
            </td>
          </tr>
          <tr v-for="customer in customers" :key="customer.id" class="hover:bg-gray-50 transition">
            <td class="px-6 py-4">
              <button
                @click="openInteractionHistory(customer)"
                class="text-left hover:text-blue-600 transition"
              >
                <div class="text-sm font-medium text-blue-600 hover:underline">{{ customer.name }}</div>
                <div v-if="customer.email" class="text-sm text-gray-500">{{ customer.email }}</div>
              </button>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ customer.phone || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getStageClass(customer.stage)" class="px-3 py-1 text-xs font-semibold rounded-full">
                {{ t(`customers.stage_${customer.stage}`) }}
              </span>
            </td>
            <td class="px-6 py-4">
              <div v-if="customer.latest_interaction" class="text-sm">
                <div class="flex items-center gap-2 mb-1">
                  <span
                    :style="{
                      backgroundColor: customer.latest_interaction.interaction_result?.color + '20',
                      color: customer.latest_interaction.interaction_result?.color
                    }"
                    class="px-2 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ customer.latest_interaction.interaction_result?.name }}
                  </span>
                  <span class="text-xs text-gray-500">
                    {{ formatShortDate(customer.latest_interaction.interaction_date) }}
                  </span>
                </div>
                <p class="text-xs text-gray-600 line-clamp-2">
                  {{ customer.latest_interaction.notes }}
                </p>
              </div>
              <span v-else class="text-sm text-gray-400">-</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              <span v-if="customer.estimated_value" class="font-semibold text-green-600">
                {{ formatCurrency(customer.estimated_value) }}
              </span>
              <span v-else class="text-gray-400">-</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(customer.created_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex items-center justify-end space-x-2">
                <!-- Enroll Button -->
                <button
                  v-if="authStore.hasPermission('enrollments.create')"
                  @click="createEnrollment(customer)"
                  class="px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-xs flex items-center gap-1"
                  :title="t('enrollments.create')"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  Enroll
                </button>
                <!-- Zalo Button -->
                <button
                  @click="openZaloChat(customer)"
                  class="text-green-600 hover:text-green-900 transition"
                  :title="t('zalo.chat_via_zalo')"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                  </svg>
                </button>
                <!-- Edit Button -->
                <button
                  v-if="authStore.hasPermission('customers.edit')"
                  @click="editCustomer(customer)"
                  class="text-blue-600 hover:text-blue-900 transition"
                  :title="t('common.edit')"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <!-- Delete Button -->
                <button
                  v-if="authStore.hasPermission('customers.delete')"
                  @click="deleteCustomer(customer)"
                  class="text-red-600 hover:text-red-900 transition"
                  :title="t('common.delete')"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="px-6 py-4 border-t bg-gray-50">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            {{ t('common.showing') }} {{ pagination.from }} - {{ pagination.to }} {{ t('common.of') }} {{ pagination.total }}
          </div>
          <div class="flex space-x-1">
            <button
              @click="loadCustomers(1)"
              :disabled="pagination.current_page === 1"
              class="px-3 py-1 border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
              title="Trang đầu"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
              </svg>
            </button>
            <button
              @click="loadCustomers(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="px-3 py-1 border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ t('common.previous') }}
            </button>
            <span class="px-3 py-1 bg-blue-600 text-white rounded">
              {{ pagination.current_page }}
            </span>
            <button
              @click="loadCustomers(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-1 border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ t('common.next') }}
            </button>
            <button
              @click="loadCustomers(pagination.last_page)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-1 border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
              title="Trang cuối"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
              </svg>
            </button>
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

    <!-- Customer Detail Modal (Info + Children + Interactions) -->
    <CustomerDetailModal
      :show="showInteractionHistoryModal"
      :customer="selectedCustomerForHistory"
      @close="closeInteractionHistoryModal"
    />

    <!-- Customer Zalo Chat Modal -->
    <CustomerZaloChatModal
      :show="showZaloChatModal"
      :customer="selectedCustomerForZalo"
      @close="closeZaloChatModal"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';
import api from '../../services/api';
import CustomerModal from '../../components/customers/CustomerModal.vue';
import CustomerDetailModal from '../../components/customers/CustomerDetailModal.vue';
import CustomerZaloChatModal from '../../components/customers/CustomerZaloChatModal.vue';

// Sort Icon Component
const SortIcon = {
  props: ['field', 'currentSort', 'currentDir'],
  template: `
    <span class="inline-flex flex-col ml-1">
      <svg
        class="w-3 h-3 -mb-1"
        :class="currentSort === field && currentDir === 'asc' ? 'text-blue-600' : 'text-gray-300'"
        fill="currentColor"
        viewBox="0 0 24 24"
      >
        <path d="M7 14l5-5 5 5H7z"/>
      </svg>
      <svg
        class="w-3 h-3"
        :class="currentSort === field && currentDir === 'desc' ? 'text-blue-600' : 'text-gray-300'"
        fill="currentColor"
        viewBox="0 0 24 24"
      >
        <path d="M7 10l5 5 5-5H7z"/>
      </svg>
    </span>
  `
};

const authStore = useAuthStore();
const { t } = useI18n();
const swal = useSwal();

const customers = ref([]);
const branches = ref([]);
const loading = ref(false);
const filters = ref({
  search: '',
  stage: '',
  branch_id: '',
});
const sortBy = ref('created_at');
const sortDir = ref('desc');
const pagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0,
});
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showInteractionHistoryModal = ref(false);
const showZaloChatModal = ref(false);
const selectedCustomer = ref(null);
const selectedCustomerForHistory = ref(null);
const selectedCustomerForZalo = ref(null);

const hasActiveFilters = computed(() => {
  return filters.value.search || filters.value.stage || filters.value.branch_id;
});

const toggleSort = (field) => {
  if (sortBy.value === field) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = field;
    sortDir.value = 'asc';
  }
  loadCustomers(1);
};

const getStageClass = (stage) => {
  const classes = {
    lead: 'bg-gray-100 text-gray-800',
    contacted: 'bg-blue-100 text-blue-800',
    qualified: 'bg-green-100 text-green-800',
    proposal: 'bg-yellow-100 text-yellow-800',
    negotiation: 'bg-orange-100 text-orange-800',
    closed_won: 'bg-emerald-100 text-emerald-800',
    closed_lost: 'bg-red-100 text-red-800',
  };
  return classes[stage] || 'bg-gray-100 text-gray-800';
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND'
  }).format(value);
};

const formatShortDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('vi-VN', {
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('vi-VN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  });
};

const openInteractionHistory = (customer) => {
  selectedCustomerForHistory.value = customer;
  showInteractionHistoryModal.value = true;
};

const closeInteractionHistoryModal = () => {
  showInteractionHistoryModal.value = false;
  selectedCustomerForHistory.value = null;
  // Reload customers để refresh latest interaction
  loadCustomers(pagination.value.current_page);
};

const openZaloChat = (customer) => {
  selectedCustomerForZalo.value = customer;
  showZaloChatModal.value = true;
};

const closeZaloChatModal = () => {
  showZaloChatModal.value = false;
  selectedCustomerForZalo.value = null;
};

const createEnrollment = (customer) => {
  // Navigate to enrollment creation page with customer info
  window.location.href = `/enrollments/create?customer_id=${customer.id}`;
};

const loadCustomers = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      per_page: 15,
      search: filters.value.search || undefined,
      stage: filters.value.stage || undefined,
      branch_id: filters.value.branch_id || undefined,
      sort_by: sortBy.value,
      sort_dir: sortDir.value,
    };
    const response = await api.get('/api/customers', { params });
    if (response.data.success) {
      customers.value = response.data.data.data;
      pagination.value = {
        current_page: response.data.data.current_page,
        last_page: response.data.data.last_page,
        from: response.data.data.from,
        to: response.data.data.to,
        total: response.data.data.total,
      };
    }
  } catch (error) {
    console.error('Failed to load customers:', error);
    swal.error(t('common.error_occurred'));
  } finally {
    loading.value = false;
  }
};

const loadBranches = async () => {
  try {
    const response = await api.get('/api/branches/list');
    if (response.data.success) {
      branches.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load branches:', error);
  }
};

let searchTimeout;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => loadCustomers(1), 500);
};

const resetFilters = () => {
  filters.value = {
    search: '',
    stage: '',
    branch_id: '',
  };
  sortBy.value = 'created_at';
  sortDir.value = 'desc';
  loadCustomers(1);
};

const editCustomer = (customer) => {
  selectedCustomer.value = customer;
  showEditModal.value = true;
};

const deleteCustomer = async (customer) => {
  const result = await swal.confirmDelete(
    `${t('customers.confirm_delete')}: ${customer.name}?`
  );

  if (!result.isConfirmed) return;

  try {
    const response = await api.delete(`/api/customers/${customer.id}`);
    if (response.data.success) {
      swal.success(response.data.message);
      loadCustomers(pagination.value.current_page);
    }
  } catch (error) {
    console.error('Delete error:', error);
    swal.error(error.response?.data?.message || t('common.error_occurred'));
  }
};

const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  selectedCustomer.value = null;
};

const handleSaved = () => {
  closeModal();
  loadCustomers(pagination.value.current_page);
};

onMounted(() => {
  loadCustomers();
  if (authStore.isSuperAdmin) {
    loadBranches();
  }
});
</script>

<style scoped>
.scale-85-container {
  font-size: 85%;
}

.scale-85-container th,
.scale-85-container td {
  padding: 0.85rem 1.2rem !important;
}

.scale-85-container h1 {
  font-size: 1.4rem !important;
}

.scale-85-container button {
  font-size: 0.85rem !important;
  padding: 0.4rem 0.8rem !important;
}

.scale-85-container svg {
  width: 1.1rem !important;
  height: 1.1rem !important;
}

/* Override for sort icons */
.scale-85-container th svg {
  width: 0.75rem !important;
  height: 0.75rem !important;
}
</style>
