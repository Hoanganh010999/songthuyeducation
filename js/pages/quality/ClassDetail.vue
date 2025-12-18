<template>
  <div class="flex flex-col h-full">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button @click="goBackToClassList" class="text-gray-600 hover:text-gray-900">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ classData?.name || 'Loading...' }}</h1>
            <p class="text-sm text-gray-500">{{ classData?.code }} - {{ classData?.subject?.name }}</p>
          </div>
        </div>
        <div class="flex items-center space-x-4">
          <span class="px-3 py-1 text-sm font-medium rounded-full" :class="statusClass">
            {{ classData?.status }}
          </span>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white border-b border-gray-200">
      <nav class="flex -mb-px px-6">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'py-4 px-6 text-sm font-medium border-b-2 transition-colors',
            activeTab === tab.id
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          {{ t(tab.label) }}
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="flex-1 overflow-y-auto bg-gray-50">
      <div v-if="loading" class="flex items-center justify-center h-full">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-4 text-gray-600">{{ t('class_detail.loading') }}</p>
        </div>
      </div>
      
      <div v-else>
        <WeeklyScheduleTab v-if="activeTab === 'schedule'" :class-id="classId" :class-data="classData" @refresh="loadClassData" />
        <LessonSessionsTab v-else-if="activeTab === 'lessons'" :class-id="classId" :class-data="classData" @refresh="loadClassData" />
        <StudentsTab v-else-if="activeTab === 'students'" :class-id="classId" :class-data="classData" @refresh="loadClassData" />
        <OverviewTab v-else-if="activeTab === 'overview'" :class-id="classId" :class-data="classData" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from '../../composables/useI18n';
import api from '../../api';
import Swal from 'sweetalert2';
import WeeklyScheduleTab from './classDetail/WeeklyScheduleTab.vue';
import LessonSessionsTab from './classDetail/LessonSessionsTab.vue';
import StudentsTab from './classDetail/StudentsTab.vue';
import OverviewTab from './classDetail/OverviewTab.vue';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const classId = ref(route.params.id);
const classData = ref(null);
const loading = ref(true);
const activeTab = ref('schedule');

const tabs = [
  { id: 'schedule', label: 'class_detail.tab_schedule' },
  { id: 'lessons', label: 'class_detail.tab_lessons' },
  { id: 'students', label: 'class_detail.tab_students' },
  { id: 'overview', label: 'class_detail.tab_overview' },
];

const statusClass = computed(() => {
  const status = classData.value?.status;
  if (status === 'active') return 'bg-green-100 text-green-800';
  if (status === 'completed') return 'bg-blue-100 text-blue-800';
  if (status === 'cancelled') return 'bg-red-100 text-red-800';
  return 'bg-gray-100 text-gray-800';
});

const loadClassData = async () => {
  try {
    loading.value = true;
    const response = await api.classes.getDetail(classId.value);
    classData.value = response.data.data;
  } catch (error) {
    console.error('Error loading class data:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Failed to load class data',
    });
  } finally {
    loading.value = false;
  }
};

const goBackToClassList = () => {
  // Navigate to Quality Management with Classes module selected
  router.push({ path: '/quality', query: { module: 'classes' } });
};

onMounted(() => {
  loadClassData();
});
</script>

