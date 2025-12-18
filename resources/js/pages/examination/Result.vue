<template>
  <div class="result-page max-w-4xl mx-auto py-8 px-4">
    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
      <p class="mt-4 text-gray-600">Đang tải kết quả...</p>
    </div>

    <template v-else-if="result">
      <!-- Score summary -->
      <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
        <div class="text-center">
          <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ result.test?.title }}</h1>
          <p class="text-gray-600">Kết quả bài thi</p>
        </div>

        <!-- Pending publication status (graded but not published) -->
        <div v-if="isPendingPublication" class="flex flex-col items-center my-8">
          <div class="w-40 h-40 rounded-full bg-blue-100 flex items-center justify-center">
            <div class="text-center">
              <div class="text-5xl mb-2">📝</div>
              <div class="text-lg font-semibold text-blue-800">Đang chấm điểm</div>
            </div>
          </div>
          
          <div class="mt-6 text-center max-w-md">
            <p class="text-gray-600 mb-2">Bài làm của bạn đã được nộp thành công</p>
            <p class="text-sm text-gray-500">{{ result.submission?.pending_message || 'Bài làm của bạn đang được chấm điểm. Điểm số sẽ được công bố sau.' }}</p>
          </div>

          <!-- Progress: % câu đã làm -->
          <div class="mt-6 w-full max-w-md">
            <div class="bg-gray-100 rounded-lg p-4">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600">Tiến độ làm bài</span>
                <span class="text-lg font-bold text-blue-600">100%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3">
                <div 
                  class="bg-blue-600 h-3 rounded-full transition-all"
                  style="width: 100%"
                ></div>
              </div>
              <p class="text-sm text-gray-500 mt-2">
                Đã hoàn thành bài thi
              </p>
            </div>
          </div>
        </div>

        <!-- Pending grading status -->
        <div v-else-if="isPendingGrading" class="flex flex-col items-center my-8">
          <div class="w-40 h-40 rounded-full bg-yellow-100 flex items-center justify-center">
            <div class="text-center">
              <div class="text-5xl mb-2">⏳</div>
              <div class="text-lg font-semibold text-yellow-800">Đang chờ chấm</div>
            </div>
          </div>
          
          <div class="mt-6 text-center">
            <p class="text-gray-600 mb-2">Bài thi của bạn đã được nộp thành công</p>
            <p class="text-sm text-gray-500">Kết quả sẽ được cập nhật sau khi giáo viên chấm bài</p>
          </div>

          <!-- Progress: % câu đã làm -->
          <div class="mt-6 w-full max-w-md">
            <div class="bg-gray-100 rounded-lg p-4">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600">Tiến độ làm bài</span>
                <span class="text-lg font-bold text-blue-600">{{ completionPercentage }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3">
                <div 
                  class="bg-blue-600 h-3 rounded-full transition-all"
                  :style="{ width: completionPercentage + '%' }"
                ></div>
              </div>
              <p class="text-sm text-gray-500 mt-2">
                Đã làm {{ answeredCount }} / {{ totalQuestions }} câu
              </p>
            </div>
          </div>
        </div>

        <!-- Score circle (Only show when graded) -->
        <div v-else class="flex justify-center my-8">
          <div
            class="w-40 h-40 rounded-full flex items-center justify-center"
            :class="scoreClass"
          >
            <div class="text-center">
              <!-- Speaking: Show Band Score -->
              <template v-if="isSpeaking">
                <div class="text-4xl font-bold">{{ bandScoreDisplay }}</div>
                <div class="text-sm mt-1">/ 9</div>
              </template>
              <!-- Other tests: Show Percentage -->
              <template v-else>
                <div class="text-4xl font-bold">{{ parseFloat(result.submission?.percentage || 0).toFixed(1) }}%</div>
                <div class="text-sm mt-1">{{ result.submission?.score }} / {{ result.submission?.max_score }}</div>
              </template>
            </div>
          </div>
        </div>

        <!-- IELTS Band score (Only when graded) -->
        <div v-if="!isPendingGrading && result.submission?.band_score" class="text-center mb-6">
          <div class="inline-block bg-purple-100 text-purple-800 px-6 py-3 rounded-lg">
            <span class="text-sm">Band Score:</span>
            <span class="text-3xl font-bold ml-2">{{ parseFloat(result.submission.band_score).toString() }}</span>
          </div>
        </div>

        <!-- Skill scores (IELTS - Only when graded) -->
        <div v-if="!isPendingGrading && result.submission?.skill_scores" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <div v-for="(score, skill) in result.submission.skill_scores" :key="skill" class="bg-gray-50 p-4 rounded-lg text-center">
            <div class="text-sm text-gray-600 capitalize">{{ skill }}</div>
            <div class="text-2xl font-bold text-gray-800">{{ score }}</div>
          </div>
        </div>

        <!-- Status -->
        <div class="flex justify-center">
          <span
            v-if="isPendingGrading"
            class="px-4 py-2 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800"
          >
            ⏳ Đang chờ chấm
          </span>
          <span
            v-else-if="result.submission?.passed !== null"
            class="px-4 py-2 rounded-full text-sm font-medium"
            :class="result.submission?.passed ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
          >
            {{ result.submission?.passed ? '✓ Đạt' : '✗ Chưa đạt' }}
          </span>
        </div>

        <!-- Stats -->
        <div class="grid gap-4 mt-8 pt-6 border-t" :class="isSpeaking ? 'grid-cols-2' : 'grid-cols-3'">
          <div class="text-center">
            <div class="text-sm text-gray-500">Thời gian làm bài</div>
            <div class="text-lg font-semibold">{{ formatDuration(result.submission?.time_spent) }}</div>
          </div>
          <div class="text-center">
            <div class="text-sm text-gray-500">Nộp bài lúc</div>
            <div class="text-lg font-semibold">{{ formatDate(result.submission?.submitted_at) }}</div>
          </div>
          <!-- Hide "Số câu đúng" for Speaking (only has 1 audio question) -->
          <div v-if="!isSpeaking" class="text-center">
            <div class="text-sm text-gray-500">Số câu đúng</div>
            <div class="text-lg font-semibold">
              <span v-if="isPendingGrading" class="text-yellow-600">Đang chờ chấm</span>
              <span v-else>{{ correctCount }} / {{ totalQuestions }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Feedback -->
      <div v-if="result.submission?.feedback" class="bg-blue-50 rounded-lg p-6 mb-8">
        <h3 class="font-semibold text-blue-800 mb-2">Nhận xét của giáo viên</h3>
        <p class="text-blue-700">{{ result.submission.feedback }}</p>
      </div>

      <!-- Answers review -->
      <div v-if="!isPendingGrading" class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-800">Chi tiết câu trả lời</h2>
          <button
            @click="showAnswers = !showAnswers"
            class="text-sm text-blue-600 hover:text-blue-800"
          >
            {{ showAnswers ? 'Ẩn đáp án' : 'Hiện đáp án' }}
          </button>
        </div>

        <div class="divide-y">
          <div
            v-for="(answer, index) in result.answers"
            :key="answer.question_id"
            class="p-6"
            :class="{ 'bg-green-50': answer.is_correct, 'bg-red-50': answer.is_correct === false }"
          >
            <div class="flex items-start justify-between mb-3">
              <div class="flex items-center">
                <span class="w-8 h-8 flex items-center justify-center rounded-full text-white text-sm font-medium mr-3"
                  :class="answer.is_correct ? 'bg-green-500' : answer.is_correct === false ? 'bg-red-500' : 'bg-gray-400'">
                  {{ index + 1 }}
                </span>
                <span class="text-sm text-gray-500 capitalize">{{ answer.question_type }}</span>
              </div>
              <div class="text-sm">
                <span class="font-medium">{{ answer.points_earned ?? '-' }}</span>
                <span class="text-gray-500"> / {{ answer.max_points }}</span>
              </div>
            </div>

            <p class="text-gray-800 font-medium mb-3">{{ answer.question_title }}</p>

            <div class="space-y-3 text-sm">
              <!-- Answer Display -->
              <div>
                <div class="text-gray-500 mb-1">Câu trả lời của bạn:</div>
                
                <!-- Audio Answer (Speaking) -->
                <div v-if="answer.audio_file_path" class="mt-2 space-y-2">
                  <div class="flex items-center space-x-3 bg-blue-50 rounded-lg p-3">
                    <span class="text-blue-600 font-medium">
                      🎤 Ghi âm của bạn
                    </span>
                    <span v-if="answer.audio_duration" class="text-sm text-gray-500">
                      ({{ Math.floor(answer.audio_duration / 60) }}:{{ String(answer.audio_duration % 60).padStart(2, '0') }})
                    </span>
                  </div>
                  <audio controls class="w-full">
                    <source :src="`/storage/${answer.audio_file_path}`" type="audio/wav" />
                    <source :src="`/storage/${answer.audio_file_path}`" type="audio/webm" />
                    <source :src="`/storage/${answer.audio_file_path}`" type="audio/mp3" />
                    Trình duyệt không hỗ trợ phát audio.
                  </audio>
                </div>
                
                <!-- Text Answer -->
                <div v-else class="ml-2 font-medium" :class="answer.is_correct ? 'text-green-700' : 'text-red-700'">
                  {{ formatAnswer(answer.user_answer) || '(Chưa trả lời)' }}
                </div>
              </div>

              <div v-if="showAnswers && answer.correct_answer">
                <span class="text-gray-500">Đáp án đúng:</span>
                <span class="ml-2 font-medium text-green-700">{{ formatAnswer(answer.correct_answer) }}</span>
              </div>

              <div v-if="showAnswers && answer.explanation" class="mt-2 p-3 bg-blue-50 rounded">
                <span class="text-blue-800">{{ answer.explanation }}</span>
              </div>

              <!-- AI Feedback with Accordion (for Speaking/Writing) -->
              <!-- Show if has feedback OR has grading_criteria with feedback -->
              <div v-if="(isSpeaking || isWriting) && (answer.feedback || (answer.grading_criteria && hasCriteriaFeedback(answer.grading_criteria)))" class="mt-4">
                <FeedbackAccordion
                  :feedback="answer.feedback"
                  :grading-criteria="answer.grading_criteria"
                  :band-score="answer.band_score || answer.points_earned"
                  :test-type="isSpeaking ? 'speaking' : 'writing'"
                />
              </div>
              
              <!-- Regular Feedback (for other test types) -->
              <div v-else-if="answer.feedback" class="mt-3 p-4 bg-yellow-50 rounded-lg">
                <div class="text-sm font-semibold text-yellow-800 mb-2">📝 Nhận xét chi tiết:</div>
                <div class="text-sm text-yellow-900 whitespace-pre-wrap leading-relaxed">{{ answer.feedback }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pending grading message for answers -->
      <div v-else class="bg-white rounded-lg shadow-lg p-8 text-center">
        <div class="text-6xl mb-4">📝</div>
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Chi tiết câu trả lời</h3>
        <p class="text-gray-600">Câu trả lời và đáp án sẽ hiển thị sau khi bài thi được chấm</p>
      </div>

      <!-- Actions -->
      <div class="mt-8 flex justify-center space-x-4">
        <button
          @click="goBack"
          class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
        >
          ← Quay lại
        </button>
        <button
          v-if="canRetake"
          @click="retake"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          Làm lại
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSubmissionStore } from '@/stores/examination'
import FeedbackAccordion from '@/components/examination/FeedbackAccordion.vue'

const route = useRoute()
const router = useRouter()
const store = useSubmissionStore()

const loading = ref(true)
const result = ref(null)
const showAnswers = ref(false)
const canRetake = ref(false)

const submissionId = computed(() => route.params.submissionId)

// Check if submission is pending publication (graded but not published)
const isPendingPublication = computed(() => {
  // If has pending_message, it means student can't see results yet
  return !!result.value?.submission?.pending_message || 
         (result.value?.submission?.is_published === false && 
          result.value?.submission?.status === 'graded')
})

// Check if submission is pending grading
const isPendingGrading = computed(() => {
  // Skip if pending publication
  if (isPendingPublication.value) return false
  
  const status = result.value?.submission?.status
  // Pending if status is 'submitted' or 'grading', and no score yet
  return (status === 'submitted' || status === 'grading') && !result.value?.submission?.score
})

// Calculate completion percentage (how many questions answered)
const totalQuestions = computed(() => {
  // Use total questions from test if available (correct total)
  if (result.value?.test?.total_questions) {
    return result.value.test.total_questions
  }
  // Fallback to answers length
  return result.value?.answers?.length || 0
})

const answeredCount = computed(() => {
  // If graded, show the actual submitted answers count
  if (!result.value?.answers) return 0
  // Count all submitted answers (even if empty, they were part of submission)
  return result.value.answers.length
})

const completionPercentage = computed(() => {
  if (totalQuestions.value === 0) return '0.0'
  // If graded, use the score percentage
  if (!isPendingGrading.value && result.value?.submission?.percentage !== null) {
    return parseFloat(result.value.submission.percentage).toFixed(1)
  }
  // Otherwise show answered percentage
  return ((answeredCount.value / totalQuestions.value) * 100).toFixed(1)
})

const correctCount = computed(() => {
  // If graded and has score, use that
  if (!isPendingGrading.value && result.value?.submission?.score !== null) {
    return result.value.submission.score
  }
  // Otherwise count from answers
  if (!result.value?.answers) return 0
  return result.value.answers.filter(a => a.is_correct).length
})

const scoreClass = computed(() => {
  const percentage = result.value?.submission?.percentage || 0
  if (percentage >= 80) return 'bg-green-100 text-green-800'
  if (percentage >= 60) return 'bg-yellow-100 text-yellow-800'
  return 'bg-red-100 text-red-800'
})

// Check if test is Speaking
const isSpeaking = computed(() => {
  return result.value?.test?.subtype === 'speaking'
})

// Check if test is Writing
const isWriting = computed(() => {
  return result.value?.test?.subtype === 'writing'
})

// Band score display (for Speaking)
const bandScoreDisplay = computed(() => {
  if (!isSpeaking.value) return null
  const bandScore = result.value?.submission?.band_score
  if (!bandScore) return '-'
  // Format: remove trailing zeros (5.50 -> 5.5, 6.00 -> 6)
  return parseFloat(bandScore).toString()
})

onMounted(async () => {
  try {
    result.value = await store.getResult(submissionId.value)
  } catch (error) {
    console.error('Error loading result:', error)
  } finally {
    loading.value = false
  }
})

function formatAnswer(answer) {
  if (answer === null || answer === undefined) return null
  if (Array.isArray(answer)) return answer.join(', ')
  if (typeof answer === 'object') return JSON.stringify(answer)
  return String(answer)
}

function formatDuration(seconds) {
  if (!seconds) return '-'
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins} phút ${secs} giây`
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('vi-VN')
}

function goBack() {
  router.push({ name: 'examination.my-assignments' })
}

function retake() {
  // Navigate to take test with the assignment ID
  router.push({
    name: 'examination.take-test',
    params: { assignmentId: result.value?.submission?.assignment_id }
  })
}

// Check if grading_criteria has any feedback
function hasCriteriaFeedback(gradingCriteria) {
  if (!gradingCriteria || typeof gradingCriteria !== 'object') return false
  
  for (const [key, value] of Object.entries(gradingCriteria)) {
    if (typeof value === 'object' && value !== null) {
      if (value.feedback && value.feedback.trim().length > 0) {
        return true
      }
    }
  }
  
  return false
}
</script>
