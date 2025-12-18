<template>
  <div class="work-calendar">
    <!-- Calendar Header -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button
            @click="previousMonth"
            class="p-2 hover:bg-gray-100 rounded-lg transition"
          >
            <i class="fas fa-chevron-left"></i>
          </button>
          <h2 class="text-2xl font-bold text-gray-900">
            {{ currentMonthName }} {{ currentYear }}
          </h2>
          <button
            @click="nextMonth"
            class="p-2 hover:bg-gray-100 rounded-lg transition"
          >
            <i class="fas fa-chevron-right"></i>
          </button>
          <button
            @click="goToToday"
            class="px-4 py-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition text-sm"
          >
            {{ $t('common.today') }}
          </button>
        </div>

        <div class="flex items-center space-x-3">
          <!-- Filters -->
          <select v-model="filterType" @change="loadCalendarItems" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">{{ $t('work.items_all_types') }}</option>
            <option value="project">{{ $t('work.type_project') }}</option>
            <option value="task">{{ $t('work.type_task') }}</option>
          </select>

          <select v-model="filterStatus" @change="loadCalendarItems" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">{{ $t('work.items_all_statuses') }}</option>
            <option value="pending">{{ $t('work.status_pending') }}</option>
            <option value="assigned">{{ $t('work.status_assigned') }}</option>
            <option value="in_progress">{{ $t('work.status_in_progress') }}</option>
            <option value="submitted">{{ $t('work.status_submitted') }}</option>
            <option value="completed">{{ $t('work.status_completed') }}</option>
          </select>
        </div>
      </div>

      <!-- Legend -->
      <div class="flex items-center space-x-6 mt-4 text-sm">
        <div class="flex items-center space-x-2">
          <div class="w-3 h-3 rounded bg-green-500"></div>
          <span class="text-gray-600">{{ $t('work.priority_low') }}</span>
        </div>
        <div class="flex items-center space-x-2">
          <div class="w-3 h-3 rounded bg-blue-500"></div>
          <span class="text-gray-600">{{ $t('work.priority_medium') }}</span>
        </div>
        <div class="flex items-center space-x-2">
          <div class="w-3 h-3 rounded bg-orange-500"></div>
          <span class="text-gray-600">{{ $t('work.priority_high') }}</span>
        </div>
        <div class="flex items-center space-x-2">
          <div class="w-3 h-3 rounded bg-red-500"></div>
          <span class="text-gray-600">{{ $t('work.priority_urgent') }}</span>
        </div>
      </div>
    </div>

    <!-- Calendar Grid -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <!-- Day Headers -->
      <div class="grid grid-cols-7 border-b">
        <div
          v-for="day in weekDays"
          :key="day"
          class="p-3 text-center text-sm font-semibold text-gray-700 bg-gray-50"
        >
          {{ $t(`calendar.days.${day}`) }}
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="p-12 text-center">
        <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
      </div>

      <!-- Calendar Days -->
      <div v-else class="grid grid-cols-7">
        <div
          v-for="day in calendarDays"
          :key="day.key"
          class="min-h-[120px] border-b border-r p-2 transition"
          :class="{
            'bg-gray-50': !day.isCurrentMonth,
            'bg-blue-50': day.isToday,
            'hover:bg-gray-50': day.isCurrentMonth && !day.isToday
          }"
        >
          <!-- Day Number -->
          <div class="flex items-center justify-between mb-2">
            <span
              class="text-sm font-medium"
              :class="{
                'text-gray-400': !day.isCurrentMonth,
                'text-blue-600 font-bold': day.isToday,
                'text-gray-700': day.isCurrentMonth && !day.isToday
              }"
            >
              {{ day.day }}
            </span>
            <span v-if="getDayItemsCount(day.date) > 3" class="text-xs text-gray-500">
              +{{ getDayItemsCount(day.date) - 3 }}
            </span>
          </div>

          <!-- Work Items -->
          <div class="space-y-1">
            <div
              v-for="(item, index) in getDayItems(day.date).slice(0, 3)"
              :key="item.id"
              @click="viewWorkItem(item.id)"
              class="text-xs p-1.5 rounded cursor-pointer hover:opacity-80 transition truncate"
              :class="getItemClass(item)"
              :title="item.title"
            >
              <div class="flex items-center space-x-1">
                <i class="fas fa-circle text-[6px]" :class="getPriorityDotClass(item.priority)"></i>
                <span class="font-medium truncate">{{ item.code }}</span>
              </div>
              <div class="truncate text-gray-700">{{ item.title }}</div>
            </div>
          </div>

          <!-- Show All Button -->
          <button
            v-if="getDayItemsCount(day.date) > 3"
            @click="showDayDetails(day.date)"
            class="mt-2 text-xs text-blue-600 hover:text-blue-700 w-full text-left"
          >
            {{ $t('work.show_all') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Day Details Modal -->
    <div
      v-if="selectedDayItems"
      @click="selectedDayItems = null"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
    >
      <div
        @click.stop
        class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden"
      >
        <div class="p-6 border-b">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900">
              {{ formatDate(selectedDate) }}
            </h3>
            <button @click="selectedDayItems = null" class="text-gray-500 hover:text-gray-700">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <p class="text-sm text-gray-600 mt-1">
            {{ selectedDayItems.length }} {{ $t('work.items') }}
          </p>
        </div>
        <div class="overflow-y-auto max-h-[calc(80vh-120px)]">
          <div class="divide-y">
            <div
              v-for="item in selectedDayItems"
              :key="item.id"
              @click="viewWorkItem(item.id)"
              class="p-4 hover:bg-gray-50 cursor-pointer transition"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center space-x-2 mb-2">
                    <span class="text-xs font-mono text-gray-500">{{ item.code }}</span>
                    <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(item.status)">
                      {{ $t(`work.status_${item.status}`) }}
                    </span>
                    <span class="px-2 py-1 text-xs rounded-full" :class="getPriorityClass(item.priority)">
                      {{ $t(`work.priority_${item.priority}`) }}
                    </span>
                  </div>
                  <h4 class="text-sm font-medium text-gray-900">{{ item.title }}</h4>
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
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();

const loading = ref(true);
const currentDate = ref(new Date());
const workItems = ref([]);
const filterType = ref('');
const filterStatus = ref('');
const selectedDate = ref(null);
const selectedDayItems = ref(null);

const weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

const currentMonth = computed(() => currentDate.value.getMonth());
const currentYear = computed(() => currentDate.value.getFullYear());
const currentMonthName = computed(() => {
  return new Date(currentYear.value, currentMonth.value).toLocaleDateString('vi-VN', { month: 'long' });
});

const calendarDays = computed(() => {
  const firstDay = new Date(currentYear.value, currentMonth.value, 1);
  const lastDay = new Date(currentYear.value, currentMonth.value + 1, 0);

  // Get day of week (0 = Sunday, 1 = Monday, etc.)
  let startDay = firstDay.getDay();
  // Convert to Monday = 0
  startDay = startDay === 0 ? 6 : startDay - 1;

  const days = [];

  // Previous month days
  const prevMonthLastDay = new Date(currentYear.value, currentMonth.value, 0).getDate();
  for (let i = startDay - 1; i >= 0; i--) {
    const day = prevMonthLastDay - i;
    const date = new Date(currentYear.value, currentMonth.value - 1, day);
    days.push({
      day,
      date: formatDateKey(date),
      isCurrentMonth: false,
      isToday: false,
      key: `prev-${day}`
    });
  }

  // Current month days
  const today = new Date();
  for (let day = 1; day <= lastDay.getDate(); day++) {
    const date = new Date(currentYear.value, currentMonth.value, day);
    const isToday = date.toDateString() === today.toDateString();
    days.push({
      day,
      date: formatDateKey(date),
      isCurrentMonth: true,
      isToday,
      key: `current-${day}`
    });
  }

  // Next month days
  const remainingDays = 42 - days.length; // 6 rows * 7 days
  for (let day = 1; day <= remainingDays; day++) {
    const date = new Date(currentYear.value, currentMonth.value + 1, day);
    days.push({
      day,
      date: formatDateKey(date),
      isCurrentMonth: false,
      isToday: false,
      key: `next-${day}`
    });
  }

  return days;
});

const formatDateKey = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const getDayItems = (dateKey) => {
  return workItems.value.filter(item => item.due_date === dateKey);
};

const getDayItemsCount = (dateKey) => {
  return getDayItems(dateKey).length;
};

const loadCalendarItems = async () => {
  try {
    loading.value = true;

    const startDate = new Date(currentYear.value, currentMonth.value, 1);
    const endDate = new Date(currentYear.value, currentMonth.value + 1, 0);

    const params = {
      due_date_from: formatDateKey(startDate),
      due_date_to: formatDateKey(endDate),
      per_page: 1000
    };

    if (filterType.value) params.type = filterType.value;
    if (filterStatus.value) params.status = filterStatus.value;

    const response = await axios.get('/api/work/items', { params });
    workItems.value = response.data.data;
  } catch (error) {
    console.error('Failed to load calendar items:', error);
  } finally {
    loading.value = false;
  }
};

const previousMonth = () => {
  currentDate.value = new Date(currentYear.value, currentMonth.value - 1, 1);
  loadCalendarItems();
};

const nextMonth = () => {
  currentDate.value = new Date(currentYear.value, currentMonth.value + 1, 1);
  loadCalendarItems();
};

const goToToday = () => {
  currentDate.value = new Date();
  loadCalendarItems();
};

const showDayDetails = (dateKey) => {
  selectedDate.value = dateKey;
  selectedDayItems.value = getDayItems(dateKey);
};

const viewWorkItem = (id) => {
  router.push({ name: 'work.item.detail', params: { id } });
};

const getItemClass = (item) => {
  const baseClass = 'border-l-2';
  const statusClasses = {
    pending: 'bg-gray-100 border-gray-400',
    assigned: 'bg-blue-100 border-blue-500',
    in_progress: 'bg-yellow-100 border-yellow-500',
    submitted: 'bg-purple-100 border-purple-500',
    revision_requested: 'bg-orange-100 border-orange-500',
    completed: 'bg-green-100 border-green-500',
    cancelled: 'bg-red-100 border-red-500'
  };
  return `${baseClass} ${statusClasses[item.status] || statusClasses.pending}`;
};

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-gray-100 text-gray-800',
    assigned: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    submitted: 'bg-purple-100 text-purple-800',
    revision_requested: 'bg-orange-100 text-orange-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const getPriorityClass = (priority) => {
  const classes = {
    low: 'bg-green-100 text-green-800',
    medium: 'bg-blue-100 text-blue-800',
    high: 'bg-orange-100 text-orange-800',
    urgent: 'bg-red-100 text-red-800'
  };
  return classes[priority] || 'bg-gray-100 text-gray-800';
};

const getPriorityDotClass = (priority) => {
  const classes = {
    low: 'text-green-500',
    medium: 'text-blue-500',
    high: 'text-orange-500',
    urgent: 'text-red-500'
  };
  return classes[priority] || 'text-gray-500';
};

const formatDate = (dateKey) => {
  if (!dateKey) return '';
  return new Date(dateKey).toLocaleDateString('vi-VN', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

onMounted(() => {
  loadCalendarItems();
});
</script>

<style scoped>
/* Calendar specific styles */
.grid > div:nth-child(7n) {
  border-right: none;
}

.grid > div:nth-last-child(-n+7) {
  border-bottom: none;
}
</style>
