<template>
  <div class="p-6">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">{{ t('quality.settings') }}</h1>
      <p class="text-gray-600 mt-1">{{ t('quality.settings_description') }}</p>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="-mb-px flex space-x-8">
        <button
          @click="activeTab = 'attendance_fee'"
          :class="[
            activeTab === 'attendance_fee'
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          {{ t('attendance_fee.title') }}
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div v-if="activeTab === 'attendance_fee'">
      <AttendanceFeeSettings />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from '../../composables/useI18n';
import AttendanceFeeSettings from './settings/AttendanceFeeSettings.vue';

const { t } = useI18n();
const activeTab = ref('attendance_fee');
</script>

