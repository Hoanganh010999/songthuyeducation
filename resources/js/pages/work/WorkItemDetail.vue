<template>
  <div class="work-item-detail">
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
      <i class="fas fa-exclamation-circle text-3xl text-red-500 mb-3"></i>
      <p class="text-red-700">{{ error }}</p>
      <button @click="$router.push({ name: 'work.items.list' })" class="mt-4 text-blue-600 hover:text-blue-700">
        {{ t('common.back_to_list') }}
      </button>
    </div>

    <!-- Work Item Content -->
    <div v-else-if="workItem">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6">
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
              <div class="flex items-center space-x-3 mb-3">
                <button @click="$router.push({ name: 'work.items.list' })" class="text-gray-500 hover:text-gray-700">
                  <i class="fas fa-arrow-left"></i>
                </button>
                <span class="text-sm font-mono text-gray-500">{{ workItem.code }}</span>
                <span class="px-2 py-1 text-xs rounded-full" :class="getTypeClass(workItem.type)">
                  {{ t(`work.type_${workItem.type}`) }}
                </span>
                <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(workItem.status)">
                  {{ t(`work.status_${workItem.status}`) }}
                </span>
                <span class="px-2 py-1 text-xs rounded-full" :class="getPriorityClass(workItem.priority)">
                  {{ t(`work.priority_${workItem.priority}`) }}
                </span>
              </div>
              <h1 class="text-2xl font-bold text-gray-900">{{ workItem.title }}</h1>
              <div v-if="workItem.parent" class="mt-2 text-sm text-gray-600">
                <i class="fas fa-level-up-alt rotate-90 mr-1"></i>
                <router-link :to="{ name: 'work.item.detail', params: { id: workItem.parent.id } }" class="text-blue-600 hover:text-blue-700">
                  {{ workItem.parent.title }}
                </router-link>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <button
                v-if="can('work_items.edit')"
                @click="editWorkItem"
                class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
              >
                <i class="fas fa-edit mr-2"></i>
                {{ t('common.edit') }}
              </button>
              <button
                v-if="canSubmit"
                @click="submitWork"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg"
              >
                <i class="fas fa-paper-plane mr-2"></i>
                {{ t('work.submit') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Info -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Description -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ t('work.description') }}</h3>
            <div class="prose max-w-none text-gray-700" v-html="workItem.description || t('work.no_description')"></div>
          </div>

          <!-- Attachments -->
          <div v-if="workItem.attachments && workItem.attachments.length > 0" class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
              <i class="fas fa-paperclip mr-2"></i>
              {{ t('work.attachments') }}
            </h3>
            <div class="space-y-2">
              <a
                v-for="attachment in workItem.attachments"
                :key="attachment.id"
                :href="attachment.url"
                target="_blank"
                class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition"
              >
                <i class="fas fa-file text-gray-400 mr-3"></i>
                <div class="flex-1">
                  <p class="text-sm font-medium text-gray-900">{{ attachment.name }}</p>
                  <p class="text-xs text-gray-500">{{ formatFileSize(attachment.size) }}</p>
                </div>
                <i class="fas fa-download text-gray-400"></i>
              </a>
            </div>
          </div>

          <!-- Submissions -->
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-inbox mr-2"></i>
                {{ t('work.submissions') }}
              </h3>
              <span class="text-sm text-gray-500">{{ workItem.submissions?.length || 0 }} {{ t('work.submissions') }}</span>
            </div>

            <div v-if="workItem.submissions && workItem.submissions.length > 0" class="space-y-4">
              <div
                v-for="submission in workItem.submissions"
                :key="submission.id"
                class="border border-gray-200 rounded-lg p-4"
              >
                <div class="flex items-start justify-between mb-3">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-blue-500 text-white flex items-center justify-center font-medium">
                      {{ getInitials(submission.submitter?.name) }}
                    </div>
                    <div>
                      <p class="text-sm font-medium text-gray-900">{{ submission.submitter?.name }}</p>
                      <p class="text-xs text-gray-500">{{ formatDateTime(submission.submitted_at) }}</p>
                    </div>
                  </div>
                  <span class="px-2 py-1 text-xs rounded-full" :class="getSubmissionStatusClass(submission.status)">
                    {{ t(`work.submission_status_${submission.status}`) }}
                  </span>
                </div>
                <p class="text-sm text-gray-700 mb-3">{{ submission.comments }}</p>
                <div v-if="submission.attachments && submission.attachments.length > 0" class="flex flex-wrap gap-2">
                  <a
                    v-for="file in submission.attachments"
                    :key="file.id"
                    :href="file.url"
                    target="_blank"
                    class="text-xs px-2 py-1 bg-gray-100 rounded hover:bg-gray-200"
                  >
                    <i class="fas fa-file mr-1"></i>
                    {{ file.name }}
                  </a>
                </div>
                <div v-if="submission.reviewer_comments" class="mt-3 p-3 bg-yellow-50 rounded border-l-4 border-yellow-400">
                  <p class="text-xs font-medium text-yellow-800 mb-1">{{ t('work.reviewer_comments') }}</p>
                  <p class="text-sm text-yellow-900">{{ submission.reviewer_comments }}</p>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8 text-gray-500">
              <i class="fas fa-inbox text-4xl mb-2"></i>
              <p class="text-sm">{{ t('work.no_submissions') }}</p>
            </div>
          </div>

          <!-- Discussions -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
              <i class="fas fa-comments mr-2"></i>
              {{ t('work.discussions') }}
            </h3>

            <div v-if="workItem.discussions && workItem.discussions.length > 0" class="space-y-4 mb-6">
              <div
                v-for="discussion in workItem.discussions"
                :key="discussion.id"
                class="flex space-x-3"
              >
                <div class="w-8 h-8 rounded-full bg-gray-300 text-gray-700 flex items-center justify-center text-xs font-medium flex-shrink-0">
                  {{ getInitials(discussion.user?.name) }}
                </div>
                <div class="flex-1">
                  <div class="flex items-center space-x-2 mb-1">
                    <span class="text-sm font-medium text-gray-900">{{ discussion.user?.name }}</span>
                    <span class="text-xs text-gray-500">{{ formatDateTime(discussion.created_at) }}</span>
                  </div>
                  <p class="text-sm text-gray-700">{{ discussion.message }}</p>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-6 text-gray-500 mb-6">
              <p class="text-sm">{{ t('work.no_discussions') }}</p>
            </div>

            <!-- Add Comment -->
            <div class="border-t pt-4">
              <textarea
                v-model="newComment"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                :placeholder="t('work.add_comment')"
              ></textarea>
              <div class="mt-2 flex justify-end">
                <button
                  @click="addComment"
                  :disabled="!newComment.trim()"
                  class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <i class="fas fa-comment mr-2"></i>
                  {{ t('work.post_comment') }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Sidebar -->
        <div class="lg:col-span-1 space-y-6">
          <!-- Details Card -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ t('work.details') }}</h3>
            <div class="space-y-4">
              <!-- Creator -->
              <div>
                <p class="text-xs text-gray-500 mb-1">{{ t('work.creator') }}</p>
                <div class="flex items-center space-x-2">
                  <div class="w-6 h-6 rounded-full bg-green-500 text-white flex items-center justify-center text-xs font-medium">
                    {{ getInitials(workItem.creator?.name) }}
                  </div>
                  <span class="text-sm text-gray-900">{{ workItem.creator?.name }}</span>
                </div>
              </div>

              <!-- Created Date -->
              <div>
                <p class="text-xs text-gray-500 mb-1">{{ t('work.created_at') }}</p>
                <p class="text-sm text-gray-900">{{ formatDateTime(workItem.created_at) }}</p>
              </div>

              <!-- Due Date -->
              <div>
                <p class="text-xs text-gray-500 mb-1">{{ t('work.due_date') }}</p>
                <p class="text-sm font-medium" :class="isOverdue(workItem.due_date) ? 'text-red-600' : 'text-gray-900'">
                  {{ workItem.due_date ? formatDate(workItem.due_date) : '-' }}
                  <i v-if="isOverdue(workItem.due_date)" class="fas fa-exclamation-circle ml-1"></i>
                </p>
              </div>

              <!-- Estimated Hours -->
              <div v-if="workItem.estimated_hours">
                <p class="text-xs text-gray-500 mb-1">{{ t('work.estimated_hours') }}</p>
                <p class="text-sm text-gray-900">{{ workItem.estimated_hours }}h</p>
              </div>

              <!-- Actual Hours -->
              <div v-if="workItem.actual_hours">
                <p class="text-xs text-gray-500 mb-1">{{ t('work.actual_hours') }}</p>
                <p class="text-sm text-gray-900">{{ workItem.actual_hours }}h</p>
              </div>

              <!-- Complexity -->
              <div v-if="workItem.metadata?.complexity">
                <p class="text-xs text-gray-500 mb-1">{{ t('work.complexity') }}</p>
                <div class="flex items-center space-x-1">
                  <div v-for="i in 10" :key="i" class="w-3 h-3 rounded-full" :class="i <= workItem.metadata.complexity ? 'bg-blue-500' : 'bg-gray-200'"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Assignments Card -->
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-800">{{ t('work.assignments') }}</h3>
              <button
                v-if="can('work_items.assign')"
                @click="manageAssignments"
                class="text-blue-600 hover:text-blue-700 text-sm"
              >
                <i class="fas fa-plus"></i>
              </button>
            </div>

            <div v-if="workItem.assignments && workItem.assignments.length > 0" class="space-y-3">
              <div
                v-for="assignment in workItem.assignments"
                :key="assignment.id"
                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
              >
                <div class="flex items-center space-x-3">
                  <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center text-xs font-medium">
                    {{ getInitials(assignment.user?.name) }}
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ assignment.user?.name }}</p>
                    <span class="text-xs px-2 py-0.5 rounded" :class="getRoleClass(assignment.role)">
                      {{ t(`work.role_${assignment.role}`) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-6 text-gray-500">
              <i class="fas fa-user-slash text-2xl mb-2"></i>
              <p class="text-sm">{{ t('work.no_assignments') }}</p>
            </div>
          </div>

          <!-- Child Items -->
          <div v-if="workItem.type === 'project' && workItem.children && workItem.children.length > 0" class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
              <i class="fas fa-tasks mr-2"></i>
              {{ t('work.child_items') }}
            </h3>
            <div class="space-y-2">
              <router-link
                v-for="child in workItem.children"
                :key="child.id"
                :to="{ name: 'work.item.detail', params: { id: child.id } }"
                class="block p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition"
              >
                <div class="flex items-center justify-between">
                  <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900">{{ child.title }}</p>
                    <p class="text-xs text-gray-500">{{ child.code }}</p>
                  </div>
                  <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(child.status)">
                    {{ t(`work.status_${child.status}`) }}
                  </span>
                </div>
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const { t } = useI18n();

const loading = ref(true);
const error = ref(null);
const workItem = ref(null);
const newComment = ref('');

const loadWorkItem = async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await axios.get(`/api/work/items/${route.params.id}`);
    workItem.value = response.data;
  } catch (err) {
    console.error('Failed to load work item:', err);
    error.value = err.response?.data?.message || 'Failed to load work item';
  } finally {
    loading.value = false;
  }
};

