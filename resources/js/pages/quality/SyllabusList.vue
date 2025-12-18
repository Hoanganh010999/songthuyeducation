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
            <td class="px-6 py-4 relative">
              <div class="inline-block" @click.stop>
                <button
                  v-if="canChangeStatus || syllabus.can_edit"
                  @click="toggleStatusDropdown(syllabus.id)"
                  :class="statusClass(syllabus.status)"
                  class="px-3 py-1 text-xs rounded-full cursor-pointer hover:opacity-80 transition flex items-center space-x-1"
                >
                  <span>{{ statusText(syllabus.status) }}</span>
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
                <span v-else :class="statusClass(syllabus.status)" class="px-2 py-1 text-xs rounded-full">
                  {{ statusText(syllabus.status) }}
                </span>

                <!-- Status Dropdown -->
                <div
                  v-if="statusDropdownOpen === syllabus.id"
                  class="absolute z-50 mt-1 bg-white rounded-lg shadow-lg border border-gray-200 py-1 min-w-[150px]"
                  @click.stop
                >
                  <button
                    v-for="status in availableStatuses"
                    :key="status.value"
                    @click="changeStatus(syllabus, status.value)"
                    :class="{'bg-gray-100': syllabus.status === status.value}"
                    class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 flex items-center space-x-2"
                  >
                    <span :class="statusClass(status.value)" class="w-2 h-2 rounded-full"></span>
                    <span>{{ status.label }}</span>
                  </button>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-end space-x-2">
                <!-- View Button -->
                <button
                  @click="viewSyllabus(syllabus)"
                  :title="t('syllabus.view_details')"
                  class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                </button>

                <!-- Edit Button - use can_edit from API (head teacher, dept head, or permission) -->
                <button
                  v-if="syllabus.can_edit"
                  @click="editSyllabus(syllabus)"
                  :title="t('common.edit')"
                  class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </button>

                <!-- Delete Button - use can_edit from API (head teacher, dept head, or permission) -->
                <button
                  v-if="syllabus.can_edit"
                  @click="deleteSyllabus(syllabus)"
                  :title="t('common.delete')"
                  class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
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
import { ref, onMounted, onUnmounted } from 'vue';
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
const statusDropdownOpen = ref(null);

// Available statuses
const availableStatuses = [
  { value: 'draft', label: t('syllabus.status_draft') || 'Bản nháp' },
  { value: 'approved', label: t('syllabus.status_approved') || 'Đã duyệt' },
  { value: 'in_use', label: t('syllabus.status_in_use') || 'Đang sử dụng' },
  { value: 'archived', label: t('syllabus.status_archived') || 'Lưu trữ' }
];

// Check if user can change status
const canChangeStatus = authStore.hasPermission('syllabus.change_status');

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

const toggleStatusDropdown = (syllabusId) => {
  if (statusDropdownOpen.value === syllabusId) {
    statusDropdownOpen.value = null;
  } else {
    statusDropdownOpen.value = syllabusId;
  }
};

const changeStatus = async (syllabus, newStatus) => {
  if (syllabus.status === newStatus) {
    statusDropdownOpen.value = null;
    return;
  }

  try {
    const response = await axios.patch(`/api/lesson-plans/${syllabus.id}/status`, {
      status: newStatus
    });

    if (response.data.success) {
      // Update local state
      syllabus.status = newStatus;

      // Show success message
      await Swal.fire({
        icon: 'success',
        title: 'Thành công!',
        text: response.data.message,
        timer: 2000,
        showConfirmButton: false
      });
    }
  } catch (error) {
    console.error('Change status error:', error);
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi!',
      text: error.response?.data?.message || 'Không thể thay đổi trạng thái',
      confirmButtonText: 'OK'
    });
  } finally {
    statusDropdownOpen.value = null;
  }
};

// Close dropdown when clicking outside
const closeDropdownOnClickOutside = (event) => {
  if (statusDropdownOpen.value && !event.target.closest('.inline-block')) {
    statusDropdownOpen.value = null;
  }
};

onMounted(() => {
  loadSyllabi();
  document.addEventListener('click', closeDropdownOnClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', closeDropdownOnClickOutside);
});
</script>

