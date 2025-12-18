<template>
  <div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-bold text-gray-900">{{ t('classes.settings_title') }}</h2>
          <p class="text-sm text-gray-600 mt-1">{{ t('classes.settings_description') }}</p>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="flex -mb-px px-6">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeTab = tab.key"
          class="px-6 py-3 text-sm font-medium border-b-2 transition"
          :class="activeTab === tab.key 
            ? 'border-blue-500 text-blue-600' 
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
        >
          {{ tab.label }}
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="p-6">
      <AcademicYearsTab v-if="activeTab === 'academic_years'" />
      <SemestersTab v-else-if="activeTab === 'semesters'" />
      <StudyPeriodsTab v-else-if="activeTab === 'study_periods'" />
      <RoomsTab v-else-if="activeTab === 'rooms'" />
      <HolidaysTab v-else-if="activeTab === 'holidays'" />
      <GoogleDriveTab v-else-if="activeTab === 'google_drive'" />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from '../../composables/useI18n';
import AcademicYearsTab from './settings/AcademicYearsTab.vue';
import SemestersTab from './settings/SemestersTab.vue';
import StudyPeriodsTab from './settings/StudyPeriodsTab.vue';
import RoomsTab from './settings/RoomsTab.vue';
import HolidaysTab from './settings/HolidaysTab.vue';
import GoogleDriveTab from './settings/GoogleDriveTab.vue';

const { t } = useI18n();
const activeTab = ref('academic_years');

const tabs = [
  { key: 'academic_years', label: t('academic_years.title') },
  { key: 'semesters', label: t('semesters.title') },
  { key: 'study_periods', label: t('study_periods.title') },
  { key: 'rooms', label: t('rooms.title') },
  { key: 'holidays', label: t('holidays.title') },
  { key: 'google_drive', label: t('google_drive.title') },
];
</script>

