<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <!-- Modal Header -->
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-xl font-bold text-gray-900">{{ t('subjects.manage_teachers') }}</h3>
            <p class="text-sm text-gray-600 mt-1">
              {{ subject.name }} 
              <span v-if="subject.code" class="text-gray-400">({{ subject.code }})</span>
            </p>
          </div>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Modal Body -->
      <div class="p-6">
        <!-- Add Teacher Section -->
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <h4 class="text-sm font-semibold text-blue-900 mb-3">{{ t('subjects.add_teachers') }}</h4>
          <div class="flex items-center space-x-3">
            <select
              v-model="selectedTeacherId"
              class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">{{ t('subjects.select_teacher') }}</option>
              <option
                v-for="teacher in availableTeachers"
                :key="teacher.id"
                :value="teacher.id"
              >
                {{ teacher.name }} - {{ teacher.position_name || 'N/A' }}
              </option>
            </select>
            <button
              @click="assignTeacher"
              :disabled="!selectedTeacherId || assigning"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ assigning ? t('common.adding') : t('common.add') }}
            </button>
          </div>
        </div>

        <!-- Current Teachers List -->
        <div>
          <h4 class="text-sm font-semibold text-gray-900 mb-3">
            {{ t('subjects.teacher_list') }} ({{ currentTeachers.length }})
          </h4>

          <!-- Loading State -->
          <div v-if="loading" class="text-center py-8">
            <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>

          <!-- Teachers Table -->
          <div v-else-if="currentTeachers.length > 0" class="border border-gray-200 rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    {{ t('teachers.teacher') }}
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    {{ t('subjects.status') }}
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                    {{ t('common.actions') }}
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr
                  v-for="teacher in currentTeachers"
                  :key="teacher.id"
                  class="hover:bg-gray-50 transition"
                >
                  <!-- Teacher Info -->
                  <td class="px-6 py-4">
                    <div class="flex items-center">
                      <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-green-700 font-semibold text-sm">
                          {{ teacher.name.charAt(0).toUpperCase() }}
                        </span>
                      </div>
                      <div class="ml-4">
                        <div class="font-medium text-gray-900">{{ teacher.name }}</div>
                        <div class="text-sm text-gray-500">{{ teacher.email }}</div>
                      </div>
                    </div>
                  </td>

                  <!-- Status -->
                  <td class="px-6 py-4">
                    <span
                      v-if="teacher.pivot.is_head"
                      class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800"
                    >
                      ⭐ {{ t('subjects.head_teacher') }}
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800"
                    >
                      {{ t('teachers.teacher') }}
                    </span>
                  </td>

                  <!-- Actions -->
                  <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end space-x-2">
                      <button
                        v-if="!teacher.pivot.is_head"
                        @click="setHeadTeacher(teacher)"
                        class="px-3 py-1 text-sm bg-green-50 text-green-700 rounded hover:bg-green-100 transition"
                      >
                        {{ t('subjects.set_head') }}
                      </button>
                      <button
                        @click="removeTeacher(teacher)"
                        class="p-2 text-red-600 hover:bg-red-50 rounded transition"
                      >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-8 border border-gray-200 border-dashed rounded-lg">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <p class="text-gray-600">{{ t('subjects.no_teachers') }}</p>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-end">
        <button
          @click="$emit('close')"
          class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition"
        >
          {{ t('common.close') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useI18n } from '../../composables/useI18n';

const { t } = useI18n();
const props = defineProps({
  subject: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close', 'updated']);

const loading = ref(false);
const assigning = ref(false);
const allTeachers = ref([]);
const currentTeachers = ref([]);
const selectedTeacherId = ref('');

const availableTeachers = computed(() => {
  const currentTeacherIds = currentTeachers.value.map(t => t.id);
  return allTeachers.value.filter(t => !currentTeacherIds.includes(t.id));
});

const loadTeachers = async () => {
  try {
    const branchId = localStorage.getItem('current_branch_id');
    
    // Load position codes from API settings
    const settingsResponse = await axios.get('/api/quality/teachers/settings', {
      params: { branch_id: branchId }
    });
    
    const positionCodes = settingsResponse.data.data.position_codes || [];
    
    if (positionCodes.length > 0) {
      const response = await axios.get('/api/quality/teachers', {
        params: {
          position_codes: positionCodes,
          branch_id: branchId
        }
      });
      allTeachers.value = response.data.data || [];
    } else {
      allTeachers.value = [];
    }
  } catch (error) {
    console.error('Load teachers error:', error);
    allTeachers.value = [];
  }
};

const loadCurrentTeachers = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`/api/quality/subjects/${props.subject.id}`);
    currentTeachers.value = response.data.data.teachers || [];
  } catch (error) {
    console.error('Load current teachers error:', error);
  } finally {
    loading.value = false;
  }
};

