<template>
  <TransitionRoot appear :show="show" as="template">
    <Dialog as="div" @close="$emit('close')" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black bg-opacity-25" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white shadow-xl transition-all">
              <!-- Header -->
              <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                      <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                    <div>
                      <h3 class="text-lg font-semibold text-white">{{ mode === 'review' ? 'Xem lại bài tập' : 'Chấm bài tập' }}</h3>
                      <p class="text-sm text-orange-100">{{ submission?.student?.name }}</p>
                    </div>
                  </div>
                  <button @click="$emit('close')" class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Content -->
              <div class="p-6 max-h-[70vh] overflow-y-auto">
                <!-- Loading -->
                <div v-if="loading" class="text-center py-12">
                  <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600 mx-auto"></div>
                  <p class="mt-4 text-gray-600">Đang tải bài làm...</p>
                </div>

                <!-- Answers -->
                <div v-else-if="answers && answers.length > 0" class="space-y-6">
                  <GradingAnswerCard
                    v-for="(answer, index) in answers"
                    :key="answer.id"
                    :answer="answer"
                    :index="index"
                    :question-type-name="getQuestionTypeName(answer.exercise?.type)"
                    :show-correct-answer="true"
                  >
                    <!-- Use WritingGradingEditor for essay questions -->
                    <template v-if="answer.exercise?.type === 'essay'" #student-answer="{ answer }">
                      <div class="text-sm font-medium text-gray-700 mb-2">Bài làm của học sinh:</div>
                      <WritingGradingEditor
                        :content="answer.answer_text || answer.answer || ''"
                        :editable="mode === 'grading'"
                        :initial-annotations="answer.annotations || []"
                        :question="getQuestionText(answer.exercise)"
                        @update:annotations="(annotations) => updateAnnotations(answer.id, annotations)"
                        @update:feedback="(feedback) => updateFeedback(answer.id, feedback)"
                        @update:score="(score) => updateScore(answer.id, score)"
                      />

                      <!-- AI Feedback Display -->
                      <div v-if="answerFeedback[answer.id]" class="mt-4 bg-purple-50 border-2 border-purple-200 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                          <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                          </div>
                          <div class="flex-1">
                            <h5 class="text-sm font-semibold text-purple-900 mb-2">Nhận xét từ AI</h5>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ answerFeedback[answer.id] }}</p>
                            <div v-if="answerScores[answer.id]" class="mt-2 text-sm">
                              <span class="font-medium text-purple-700">Điểm đề xuất: </span>
                              <span class="font-bold text-purple-900">{{ answerScores[answer.id] }}/100</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </template>

                    <!-- Grading Controls (only in grading mode) -->
                    <template v-if="mode === 'grading'" #grading="{ answer }">
                      <GradingControls
                        mode="binary"
                        :value="answer.is_correct"
                        :disabled="currentAnswerId === answer.id"
                        @grade="markAnswer(answer, $event)"
                      />
                    </template>

                    <!-- Review Mode - Show Result Only -->
                    <template v-else-if="mode === 'review'" #grading="{ answer }">
                      <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600">Kết quả:</span>
                        <span v-if="answer.is_correct === true" class="px-4 py-2 text-sm rounded-lg bg-green-100 text-green-700 font-medium">
                          ✓ Đúng
                        </span>
                        <span v-else-if="answer.is_correct === false" class="px-4 py-2 text-sm rounded-lg bg-red-100 text-red-700 font-medium">
                          ✗ Sai
                        </span>
                        <span v-else class="px-4 py-2 text-sm rounded-lg bg-gray-100 text-gray-500 font-medium">
                          Chưa chấm
                        </span>
                      </div>
                    </template>
                  </GradingAnswerCard>
                </div>

                <!-- No answers -->
                <div v-else class="text-center py-12 text-gray-500">
                  Không có câu trả lời nào
                </div>

                <!-- Overall Grading Section -->
                <div v-if="answers && answers.length > 0" class="mt-8 bg-blue-50 rounded-lg p-6 border-2 border-blue-200">
                  <h4 class="font-semibold text-blue-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Kết quả chấm bài
                  </h4>

                  <!-- Auto Score Summary -->
                  <div class="bg-white rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-sm text-gray-600">Số câu đúng:</span>
                      <span class="font-semibold text-green-600">{{ correctCount }} / {{ answers.length }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                      <span class="text-sm text-gray-600">Điểm tự động:</span>
                      <span class="font-semibold text-blue-600">{{ autoScore }}</span>
                    </div>
                  </div>

                  <!-- Final Score - Review Mode -->
                  <div v-if="mode === 'review'" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Điểm cuối cùng</label>
                    <div class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg bg-gray-50">
                      <span class="text-2xl font-bold text-blue-600">{{ overallGrade.score || 'Chưa có' }}</span>
                      <span v-if="overallGrade.score" class="text-gray-600">/100</span>
                    </div>
                  </div>

                  <!-- Final Score Input - Grading Mode -->
                  <div v-else class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Điểm cuối cùng</label>
                    <input
                      v-model.number="overallGrade.score"
                      type="number"
                      min="0"
                      max="100"
                      step="0.5"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="Nhập điểm (0-100)"
                    />
                  </div>

                  <!-- Teacher Feedback - Review Mode -->
                  <div v-if="mode === 'review' && overallGrade.feedback" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nhận xét của giáo viên</label>
                    <div class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg bg-gray-50">
                      <p class="text-gray-700 whitespace-pre-wrap">{{ overallGrade.feedback }}</p>
                    </div>
                  </div>

                  <!-- Feedback - Grading Mode -->
                  <FeedbackForm
                    v-if="mode === 'grading'"
                    v-model="overallGrade.feedback"
                    label="Nhận xét của giáo viên"
                    placeholder="Nhập nhận xét, góp ý cho học sinh..."
                    :rows="4"
                  />

                  <!-- Buttons -->
                  <div class="flex items-center justify-end space-x-3">
                    <button
                      @click="$emit('close')"
                      class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      {{ mode === 'review' ? 'Đóng' : 'Hủy' }}
                    </button>
                    <button
                      v-if="mode === 'grading'"
                      @click="saveGrading"
                      :disabled="saving"
                      class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center"
                    >
                      <svg v-if="saving" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      {{ saving ? 'Đang lưu...' : 'Lưu kết quả' }}
                    </button>
                  </div>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
console.log('[HomeworkGradingModal] 🎬 Component script loading...')

import { watch, ref } from 'vue'
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue'
import Swal from 'sweetalert2'
import { useGradingCore } from '../../composables/grading/useGradingCore'
import { createGradingAPI } from '../../composables/grading/useGradingAPI'
import GradingAnswerCard from '../grading/shared/GradingAnswerCard.vue'
import GradingControls from '../grading/shared/GradingControls.vue'
import FeedbackForm from '../grading/shared/FeedbackForm.vue'
import WritingGradingEditor from '../grading/writing/WritingGradingEditor.vue'

console.log('[HomeworkGradingModal] 📦 All imports loaded')

const props = defineProps({
  show: Boolean,
  submission: Object,
  homeworkId: [Number, String],
  mode: {
    type: String,
    default: 'grading', // 'grading' or 'review'
    validator: (value) => ['grading', 'review'].includes(value)
  }
})

console.log('[HomeworkGradingModal] 🚀 Component mounted with props:', props)

const emit = defineEmits(['close', 'graded'])

// Create API adapter for homework
console.log('[HomeworkGradingModal] 🔧 Creating API adapter...')
const apiAdapter = createGradingAPI('homework')
console.log('[HomeworkGradingModal] ✅ API adapter created:', apiAdapter)

// Use grading core composable
console.log('[HomeworkGradingModal] 🎯 Setting up useGradingCore with submissionId:', props.submission?.id)
const {
  loading,
  answers,
  saving,
  currentAnswerId,
  overallGrade,
  correctCount,
  autoScore,
  loadSubmission,
  gradeAnswer,
  saveOverallGrade
} = useGradingCore({
  submissionId: props.submission?.id,
  gradingType: 'homework',
  scoringType: 'percentage',
  apiAdapter
})

console.log('[HomeworkGradingModal] ✅ useGradingCore setup complete')
console.log('[HomeworkGradingModal] 📊 Initial state - loading:', loading.value, 'answers:', answers.value)

// Store annotations for essay answers
const answerAnnotations = ref({})

// Store AI feedback and scores for essay answers
const answerFeedback = ref({})
const answerScores = ref({})

// Watch for modal open to load data
watch(() => props.show, (newVal) => {
  console.log('[HomeworkGradingModal] 👁️ Watch triggered - show:', newVal)
  console.log('[HomeworkGradingModal] 📦 props.submission:', props.submission)
  console.log('[HomeworkGradingModal] 🆔 props.submission?.id:', props.submission?.id)
  console.log('[HomeworkGradingModal] 🎭 mode:', props.mode)
  console.log('[HomeworkGradingModal] 📝 has answers:', props.submission?.answers?.length)

  if (newVal && props.submission) {
    // In review mode, if submission already has answers, use them directly
    if (props.mode === 'review' && props.submission.answers && props.submission.answers.length > 0) {
      console.log('[HomeworkGradingModal] ✅ Review mode with existing answers, populating directly')
      answers.value = props.submission.answers

      // Populate answer feedback and scores if they exist
      props.submission.answers.forEach(answer => {
        if (answer.auto_feedback) {
          answerFeedback.value[answer.id] = answer.auto_feedback
        }
        if (answer.points_earned !== null && answer.points_earned !== undefined) {
          answerScores.value[answer.id] = answer.points_earned
        }
        if (answer.annotations) {
          answerAnnotations.value[answer.id] = answer.annotations
        }
      })
    } else {
      console.log('[HomeworkGradingModal] ✅ Calling loadSubmission with ID:', props.submission.id)
      // Pass submission ID dynamically when modal opens
      loadSubmission(props.submission.id)
    }
  } else {
    console.log('[HomeworkGradingModal] ⚠️ Not loading - newVal:', newVal, 'has submission:', !!props.submission)
  }
}, { immediate: true })

// Mark answer as correct/incorrect
const markAnswer = async (answer, isCorrect) => {
  try {
    // Include annotations if this is an essay question
    const annotations = answerAnnotations.value[answer.id] || null
    console.log('[HomeworkGradingModal] 📝 Marking answer:', {
      answerId: answer.id,
      isCorrect,
      annotations,
      hasAnnotations: !!annotations
    })
    const options = annotations ? { annotations } : {}
    await gradeAnswer(answer.id, isCorrect, options)
  } catch (error) {
    console.error('Error marking answer:', error)
    Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: 'Không thể lưu đánh giá'
    })
  }
}

