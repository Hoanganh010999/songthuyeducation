<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" @click.self="$emit('close')">
    <div class="flex items-center justify-center min-h-screen px-4">
      <div class="fixed inset-0 bg-black opacity-30"></div>

      <div class="relative bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">{{ homework.title }}</h3>
            <p class="text-sm text-gray-500 mt-1">
              {{ submissions.length }} bài nộp
            </p>
          </div>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 transition"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Content -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-200px)]">
          <div v-if="loading" class="flex justify-center items-center py-12">
            <svg class="animate-spin h-8 w-8 text-purple-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>

          <div v-else-if="submissions.length === 0" class="text-center py-12">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-gray-500">Chưa có bài nộp nào</p>
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="submission in submissions"
              :key="submission.id"
              class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition border border-gray-200"
            >
              <div class="flex-1">
                <div class="flex items-center space-x-3">
                  <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                      <span class="text-sm font-semibold text-purple-600">
                        {{ getInitials(submission.student.name) }}
                      </span>
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900">
                      {{ submission.student.name }}
                      <span class="text-xs text-gray-500 ml-2">({{ submission.student.student_code }})</span>
                    </p>
                    <p class="text-xs text-gray-500">
                      Nộp lúc: {{ formatDate(submission.submitted_at) }}
                      <span v-if="submission.status === 'late'" class="text-red-600 ml-2">• Trễ hạn</span>
                    </p>
                  </div>
                  <div class="flex items-center space-x-2">
                    <!-- Grade Display -->
                    <div v-if="submission.status === 'graded'" class="text-right">
                      <p class="text-lg font-bold text-green-600">{{ submission.grade }}/100</p>
                      <p class="text-xs text-gray-500">Đã chấm</p>
                    </div>
                    <span
                      v-else
                      class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                    >
                      Chờ chấm
                    </span>
                  </div>
                </div>
              </div>

              <div class="flex items-center space-x-2 ml-4">
                <!-- View Submission Link -->
                <a
                  v-if="submission.submission_link"
                  :href="submission.submission_link"
                  target="_blank"
                  class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-md hover:bg-blue-100 transition"
                  title="Xem bài nộp"
                >
                  <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                  Xem
                </a>

                <!-- Grade Button -->
                <button
                  @click="openGradingModal(submission)"
                  class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-purple-600 rounded-md hover:bg-purple-700 transition"
                  :title="submission.status === 'graded' ? 'Sửa điểm' : 'Chấm bài'"
                >
                  <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                  {{ submission.status === 'graded' ? 'Sửa' : 'Chấm' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Homework Grading Modal (reuse from course) -->
  <HomeworkGradingModal
    v-if="showGradingModal && selectedSubmission"
    :submission-id="selectedSubmission.id"
    :homework-assignment-id="homework.id"
    :student-name="selectedSubmission.student.name"
    @close="closeGradingModal"
    @graded="handleGraded"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import dayjs from 'dayjs';
import HomeworkGradingModal from '../../../components/homework/HomeworkGradingModal.vue';

const props = defineProps({
  homework: {
    type: Object,
    required: true
  },
  session: {
    type: Object,
    required: true
  },
  classData: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close', 'graded']);

const submissions = ref([]);
const loading = ref(false);
const showGradingModal = ref(false);
const selectedSubmission = ref(null);

const loadSubmissions = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`/api/course/homework/${props.homework.id}/submissions`);

    if (response.data.success) {
      submissions.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Error loading submissions:', error);
  } finally {
    loading.value = false;
  }
};

const formatDate = (date) => {
  return dayjs(date).format('DD/MM/YYYY HH:mm');
};

const getInitials = (name) => {
  if (!name) return '?';
  const parts = name.split(' ');
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
};

const openGradingModal = (submission) => {
  selectedSubmission.value = submission;
  showGradingModal.value = true;
};

const closeGradingModal = () => {
  showGradingModal.value = false;
  selectedSubmission.value = null;
};

const handleGraded = () => {
  loadSubmissions(); // Reload to get updated grades
  emit('graded');
};

onMounted(() => {
  loadSubmissions();
});
</script>

