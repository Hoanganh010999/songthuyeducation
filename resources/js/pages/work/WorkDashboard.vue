<template>
  <div class="work-dashboard">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div v-for="stat in statistics" :key="stat.label" class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ t(stat.label) }}</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ stat.value }}</p>
            <p v-if="stat.change" class="text-sm mt-2" :class="stat.change > 0 ? 'text-green-600' : 'text-red-600'">
              <i :class="stat.change > 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
              {{ Math.abs(stat.change) }}% {{ t('work.dashboard_from_last_month') }}
            </p>
          </div>
          <div class="text-4xl" :class="stat.color">
            <i :class="stat.icon"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Status Distribution -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- Status Breakdown -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ t('work.dashboard_by_status') }}</h3>
        <div class="space-y-3">
          <div v-for="status in statusBreakdown" :key="status.status" class="flex items-center justify-between">
            <div class="flex items-center space-x-3 flex-1">
              <div class="w-3 h-3 rounded-full" :class="status.color"></div>
              <span class="text-sm text-gray-700">{{ t(`work.status_${status.status}`) }}</span>
            </div>
            <div class="flex items-center space-x-4">
              <span class="text-sm font-semibold text-gray-900">{{ status.count }}</span>
              <div class="w-32 bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full" :class="status.color" :style="`width: ${status.percentage}%`"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Priority Distribution -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ t('work.dashboard_by_priority') }}</h3>
        <div class="space-y-3">
          <div v-for="priority in priorityBreakdown" :key="priority.priority" class="flex items-center justify-between">
            <div class="flex items-center space-x-3 flex-1">
              <div class="w-3 h-3 rounded-full" :class="priority.color"></div>
              <span class="text-sm text-gray-700">{{ t(`work.priority_${priority.priority}`) }}</span>
            </div>
            <div class="flex items-center space-x-4">
              <span class="text-sm font-semibold text-gray-900">{{ priority.count }}</span>
              <div class="w-32 bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full" :class="priority.color" :style="`width: ${priority.percentage}%`"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Work Items -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-800">{{ t('work.dashboard_recent_items') }}</h3>
          <router-link :to="{ name: 'work.items.list' }" class="text-sm text-blue-600 hover:text-blue-700">
            {{ t('work.dashboard_view_all') }} <i class="fas fa-arrow-right ml-1"></i>
          </router-link>
        </div>
      </div>
      <div v-if="loading" class="p-6 text-center">
        <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
      </div>
      <div v-else-if="recentItems.length === 0" class="p-6 text-center text-gray-500">
        <i class="fas fa-inbox text-4xl mb-2"></i>
        <p>{{ t('work.dashboard_no_items') }}</p>
      </div>
      <div v-else class="divide-y divide-gray-200">
        <div v-for="item in recentItems" :key="item.id" class="p-4 hover:bg-gray-50 cursor-pointer"
             @click="viewWorkItem(item.id)">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center space-x-2 mb-2">
                <span class="text-xs font-mono text-gray-500">{{ item.code }}</span>
                <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(item.status)">
                  {{ t(`work.status_${item.status}`) }}
                </span>
                <span class="px-2 py-1 text-xs rounded-full" :class="getPriorityClass(item.priority)">
                  {{ t(`work.priority_${item.priority}`) }}
                </span>
              </div>
              <h4 class="text-sm font-medium text-gray-900">{{ item.title }}</h4>
              <p class="text-xs text-gray-500 mt-1">
                <i class="fas fa-user text-xs mr-1"></i>
                {{ item.creator?.name }}
                <span class="mx-2">•</span>
                <i class="fas fa-clock text-xs mr-1"></i>
                {{ formatDate(item.created_at) }}
              </p>
            </div>
            <div v-if="item.due_date" class="ml-4 text-right">
              <p class="text-xs text-gray-500">{{ t('work.due_date') }}</p>
              <p class="text-sm font-medium" :class="isOverdue(item.due_date) ? 'text-red-600' : 'text-gray-900'">
                {{ formatDate(item.due_date) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';

const router = useRouter();
const { t } = useI18n();
const loading = ref(true);
const statistics = ref([
  { label: 'work.dashboard_total_items', value: 0, icon: 'fas fa-tasks', color: 'text-blue-500' },
  { label: 'work.dashboard_in_progress', value: 0, icon: 'fas fa-spinner', color: 'text-yellow-500', change: 0 },
  { label: 'work.dashboard_completed', value: 0, icon: 'fas fa-check-circle', color: 'text-green-500', change: 0 },
  { label: 'work.dashboard_overdue', value: 0, icon: 'fas fa-exclamation-triangle', color: 'text-red-500' }
]);

const statusBreakdown = ref([]);
const priorityBreakdown = ref([]);
const recentItems = ref([]);

const loadDashboard = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/work/dashboard');
    const data = response.data;

    // Update statistics
    statistics.value[0].value = data.statistics.total_work_items;
    statistics.value[1].value = data.statistics.by_status.in_progress;
    statistics.value[2].value = data.statistics.by_status.completed;
    statistics.value[3].value = data.statistics.deadlines.overdue;

    // Status breakdown
    statusBreakdown.value = Object.entries(data.statistics.by_status).map(([status, count]) => ({
      status,
      count,
      percentage: data.statistics.total_work_items > 0
        ? (count / data.statistics.total_work_items) * 100
        : 0,
      color: getStatusColorClass(status)
    }));

    // Priority breakdown
    priorityBreakdown.value = Object.entries(data.statistics.by_priority).map(([priority, count]) => ({
      priority,
      count,
      percentage: data.statistics.total_work_items > 0
        ? (count / data.statistics.total_work_items) * 100
        : 0,
      color: getPriorityColorClass(priority)
    }));

    recentItems.value = data.recent_work_items;
  } catch (error) {
    console.error('Failed to load dashboard:', error);
  } finally {
    loading.value = false;
  }
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

const getStatusColorClass = (status) => {
  const classes = {
    pending: 'bg-gray-500',
    assigned: 'bg-blue-500',
    in_progress: 'bg-yellow-500',
    submitted: 'bg-purple-500',
    revision_requested: 'bg-orange-500',
    completed: 'bg-green-500',
    cancelled: 'bg-red-500'
  };
  return classes[status] || 'bg-gray-500';
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

const getPriorityColorClass = (priority) => {
  const classes = {
    low: 'bg-green-500',
    medium: 'bg-blue-500',
    high: 'bg-orange-500',
    urgent: 'bg-red-500'
  };
  return classes[priority] || 'bg-gray-500';
};

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('vi-VN');
};

const isOverdue = (dueDate) => {
  if (!dueDate) return false;
  return new Date(dueDate) < new Date();
};

const viewWorkItem = (id) => {
  router.push({ name: 'work.item.detail', params: { id } });
};

onMounted(() => {
  loadDashboard();
});
</script>

<style scoped>
.work-dashboard {
  /* Additional dashboard styles */
}
</style>
