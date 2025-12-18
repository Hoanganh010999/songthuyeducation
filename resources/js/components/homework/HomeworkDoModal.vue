<template>
  <!-- Modal với backdrop static - không cho click ra ngoài -->
  <div class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-[999]" @click.stop>
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-6xl max-h-[95vh] overflow-hidden flex flex-col" @click.stop>
      <!-- Header -->
      <div class="bg-gradient-to-r from-orange-600 to-orange-500 text-white px-6 py-4">
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <h2 class="text-2xl font-bold">{{ homework.title }}</h2>
            <div class="flex items-center space-x-4 mt-2 text-sm">
              <span v-if="homework.deadline" class="flex items-center space-x-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>Hạn: {{ formatDate(homework.deadline) }}</span>
              </span>
              <span class="flex items-center space-x-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>{{ exercises.length }} câu hỏi</span>
              </span>
              <span class="flex items-center space-x-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
                <span>Tổng: {{ totalPoints }} điểm</span>
              </span>
            </div>
          </div>
          <button
            @click="handleClose"
            class="ml-4 text-white hover:bg-orange-700 rounded-lg p-2 transition-colors"
            :disabled="isSubmitting"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Progress Bar -->
      <div class="bg-gray-100 px-6 py-3 border-b">
        <div class="flex items-center justify-between text-sm text-gray-700">
          <span>Câu {{ currentQuestionIndex + 1 }} / {{ exercises.length }}</span>
          <span>Đã trả lời: {{ answeredCount }} / {{ exercises.length }}</span>
        </div>
        <div class="mt-2 w-full bg-gray-300 rounded-full h-2">
          <div
            class="bg-orange-600 h-2 rounded-full transition-all duration-300"
            :style="{ width: progressPercentage + '%' }"
          ></div>
        </div>
      </div>

      <!-- Content - Hiển thị từng câu hỏi -->
      <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
        <div v-if="exercises.length > 0" class="max-w-4xl mx-auto">
          <!-- Description -->
          <div v-if="homework.description && currentQuestionIndex === 0" class="mb-6 p-4 bg-orange-50 rounded-lg border border-orange-200">
            <h3 class="text-sm font-semibold text-orange-900 mb-2">Hướng dẫn:</h3>
            <div class="text-sm text-gray-700" v-html="homework.description"></div>
          </div>

          <!-- Current Exercise -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-start justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900">
                Câu {{ currentQuestionIndex + 1 }}: {{ currentExercise.title }}
              </h3>
              <span class="text-sm font-semibold text-orange-600 whitespace-nowrap ml-4">
                {{ currentExercise.pivot?.points || currentExercise.points || 0 }} điểm
              </span>
            </div>

            <!-- Exercise Info -->
            <div class="flex items-center space-x-2 mb-4">
              <span class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded">{{ currentExercise.type }}</span>
              <span v-if="currentExercise.skill" class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded">{{ currentExercise.skill }}</span>
              <span v-if="currentExercise.difficulty" class="px-2 py-1 text-xs bg-purple-100 text-purple-700 rounded">{{ currentExercise.difficulty }}</span>
            </div>

            <!-- Instructions -->
            <div v-if="currentExerciseContent.instructions" class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
              <p class="text-sm text-gray-700" v-html="currentExerciseContent.instructions"></p>
            </div>

            <!-- Reading Passage -->
            <div v-if="currentExerciseContent.passage" class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-300">
              <div class="flex items-center space-x-2 mb-3">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <h4 class="font-semibold text-gray-900">Đoạn văn:</h4>
              </div>
              <div class="text-gray-800 leading-relaxed whitespace-pre-wrap text-justify" v-html="formatText(currentExerciseContent.passage)"></div>
            </div>

            <!-- Question Renderer -->
            <QuestionRenderer
              :question="currentQuestionData"
              :questionNumber="currentQuestionIndex + 1"
              :showResult="false"
              :disabled="false"
              v-model="answers[currentExercise.id]"
            />
          </div>
        </div>

        <!-- No exercises -->
        <div v-else class="text-center py-12">
          <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <p class="text-gray-500">Bài tập này chưa có câu hỏi</p>
        </div>
      </div>

      <!-- Footer - Navigation & Submit -->
      <div class="border-t px-6 py-4 bg-white flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <button
            @click="previousQuestion"
            :disabled="currentQuestionIndex === 0 || isSubmitting"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center space-x-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            <span>Câu trước</span>
          </button>
          <button
            @click="nextQuestion"
            :disabled="currentQuestionIndex === exercises.length - 1 || isSubmitting"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center space-x-2"
          >
            <span>Câu sau</span>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>

        <div class="flex items-center space-x-3">
          <button
            @click="handleClose"
            :disabled="isSubmitting"
            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 disabled:opacity-50 transition-colors"
          >
            Hủy
          </button>
          <button
            @click="confirmSubmit"
            :disabled="isSubmitting || answeredCount === 0"
            class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center space-x-2"
          >
            <svg v-if="isSubmitting" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ isSubmitting ? 'Đang nộp...' : 'Nộp bài' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import QuestionRenderer from '../examination/questions/QuestionRenderer.vue';