const assignTeacher = async () => {
  if (!selectedTeacherId.value) return;

  assigning.value = true;
  try {
    await axios.post(`/api/quality/subjects/${props.subject.id}/assign-teacher`, {
      user_id: selectedTeacherId.value
    });

    await Swal.fire({
      icon: 'success',
      title: t('common.success'),
      text: t('subjects.assign_success'),
      confirmButtonText: 'OK',
      timer: 1500
    });

    selectedTeacherId.value = '';
    await loadCurrentTeachers();
    emit('updated');
  } catch (error) {
    console.error('Assign teacher error:', error);
    
    // Handle specific error codes
    const errorCode = error.response?.data?.error_code;
    
    if (errorCode === 'NO_GOOGLE_EMAIL') {
      await Swal.fire({
        icon: 'warning',
        title: t('common.warning'),
        html: `
          <div class="text-left">
            <p class="mb-3">${error.response.data.message}</p>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mt-2">
              <p class="text-sm text-yellow-800">
                <strong>💡 ${t('subjects.how_to_fix')}:</strong><br>
                1. ${t('subjects.go_to_users_management')}<br>
                2. ${t('subjects.click_assign_google_email')}<br>
                3. ${t('subjects.then_add_teacher_to_subject')}
              </p>
            </div>
          </div>
        `,
        confirmButtonText: t('common.ok'),
        width: '600px'
      });
    } else {
    await Swal.fire({
      icon: 'error',
      title: t('common.error'),
        html: `
          <div class="text-left">
            <p class="mb-2">${error.response?.data?.message || t('common.error_occurred')}</p>
            ${error.response?.data?.details ? `
              <p class="text-sm text-gray-600 mt-2">${error.response.data.details}</p>
            ` : ''}
          </div>
        `,
        confirmButtonText: t('common.ok')
    });
    }
  } finally {
    assigning.value = false;
  }
};

const removeTeacher = async (teacher) => {
  const result = await Swal.fire({
    title: t('common.confirm'),
    text: `Gỡ ${teacher.name} khỏi môn học?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#EF4444',
    cancelButtonColor: '#6B7280',
    confirmButtonText: t('common.remove'),
    cancelButtonText: t('common.cancel')
  });

  if (!result.isConfirmed) return;

  try {
    await axios.post(`/api/quality/subjects/${props.subject.id}/remove-teacher`, {
      user_id: teacher.id
    });

    await Swal.fire({
      icon: 'success',
      title: t('common.success'),
      text: t('subjects.remove_success'),
      confirmButtonText: 'OK',
      timer: 1500
    });

    await loadCurrentTeachers();
    emit('updated');
  } catch (error) {
    console.error('Remove teacher error:', error);
    await Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || t('common.error_occurred'),
      confirmButtonText: 'OK'
    });
  }
};

const setHeadTeacher = async (teacher) => {
  const result = await Swal.fire({
    title: t('common.confirm'),
    text: `Đặt ${teacher.name} làm trưởng bộ môn?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#10B981',
    cancelButtonColor: '#6B7280',
    confirmButtonText: t('common.confirm'),
    cancelButtonText: t('common.cancel')
  });

  if (!result.isConfirmed) return;

  try {
    await axios.post(`/api/quality/subjects/${props.subject.id}/set-head-teacher`, {
      user_id: teacher.id
    });

    await Swal.fire({
      icon: 'success',
      title: t('common.success'),
      text: t('subjects.set_head_success'),
      confirmButtonText: 'OK',
      timer: 1500
    });

    await loadCurrentTeachers();
    emit('updated');
  } catch (error) {
    console.error('Set head teacher error:', error);
    await Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || t('common.error_occurred'),
      confirmButtonText: 'OK'
    });
  }
};

onMounted(async () => {
  await Promise.all([
    loadTeachers(),
    loadCurrentTeachers()
  ]);
});
</script>