const canSubmit = computed(() => {
  if (!workItem.value) return false;
  return (
    can('work_submissions.create') &&
    ['assigned', 'in_progress', 'revision_requested'].includes(workItem.value.status) &&
    workItem.value.assignments?.some(a => a.user_id === authStore.user?.id && a.role === 'executor')
  );
});

const editWorkItem = () => {
  router.push({ name: 'work.item.edit', params: { id: workItem.value.id } });
};

const submitWork = () => {
  // TODO: Open submit work modal
  console.log('Submit work');
};

const manageAssignments = () => {
  // TODO: Open manage assignments modal
  console.log('Manage assignments');
};

const addComment = async () => {
  if (!newComment.value.trim()) return;

  try {
    await axios.post(`/api/work/items/${workItem.value.id}/discussions`, {
      message: newComment.value
    });
    newComment.value = '';
    await loadWorkItem();
  } catch (err) {
    console.error('Failed to add comment:', err);
  }
};

const can = (permission) => {
  return authStore.hasPermission(permission);
};

const getTypeClass = (type) => {
  return type === 'project' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800';
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

const getSubmissionStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const getRoleClass = (role) => {
  const classes = {
    executor: 'bg-blue-100 text-blue-800',
    reviewer: 'bg-purple-100 text-purple-800',
    observer: 'bg-gray-100 text-gray-800'
  };
  return classes[role] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('vi-VN');
};

const formatDateTime = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleString('vi-VN');
};

const isOverdue = (dueDate) => {
  if (!dueDate) return false;
  return new Date(dueDate) < new Date();
};

const getInitials = (name) => {
  if (!name) return '?';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

onMounted(() => {
  loadWorkItem();
});
</script>

<style scoped>
.rotate-90 {
  transform: rotate(90deg);
}

.prose {
  line-height: 1.6;
}
</style>