import Swal from 'sweetalert2';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();

const props = defineProps({
  homework: {
    type: Object,
    required: true
  },
  studentId: {
    type: Number,
    default: null
  }
});

const emit = defineEmits(['close', 'submitted']);

const exercises = ref([]);
const currentQuestionIndex = ref(0);
const answers = ref({});
const isSubmitting = ref(false);

// Current exercise
const currentExercise = computed(() => exercises.value[currentQuestionIndex.value] || {});

// Parse current exercise content
const currentExerciseContent = computed(() => {
  if (typeof currentExercise.value.content === 'string') {
    try {
      return JSON.parse(currentExercise.value.content);
    } catch (e) {
      return { passage: null, question: null, instructions: null };
    }
  }
  return currentExercise.value.content || { passage: null, question: null, instructions: null };
});

// Prepare question data for QuestionRenderer
const currentQuestionData = computed(() => {
  const content = currentExerciseContent.value;
  return {
    id: currentExercise.value.id,
    type: currentExercise.value.type,
    title: currentExercise.value.title,
    content: content.question || content,
    correct_answer: currentExercise.value.correct_answer,
    points: currentExercise.value.pivot?.points || currentExercise.value.points || 0,
    options: currentExercise.value.options || [],  // Include options for multiple choice questions
    image_url: currentExercise.value.image_url
  };
});

// Total points
const totalPoints = computed(() => {
  return exercises.value.reduce((sum, ex) => {
    const points = ex.pivot?.points || ex.points || 0;
    return sum + Number(points);  // Convert to number to avoid string concatenation
  }, 0);
});

// Answered count
const answeredCount = computed(() => {
  return Object.keys(answers.value).filter(key => {
    const answer = answers.value[key];
    return answer !== null && answer !== undefined && answer !== '';
  }).length;
});

// Progress percentage
const progressPercentage = computed(() => {
  if (exercises.value.length === 0) return 0;
  return (answeredCount.value / exercises.value.length) * 100;
});

