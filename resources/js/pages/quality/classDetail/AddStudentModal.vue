<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white">
        <h3 class="text-lg font-semibold text-gray-900">{{ t('class_detail.add_student') }}</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="p-6">
        <div class="space-y-4">
          <!-- Search Student -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('common.search') }} {{ t('quality.student') }}
            </label>
            <input
              v-model="searchQuery"
              @input="debouncedFilter"
              type="text"
              placeholder="Nhập mã học viên để tìm..."
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
            />
            <p class="text-xs text-blue-600 mt-1">💡 Chỉ cho phép tìm theo mã học viên (ví dụ: SV001)</p>
          </div>

          <!-- Student Selection with Class Info -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('class_detail.student_name') }} *
            </label>
            <div class="relative">
              <select
                v-model="form.student_id"
                @change="onStudentSelected"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                required
              >
                <option value="">{{ t('common.select') || 'Select...' }}</option>
                <option 
                  v-for="student in filteredStudents" 
                  :key="student.id" 
                  :value="student.user_id"
                  :class="{'text-orange-600': !student.classes || student.classes.length === 0}"
                >
                  {{ student.user?.name }} - {{ student.student_code }}
                  {{ student.classes && student.classes.length > 0 ? ` [${student.classes.map(c => c.name).join(', ')}]` : ' ⚠️ Chưa có lớp' }}
                </option>
              </select>
            </div>
          </div>

          <!-- Selected Student Info -->
          <div v-if="selectedStudent" class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900 mb-2">{{ t('quality.student_info') }}:</p>
                <div class="space-y-1 text-sm">
                  <p><span class="text-gray-600">{{ t('common.name') }}:</span> <strong>{{ selectedStudent.user?.name }}</strong></p>
                  <p><span class="text-gray-600">{{ t('quality.student_code') }}:</span> <strong>{{ selectedStudent.student_code }}</strong></p>
                  <p><span class="text-gray-600">{{ t('common.email') }}:</span> {{ selectedStudent.user?.email || '-' }}</p>
                  <p><span class="text-gray-600">{{ t('common.phone') }}:</span> {{ selectedStudent.user?.phone || '-' }}</p>
                </div>

                <!-- Active Classes -->
                <div class="mt-3">
                  <p class="text-sm font-medium text-gray-700 mb-1">{{ t('quality.current_classes') }}:</p>
                  <div v-if="selectedStudent.classes && selectedStudent.classes.length > 0" class="flex flex-wrap gap-1">
                    <span v-for="cls in selectedStudent.classes" :key="cls.id" class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                      {{ cls.name }}
                    </span>
                  </div>
                  <div v-else class="flex items-center gap-1 text-orange-600">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-xs font-medium">{{ t('quality.no_active_classes') }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Enrollment Date -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('class_detail.enrollment_date') }} *
            </label>
            <input
              v-model="form.enrollment_date"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              required
            />
          </div>

          <!-- Notes -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('class_detail.notes') }}
            </label>
            <textarea
              v-model="form.notes"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              :placeholder="t('class_detail.notes')"
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3 sticky bottom-0 bg-white">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
        >
          {{ t('common.cancel') }}
        </button>
        <button
          @click="saveStudent"
          :disabled="saving || !form.student_id || !form.enrollment_date"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50"
        >
          {{ saving ? t('common.saving') : t('common.save') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import api from '../../../services/api';
import dayjs from 'dayjs';
import Swal from 'sweetalert2';

const { t } = useI18n();

const props = defineProps({
  classId: {
    type: [String, Number],
    required: true
  }
});

const emit = defineEmits(['close', 'saved']);

const availableStudents = ref([]);
const filteredStudents = ref([]);
const searchQuery = ref('');
const saving = ref(false);
const selectedStudent = ref(null);
let searchTimeout = null;

const form = ref({
  student_id: '',
  enrollment_date: dayjs().format('YYYY-MM-DD'),
  notes: ''
});

const loadAvailableStudents = async () => {
  try {
    // Get students from Quality Management student list
    const response = await api.get('/api/quality/students', {
      params: { per_page: 1000 }
    });
    availableStudents.value = response.data.data.data || [];
    filteredStudents.value = availableStudents.value;
  } catch (error) {
    console.error('Error loading students:', error);
  }
};

const filterStudents = async () => {
  if (!searchQuery.value) {
    filteredStudents.value = availableStudents.value;
    return;
  }

  const query = searchQuery.value.toLowerCase();
  
  // ONLY filter by student code
  let filtered = availableStudents.value.filter(student => {
    const code = student.student_code?.toLowerCase() || '';
    return code.includes(query);
  });
  
  // If not found in list and query >= 2 chars, search by code on server
  if (query.length >= 2 && filtered.length === 0) {
    try {
      const response = await api.get('/api/quality/students/search-by-code', {
        params: { student_code: query }
      });
      if (response.data.data) {
        // Add the found student to list if not already there
        const foundStudent = response.data.data;
        const exists = availableStudents.value.find(s => s.id === foundStudent.id);
        if (!exists) {
          availableStudents.value.push(foundStudent);
        }
        filtered = [foundStudent];
      }
    } catch (error) {
      console.error('Error searching student by code:', error);
    }
  }
  
  filteredStudents.value = filtered;
};

const debouncedFilter = () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }
  searchTimeout = setTimeout(() => {
    filterStudents();
  }, 300);
};

const onStudentSelected = () => {
  selectedStudent.value = [...availableStudents.value, ...filteredStudents.value]
    .find(s => s.user_id === form.value.student_id);
};

const saveStudent = async () => {
  try {
    saving.value = true;
    
    await api.post(`/api/quality/classes/${props.classId}/students`, {
      student_id: form.value.student_id,
      enrollment_date: form.value.enrollment_date,
      status: 'active',
      notes: form.value.notes
    });
    
    emit('saved');
  } catch (error) {
    console.error('Error adding student:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Failed to add student'
    });
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadAvailableStudents();
});
</script>
