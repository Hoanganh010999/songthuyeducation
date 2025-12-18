<template>
  <div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900">{{ t('scheduled_tasks.title') }}</h2>
      <p class="text-gray-600 mt-1">{{ t('scheduled_tasks.description') }}</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Tasks by Category -->
    <div v-else class="space-y-6">
      <div v-for="(category, categoryKey) in groupedTasks" :key="categoryKey" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <!-- Category Header -->
        <div class="px-6 py-4 border-b border-gray-200" :class="getCategoryBgClass(categoryKey)">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="getCategoryIconBgClass(categoryKey)">
              <component :is="getCategoryIcon(categoryKey)" class="w-6 h-6" :class="getCategoryIconClass(categoryKey)" />
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-900">
                {{ categories[categoryKey]?.name_vi || categories[categoryKey]?.name }}
              </h3>
              <p class="text-sm text-gray-500">{{ category.length }} {{ t('scheduled_tasks.tasks') }}</p>
            </div>
          </div>
        </div>

        <!-- Tasks List -->
        <div class="divide-y divide-gray-100">
          <div v-for="task in category" :key="task.key" class="p-6 hover:bg-gray-50 transition-colors">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center space-x-3">
                  <h4 class="text-base font-medium text-gray-900">
                    {{ task.name_vi || task.name }}
                  </h4>
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="task.enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                  >
                    {{ task.enabled ? t('scheduled_tasks.enabled') : t('scheduled_tasks.disabled') }}
                  </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">
                  {{ task.description_vi || task.description }}
                </p>
                <div class="flex items-center space-x-4 mt-3">
                  <div class="flex items-center text-sm text-gray-600">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <code class="bg-gray-100 px-2 py-0.5 rounded text-xs font-mono">{{ task.schedule }}</code>
                    <span class="ml-2 text-gray-500">({{ task.schedule_readable }})</span>
                  </div>
                  <div v-if="task.last_run" class="flex items-center text-sm text-gray-500">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ t('scheduled_tasks.last_run') }}: {{ formatDate(task.last_run) }}
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex items-center space-x-2 ml-4">
                <button
                  @click="openEditModal(task)"
                  class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                  :title="t('common.edit')"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button
                  @click="runTask(task)"
                  :disabled="runningTask === task.key"
                  class="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors disabled:opacity-50"
                  :title="t('scheduled_tasks.run_now')"
                >
                  <svg v-if="runningTask !== task.key" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <svg v-else class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                </button>
                <button
                  @click="toggleTask(task)"
                  :disabled="togglingTask === task.key"
                  class="p-2 rounded-lg transition-colors"
                  :class="task.enabled ? 'text-green-600 hover:text-red-600 hover:bg-red-50' : 'text-gray-400 hover:text-green-600 hover:bg-green-50'"
                  :title="task.enabled ? t('scheduled_tasks.disable') : t('scheduled_tasks.enable')"
                >
                  <svg v-if="task.enabled" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="closeEditModal"></div>

        <div class="relative inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
          <div class="absolute top-0 right-0 pt-4 pr-4">
            <button @click="closeEditModal" class="text-gray-400 hover:text-gray-500">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="sm:flex sm:items-start">
            <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
              <h3 class="text-lg font-medium leading-6 text-gray-900">
                {{ t('scheduled_tasks.edit_task') }}
              </h3>
              <p class="mt-1 text-sm text-gray-500">
                {{ editingTask?.name_vi || editingTask?.name }}
              </p>

              <div class="mt-6 space-y-4">
                <!-- Schedule Input -->
                <div>
                  <label class="block text-sm font-medium text-gray-700">
                    {{ t('scheduled_tasks.cron_schedule') }}
                  </label>
                  <input
                    v-model="editForm.schedule"
                    type="text"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm font-mono"
                    placeholder="*/30 * * * *"
                  />
                  <p class="mt-1 text-xs text-gray-500">
                    {{ t('scheduled_tasks.cron_format_help') }}
                  </p>
                </div>

                <!-- Common Schedules -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ t('scheduled_tasks.quick_select') }}
                  </label>
                  <div class="flex flex-wrap gap-2">
                    <button
                      v-for="preset in cronPresets"
                      :key="preset.value"
                      @click="editForm.schedule = preset.value"
                      class="px-3 py-1 text-xs border rounded-full hover:bg-gray-50 transition-colors"
                      :class="editForm.schedule === preset.value ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-300 text-gray-600'"
                    >
                      {{ preset.label }}
                    </button>
                  </div>
                </div>

                <!-- Enabled Toggle -->
                <div class="flex items-center justify-between">
                  <label class="text-sm font-medium text-gray-700">
                    {{ t('scheduled_tasks.task_enabled') }}
                  </label>
                  <button
                    @click="editForm.enabled = !editForm.enabled"
                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    :class="editForm.enabled ? 'bg-blue-600' : 'bg-gray-200'"
                  >
                    <span
                      class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                      :class="editForm.enabled ? 'translate-x-5' : 'translate-x-0'"
                    ></span>
                  </button>
                </div>

                <!-- Reset to Default -->
                <div class="pt-2 border-t border-gray-200">
                  <button
                    @click="resetToDefault"
                    class="text-sm text-gray-500 hover:text-gray-700 underline"
                  >
                    {{ t('scheduled_tasks.reset_to_default') }}
                  </button>
                  <span class="text-xs text-gray-400 ml-2">
                    ({{ editingTask?.default_schedule }})
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-6 sm:flex sm:flex-row-reverse gap-3">
            <button
              @click="saveTask"
              :disabled="saving"
              class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm disabled:opacity-50"
            >
              <svg v-if="saving" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ t('common.save') }}
            </button>
            <button
              @click="closeEditModal"
              class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm"
            >
              {{ t('common.cancel') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';

const { t } = useI18n();
const { showSuccess, showError, showConfirm } = useSwal();

const loading = ref(true);
const tasks = ref([]);
const categories = ref({});
const showEditModal = ref(false);
const editingTask = ref(null);
const editForm = ref({ schedule: '', enabled: true });
const saving = ref(false);
const runningTask = ref(null);
const togglingTask = ref(null);

const cronPresets = [
  { label: 'Every minute', value: '* * * * *' },
  { label: 'Every 5 minutes', value: '*/5 * * * *' },
  { label: 'Every 10 minutes', value: '*/10 * * * *' },
  { label: 'Every 15 minutes', value: '*/15 * * * *' },
  { label: 'Every 30 minutes', value: '*/30 * * * *' },
  { label: 'Every hour', value: '0 * * * *' },
  { label: 'Every 6 hours', value: '0 */6 * * *' },
  { label: 'Daily at 9 AM', value: '0 9 * * *' },
  { label: 'Daily at 2 AM', value: '0 2 * * *' },
];

const groupedTasks = computed(() => {
  const grouped = {};
  tasks.value.forEach(task => {
    if (!grouped[task.category]) {
      grouped[task.category] = [];
    }
    grouped[task.category].push(task);
  });
  return grouped;
});

const getCategoryBgClass = (category) => {
  const classes = {
    notifications: 'bg-blue-50',
    google_drive: 'bg-green-50',
    sync: 'bg-purple-50',
    maintenance: 'bg-orange-50',
  };
  return classes[category] || 'bg-gray-50';
};

const getCategoryIconBgClass = (category) => {
  const classes = {
    notifications: 'bg-blue-100',
    google_drive: 'bg-green-100',
    sync: 'bg-purple-100',
    maintenance: 'bg-orange-100',
  };
  return classes[category] || 'bg-gray-100';
};

const getCategoryIconClass = (category) => {
  const classes = {
    notifications: 'text-blue-600',
    google_drive: 'text-green-600',
    sync: 'text-purple-600',
    maintenance: 'text-orange-600',
  };
  return classes[category] || 'text-gray-600';
};

const getCategoryIcon = (category) => {
  // Return SVG component based on category
  return {
    template: getCategoryIconSvg(category),
  };
};

const getCategoryIconSvg = (category) => {
  const icons = {
    notifications: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>`,
    google_drive: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>`,
    sync: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>`,
    maintenance: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>`,
  };
  return icons[category] || icons.maintenance;
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleString('vi-VN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const fetchTasks = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/scheduled-tasks');
    if (response.data.success) {
      tasks.value = response.data.data;
      categories.value = response.data.categories;
    }
  } catch (error) {
    console.error('Failed to fetch tasks:', error);
    showError(t('scheduled_tasks.fetch_error'));
  } finally {
    loading.value = false;
  }
};

const openEditModal = (task) => {
  editingTask.value = task;
  editForm.value = {
    schedule: task.schedule,
    enabled: task.enabled,
  };
  showEditModal.value = true;
};

const closeEditModal = () => {
  showEditModal.value = false;
  editingTask.value = null;
  editForm.value = { schedule: '', enabled: true };
};

const saveTask = async () => {
  if (!editingTask.value) return;

  try {
    saving.value = true;
    const response = await axios.put(`/api/scheduled-tasks/${editingTask.value.key}`, editForm.value);
    if (response.data.success) {
      showSuccess(t('scheduled_tasks.save_success'));
      closeEditModal();
      await fetchTasks();
    }
  } catch (error) {
    console.error('Failed to save task:', error);
    showError(error.response?.data?.message || t('scheduled_tasks.save_error'));
  } finally {
    saving.value = false;
  }
};

const resetToDefault = async () => {
  if (!editingTask.value) return;

  const confirmed = await showConfirm(
    t('scheduled_tasks.reset_confirm_title'),
    t('scheduled_tasks.reset_confirm_message')
  );

  if (!confirmed) return;

  try {
    const response = await axios.post(`/api/scheduled-tasks/${editingTask.value.key}/reset`);
    if (response.data.success) {
      showSuccess(t('scheduled_tasks.reset_success'));
      closeEditModal();
      await fetchTasks();
    }
  } catch (error) {
    console.error('Failed to reset task:', error);
    showError(t('scheduled_tasks.reset_error'));
  }
};

const runTask = async (task) => {
  const confirmed = await showConfirm(
    t('scheduled_tasks.run_confirm_title'),
    t('scheduled_tasks.run_confirm_message', { name: task.name_vi || task.name })
  );

  if (!confirmed) return;

  try {
    runningTask.value = task.key;
    const response = await axios.post(`/api/scheduled-tasks/${task.key}/run`);
    if (response.data.success) {
      showSuccess(t('scheduled_tasks.run_success'));
      await fetchTasks();
    } else {
      showError(response.data.message || t('scheduled_tasks.run_error'));
    }
  } catch (error) {
    console.error('Failed to run task:', error);
    showError(error.response?.data?.message || t('scheduled_tasks.run_error'));
  } finally {
    runningTask.value = null;
  }
};

const toggleTask = async (task) => {
  try {
    togglingTask.value = task.key;
    const response = await axios.put(`/api/scheduled-tasks/${task.key}`, {
      enabled: !task.enabled,
    });
    if (response.data.success) {
      showSuccess(task.enabled ? t('scheduled_tasks.disabled_success') : t('scheduled_tasks.enabled_success'));
      await fetchTasks();
    }
  } catch (error) {
    console.error('Failed to toggle task:', error);
    showError(t('scheduled_tasks.toggle_error'));
  } finally {
    togglingTask.value = null;
  }
};

onMounted(() => {
  fetchTasks();
});
</script>