// Save overall grading
const saveGrading = async () => {
  if (overallGrade.value.score === null || overallGrade.value.score === '') {
    Swal.fire({
      icon: 'warning',
      title: 'Thiếu thông tin',
      text: 'Vui lòng nhập điểm cuối cùng'
    })
    return
  }

  try {
    console.log('[HomeworkGradingModal] 💾 Starting bulk save...')

    // First, save all individual answers with their annotations and AI feedback
    for (const answer of answers.value) {
      // Check if this answer has annotations or AI feedback that need to be saved
      const annotations = answerAnnotations.value[answer.id]
      const feedback = answerFeedback.value[answer.id]
      const score = answerScores.value[answer.id]

      if (annotations && annotations.length > 0) {
        console.log('[HomeworkGradingModal] 📝 Saving annotations for answer:', answer.id, annotations.length)

        // Determine is_correct based on current state or default to true for essay questions
        const isCorrect = answer.is_correct !== null ? answer.is_correct : true

        // Prepare options with annotations and AI feedback
        const options = { annotations }
        if (feedback) {
          options.auto_feedback = feedback
          console.log('[HomeworkGradingModal] 💬 Saving AI feedback for answer:', answer.id, feedback.substring(0, 100) + '...')
        }

        // Save this answer with its annotations and feedback
        await gradeAnswer(answer.id, isCorrect, options)
      } else if (feedback) {
        // If only AI feedback exists (no annotations)
        console.log('[HomeworkGradingModal] 💬 Saving only AI feedback for answer:', answer.id)
        const isCorrect = answer.is_correct !== null ? answer.is_correct : true
        await gradeAnswer(answer.id, isCorrect, { auto_feedback: feedback })
      } else if (answer.is_correct !== null && answer.is_correct !== undefined) {
        // If answer already has is_correct but no new annotations or feedback, save it anyway
        console.log('[HomeworkGradingModal] ✓ Saving answer grading:', answer.id, answer.is_correct)
        await gradeAnswer(answer.id, answer.is_correct, {})
      }
    }

    // Then save the overall grade
    await saveOverallGrade()

    console.log('[HomeworkGradingModal] ✅ All data saved successfully')

    Swal.fire({
      icon: 'success',
      title: 'Thành công',
      text: 'Đã lưu kết quả chấm bài',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    })

    emit('graded', { ...props.submission, grade: overallGrade.value.score })
    emit('close')
  } catch (error) {
    console.error('Error saving grading:', error)
    Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: 'Không thể lưu kết quả chấm bài'
    })
  }
}

