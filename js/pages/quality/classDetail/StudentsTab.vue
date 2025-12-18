<template>
  <div class="p-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6 flex items-center justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">{{ t('class_detail.tab_students') }}</h3>
        <p class="text-sm text-gray-500 mt-1">
          {{ students.length }} {{ t('class_detail.total_students') || 'students' }}
        </p>
      </div>
      <button
        @click="showAddModal = true"
        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
      >
        {{ t('class_detail.add_student') }}
      </button>
    </div>

    <!-- Students Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('class_detail.student_name') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('class_detail.student_code') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('class_detail.enrollment_date') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('class_detail.student_status') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('class_detail.homework_completion_rate') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('class_detail.absence_rate') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('class_detail.average_grade') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('class_detail.actions') }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="student in students" :key="student.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-10 w-10 bg-blue-500 rounded-full flex items-center justify-center">
                  <span class="text-white font-semibold text-sm">
                    {{ getInitials(student.student_name) }}
                  </span>
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900">{{ student.student_name }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ student.student_code || 'N/A' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(student.enrollment_date) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="statusClass(student.status)">
                {{ t(`class_detail.status_${student.status}`) || student.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div class="flex-1">
                  <div class="text-sm font-medium text-gray-900">{{ student.statistics.homework_completion_rate }}%</div>
                  <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                    <div
                      class="bg-green-600 h-1.5 rounded-full"
                      :style="{ width: student.statistics.homework_completion_rate + '%' }"
                    ></div>
                  </div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div class="flex-1">
                  <div class="text-sm font-medium text-gray-900">{{ student.statistics.absence_rate }}%</div>
                  <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                    <div
                      class="bg-red-600 h-1.5 rounded-full"
                      :style="{ width: student.statistics.absence_rate + '%' }"
                    ></div>
                  </div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ student.statistics.average_grade?.toFixed(1) || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
              <button
                @click="editStudent(student)"
                class="text-blue-600 hover:text-blue-900 mr-3"
              >
                {{ t('class_detail.edit_student') }}
              </button>
              <button
                @click="removeStudent(student)"
                class="text-red-600 hover:text-red-900"
              >
                {{ t('class_detail.remove_student') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div v-if="students.length === 0" class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
      <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
      </svg>
      <p class="text-gray-500">{{ t('class_detail.no_students') || 'No students yet' }}</p>
    </div>

    <!-- Add Student Modal -->
    <AddStudentModal
      v-if="showAddModal"
      :class-id="classId"
      @close="showAddModal = false"
      @saved="handleStudentAdded"
    />

    <!-- Edit Student Modal -->
    <EditStudentModal
      v-if="showEditModal"
      :class-id="classId"
      :student="selectedStudent"
      @close="showEditModal = false"
      @saved="handleStudentUpdated"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import api from '../../../api';
import dayjs from 'dayjs';
import Swal from 'sweetalert2';
import AddStudentModal from './AddStudentModal.vue';
import EditStudentModal from './EditStudentModal.vue';

const { t } = useI18n();

const props = defineProps({
  classId: {
    type: [String, Number],
    required: true
  },
  classData: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['refresh']);

const students = ref([]);
const showAddModal = ref(false);
const showEditModal = ref(false);
const selectedStudent = ref(null);

const statusClass = (status) => {
  if (status === 'active') return 'bg-green-100 text-green-800';
  if (status === 'completed') return 'bg-blue-100 text-blue-800';
  if (status === 'dropped') return 'bg-red-100 text-red-800';
  if (status === 'transferred') return 'bg-yellow-100 text-yellow-800';
  return 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
  return dayjs(date).format('DD/MM/YYYY');
};

const getInitials = (name) => {
  if (!name) return '?';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
};

const loadStudents = async () => {
  try {
    const response = await api.classes.getStudents(props.classId);
    // API already returns flat data with student_name, student_code
    students.value = response.data.data || [];
  } catch (error) {
    console.error('Error loading students:', error);
  }
};

const editStudent = (student) => {
  selectedStudent.value = student;
  showEditModal.value = true;
};

const removeStudent = async (student) => {
  const result = await Swal.fire({
    title: t('class_detail.confirm_remove'),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: t('common.yes'),
    cancelButtonText: t('common.cancel')
  });

  if (result.isConfirmed) {
    try {
      await api.classes.removeStudent(props.classId, student.id);
      Swal.fire({
        icon: 'success',
        title: t('class_detail.student_removed'),
        timer: 2000,
        showConfirmButton: false
      });
      loadStudents();
      emit('refresh');
    } catch (error) {
      console.error('Error removing student:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: error.response?.data?.message || 'Failed to remove student'
      });
    }
  }
};

const handleStudentAdded = () => {
  showAddModal.value = false;
  Swal.fire({
    icon: 'success',
    title: t('class_detail.student_added'),
    timer: 2000,
    showConfirmButton: false
  });
  loadStudents();
  emit('refresh');
};

const handleStudentUpdated = () => {
  showEditModal.value = false;
  Swal.fire({
    icon: 'success',
    title: t('class_detail.student_updated'),
    timer: 2000,
    showConfirmButton: false
  });
  loadStudents();
  emit('refresh');
};

onMounted(() => {
  loadStudents();
});
</script>

