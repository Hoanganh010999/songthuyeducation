<template>
  <div class="max-w-7xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ t('accounting.account_items') }}</h2>
        <p class="text-gray-600 mt-1">{{ t('accounting.items_subtitle') }}</p>
      </div>
      <button
        @click="openCreateModal"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center space-x-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>{{ t('accounting.add_item') }}</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('accounting.type') }}</label>
          <select
            v-model="filters.type"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">{{ t('accounting.all_types') }}</option>
            <option value="income">{{ t('accounting.income') }}</option>
            <option value="expense">{{ t('accounting.expense') }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('accounting.status') }}</label>
          <select
            v-model="filters.is_active"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">{{ t('accounting.all_status') }}</option>
            <option value="1">{{ t('accounting.active') }}</option>
            <option value="0">{{ t('accounting.inactive') }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.type') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.cost_type') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.category') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.code') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.name') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.status') }}
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.actions') }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="loading">
            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
              {{ t('accounting.loading') }}
            </td>
          </tr>
          <tr v-else-if="items.length === 0">
            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
              {{ t('accounting.no_items') }}
            </td>
          </tr>
          <tr v-else v-for="item in items" :key="item.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                class="px-2 py-1 text-xs font-medium rounded-full"
                :class="item.type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
              >
                {{ t(`accounting.${item.type}`) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <span v-if="item.category?.cost_type" class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                {{ t(`accounting.${item.category.cost_type}`) }}
              </span>
              <span v-else>-</span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ item.category?.name || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ item.code }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">
              {{ item.name }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                class="px-2 py-1 text-xs font-medium rounded-full"
                :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
              >
                {{ item.is_active ? t('accounting.active') : t('accounting.inactive') }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button
                @click="openEditModal(item)"
                class="text-blue-600 hover:text-blue-900 mr-3"
              >
                {{ t('accounting.edit') }}
              </button>
              <button
                @click="confirmDelete(item)"
                class="text-red-600 hover:text-red-900"
              >
                {{ t('accounting.delete') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Item Modal -->
    <AccountItemModal
      v-if="showModal"
      :item="selectedItem"
      :categories="categories"
      @close="closeModal"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';
import AccountItemModal from './AccountItemModal.vue';

const { t } = useI18n();

const loading = ref(false);
const items = ref([]);
const categories = ref([]);
const showModal = ref(false);
const selectedItem = ref(null);
const filters = ref({
  search: '',
  type: '',
  is_active: ''
});

const fetchCategories = async () => {
  try {
    const response = await axios.get('/api/accounting/account-categories');
    categories.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching categories:', error);
  }
};

const fetchItems = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/accounting/account-items', {
      params: filters.value
    });
    items.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching account items:', error);
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  selectedItem.value = null;
  showModal.value = true;
};

const openEditModal = (item) => {
  selectedItem.value = item;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  selectedItem.value = null;
};

const handleSaved = () => {
  closeModal();
  fetchItems();
};

const confirmDelete = async (item) => {
  if (!confirm(t('accounting.confirm_delete'))) return;
  
  try {
    await axios.delete(`/api/accounting/account-items/${item.id}`);
    await fetchItems();
  } catch (error) {
    console.error('Error deleting account item:', error);
    alert(error.response?.data?.message || t('accounting.delete_error'));
  }
};

// Watch filters
watch(filters, () => {
  fetchItems();
}, { deep: true });

onMounted(() => {
  fetchCategories();
  fetchItems();
});
</script>

