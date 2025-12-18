<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">{{ t('settings.language_management') }}</h2>
          <p class="text-sm text-gray-600 mt-1">{{ t('settings.manage_system_languages') }}</p>
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

    <!-- Languages Grid -->
    <div v-else class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="language in languages"
          :key="language.id"
          class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
        >
          <!-- Language Header -->
          <div class="flex items-start justify-between mb-3">
            <div class="flex items-center space-x-3">
              <span class="text-4xl">{{ language.flag }}</span>
              <div>
                <h3 class="font-semibold text-gray-900">{{ language.name }}</h3>
                <span class="text-xs font-mono bg-gray-100 text-gray-600 px-2 py-1 rounded">
                  {{ language.code }}
                </span>
              </div>
            </div>
            <div class="flex flex-col items-end space-y-1">
              <span
                v-if="language.is_default"
                class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full font-medium"
              >
                ⭐ {{ t('settings.default') }}
              </span>
              <span
                :class="[
                  'px-2 py-1 text-xs rounded-full font-medium',
                  language.is_active
                    ? 'bg-green-100 text-green-800'
                    : 'bg-red-100 text-red-800'
                ]"
              >
                {{ language.is_active ? t('common.active') : t('common.inactive') }}
              </span>
            </div>
          </div>

          <!-- Language Stats -->
          <div class="mb-3 p-3 bg-gray-50 rounded-lg">
            <div class="text-sm text-gray-600">
              <div class="flex justify-between items-center">
                <span>{{ t('settings.direction') }}:</span>
                <span class="font-medium text-gray-900">{{ language.direction.toUpperCase() }}</span>
              </div>
              <div class="flex justify-between items-center mt-1">
                <span>{{ t('settings.translations') }}:</span>
                <span class="font-medium text-gray-900">{{ language.translations_count || 0 }}</span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-between pt-3 border-t border-gray-200">
            <div class="flex space-x-2">
              <button
                @click="$emit('view-translations', language)"
                class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-1"
                :title="t('settings.view_translations')"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                </svg>
                <span>{{ t('settings.translations') }}</span>
              </button>
              <button
                @click="editLanguage(language)"
                class="p-1.5 text-green-600 hover:bg-green-50 rounded-lg transition"
                :title="t('common.edit')"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
            </div>
            <div class="flex space-x-2">
              <button
                v-if="!language.is_default"
                @click="setAsDefault(language)"
                class="p-1.5 text-yellow-600 hover:bg-yellow-50 rounded-lg transition"
                :title="t('settings.set_default')"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
              </button>
              <button
                v-if="!language.is_default"
                @click="deleteLanguage(language)"
                class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition"
                :title="t('common.delete')"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

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
import api from '../../services/api';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';
import LanguageModal from './LanguageModal.vue';

const { t } = useI18n();
const swal = useSwal();

defineEmits(['view-translations']);

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

