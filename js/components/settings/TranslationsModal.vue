<template>
  <!-- Full Screen Modal -->
  <div class="fixed inset-0 z-50 overflow-hidden">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

    <!-- Modal Content -->
    <div class="absolute inset-0 overflow-hidden">
      <div class="absolute inset-0 overflow-hidden">
        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
          <div class="pointer-events-auto w-screen max-w-6xl transform transition ease-in-out duration-300">
            <div class="flex h-full flex-col bg-white shadow-xl">
              <!-- Header -->
              <div class="bg-blue-600 px-6 py-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <span class="text-4xl">{{ language.flag }}</span>
                    <div>
                      <h2 class="text-xl font-semibold text-white">
                        {{ t('settings.translations_for') }}: {{ language.name }}
                      </h2>
                      <p class="text-sm text-blue-100">{{ t('settings.manage_translations_desc') }}</p>
                    </div>
                  </div>
                  <button
                    @click="$emit('close')"
                    class="rounded-lg p-2 text-white hover:bg-blue-700 transition"
                  >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Filters & Actions -->
              <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-4">
                    <!-- Group Filter -->
                    <select
                      v-model="filters.group"
                      @change="loadTranslations"
                      class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      <option value="">{{ t('settings.all_groups') }}</option>
                      <option v-for="group in groups" :key="group" :value="group">
                        {{ group }}
                      </option>
                    </select>

                    <!-- Search -->
                    <input
                      v-model="filters.search"
                      @input="debouncedSearch"
                      type="text"
                      :placeholder="t('settings.search_translations')"
                      class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 w-64"
                    />
                  </div>

                  <button
                    @click="showAddModal = true"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ t('settings.add_translation') }}
                  </button>
                </div>
              </div>

              <!-- Content -->
              <div class="flex-1 overflow-y-auto p-6">
                <!-- Loading -->
                <div v-if="loading" class="flex items-center justify-center h-64">
                  <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                </div>

                <!-- Translations by Group -->
                <div v-else>
                  <div v-for="(items, group) in groupedTranslations" :key="group" class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                      <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">{{ group }}</span>
                        <span class="text-sm text-gray-500">({{ items.length }} {{ t('settings.items') }})</span>
                      </h3>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                      <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                          <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-1/3">
                              {{ t('settings.key') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                              {{ t('settings.value') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-24">
                              {{ t('common.actions') }}
                            </th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                          <tr v-for="translation in items" :key="translation.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                              <code class="text-sm font-mono text-gray-900 bg-gray-100 px-2 py-1 rounded">
                                {{ translation.key }}
                              </code>
                            </td>
                            <td class="px-4 py-3">
                              <div class="text-sm text-gray-900">{{ translation.value }}</div>
                            </td>
                            <td class="px-4 py-3 text-right">
                              <div class="flex justify-end space-x-2">
                                <button
                                  @click="editTranslation(translation)"
                                  class="p-1 text-blue-600 hover:bg-blue-50 rounded transition"
                                >
                                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                  </svg>
                                </button>
                                <button
                                  @click="deleteTranslation(translation)"
                                  class="p-1 text-red-600 hover:bg-red-50 rounded transition"
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
                    </div>
                  </div>

                  <!-- Empty State -->
                  <div v-if="Object.keys(groupedTranslations).length === 0" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-600">{{ t('common.no_data') }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Translation Modal -->
    <TranslationEditModal
      v-if="showAddModal || showEditModal"
      :language="language"
      :translation="selectedTranslation"
      :is-edit="showEditModal"
      :groups="groups"
      @close="closeEditModal"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';
import TranslationEditModal from './TranslationEditModal.vue';

const props = defineProps({
  language: {
    type: Object,
    required: true
  }
});

defineEmits(['close']);

const { t } = useI18n();
const swal = useSwal();

const translations = ref([]);
const groups = ref([]);
const loading = ref(false);
const filters = ref({
  group: '',
  search: ''
});
const showAddModal = ref(false);
const showEditModal = ref(false);
const selectedTranslation = ref(null);
let searchTimeout = null;

const groupedTranslations = computed(() => {
  const grouped = {};
  translations.value.forEach(translation => {
    if (!grouped[translation.group]) {
      grouped[translation.group] = [];
    }
    grouped[translation.group].push(translation);
  });
  return grouped;
});

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
    params.append('language_id', props.language.id);
    params.append('per_page', 1000); // Load all translations (no pagination limit)
    if (filters.value.group) params.append('group', filters.value.group);
    if (filters.value.search) params.append('search', filters.value.search);

    const response = await api.get(`/api/settings/translations?${params.toString()}`);
    if (response.data.success) {
      translations.value = response.data.data.data || response.data.data;
    }
  } catch (error) {
    console.error('Failed to load translations:', error);
  } finally {
    loading.value = false;
  }
};

const debouncedSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadTranslations();
  }, 500);
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

const closeEditModal = () => {
  showAddModal.value = false;
  showEditModal.value = false;
  selectedTranslation.value = null;
};

const handleSaved = () => {
  closeEditModal();
  loadTranslations();
};

onMounted(() => {
  loadGroups();
  loadTranslations();
});
</script>

