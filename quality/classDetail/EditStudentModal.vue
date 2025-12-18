<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">{{ t('class_detail.edit_student') }}</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="p-6">
        <div class="space-y-4">
          <!-- Student Name (readonly) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('class_detail.student_name') }}
            </label>
            <input
              :value="student.student_name"
              type="text"
              disabled
              class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
            />
          </div>

          <!-- Status -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('class_detail.student_status') }}
            </label>
            <select
              v-model="form.status"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="active">{{ t('class_detail.status_active') }}</option>
              <option value="completed">{{ t('class_detail.status_completed') }}</option>
              <option value="dropped">{{ t('class_detail.status_dropped') }}</option>
              <option value="transferred">{{ t('class_detail.status_transferred') }}</option>
            </select>
          </div>

          <!-- Discount -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('class_detail.discount_percent') }}
            </label>
            <input
              v-model.number="form.discount_percent"
              type="number"
              min="0"
              max="100"
              step="0.01"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
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
      <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
        >
          {{ t('common.cancel') }}
        </button>
        <button
          @click="saveStudent"
          :disabled="saving"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50"
        >
          {{ saving ? t('common.saving') : t('common.save') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import api from '../../../api';
import Swal from 'sweetalert2';

const { t } = useI18n();

const props = defineProps({
  classId: {
    type: [String, Number],
    required: true
  },
  student: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close', 'saved']);

const saving = ref(false);

const form = ref({
  status: '',
  discount_percent: 0,
  notes: ''
});

const saveStudent = async () => {
  try {
    saving.value = true;
    
    await api.classes.updateStudent(props.classId, props.student.id, form.value);
    
    emit('saved');
  } catch (error) {
    console.error('Error updating student:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Failed to update student'
    });
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  form.value = {
    status: props.student.status,
    discount_percent: props.student.discount_percent || 0,
    notes: props.student.notes || ''
  };
});
</script>

