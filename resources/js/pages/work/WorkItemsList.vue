<template>
  <div class="work-items-list">
    <!-- Header with Actions -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">{{ t('work.items_title') }}</h2>
        <p class="text-gray-600 text-sm mt-1">{{ t('work.items_description') }}</p>
      </div>
      <button
        v-if="can('work_items.create')"
        @click="createWorkItem"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2"
      >
        <i class="fas fa-plus"></i>
        <span>{{ t('work.items_create') }}</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Search -->
        <div>
          <input
            v-model="filters.search"
            type="text"
            :placeholder="t('work.items_search')"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            @input="debouncedSearch"
          />
        </div>

        <!-- Type Filter -->
        <div>
          <select v-model="filters.type" @change="loadWorkItems" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">{{ t('work.items_all_types') }}</option>
            <option value="project">{{ t('work.type_project') }}</option>
            <option value="task">{{ t('work.type_task') }}</option>
          </select>
        </div>

        <!-- Status Filter -->
        <div>
          <select v-model="filters.status" @change="loadWorkItems" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">{{ t('work.items_all_statuses') }}</option>
            <option value="pending">{{ t('work.status_pending') }}</option>
            <option value="assigned">{{ t('work.status_assigned') }}</option>
            <option value="in_progress">{{ t('work.status_in_progress') }}</option>
            <option value="submitted">{{ t('work.status_submitted') }}</option>
            <option value="completed">{{ t('work.status_completed') }}</option>
          </select>
        </div>

        <!-- Priority Filter -->
        <div>
          <select v-model="filters.priority" @change="loadWorkItems" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">{{ t('work.items_all_priorities') }}</option>
            <option value="low">{{ t('work.priority_low') }}</option>
            <option value="medium">{{ t('work.priority_medium') }}</option>
            <option value="high">{{ t('work.priority_high') }}</option>
            <option value="urgent">{{ t('work.priority_urgent') }}</option>
          </select>
        </div>

        <!-- Quick Filters -->
        <div class="flex items-center space-x-2">
          <button
            @click="toggleOverdue"
            :class="filters.overdue ? 'bg-red-100 text-red-700 border-red-300' : 'bg-white text-gray-700 border-gray-300'"
            class="px-3 py-2 border rounded-lg text-sm"
          >
            <i class="fas fa-exclamation-triangle mr-1"></i>
            {{ t('work.items_overdue') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Items Table -->
    <div class="bg-white rounded-lg shadow">
      <div v-if="loading" class="p-8 text-center">
        <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
      </div>

      <div v-else-if="workItems.length === 0" class="p-8 text-center text-gray-500">
        <i class="fas fa-inbox text-5xl mb-4"></i>
        <p class="text-lg">{{ t('work.items_no_items') }}</p>
        <button
          v-if="can('work_items.create')"
          @click="createWorkItem"
          class="mt-4 text-blue-600 hover:text-blue-700"
        >
          {{ t('work.items_create_first') }}
        </button>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('work.items_code') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('work.items_title') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('work.items_type') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('work.items_status') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('work.items_priority') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('work.items_due_date') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('work.items_assignees') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ t('common.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="item in workItems"
              :key="item.id"
              class="hover:bg-gray-50 cursor-pointer"
              @click="viewWorkItem(item.id)"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm font-mono text-gray-900">{{ item.code }}</span>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900">{{ item.title }}</div>
                <div v-if="item.parent" class="text-xs text-gray-500 mt-1">
                  <i class="fas fa-level-up-alt rotate-90"></i>
                  {{ item.parent.title }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs rounded-full"
                      :class="item.type === 'project' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'">
                  {{ t(`work.type_${item.type}`) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(item.status)">
                  {{ t(`work.status_${item.status}`) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs rounded-full" :class="getPriorityClass(item.priority)">
                  {{ t(`work.priority_${item.priority}`) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div v-if="item.due_date" class="text-sm"
                     :class="isOverdue(item.due_date) ? 'text-red-600 font-semibold' : 'text-gray-900'">
                  {{ formatDate(item.due_date) }}
                  <i v-if="isOverdue(item.due_date)" class="fas fa-exclamation-circle ml-1"></i>
                </div>
                <span v-else class="text-sm text-gray-400">-</span>
              </td>
              <td class="px-6 py-4">
                <div class="flex -space-x-2">
                  <div
                    v-for="(assignment, index) in item.assignments.slice(0, 3)"
                    :key="assignment.id"
                    class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center text-xs font-medium border-2 border-white"
                    :title="assignment.user?.name"
                  >
                    {{ getInitials(assignment.user?.name) }}
                  </div>
                  <div
                    v-if="item.assignments.length > 3"
                    class="w-8 h-8 rounded-full bg-gray-300 text-gray-700 flex items-center justify-center text-xs font-medium border-2 border-white"
                  >
                    +{{ item.assignments.length - 3 }}
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  @click.stop="viewWorkItem(item.id)"
                  class="text-blue-600 hover:text-blue-900 mr-3"
                >
                  <i class="fas fa-eye"></i>
                </button>
                <button
                  v-if="can('work_items.edit')"
                  @click.stop="editWorkItem(item.id)"
                  class="text-gray-600 hover:text-gray-900"
                >
                  <i class="fas fa-edit"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="px-6 py-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            {{ t('common.showing') }}
            <span class="font-medium">{{ pagination.from }}</span>
            {{ t('common.to') }}
            <span class="font-medium">{{ pagination.to }}</span>
            {{ t('common.of') }}
            <span class="font-medium">{{ pagination.total }}</span>
            {{ t('common.results') }}
          </div>
          <div class="flex space-x-2">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <i class="fas fa-chevron-left"></i>
            </button>
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';

const router = useRouter();
const authStore = useAuthStore();
const { t } = useI18n();

const loading = ref(false);
const workItems = ref([]);
const filters = reactive({
  search: '',
  type: '',
  status: '',
  priority: '',
  overdue: false,
  page: 1
});

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0
});

let searchTimeout = null;

const loadWorkItems = async () => {
  try {
    loading.value = true;
    const params = {
      ...filters,
      page: filters.page
    };

    const response = await axios.get('/api/work/items', { params });
    workItems.value = response.data.data;
    pagination.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      per_page: response.data.per_page,
      total: response.data.total,
      from: response.data.from,
      to: response.data.to
    };
  } catch (error) {
    console.error('Failed to load work items:', error);
  } finally {
    loading.value = false;
  }
};

const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    filters.page = 1;
    loadWorkItems();
  }, 500);
};

const toggleOverdue = () => {
  filters.overdue = !filters.overdue;
  filters.page = 1;
  loadWorkItems();
};

const changePage = (page) => {
  filters.page = page;
  loadWorkItems();
};

const createWorkItem = () => {
  router.push({ name: 'work.item.create' });
};

const viewWorkItem = (id) => {
  router.push({ name: 'work.item.detail', params: { id } });
};

const editWorkItem = (id) => {
  router.push({ name: 'work.item.edit', params: { id } });
};

const can = (permission) => {
  return authStore.hasPermission(permission);
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

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('vi-VN');
};

const isOverdue = (dueDate) => {
  if (!dueDate) return false;
  return new Date(dueDate) < new Date();
};

const getInitials = (name) => {
  if (!name) return '?';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

onMounted(() => {
  loadWorkItems();
});
</script>

<style scoped>
.rotate-90 {
  transform: rotate(90deg);
}
</style>
