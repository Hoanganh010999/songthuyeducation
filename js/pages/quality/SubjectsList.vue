<template>
  <div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-bold text-gray-900">{{ t('subjects.title') }}</h2>
          <p class="text-sm text-gray-600 mt-1">{{ t('subjects.description') }}</p>
        </div>
        
        <button
          v-if="authStore.hasPermission('subjects.create')"
          @click="openCreateModal"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center space-x-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>{{ t('subjects.create_subject') }}</span>
        </button>
      </div>
    </div>

    <!-- Content -->
    <div class="p-6">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <svg class="animate-spin h-10 w-10 text-blue-600 mx-auto" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-600 mt-3">{{ t('teachers.loading') }}</p>
      </div>

      <!-- Subjects Grid -->
      <div v-else-if="subjects.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="subject in subjects"
          :key="subject.id"
          class="border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-all"
          :style="{ borderTopColor: subject.color, borderTopWidth: '4px' }"
        >
          <!-- Subject Header -->
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
              <h3 class="text-lg font-bold text-gray-900">{{ subject.name }}</h3>
              <p v-if="subject.code" class="text-sm text-gray-500 mt-1">
                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                  {{ subject.code }}
                </span>
              </p>
            </div>
            
            <!-- Color Badge -->
            <div class="w-8 h-8 rounded-full flex-shrink-0" :style="{ backgroundColor: subject.color }"></div>
          </div>

          <!-- Description -->
          <p v-if="subject.description" class="text-sm text-gray-600 mb-4 line-clamp-2">
            {{ subject.description }}
          </p>

          <!-- Stats -->
          <div class="flex items-center space-x-4 mb-4 text-sm">
            <div class="flex items-center text-gray-600">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <span>{{ subject.active_teachers_count || 0 }} {{ t('subjects.teachers_count') }}</span>
            </div>
          </div>

          <!-- Head Teacher -->
          <div class="mb-4 p-3 bg-green-50 rounded-lg">
            <div class="flex items-center justify-between">
              <span class="text-xs font-medium text-green-900">{{ t('subjects.head_teacher') }}:</span>
              <span v-if="subject.head_teacher" class="text-sm font-semibold text-green-800">
                {{ subject.head_teacher.name }}
              </span>
              <span v-else class="text-sm text-green-600 italic">{{ t('subjects.no_head') }}</span>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center space-x-2">
            <button
              v-if="authStore.hasPermission('subjects.assign_teachers')"
              @click="openManageTeachersModal(subject)"
              class="flex-1 px-3 py-2 bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition text-sm font-medium"
            >
              {{ t('subjects.manage_teachers') }}
            </button>
            
            <button
              v-if="authStore.hasPermission('subjects.edit')"
              @click="openEditModal(subject)"
              class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            
            <button
              v-if="authStore.hasPermission('subjects.delete')"
              @click="confirmDelete(subject)"
              class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12 border border-gray-200 border-dashed rounded-lg">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
        <p class="text-gray-600 font-medium mb-2">{{ t('subjects.no_subjects') }}</p>
        <p class="text-sm text-gray-500 mb-4">{{ t('subjects.no_subjects_desc') }}</p>
        <button
          v-if="authStore.hasPermission('subjects.create')"
          @click="openCreateModal"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
        >
          {{ t('subjects.create_subject') }}
        </button>
      </div>
    </div>

    <!-- Subject Modal -->
    <SubjectModal
      v-if="showSubjectModal"
      :subject="selectedSubject"
      @close="closeSubjectModal"
      @saved="handleSubjectSaved"
    />

    <!-- Manage Teachers Modal -->
    <ManageTeachersModal
      v-if="showManageTeachersModal"
      :subject="selectedSubject"
      @close="closeManageTeachersModal"
      @updated="loadSubjects"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useAuthStore } from '../../stores/auth';
import { useI18n } from '../../composables/useI18n';
import SubjectModal from './SubjectModal.vue';
import ManageTeachersModal from './ManageTeachersModal.vue';

const { t } = useI18n();
const authStore = useAuthStore();

const loading = ref(false);
const subjects = ref([]);
const selectedSubject = ref(null);
const showSubjectModal = ref(false);
const showManageTeachersModal = ref(false);

const loadSubjects = async () => {
  loading.value = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const response = await axios.get('/api/quality/subjects', {
      params: { branch_id: branchId }
    });
    subjects.value = response.data.data || [];
  } catch (error) {
    console.error('Load subjects error:', error);
    await Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || t('subjects.error_loading'),
      confirmButtonText: 'OK'
    });
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  selectedSubject.value = null;
  showSubjectModal.value = true;
};

const openEditModal = (subject) => {
  selectedSubject.value = subject;
  showSubjectModal.value = true;
};

const closeSubjectModal = () => {
  showSubjectModal.value = false;
  selectedSubject.value = null;
};

const handleSubjectSaved = () => {
  closeSubjectModal();
  loadSubjects();
};

const openManageTeachersModal = (subject) => {
  selectedSubject.value = subject;
  showManageTeachersModal.value = true;
};

const closeManageTeachersModal = () => {
  showManageTeachersModal.value = false;
  selectedSubject.value = null;
};

const confirmDelete = async (subject) => {
  const result = await Swal.fire({
    title: t('subjects.delete_confirm'),
    text: t('subjects.delete_warning'),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#EF4444',
    cancelButtonColor: '#6B7280',
    confirmButtonText: t('common.delete'),
    cancelButtonText: t('common.cancel')
  });

  if (result.isConfirmed) {
    await deleteSubject(subject);
  }
};

const deleteSubject = async (subject) => {
  try {
    await axios.delete(`/api/quality/subjects/${subject.id}`);
    await Swal.fire({
      icon: 'success',
      title: t('common.success'),
      text: t('subjects.delete_success'),
      confirmButtonText: 'OK'
    });
    await loadSubjects();
  } catch (error) {
    console.error('Delete subject error:', error);
    await Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || t('common.error_occurred'),
      confirmButtonText: 'OK'
    });
  }
};

onMounted(() => {
  loadSubjects();
});
</script>

