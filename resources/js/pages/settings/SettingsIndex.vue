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
        <div v-if="canViewAccessControl" class="mb-2">
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
              v-if="canManageRoles"
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
              v-if="canManagePermissions"
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
        <div v-if="canViewLanguages" class="mb-2">
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
              v-if="canManageLanguages"
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
        <div v-if="canViewGeneralSettings" class="mb-2">
          <button
            @click="toggleCategory('general')"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-gray-50': expandedCategories.includes('general') }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">{{ t('settings.general_settings') }}</div>
                <div class="text-xs text-gray-500">{{ t('settings.general_settings_description') }}</div>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
              </svg>
              <span class="text-sm font-medium">{{ t('settings.basic_settings') }}</span>
            </button>
          </div>
        </div>

        <!-- Google Drive Settings -->
        <div v-if="canViewGoogleDrive" class="mb-2">
          <button
            @click="selectItem('google-drive-settings')"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'google-drive-settings' }"
          >
            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center border border-gray-200">
              <!-- Google Drive Official Icon -->
              <svg class="w-6 h-6" viewBox="0 0 87.3 78" xmlns="http://www.w3.org/2000/svg">
                <path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z" fill="#0066da"/>
                <path d="m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-25.4 44a9.06 9.06 0 0 0 -1.2 4.5h27.5z" fill="#00ac47"/>
                <path d="m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.502l5.852 11.5z" fill="#ea4335"/>
                <path d="m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z" fill="#00832d"/>
                <path d="m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z" fill="#2684fc"/>
                <path d="m73.4 26.5-12.7-22c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 28h27.45c0-1.55-.4-3.1-1.2-4.5z" fill="#ffba00"/>
              </svg>
            </div>
            <div class="flex-1 text-left">
              <div class="font-semibold text-gray-900">{{ t('google_drive.settings_title') }}</div>
              <div class="text-xs text-gray-500">{{ t('google_drive.manage_integration') }}</div>
            </div>
          </button>
        </div>

        <!-- Scheduled Tasks -->
        <div v-if="canViewScheduledTasks" class="mb-2">
          <button
            @click="selectItem('scheduled-tasks')"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50 text-blue-600': selectedItem === 'scheduled-tasks' }"
          >
            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="flex-1 text-left">
              <div class="font-semibold text-gray-900">{{ t('scheduled_tasks.title') }}</div>
              <div class="text-xs text-gray-500">{{ t('scheduled_tasks.menu_description') }}</div>
            </div>
          </button>
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

      <!-- Google Drive Settings -->
      <div v-else-if="selectedItem === 'google-drive-settings'" class="p-6">
        <GoogleDriveSettingsContent />
      </div>

      <!-- Scheduled Tasks -->
      <div v-else-if="selectedItem === 'scheduled-tasks'" class="p-6">
        <ScheduledTasksContent />
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
import { ref, computed } from 'vue';
import { useI18n } from '../../composables/useI18n';
import RolesContent from '../../components/settings/RolesContent.vue';
import PermissionsContent from '../../components/settings/PermissionsContent.vue';
import LanguagesContent from '../../components/settings/LanguagesContent.vue';
import GeneralSettingsContent from '../../components/settings/GeneralSettingsContent.vue';
import GoogleDriveSettingsContent from '../../components/settings/GoogleDriveSettingsContent.vue';
import ScheduledTasksContent from '../../components/settings/ScheduledTasksContent.vue';
import TranslationsModal from '../../components/settings/TranslationsModal.vue';
import { useAuthStore } from '../../stores/auth';

const { t } = useI18n();
const authStore = useAuthStore();

const expandedCategories = ref(['access-control']); // Default expanded
const selectedItem = ref('roles-list'); // Default selected
const showTranslationsModal = ref(false);
const selectedLanguage = ref(null);

// Check if user has permission to see Access Control category
const canViewAccessControl = computed(() => {
  return authStore.hasPermission('roles.view') || authStore.hasPermission('permissions.view');
});

// Check if user has permission to see Languages category
const canViewLanguages = computed(() => {
  return authStore.hasPermission('languages.view') || authStore.hasPermission('system-settings.view');
});

// Check if user has permission to see General Settings
const canViewGeneralSettings = computed(() => {
  return authStore.hasPermission('system-settings.view') || authStore.hasPermission('system-settings.edit');
});

// Check if user has permission to see Google Drive Settings
const canViewGoogleDrive = computed(() => {
  return authStore.hasPermission('google-drive.settings') || authStore.hasPermission('google-drive.view');
});

// Check if user has permission to see Scheduled Tasks
const canViewScheduledTasks = computed(() => {
  return authStore.hasPermission('settings.view') || authStore.hasPermission('settings.edit');
});

// Check individual permissions
const canManageRoles = computed(() => authStore.hasPermission('roles.view'));
const canManagePermissions = computed(() => authStore.hasPermission('permissions.view'));
const canManageLanguages = computed(() => authStore.hasPermission('languages.view'));

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

