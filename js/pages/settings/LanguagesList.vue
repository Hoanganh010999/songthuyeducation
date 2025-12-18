<template>
  <div class="bg-white rounded-lg shadow">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">{{ t('settings.language_management') }}</h2>
          <p class="text-sm text-gray-600 mt-1">Manage system languages and translations</p>
        </div>
        <button
          @click="showCreateModal = true"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          {{ t('settings.add_language') }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="p-8 text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="text-gray-600 mt-2">{{ t('common.loading') }}</p>
    </div>

    <!-- Languages List -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('settings.language_name') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('settings.language_code') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('settings.language_flag') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('common.status') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('settings.is_default') }}
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('common.actions') }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="language in languages" :key="language.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">{{ language.name }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 py-1 text-xs font-mono bg-gray-100 text-gray-800 rounded">
                {{ language.code }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="text-2xl">{{ language.flag }}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="[
                  'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                  language.is_active
                    ? 'bg-green-100 text-green-800'
                    : 'bg-red-100 text-red-800'
                ]"
              >
                {{ language.is_active ? t('common.active') : t('common.inactive') }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span v-if="language.is_default" class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                ⭐ {{ t('settings.is_default') }}
              </span>
              <button
                v-else
                @click="setAsDefault(language)"
                class="text-xs text-blue-600 hover:text-blue-800 underline"
              >
                {{ t('settings.set_default') }}
              </button>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex justify-end gap-2">
                <button
                  @click="viewTranslations(language)"
                  class="text-blue-600 hover:text-blue-900"
                  title="View Translations"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
                <button
                  @click="editLanguage(language)"
                  class="text-green-600 hover:text-green-900"
                  title="Edit"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button
                  v-if="!language.is_default"
                  @click="deleteLanguage(language)"
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
      <div v-if="languages.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
        </svg>
        <p class="mt-2 text-sm text-gray-600">{{ t('common.no_data') }}</p>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <LanguageModal
      v-if="showCreateModal || showEditModal"
      :language="selectedLanguage"
      :is-edit="showEditModal"
      @close="closeModal"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../services/api';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';
import LanguageModal from '../../components/settings/LanguageModal.vue';

const { t } = useI18n();
const swal = useSwal();
const router = useRouter();

const languages = ref([]);
const loading = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedLanguage = ref(null);

const loadLanguages = async () => {
  loading.value = true;
  try {
    const response = await api.get('/api/settings/languages');
    console.log('Languages response:', response.data);
    if (response.data.success) {
      languages.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load languages:', error);
    swal.error('Có lỗi xảy ra khi tải danh sách ngôn ngữ');
  } finally {
    loading.value = false;
  }
};

const editLanguage = (language) => {
  selectedLanguage.value = language;
  showEditModal.value = true;
};

const deleteLanguage = async (language) => {
  const result = await swal.confirmDelete(
    `Bạn có chắc chắn muốn xóa ngôn ngữ "${language.name}"?`
  );
  
  if (!result.isConfirmed) return;

  try {
    const response = await api.delete(`/api/settings/languages/${language.id}`);
    if (response.data.success) {
      swal.success(response.data.message);
      loadLanguages();
    }
  } catch (error) {
    console.error('Failed to delete language:', error);
    swal.error(error.response?.data?.message || 'Có lỗi xảy ra khi xóa ngôn ngữ');
  }
};

const setAsDefault = async (language) => {
  try {
    const response = await api.post(`/api/settings/languages/${language.id}/set-default`);
    if (response.data.success) {
      swal.success(response.data.message);
      loadLanguages();
    }
  } catch (error) {
    console.error('Failed to set default language:', error);
    swal.error('Có lỗi xảy ra khi đặt ngôn ngữ mặc định');
  }
};

const viewTranslations = (language) => {
  router.push({ name: 'SettingsTranslations', query: { language_id: language.id } });
};

const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  selectedLanguage.value = null;
};

const handleSaved = () => {
  closeModal();
  loadLanguages();
};

onMounted(() => {
  loadLanguages();
});
</script>

