<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full flex flex-col max-h-[95vh]">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">
            📋 {{ t('class_detail.student_evaluation') || 'Đánh giá học viên' }}
          </h3>
          <p class="text-sm text-gray-500 mt-1">
            {{ studentName }} - {{ sessionTitle }}
          </p>
        </div>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="p-6 overflow-y-auto flex-1">
        <div class="space-y-4">
          <div v-for="field in evaluationForm.fields" :key="field.id" class="bg-gray-50 p-4 rounded-lg">
            <!-- Field Title -->
            <label class="block text-base font-semibold text-gray-900 mb-1">
              {{ field.field_title }}
              <span v-if="field.is_required" class="text-red-500">*</span>
            </label>
            
            <!-- Field Description -->
            <p v-if="field.field_description" class="text-sm text-gray-500 italic mb-3">
              {{ field.field_description }}
            </p>

            <!-- Text Field with Quill Editor -->
            <div v-if="field.field_type === 'text'">
              <QuillEditor
                v-model:content="formData[field.id]"
                contentType="html"
                theme="snow"
                :toolbar="[
                  ['bold', 'italic', 'underline'],
                  [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                  ['clean']
                ]"
                class="bg-white"
                style="min-height: 150px;"
              />
            </div>

            <!-- Checkbox Field -->
            <div v-else-if="field.field_type === 'checkbox'" class="space-y-2">
              <label
                v-for="(option, idx) in field.field_config.checkboxes"
                :key="idx"
                class="flex items-center space-x-2 p-2 hover:bg-gray-100 rounded cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="option.value"
                  v-model="formData[field.id]"
                  class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="text-sm">{{ option.label }}</span>
              </label>
            </div>

            <!-- Dropdown Field -->
            <select
              v-else-if="field.field_type === 'dropdown'"
              v-model="formData[field.id]"
              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">{{ t('common.select') || 'Chọn...' }}</option>
              <option
                v-for="(option, idx) in field.field_config.options"
                :key="idx"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3 flex-shrink-0">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
        >
          {{ t('common.cancel') || 'Hủy' }}
        </button>
        <button
          @click="saveEvaluation"
          :disabled="saving"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50 flex items-center space-x-2"
        >
          <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>{{ saving ? (t('common.saving') || 'Đang lưu...') : (t('common.save') || 'Lưu') }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

const { t } = useI18n();

const props = defineProps({
  evaluationForm: {
    type: Object,
    required: true
  },
  studentId: {
    type: Number,
    required: true
  },
  studentName: {
    type: String,
    required: true
  },
  sessionId: {
    type: Number,
    required: true
  },
  sessionTitle: {
    type: String,
    default: ''
  },
  existingData: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['close', 'saved']);

const formData = ref({});
const saving = ref(false);

const initializeFormData = () => {
  const data = {};
  
  props.evaluationForm.fields.forEach(field => {
    if (props.existingData && props.existingData[field.id] !== undefined) {
      // Load existing data
      data[field.id] = props.existingData[field.id];
    } else {
      // Initialize empty
      if (field.field_type === 'checkbox') {
        data[field.id] = [];
      } else {
        data[field.id] = '';
      }
    }
  });
  
  formData.value = data;
};

const saveEvaluation = () => {
  saving.value = true;
  
  // Emit data back to parent
  emit('saved', {
    studentId: props.studentId,
    evaluationData: formData.value
  });
};

onMounted(() => {
  initializeFormData();
});
</script>

<style scoped>
:deep(.ql-container) {
  min-height: 120px;
  font-size: 14px;
}

:deep(.ql-editor) {
  min-height: 120px;
}
</style>