// Get question type name
const getQuestionTypeName = (type) => {
  const types = {
    'multiple_choice': 'Trắc nghiệm',
    'essay': 'Tự luận',
    'short_answer': 'Câu trả lời ngắn',
    'true_false': 'Đúng/Sai'
  }
  return types[type] || type
}

// Get question text from exercise
const getQuestionText = (exercise) => {
  if (!exercise) return ''

  // If content is a JSON object with 'question' field, extract it
  if (exercise.content) {
    try {
      // Parse if it's a string
      const content = typeof exercise.content === 'string'
        ? JSON.parse(exercise.content)
        : exercise.content

      if (content.question) {
        return content.question
      }
    } catch (e) {
      // If parsing fails, use as is
      if (typeof exercise.content === 'string') {
        return exercise.content
      }
    }
  }

  // Fallback to title or other fields
  return exercise.title ||
         exercise.question_text ||
         exercise.text ||
         ''
}

// Update annotations for an answer
const updateAnnotations = (answerId, annotations) => {
  console.log('[HomeworkGradingModal] 📝 Updating annotations for answer:', answerId, annotations)
  answerAnnotations.value[answerId] = annotations
}

// Update AI feedback for an answer
const updateFeedback = (answerId, feedback) => {
  console.log('[HomeworkGradingModal] 💬 Updating AI feedback for answer:', answerId, feedback.substring(0, 100) + '...')
  answerFeedback.value[answerId] = feedback
}

// Update AI score for an answer
const updateScore = (answerId, score) => {
  console.log('[HomeworkGradingModal] 🎯 Updating AI score for answer:', answerId, score)
  answerScores.value[answerId] = score
}
</script>
