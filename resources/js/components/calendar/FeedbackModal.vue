<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    @click.self="$emit('close')"
  >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[95vh] flex flex-col">
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b bg-gradient-to-r from-blue-500 to-purple-600 rounded-t-lg">
        <h2 class="text-xl font-bold text-white">
          <span v-if="isReadonly">
            👁️ {{ isPlacementTest ? 'Xem kết quả test' : 'Xem đánh giá học thử' }}
          </span>
          <span v-else>
            {{ isPlacementTest ? t('calendar.feedback_modal_title_test') : t('calendar.feedback_modal_title_trial') }}
          </span>
        </h2>
        <button
          @click="$emit('close')"
          class="text-white hover:text-gray-200 transition"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="flex-1 overflow-y-auto px-6 py-4">
        <!-- Event Info -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
          <h3 class="font-semibold text-gray-900 mb-2">{{ event.title }}</h3>
          <div class="text-sm text-gray-600">
            <p>📅 {{ formatDate(event.start_date) }}</p>
            <p v-if="event.customer_info" class="mt-1">
              👤 {{ event.customer_info.name }}
            </p>
            <p v-if="isTrialClass && classTeacher" class="mt-1">
              👨‍🏫 {{ t('calendar.trial_class_teacher') }}: {{ classTeacher }}
            </p>
          </div>
        </div>

        <!-- Placement Test Fields -->
        <div v-if="isPlacementTest" class="space-y-4">
          <!-- Score -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('calendar.test_score') }} 
              <span v-if="!isReadonly" class="text-red-500">*</span>
            </label>
            <input
              v-model="formData.score"
              type="number"
              min="0"
              max="100"
              :disabled="isReadonly"
              :placeholder="isReadonly ? '' : '0-100'"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
            />
          </div>

          <!-- Level -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('calendar.test_level') }} 
              <span v-if="!isReadonly" class="text-red-500">*</span>
            </label>
            <input
              v-model="formData.level"
              type="text"
              :disabled="isReadonly"
              :placeholder="isReadonly ? '' : 'VD: IELTS 5.0, Pre-Intermediate...'"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
            />
          </div>
        </div>

        <!-- Trial Class Fields -->
        <div v-if="isTrialClass" class="space-y-4">
          <!-- Rating -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ t('calendar.trial_rating') }} 
              <span v-if="!isReadonly" class="text-red-500">*</span>
              <span v-if="formData.rating > 0" class="ml-2 text-blue-600 font-semibold">
                ({{ formData.rating }}/5)
              </span>
            </label>
            <div class="flex items-center space-x-1">
              <button
                v-for="star in 5"
                :key="star"
                @click="!isReadonly && (formData.rating = star)"
                type="button"
                :disabled="isReadonly"
                class="text-3xl transition focus:outline-none"
                :class="{'hover:scale-110 cursor-pointer': !isReadonly, 'cursor-not-allowed opacity-75': isReadonly}"
              >
                <span v-if="star <= formData.rating" class="text-yellow-400">★</span>
                <span v-else class="text-gray-300">☆</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Feedback Content (Common) -->
        <div class="mt-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('calendar.feedback_content') }} 
            <span v-if="!isReadonly" class="text-red-500">*</span>
          </label>
          <div ref="editorContainer" class="bg-white border border-gray-300 rounded-lg min-h-[300px]"></div>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-end gap-3 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
        >
          {{ isReadonly ? 'Đóng' : t('calendar.cancel') }}
        </button>
        <button
          v-if="!isReadonly"
          @click="handleSubmit"
          :disabled="loading"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <span v-if="loading" class="animate-spin">⏳</span>
          {{ t('calendar.submit_feedback') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount, computed } from 'vue';
import { useI18n } from '../../composables/useI18n';
import { useAuthStore } from '../../stores/auth';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import dayjs from 'dayjs';
import Swal from 'sweetalert2';

const { t } = useI18n();
const authStore = useAuthStore();

const props = defineProps({
  show: Boolean,
  event: {
    type: Object,
    default: () => ({})
  },
  classTeacher: {
    type: String,
    default: null
  }
});

const emit = defineEmits(['close', 'submit']);

const editorContainer = ref(null);
let quillInstance = null;
const loading = ref(false);

const formData = ref({
  score: null,
  level: '',
  rating: 0,
  feedback: ''
});

const isPlacementTest = computed(() => props.event.calendarId === 'placement_test');
const isTrialClass = computed(() => props.event.calendarId === 'class_session' && props.event.raw?.trial_students_count > 0);

// Readonly khi đã completed HOẶC không có quyền edit
const isReadonly = computed(() => {
  const isCompleted = props.event.raw?.status === 'completed';
  
  // Nếu đã completed, luôn readonly
  if (isCompleted) return true;
  
  // Nếu chưa completed, check quyền edit
  const currentUserId = authStore.user?.id;
  const hasPermission = authStore.hasPermission('calendar.submit_feedback');
  
  if (isPlacementTest.value) {
    // GV được gán hoặc có quyền submit_feedback
    const isAssignedTeacher = props.event.raw?.assigned_teacher_id == currentUserId;
    return !(hasPermission || isAssignedTeacher);
  } else if (isTrialClass.value) {
    // GV dạy buổi đó hoặc có quyền submit_feedback
    const isClassTeacher = props.event.raw?.customer_info?.teacher_id == currentUserId;
    return !(hasPermission || isClassTeacher);
  }
  
  return !hasPermission;
});

const formatDate = (date) => {
  return dayjs(date).format('DD/MM/YYYY HH:mm');
};

const initEditor = () => {
  console.log('🔧 Initializing editor...', {
    containerExists: !!editorContainer.value,
    quillExists: !!quillInstance
  });
  
  if (!editorContainer.value) {
    console.error('❌ Editor container not found!');
    return;
  }
  
  // Cleanup existing instance
  if (quillInstance) {
    console.log('🔄 Cleaning up old Quill instance');
    quillInstance = null;
  }
  
  // Clear container
  editorContainer.value.innerHTML = '';

  console.log('✅ Creating new Quill instance');
  quillInstance = new Quill(editorContainer.value, {
    theme: 'snow',
    placeholder: isReadonly.value ? '' : t('calendar.feedback_placeholder'),
    readOnly: isReadonly.value,
    modules: {
      toolbar: isReadonly.value ? false : [
        [{ 'header': [1, 2, 3, false] }],
        ['bold', 'italic', 'underline'],
        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
        [{ 'color': [] }, { 'background': [] }],
        ['clean']
      ]
    }
  });

  console.log('✅ Quill initialized successfully (readonly:', isReadonly.value, ')');

  // Load existing feedback if any
  if (isPlacementTest.value) {
    const testResult = props.event.raw?.test_result;
    if (testResult?.notes) {
      console.log('📝 Loading existing test feedback:', testResult.notes);
      quillInstance.root.innerHTML = testResult.notes;
    }
  } else if (isTrialClass.value) {
    // Load trial feedback
    const trialFeedback = props.event.raw?.customer_info?.trial_feedback;
    if (trialFeedback?.feedback) {
      console.log('📝 Loading existing trial feedback:', trialFeedback);
      quillInstance.root.innerHTML = trialFeedback.feedback;
      
      // Load rating
      if (trialFeedback.rating) {
        formData.value.rating = trialFeedback.rating;
      }
    }
  }
};

const resetForm = () => {
  const testResult = props.event.raw?.test_result;
  
  if (isPlacementTest.value && testResult) {
    formData.value.score = testResult.score || null;
    formData.value.level = testResult.level || '';
  } else {
    formData.value.score = null;
    formData.value.level = '';
  }
  
  formData.value.rating = 0;
  formData.value.feedback = '';
  
  if (quillInstance) {
    quillInstance.root.innerHTML = '';
  }
};

const handleSubmit = async () => {
  // Get feedback from Quill
  if (!quillInstance) return;

  const feedbackHtml = quillInstance.root.innerHTML;
  const feedbackText = quillInstance.getText().trim();

  // Validation
  if (!feedbackText) {
    await Swal.fire({
      icon: 'warning',
      title: 'Thiếu thông tin',
      text: t('calendar.feedback_required') || 'Vui lòng nhập nội dung đánh giá',
      confirmButtonText: 'Đồng ý'
    });
    return;
  }

  if (isPlacementTest.value) {
    if (!formData.value.score || !formData.value.level) {
      await Swal.fire({
        icon: 'warning',
        title: 'Thiếu thông tin',
        text: 'Vui lòng điền đầy đủ điểm số và trình độ',
        confirmButtonText: 'Đồng ý'
      });
      return;
    }
  }

  if (isTrialClass.value && formData.value.rating === 0) {
    await Swal.fire({
      icon: 'warning',
      title: 'Thiếu thông tin',
      text: 'Vui lòng chọn đánh giá',
      confirmButtonText: 'Đồng ý'
    });
    return;
  }

  // Prepare data
  const data = {
    feedback: feedbackHtml,
    category: props.event.calendarId, // Include category for backend
    ...(isPlacementTest.value && {
      score: formData.value.score,
      level: formData.value.level,
    }),
    ...(isTrialClass.value && {
      rating: formData.value.rating,
    }),
  };

  emit('submit', data);
};

watch(() => props.show, (newVal) => {
  if (newVal) {
    resetForm();
    setTimeout(() => {
      initEditor();
    }, 100);
  }
});

onMounted(() => {
  if (props.show) {
    initEditor();
  }
});

onBeforeUnmount(() => {
  if (quillInstance) {
    quillInstance = null;
  }
});
</script>

<style scoped>
:deep(.ql-container) {
  min-height: 250px;
  max-height: 400px;
  font-size: 14px;
  overflow-y: auto;
}

:deep(.ql-editor) {
  min-height: 250px;
  max-height: 400px;
}
</style>

