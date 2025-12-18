<template>
  <div class="max-w-7xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ t('accounting.categories') }}</h2>
        <p class="text-gray-600 mt-1">{{ t('accounting.categories_description') }}</p>
      </div>
      <button
        @click="openCreateModal"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center space-x-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>{{ t('accounting.add_category') }}</span>
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
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('accounting.cost_type') }}</label>
          <select
            v-model="filters.cost_type"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">{{ t('accounting.all_cost_types') }}</option>
            <option value="fixed">{{ t('accounting.fixed') }}</option>
            <option value="variable">{{ t('accounting.variable') }}</option>
            <option value="infrastructure">{{ t('accounting.infrastructure') }}</option>
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

    <!-- Table View -->
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
              {{ t('accounting.code') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.name') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('accounting.parent') }}
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
          <tr v-else-if="categories.length === 0">
            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
              {{ t('accounting.no_categories') }}
            </td>
          </tr>
          <tr v-else v-for="category in categories" :key="category.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                class="px-2 py-1 text-xs font-medium rounded-full"
                :class="category.type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
              >
                {{ t(`accounting.${category.type}`) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <span v-if="category.cost_type" class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                {{ t(`accounting.${category.cost_type}`) }}
              </span>
              <span v-else>-</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ category.code }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">
              {{ category.name }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ category.parent?.name || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                class="px-2 py-1 text-xs font-medium rounded-full"
                :class="category.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
              >
                {{ category.is_active ? t('accounting.active') : t('accounting.inactive') }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button
                @click="openEditModal(category)"
                class="text-blue-600 hover:text-blue-900 mr-3"
              >
                {{ t('accounting.edit') }}
              </button>
              <button
                @click="confirmDelete(category)"
                class="text-red-600 hover:text-red-900"
              >
                {{ t('accounting.delete') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Category Modal -->
    <CategoryModal
      v-if="showModal"
      :category="selectedCategory"
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
import CategoryModal from './CategoryModal.vue';

const { t } = useI18n();

const loading = ref(false);
const categories = ref([]);
const showModal = ref(false);
const selectedCategory = ref(null);
const filters = ref({
  search: '',
  type: '',
  cost_type: '',
  is_active: ''
});

const fetchCategories = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/accounting/account-categories', {
      params: filters.value
    });
    categories.value = response.data;
  } catch (error) {
    console.error('Error fetching categories:', error);
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  selectedCategory.value = null;
  showModal.value = true;
};

const openEditModal = (category) => {
  selectedCategory.value = category;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  selectedCategory.value = null;
};

const handleSaved = () => {
  closeModal();
  fetchCategories();
};

const confirmDelete = async (category) => {
  if (!confirm(t('accounting.confirm_delete'))) return;
  
  try {
    await axios.delete(`/api/accounting/account-categories/${category.id}`);
    await fetchCategories();
  } catch (error) {
    console.error('Error deleting category:', error);
    alert(error.response?.data?.message || t('accounting.delete_error'));
  }
};

watch(filters, () => {
  fetchCategories();
}, { deep: true });

onMounted(() => {
  fetchCategories();
});
</script>

