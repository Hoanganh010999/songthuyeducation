<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Sticky Header -->
    <div class="sticky top-0 z-10 bg-white border-b shadow-sm">
      <div class="max-w-5xl mx-auto px-4 py-3">
        <div class="flex items-center justify-between flex-wrap gap-3">
          <!-- Back & Navigation -->
          <div class="flex items-center gap-2">
            <button
              @click="goBack"
              class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition"
              :title="t('common.back')"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
            </button>
            <div class="h-6 w-px bg-gray-200"></div>
            <button
              @click="goToPrev"
              class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition"
              :title="t('calendar.previous_day')"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <button
              @click="goToToday"
              class="px-3 py-1.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
            >
              {{ t('calendar.today') }}
            </button>
            <button
              @click="goToNext"
              class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition"
              :title="t('calendar.next_day')"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>

          <!-- Current Date -->
          <div class="text-center">
            <div class="text-lg font-semibold text-gray-800">{{ calendarData?.date_formatted }}</div>
            <div class="text-xs text-gray-500">{{ calendarData?.day_name }}</div>
          </div>

          <!-- Branch Filter & Stats -->
          <div class="flex items-center gap-4">
            <!-- Branch Filter -->
            <select
              v-model="selectedBranchId"
              class="text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
              @change="loadData"
            >
              <option :value="null">{{ t('calendar.all_branches') }}</option>
              <option v-for="branch in calendarData?.branches" :key="branch.id" :value="branch.id">
                {{ branch.name }}
              </option>
            </select>

            <!-- Stats -->
            <div class="flex items-center gap-4 text-sm">
              <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
                <span class="text-gray-600">{{ calendarData?.total_sessions || 0 }} {{ t('calendar.sessions') }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                <span class="text-gray-600">{{ calendarData?.completed_sessions || 0 }} {{ t('calendar.completed') }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="max-w-5xl mx-auto px-4 py-6">
      <!-- Loading -->
      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="animate-spin rounded-full h-8 w-8 border-2 border-indigo-500 border-t-transparent"></div>
      </div>

      <!-- Empty State -->
      <div v-else-if="!hasAnySession" class="text-center py-20">
        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
          <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-700 mb-1">{{ t('calendar.no_classes') }}</h3>
        <p class="text-sm text-gray-500">{{ t('calendar.no_sessions_scheduled') }}</p>
      </div>

      <!-- Timeline -->
      <div v-else class="space-y-1">
        <div
          v-for="(sessions, hour) in calendarData?.sessions_by_hour"
          :key="hour"
          class="flex"
        >
          <!-- Time Column -->
          <div class="w-16 flex-shrink-0 pt-3">
            <span class="text-sm font-medium text-gray-400">{{ hour }}:00</span>
          </div>

          <!-- Sessions Column -->
          <div class="flex-1 pb-4 border-l border-gray-200 pl-4">
            <div class="space-y-2">
              <div
                v-for="session in sessions"
                :key="session.id"
                :class="[
                  'bg-white rounded-lg border-l-4 shadow-sm hover:shadow-md transition-shadow cursor-pointer p-4',
                  session.status === 'completed' ? 'border-l-emerald-500' : 'border-l-indigo-500'
                ]"
                @click="goToClass(session.class_id)"
              >
                <div class="flex items-start justify-between gap-4">
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                      <h4 class="font-semibold text-gray-800 truncate">{{ session.class_name }}</h4>
                      <span class="text-xs text-gray-400">({{ session.class_code }})</span>
                    </div>
                    <div class="mt-1 flex items-center gap-2 text-sm text-gray-500 flex-wrap">
                      <span class="inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ session.start_time }} - {{ session.end_time }}
                      </span>
                      <span class="text-gray-300">|</span>
                      <span class="inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ session.teacher_name }}
                      </span>
                      <span v-if="session.room_name" class="text-gray-300">|</span>
                      <span v-if="session.room_name" class="inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        {{ session.room_name }}
                      </span>
                    </div>
                    <div v-if="session.lesson_title" class="mt-2 text-sm text-gray-600">
                      <span class="font-medium">{{ t('calendar.lesson') }}:</span> {{ session.lesson_title }}
                    </div>
                    <div v-if="session.subject_name || session.branch_name" class="mt-1 flex items-center gap-2 text-xs">
                      <span v-if="session.subject_name" class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded">
                        {{ session.subject_name }}
                      </span>
                      <span v-if="session.branch_name" class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded">
                        {{ session.branch_name }}
                      </span>
                    </div>
                  </div>
                  <div class="flex-shrink-0 flex flex-col items-end gap-1">
                    <span class="text-xs text-gray-400">
                      {{ t('calendar.session') }} {{ session.session_number }}/{{ session.total_sessions }}
                    </span>
                    <span
                      :class="[
                        'text-xs px-2 py-0.5 rounded',
                        session.status === 'completed'
                          ? 'bg-emerald-100 text-emerald-700'
                          : 'bg-amber-100 text-amber-700'
                      ]"
                    >
                      {{ session.status === 'completed' ? t('calendar.status_completed') : t('calendar.status_pending') }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../services/api';
import { useI18n } from '../../composables/useI18n';

const { t } = useI18n();

const route = useRoute();
const router = useRouter();

const calendarData = ref(null);
const loading = ref(true);
const currentDate = ref(route.query.date || new Date().toISOString().split('T')[0]);
const selectedBranchId = ref(route.query.branch_id ? parseInt(route.query.branch_id) : null);

const hasAnySession = computed(() => {
  if (!calendarData.value?.sessions_by_hour) return false;
  return Object.keys(calendarData.value.sessions_by_hour).length > 0;
});

const loadData = async () => {
  loading.value = true;
  try {
    const params = { date: currentDate.value };
    if (selectedBranchId.value) {
      params.branch_id = selectedBranchId.value;
    }
    const response = await api.get('/api/calendar/today', { params });
    if (response.data.success) {
      calendarData.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load calendar data:', error);
  } finally {
    loading.value = false;
  }
};

const updateUrl = () => {
  const query = { date: currentDate.value };
  if (selectedBranchId.value) {
    query.branch_id = selectedBranchId.value;
  }
  router.replace({ query });
};

const goToPrev = () => {
  if (calendarData.value?.prev_date) {
    currentDate.value = calendarData.value.prev_date;
    updateUrl();
  }
};

const goToNext = () => {
  if (calendarData.value?.next_date) {
    currentDate.value = calendarData.value.next_date;
    updateUrl();
  }
};

const goToToday = () => {
  currentDate.value = calendarData.value?.today || new Date().toISOString().split('T')[0];
  updateUrl();
};

const goBack = () => {
  router.push({ name: 'calendar' });
};

const goToClass = (classId) => {
  if (classId) {
    router.push({ name: 'class.detail', params: { id: classId } });
  }
};

watch(currentDate, () => {
  loadData();
});

watch(selectedBranchId, () => {
  updateUrl();
  loadData();
});

onMounted(() => {
  loadData();
});
</script>
