<template>
  <div class="work-item-form">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center space-x-3 mb-3">
        <button @click="$router.push({ name: 'work.items.list' })" class="text-gray-500 hover:text-gray-700">
          <i class="fas fa-arrow-left"></i>
        </button>
        <h1 class="text-2xl font-bold text-gray-900">
          {{ isEditMode ? t('work.edit_item') : t('work.create_item') }}
        </h1>
      </div>
      <p class="text-gray-600 text-sm">
        {{ isEditMode ? t('work.edit_item_description') : t('work.create_item_description') }}
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
    </div>

    <!-- Form -->
    <form v-else @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Main Information Card -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ t('work.basic_info') }}</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Title -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('work.title') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.title"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :placeholder="t('work.title_placeholder')"
            />
            <p v-if="errors.title" class="text-red-500 text-xs mt-1">{{ errors.title }}</p>
          </div>

          <!-- Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('work.type') }} <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.type"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="project">{{ t('work.type_project') }}</option>
              <option value="task">{{ t('work.type_task') }}</option>
            </select>
            <p v-if="errors.type" class="text-red-500 text-xs mt-1">{{ errors.type }}</p>
          </div>

          <!-- Priority -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('work.priority') }} <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.priority"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="low">{{ t('work.priority_low') }}</option>
              <option value="medium">{{ t('work.priority_medium') }}</option>
              <option value="high">{{ t('work.priority_high') }}</option>
              <option value="urgent">{{ t('work.priority_urgent') }}</option>
            </select>
            <p v-if="errors.priority" class="text-red-500 text-xs mt-1">{{ errors.priority }}</p>
          </div>

          <!-- Parent Item (for tasks) -->
          <div v-if="form.type === 'task'">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('work.parent_item') }}
            </label>
            <select
              v-model="form.parent_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option :value="null">{{ t('work.no_parent') }}</option>
              <option v-for="project in availableProjects" :key="project.id" :value="project.id">
                {{ project.code }} - {{ project.title }}
              </option>
            </select>
            <p v-if="errors.parent_id" class="text-red-500 text-xs mt-1">{{ errors.parent_id }}</p>
          </div>

          <!-- Due Date -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('work.due_date') }}
            </label>
            <input
              v-model="form.due_date"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <p v-if="errors.due_date" class="text-red-500 text-xs mt-1">{{ errors.due_date }}</p>
          </div>

          <!-- Estimated Hours -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('work.estimated_hours') }}
            </label>
            <input
              v-model.number="form.estimated_hours"
              type="number"
              step="0.5"
              min="0"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :placeholder="t('work.estimated_hours_placeholder')"
            />
            <p v-if="errors.estimated_hours" class="text-red-500 text-xs mt-1">{{ errors.estimated_hours }}</p>
          </div>

          <!-- Complexity -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('work.complexity') }} (1-10)
            </label>
            <input
              v-model.number="form.complexity"
              type="number"
              min="1"
              max="10"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <div class="flex items-center space-x-1 mt-2">
              <div v-for="i in 10" :key="i" class="w-4 h-4 rounded-full" :class="i <= (form.complexity || 0) ? 'bg-blue-500' : 'bg-gray-200'"></div>
            </div>
            <p v-if="errors.complexity" class="text-red-500 text-xs mt-1">{{ errors.complexity }}</p>
          </div>

          <!-- Description -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('work.description') }}
            </label>
            <textarea
              v-model="form.description"
              rows="6"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :placeholder="t('work.description_placeholder')"
            ></textarea>
            <p v-if="errors.description" class="text-red-500 text-xs mt-1">{{ errors.description }}</p>
          </div>
        </div>
      </div>

      <!-- Assignments Card -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-800">{{ t('work.assignments') }}</h3>
          <button
            type="button"
            @click="addAssignment"
            class="text-blue-600 hover:text-blue-700 text-sm"
          >
            <i class="fas fa-plus mr-1"></i>
            {{ t('work.add_assignment') }}
          </button>
        </div>

        <div v-if="form.assignments.length > 0" class="space-y-3">
          <div
            v-for="(assignment, index) in form.assignments"
            :key="index"
            class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg"
          >
            <div class="flex-1 grid grid-cols-2 gap-3">
              <!-- User -->
              <div>
                <select
                  v-model="assignment.user_id"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                >
                  <option :value="null">{{ t('work.select_user') }}</option>
                  <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                    {{ user.name }}
                  </option>
                </select>
              </div>
              <!-- Role -->
              <div>
                <select
                  v-model="assignment.role"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                >
                  <option value="executor">{{ t('work.role_executor') }}</option>
                  <option value="reviewer">{{ t('work.role_reviewer') }}</option>
                  <option value="observer">{{ t('work.role_observer') }}</option>
                </select>
              </div>
            </div>
            <button
              type="button"
              @click="removeAssignment(index)"
              class="text-red-600 hover:text-red-700"
            >
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        <div v-else class="text-center py-6 text-gray-500">
          <p class="text-sm">{{ t('work.no_assignments_yet') }}</p>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end space-x-3">
        <button
          type="button"
          @click="$router.push({ name: 'work.items.list' })"
          class="px-6 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
        >
          {{ t('common.cancel') }}
        </button>
        <button
          type="submit"
          :disabled="submitting"
          class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <i v-if="submitting" class="fas fa-spinner fa-spin mr-2"></i>
          <i v-else class="fas fa-save mr-2"></i>
          {{ isEditMode ? t('common.save') : t('common.create') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';

const router = useRouter();
const route = useRoute();
const { t } = useI18n();

const loading = ref(false);
const submitting = ref(false);
const availableProjects = ref([]);
const availableUsers = ref([]);

const isEditMode = computed(() => !!route.params.id);

const form = reactive({
  title: '',
  type: 'task',
  priority: 'medium',
  parent_id: null,
  due_date: null,
  estimated_hours: null,
  complexity: 5,
  description: '',
  assignments: []
});

const errors = reactive({
  title: '',
  type: '',
  priority: '',
  parent_id: '',
  due_date: '',
  estimated_hours: '',
  complexity: '',
  description: ''
});

const loadWorkItem = async () => {
  if (!isEditMode.value) return;

  try {
    loading.value = true;
    const response = await axios.get(`/api/work/items/${route.params.id}`);
    const item = response.data;

    form.title = item.title;
    form.type = item.type;
    form.priority = item.priority;
    form.parent_id = item.parent_id;
    form.due_date = item.due_date;
    form.estimated_hours = item.estimated_hours;
    form.complexity = item.metadata?.complexity || 5;
    form.description = item.description;
    form.assignments = item.assignments?.map(a => ({
      user_id: a.user_id,
      role: a.role
    })) || [];
  } catch (error) {
    console.error('Failed to load work item:', error);
  } finally {
    loading.value = false;
  }
};

const loadAvailableProjects = async () => {
  try {
    const response = await axios.get('/api/work/items', {
      params: { type: 'project', per_page: 100 }
    });
    availableProjects.value = response.data.data;
  } catch (error) {
    console.error('Failed to load projects:', error);
  }
};

const loadAvailableUsers = async () => {
  try {
    const response = await axios.get('/api/users', {
      params: { per_page: 100 }
    });
    availableUsers.value = response.data.data;
  } catch (error) {
    console.error('Failed to load users:', error);
  }
};

const addAssignment = () => {
  form.assignments.push({
    user_id: null,
    role: 'executor'
  });
};

const removeAssignment = (index) => {
  form.assignments.splice(index, 1);
};

const handleSubmit = async () => {
  // Clear previous errors
  Object.keys(errors).forEach(key => errors[key] = '');

  try {
    submitting.value = true;

    const payload = {
      title: form.title,
      type: form.type,
      priority: form.priority,
      parent_id: form.parent_id,
      due_date: form.due_date,
      estimated_hours: form.estimated_hours,
      description: form.description,
      metadata: {
        complexity: form.complexity
      },
      assignments: form.assignments.filter(a => a.user_id)
    };

    if (isEditMode.value) {
      await axios.put(`/api/work/items/${route.params.id}`, payload);
    } else {
      await axios.post('/api/work/items', payload);
    }

    router.push({ name: 'work.items.list' });
  } catch (error) {
    console.error('Failed to save work item:', error);
    if (error.response?.data?.errors) {
      Object.assign(errors, error.response.data.errors);
    }
  } finally {
    submitting.value = false;
  }
};

onMounted(async () => {
  await Promise.all([
    loadAvailableProjects(),
    loadAvailableUsers(),
    loadWorkItem()
  ]);
});
</script>

<style scoped>
/* Additional form styles if needed */
</style>