// Load exercises
onMounted(async () => {
  try {
    // Load homework bank items to get exercises
    if (props.homework.homework_bank && props.homework.homework_bank.length > 0) {
      // Flatten all exercises from all homework banks
      props.homework.homework_bank.forEach(bank => {
        if (bank.exercises && bank.exercises.length > 0) {
          exercises.value.push(...bank.exercises);
        }
      });
    }

    // Initialize answers object
    exercises.value.forEach(ex => {
      answers.value[ex.id] = null;
    });

    // Load existing submission if any
    try {
      const studentId = props.studentId || authStore.currentUser?.id;
      const response = await axios.get(`/api/course/homework/${props.homework.id}/my-submission`, {
        params: { student_id: studentId }
      });

      if (response.data.success && response.data.data) {
        const submission = response.data.data;
        console.log('Loaded existing submission:', submission);

        // Pre-fill answers from submission
        if (submission.answers && submission.answers.length > 0) {
          submission.answers.forEach(answer => {
            if (answer.answer_text) {
              answers.value[answer.exercise_id] = answer.answer_text;
            } else if (answer.answer) {
              try {
                answers.value[answer.exercise_id] = JSON.parse(answer.answer);
              } catch (e) {
                answers.value[answer.exercise_id] = answer.answer;
              }
            }
          });
        }
      }
    } catch (error) {
      console.error('Error loading existing submission:', error);
      // Non-critical error, just log it
    }
  } catch (error) {
    console.error('Error loading exercises:', error);
    Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: 'Không thể tải câu hỏi bài tập'
    });
  }
});

function nextQuestion() {
  if (currentQuestionIndex.value < exercises.value.length - 1) {
    currentQuestionIndex.value++;
  }
}

function previousQuestion() {
  if (currentQuestionIndex.value > 0) {
    currentQuestionIndex.value--;
  }
}

function formatText(text) {
  if (!text) return '';
  return text.replace(/\n/g, '<br>');
}

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('vi-VN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  });
}

async function confirmSubmit() {
  const unansweredCount = exercises.value.length - answeredCount.value;

  const result = await Swal.fire({
    title: 'Xác nhận nộp bài',
    html: `
      <div class="text-left">
        <p class="mb-2">Bạn đã trả lời <strong>${answeredCount.value}/${exercises.value.length}</strong> câu hỏi.</p>
        ${unansweredCount > 0 ? `<p class="text-red-600">Còn <strong>${unansweredCount}</strong> câu chưa trả lời.</p>` : ''}
        <p class="mt-2">Bạn có chắc chắn muốn nộp bài?</p>
      </div>
    `,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ea580c',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Nộp bài',
    cancelButtonText: 'Kiểm tra lại'
  });

  if (result.isConfirmed) {
    await submitHomework();
  }
}

async function submitHomework() {
  isSubmitting.value = true;

  try {
    // Transform answers object to array format expected by backend
    const answersArray = Object.entries(answers.value).map(([questionId, answer]) => ({
      question_id: parseInt(questionId),
      text_answer: typeof answer === 'string' ? answer : null,
      selected_answer: typeof answer !== 'string' ? answer : null
    }));

    // Get student_id from prop, or fallback to current authenticated user
    const studentId = props.studentId || authStore.currentUser?.id;

    if (!studentId) {
      throw new Error('Cannot determine student ID');
    }

    const submissionData = {
      homework_assignment_id: props.homework.id,
      student_id: studentId,
      answers: answersArray,
      submitted_at: new Date().toISOString()
    };

    const response = await axios.post(`/api/course/homework/${props.homework.id}/submit`, submissionData);

    if (response.data.success) {
      await Swal.fire({
        icon: 'success',
        title: 'Nộp bài thành công!',
        text: `Bạn đã hoàn thành ${answeredCount.value}/${exercises.value.length} câu hỏi.`,
        confirmButtonColor: '#ea580c'
      });

      emit('submitted', response.data);
      emit('close');
    }
  } catch (error) {
    console.error('Error submitting homework:', error);
    Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: error.response?.data?.message || 'Không thể nộp bài. Vui lòng thử lại.'
    });
  } finally {
    isSubmitting.value = false;
  }
}

async function handleClose() {
  if (answeredCount.value > 0 && !isSubmitting.value) {
    const result = await Swal.fire({
      title: 'Bạn chưa nộp bài',
      text: 'Bạn có chắc chắn muốn thoát? Câu trả lời sẽ không được lưu.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'Thoát',
      cancelButtonText: 'Tiếp tục làm bài'
    });

    if (result.isConfirmed) {
      emit('close');
    }
  } else {
    emit('close');
  }
}
</script>
