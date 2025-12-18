<template>
  <div class="bg-white rounded-lg shadow">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">{{ t('settings.manage_translations') }}</h2>
          <p class="text-sm text-gray-600 mt-1">Manage translation strings for all languages</p>
        </div>
        <button
          @click="showCreateModal = true"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add Translation
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Language Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Language</label>
          <select
            v-model="filters.language_id"
            @change="loadTranslations"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Languages</option>
            <option v-for="lang in languages" :key="lang.id" :value="lang.id">
              {{ lang.flag }} {{ lang.name }}
            </option>
          </select>
        </div>

        <!-- Group Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Group</label>
          <select
            v-model="filters.group"
            @change="loadTranslations"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Groups</option>
            <option v-for="group in groups" :key="group" :value="group">
              {{ group }}
            </option>
          </select>
        </div>

        <!-- Search -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input
            v-model="filters.search"
            @input="debouncedSearch"
            type="text"
            placeholder="Search key or value..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="p-8 text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="text-gray-600 mt-2">{{ t('common.loading') }}</p>
    </div>

    <!-- Translations Table -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Language
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Group
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Key
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Value
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="translation in translations.data" :key="translation.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="text-sm">{{ translation.language.flag }} {{ translation.language.name }}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded">{{ translation.group }}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="text-sm font-mono text-gray-900">{{ translation.key }}</span>
            </td>
            <td class="px-6 py-4">
              <div class="text-sm text-gray-900 max-w-md truncate">{{ translation.value }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex justify-end gap-2">
                <button
                  @click="editTranslation(translation)"
                  class="text-green-600 hover:text-green-900"
                  title="Edit"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button
                  @click="deleteTranslation(translation)"
                  class="text-red-600 hover:text-red-900"
                  title="Delete"
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

      <!-- Empty State -->
      <div v-if="translations.data && translations.data.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
        </svg>
        <p class="mt-2 text-sm text-gray-600">{{ t('common.no_data') }}</p>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="translations.data && translations.data.length > 0" class="px-6 py-4 border-t border-gray-200">
      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-700">
          Showing {{ translations.from }} to {{ translations.to }} of {{ translations.total }} entries
        </div>
        <div class="flex gap-2">
          <button
            v-for="page in paginationPages"
            :key="page"
            @click="changePage(page)"
            :class="[
              'px-3 py-1 rounded',
              page === translations.current_page
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <TranslationModal
      v-if="showCreateModal || showEditModal"
      :translation="selectedTranslation"
      :languages="languages"
      :groups="groups"
      :is-edit="showEditModal"
      @close="closeModal"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../services/api';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';
import TranslationModal from '../../components/settings/TranslationModal.vue';

const { t } = useI18n();
const swal = useSwal();
const route = useRoute();

const translations = ref({ data: [] });
const languages = ref([]);
const groups = ref([]);
const loading = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedTranslation = ref(null);

const filters = ref({
  language_id: route.query.language_id || '',
  group: '',
  search: '',
  page: 1,
});

let searchTimeout = null;

const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadTranslations();
  }, 500);
};

const loadLanguages = async () => {
  try {
    const response = await api.get('/api/settings/languages');
    if (response.data.success) {
      languages.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load languages:', error);
  }
};

const loadGroups = async () => {
  try {
    const response = await api.get('/api/settings/translations/groups');
    if (response.data.success) {
      groups.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load groups:', error);
  }
};

const loadTranslations = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (filters.value.language_id) params.append('language_id', filters.value.language_id);
    if (filters.value.group) params.append('group', filters.value.group);
    if (filters.value.search) params.append('search', filters.value.search);
    params.append('page', filters.value.page);

    const response = await api.get(`/api/settings/translations?${params.toString()}`);
    if (response.data.success) {
      translations.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load translations:', error);
  } finally {
    loading.value = false;
  }
};

const editTranslation = (translation) => {
  selectedTranslation.value = translation;
  showEditModal.value = true;
};

const deleteTranslation = async (translation) => {
  const result = await swal.confirmDelete(
    `Bạn có chắc chắn muốn xóa translation "${translation.key}"?`
  );
  
  if (!result.isConfirmed) return;

  try {
    const response = await api.delete(`/api/settings/translations/${translation.id}`);
    if (response.data.success) {
      swal.success(response.data.message);
      loadTranslations();
    }
  } catch (error) {
    console.error('Failed to delete translation:', error);
    swal.error('Có lỗi xảy ra khi xóa translation');
  }
};

const changePage = (page) => {
  filters.value.page = page;
  loadTranslations();
};

const paginationPages = computed(() => {
  if (!translations.value.last_page) return [];
  const pages = [];
  for (let i = 1; i <= translations.value.last_page; i++) {
    pages.push(i);
  }
  return pages;
});

const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  selectedTranslation.value = null;
};

const handleSaved = () => {
  closeModal();
  loadTranslations();
};

onMounted(() => {
  loadLanguages();
  loadGroups();
  loadTranslations();
});
</script>

