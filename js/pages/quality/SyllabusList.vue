<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ t('syllabus.list_title') }}</h2>
        <p class="text-sm text-gray-600 mt-1">{{ t('syllabus.module_description') }}</p>
      </div>
      <button
        v-if="authStore.hasPermission('lesson_plans.create') || authStore.hasPermission('syllabus.create')"
        @click="showFormModal = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center space-x-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        <span>{{ t('syllabus.create_new') }}</span>
      </button>
    </div>

    <!-- Syllabus List -->
    <div class="bg-white rounded-lg shadow">
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin h-8 w-8 border-4 border-blue-600 border-t-transparent rounded-full mx-auto"></div>
      </div>
      
      <div v-else-if="syllabi.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="mt-4 text-gray-500">{{ t('syllabus.no_items') }}</p>
      </div>

      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('syllabus.name') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('syllabus.subject') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('syllabus.total_units') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('syllabus.status') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('common.actions') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="syllabus in syllabi" :key="syllabus.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 font-medium text-gray-900">{{ syllabus.name }}</td>
            <td class="px-6 py-4 text-gray-600">{{ syllabus.subject?.name || '-' }}</td>
            <td class="px-6 py-4 text-gray-600">{{ syllabus.sessions?.length || 0 }}/{{ syllabus.total_sessions }}</td>
            <td class="px-6 py-4">
              <span :class="statusClass(syllabus.status)" class="px-2 py-1 text-xs rounded-full">
                {{ statusText(syllabus.status) }}
              </span>
            </td>
            <td class="px-6 py-4 text-right space-x-3">
              <button @click="viewSyllabus(syllabus)" class="text-blue-600 hover:text-blue-800">{{ t('syllabus.view_details') }}</button>
              <button v-if="authStore.hasPermission('lesson_plans.edit')" @click="editSyllabus(syllabus)" class="text-green-600 hover:text-green-800">{{ t('common.edit') }}</button>
              <button v-if="authStore.hasPermission('lesson_plans.delete')" @click="deleteSyllabus(syllabus)" class="text-red-600 hover:text-red-800">{{ t('common.delete') }}</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Syllabus Form Modal -->
    <div v-if="showFormModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-bold text-gray-900">{{ editingSyllabus ? t('syllabus.edit') : t('syllabus.create_new') }}</h3>
          <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <SyllabusForm 
          :syllabus="editingSyllabus" 
          @saved="onSyllabusSaved" 
          @cancel="closeModal" 
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';
import Swal from 'sweetalert2';
import SyllabusForm from './SyllabusForm.vue';

const authStore = useAuthStore();
const { t } = useI18n();
const router = useRouter();

const loading = ref(false);
const syllabi = ref([]);
const showFormModal = ref(false);
const editingSyllabus = ref(null);

const loadSyllabi = async () => {
  loading.value = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const response = await axios.get('/api/lesson-plans', { params: { branch_id: branchId } });
    syllabi.value = response.data.data;
  } catch (error) {
    console.error('Load syllabi error:', error);
    Swal.fire(t('common.error'), t('syllabus.error_load'), 'error');
  } finally {
    loading.value = false;
  }
};

const viewSyllabus = (syllabus) => {
  router.push(`/quality/syllabus/${syllabus.id}`);
};

const editSyllabus = (syllabus) => {
  editingSyllabus.value = syllabus;
  showFormModal.value = true;
};

const deleteSyllabus = async (syllabus) => {
  const result = await Swal.fire({
    title: t('syllabus.confirm_delete'),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: t('common.delete'),
    cancelButtonText: t('common.cancel')
  });

  if (!result.isConfirmed) return;

  try {
    await axios.delete(`/api/lesson-plans/${syllabus.id}`);
    await Swal.fire(t('common.success'), t('syllabus.deleted'), 'success');
    await loadSyllabi();
  } catch (error) {
    console.error('Delete syllabus error:', error);
    Swal.fire(t('common.error'), t('syllabus.error_delete'), 'error');
  }
};

const closeModal = () => {
  showFormModal.value = false;
  editingSyllabus.value = null;
};

const onSyllabusSaved = () => {
  closeModal();
  loadSyllabi();
};

const statusClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    approved: 'bg-blue-100 text-blue-800',
    active: 'bg-green-100 text-green-800',
    in_use: 'bg-green-100 text-green-800',
    archived: 'bg-gray-100 text-gray-800'
  };
  return classes[status] || classes.draft;
};

const statusText = (status) => {
  const texts = {
    draft: t('syllabus.status_draft'),
    approved: t('syllabus.status_approved'),
    active: t('syllabus.status_approved'),
    in_use: t('syllabus.status_in_use'),
    archived: t('syllabus.status_archived')
  };
  return texts[status] || status;
};

onMounted(() => {
  loadSyllabi();
});
</script>

