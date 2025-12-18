<template>
  <div class="flex h-full">
    <!-- Settings Menu (Left Panel) -->
    <div class="w-80 bg-white border-r border-gray-200 overflow-y-auto">
      <div class="p-6 border-b border-gray-200">
        <h1 class="text-2xl font-bold text-gray-900">{{ t('settings.title') }}</h1>
        <p class="text-sm text-gray-600 mt-1">{{ t('settings.description') }}</p>
      </div>

      <!-- Settings Categories -->
      <div class="p-4">
        <!-- Access Control -->
        <div class="mb-2">
          <button
            @click="toggleCategory('access-control')"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-gray-50': expandedCategories.includes('access-control') }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">{{ t('settings.access_control') }}</div>
                <div class="text-xs text-gray-500">{{ t('settings.manage_access') }}</div>
              </div>
            </div>
            <svg
              class="w-5 h-5 text-gray-400 transition-transform"
              :class="{ 'rotate-90': expandedCategories.includes('access-control') }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>

          <!-- Sub-items -->
          <div v-if="expandedCategories.includes('access-control')" class="ml-4 mt-2 space-y-1">
            <button
              @click="selectItem('roles-list')"
              class="w-full flex items-center space-x-3 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-left"
              :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'roles-list' }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <span class="text-sm font-medium">{{ t('roles.title') }}</span>
            </button>
            <button
              @click="selectItem('permissions-list')"
              class="w-full flex items-center space-x-3 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-left"
              :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'permissions-list' }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              <span class="text-sm font-medium">{{ t('permissions.title') }}</span>
            </button>
          </div>
        </div>

        <!-- Languages & Translations -->
        <div class="mb-2">
          <button
            @click="toggleCategory('languages')"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-gray-50': expandedCategories.includes('languages') }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">{{ t('settings.languages') }}</div>
                <div class="text-xs text-gray-500">{{ t('settings.manage_languages') }}</div>
              </div>
            </div>
            <svg
              class="w-5 h-5 text-gray-400 transition-transform"
              :class="{ 'rotate-90': expandedCategories.includes('languages') }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>

          <!-- Sub-items -->
          <div v-if="expandedCategories.includes('languages')" class="ml-4 mt-2 space-y-1">
            <button
              @click="selectItem('languages-list')"
              class="w-full flex items-center space-x-3 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-left"
              :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'languages-list' }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
              <span class="text-sm font-medium">{{ t('settings.language_list') }}</span>
            </button>
          </div>
        </div>

        <!-- General Settings -->
        <div class="mb-2">
          <button
            @click="toggleCategory('general')"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-gray-50': expandedCategories.includes('general') }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">General Settings</div>
                <div class="text-xs text-gray-500">Cài đặt chung hệ thống</div>
              </div>
            </div>
            <svg
              class="w-5 h-5 text-gray-400 transition-transform"
              :class="{ 'rotate-90': expandedCategories.includes('general') }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>

          <!-- Sub-items -->
          <div v-if="expandedCategories.includes('general')" class="ml-4 mt-2 space-y-1">
            <button
              @click="selectItem('general-settings')"
              class="w-full flex items-center space-x-3 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-left"
              :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'general-settings' }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="text-sm font-medium">Cài đặt tiền tệ & Chung</span>
            </button>
          </div>
        </div>

        <!-- Future Settings Categories -->
        <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200 border-dashed">
          <div class="flex items-center space-x-2 text-gray-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span class="text-sm">{{ t('settings.more_settings_coming') }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Content Area (Right Panel) -->
    <div class="flex-1 bg-gray-50 overflow-y-auto">
      <!-- Welcome State -->
      <div v-if="!selectedItem" class="flex items-center justify-center h-full">
        <div class="text-center">
          <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ t('settings.welcome_title') }}</h2>
          <p class="text-gray-600">{{ t('settings.welcome_message') }}</p>
        </div>
      </div>

      <!-- Roles List -->
      <div v-else-if="selectedItem === 'roles-list'" class="p-6">
        <RolesContent />
      </div>

      <!-- Permissions List -->
      <div v-else-if="selectedItem === 'permissions-list'" class="p-6">
        <PermissionsContent />
      </div>

      <!-- Languages List -->
      <div v-else-if="selectedItem === 'languages-list'" class="p-6">
        <LanguagesContent @view-translations="openTranslationsModal" />
      </div>

      <!-- General Settings -->
      <div v-else-if="selectedItem === 'general-settings'">
        <GeneralSettingsContent />
      </div>
    </div>

    <!-- Translations Modal -->
    <TranslationsModal
      v-if="showTranslationsModal"
      :language="selectedLanguage"
      @close="closeTranslationsModal"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from '../../composables/useI18n';
import RolesContent from '../../components/settings/RolesContent.vue';
import PermissionsContent from '../../components/settings/PermissionsContent.vue';
import LanguagesContent from '../../components/settings/LanguagesContent.vue';
import GeneralSettingsContent from '../../components/settings/GeneralSettingsContent.vue';
import TranslationsModal from '../../components/settings/TranslationsModal.vue';

const { t } = useI18n();

const expandedCategories = ref(['access-control']); // Default expanded
const selectedItem = ref('roles-list'); // Default selected
const showTranslationsModal = ref(false);
const selectedLanguage = ref(null);

const toggleCategory = (category) => {
  const index = expandedCategories.value.indexOf(category);
  if (index > -1) {
    expandedCategories.value.splice(index, 1);
  } else {
    expandedCategories.value.push(category);
  }
};

const selectItem = (item) => {
  selectedItem.value = item;
};

const openTranslationsModal = (language) => {
  selectedLanguage.value = language;
  showTranslationsModal.value = true;
};

const closeTranslationsModal = () => {
  showTranslationsModal.value = false;
  selectedLanguage.value = null;
};
</script>

