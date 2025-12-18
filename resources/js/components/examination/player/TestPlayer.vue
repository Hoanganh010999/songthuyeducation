<template>
  <div class="test-player min-h-screen bg-gray-100">
    <!-- Loading state -->
    <div v-if="loading" class="flex items-center justify-center h-screen">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Đang tải bài thi...</p>
      </div>
    </div>

    <!-- Test content -->
    <div v-else-if="submission" class="flex flex-col h-screen">
      <!-- Header -->
      <header class="bg-white shadow-sm border-b sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 py-3">
          <div class="flex items-center justify-between">
            <!-- Test title -->
            <div>
              <h1 class="text-lg font-semibold text-gray-800">{{ submission.test?.title }}</h1>
              <p class="text-sm text-gray-500">{{ submission.test?.type?.toUpperCase() }}</p>
            </div>

            <!-- Timer -->
            <div v-if="submission.time_limit" class="flex items-center">
              <div
                class="px-4 py-2 rounded-lg font-mono text-lg font-bold"
                :class="timerClass"
              >
                <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ formattedTime }}
              </div>
            </div>

            <!-- Progress -->
            <div class="flex items-center space-x-4">
              <div class="text-sm text-gray-600">
                <span class="font-medium">{{ answeredCount }}</span> / {{ totalQuestions }} câu
              </div>

              <!-- Submit button -->
              <button
                @click="confirmSubmit"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                :disabled="submitting"
              >
                <span v-if="submitting">Đang nộp...</span>
                <span v-else>Nộp bài</span>
              </button>
            </div>
          </div>

          <!-- Progress bar -->
          <div class="mt-2 h-1 bg-gray-200 rounded-full overflow-hidden">
            <div
              class="h-full bg-blue-600 transition-all duration-300"
              :style="{ width: `${progressPercent}%` }"
            ></div>
          </div>
        </div>
      </header>

      <!-- Main content -->
      <main class="flex-1 overflow-hidden flex">
        <!-- Sections sidebar (for IELTS/Cambridge) -->
        <aside v-if="sections.length > 1" class="w-64 bg-white border-r overflow-y-auto">
          <nav class="p-4 space-y-2">
            <button
              v-for="(section, index) in sections"
              :key="section.id"
              @click="currentSectionIndex = index"
              class="w-full text-left px-3 py-2 rounded-lg transition-colors"
              :class="currentSectionIndex === index
                ? 'bg-blue-100 text-blue-700'
                : 'hover:bg-gray-100 text-gray-700'"
            >
              <div class="font-medium">{{ section.title }}</div>
              <div class="text-xs text-gray-500">{{ getSectionProgress(section) }}</div>
            </button>
          </nav>
        </aside>

        <!-- Question area -->
        <div class="flex-1 overflow-y-auto p-6">
          <div class="max-w-4xl mx-auto">
            <!-- Section header -->
            <div v-if="currentSection" class="mb-6">
              <h2 class="text-xl font-semibold text-gray-800">{{ currentSection.title }}</h2>
              <p v-if="currentSection.instructions" class="mt-2 text-gray-600">{{ currentSection.instructions }}</p>

              <!-- Audio player for Listening -->
              <AudioPlayer
                v-if="currentSection.audio_track"
                :src="currentSection.audio_track.file_url"
                :transcript="currentSection.audio_track.transcript"
                class="mt-4"
              />

              <!-- Reading passage -->
              <PassageViewer
                v-if="currentSection.passage"
                :passage="currentSection.passage"
                class="mt-4"
              />
            </div>

            <!-- Questions -->
            <div class="space-y-8">
              <div
                v-for="(q, index) in currentQuestions"
                :key="q.question_id"
                :id="`question-${q.question_id}`"
                class="bg-white rounded-lg shadow p-6"
              >
                <QuestionRenderer
                  :question="q.question"
                  :questionNumber="getQuestionNumber(index)"
                  v-model="answers[q.question_id]"
                  :disabled="submitting"
                  @answer="handleAnswer"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Question navigator -->
        <aside class="w-64 bg-white border-l overflow-y-auto">
          <div class="p-4">
            <h3 class="font-medium text-gray-800 mb-3">Danh sách câu hỏi</h3>
            <div class="grid grid-cols-5 gap-2">
              <button
                v-for="(q, index) in allQuestions"
                :key="q.question_id"
                @click="scrollToQuestion(q.question_id)"
                class="w-8 h-8 rounded text-sm font-medium transition-colors"
                :class="getQuestionStatusClass(q.question_id)"
              >
                {{ index + 1 }}
              </button>
            </div>

            <!-- Legend -->
            <div class="mt-4 space-y-1 text-xs text-gray-500">
              <div class="flex items-center">
                <span class="w-4 h-4 bg-green-500 rounded mr-2"></span>
                Đã trả lời
              </div>
              <div class="flex items-center">
                <span class="w-4 h-4 bg-gray-200 rounded mr-2"></span>
                Chưa trả lời
              </div>
              <div class="flex items-center">
                <span class="w-4 h-4 bg-blue-500 rounded mr-2"></span>
                Đang xem
              </div>
            </div>
          </div>
        </aside>
      </main>
    </div>

    <!-- Submit confirmation modal -->
    <div v-if="showSubmitModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Xác nhận nộp bài</h3>

        <div v-if="unansweredCount > 0" class="mb-4 p-3 bg-yellow-50 rounded-lg">
          <p class="text-yellow-800">
            ⚠️ Bạn còn <strong>{{ unansweredCount }}</strong> câu chưa trả lời.
          </p>
        </div>

        <p class="text-gray-600 mb-6">
          Bạn có chắc chắn muốn nộp bài? Sau khi nộp, bạn không thể chỉnh sửa câu trả lời.
        </p>

        <div class="flex justify-end space-x-3">
          <button
            @click="showSubmitModal = false"
            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg"
          >
            Quay lại
          </button>
          <button
            @click="submitTest"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
            :disabled="submitting"
          >
            {{ submitting ? 'Đang nộp...' : 'Nộp bài' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useSubmissionStore } from '@/stores/examination'
import { useAntiCheat } from '@/composables/useAntiCheat'
import QuestionRenderer from '../questions/QuestionRenderer.vue'
import AudioPlayer from '../common/AudioPlayer.vue'
import PassageViewer from '../common/PassageViewer.vue'
import Swal from 'sweetalert2'

const props = defineProps({
  assignmentId: {
    type: [Number, String],
    required: true
  }
})

const router = useRouter()
const store = useSubmissionStore()

const loading = ref(true)
const submitting = ref(false)
const showSubmitModal = ref(false)
const currentSectionIndex = ref(0)
const currentQuestionId = ref(null)

// Store references
const submission = computed(() => store.currentSubmission)
const answers = computed(() => store.answers)
const remainingTime = computed(() => store.remainingTime)
const formattedTime = computed(() => store.formattedRemainingTime)
const answeredCount = computed(() => store.answeredCount)

// Computed
const sections = computed(() => submission.value?.sections || [])
const allQuestions = computed(() => submission.value?.questions || [])
const totalQuestions = computed(() => allQuestions.value.length)

const currentSection = computed(() => {
  if (sections.value.length === 0) return null
  return sections.value[currentSectionIndex.value]
})

const currentQuestions = computed(() => {
  if (!currentSection.value) return allQuestions.value

  return allQuestions.value.filter(q => q.section_id === currentSection.value.id)
})

const progressPercent = computed(() => {
  if (totalQuestions.value === 0) return 0
  return (answeredCount.value / totalQuestions.value) * 100
})

const unansweredCount = computed(() => totalQuestions.value - answeredCount.value)

const timerClass = computed(() => {
  if (!remainingTime.value) return 'bg-gray-100 text-gray-800'
  if (remainingTime.value <= 60) return 'bg-red-100 text-red-700 animate-pulse'
  if (remainingTime.value <= 300) return 'bg-yellow-100 text-yellow-700'
  return 'bg-gray-100 text-gray-800'
})

// Methods
async function initTest() {
  loading.value = true
  try {
    await store.startTest(props.assignmentId)

    // Handle prevent copy/tab switch
    if (submission.value?.test?.prevent_copy) {
      document.addEventListener('copy', preventCopy)
      document.addEventListener('contextmenu', preventContextMenu)
    }

    if (submission.value?.test?.prevent_tab_switch) {
      document.addEventListener('visibilitychange', handleVisibilityChange)
    }
  } catch (error) {
    console.error('Error starting test:', error)
    // Handle error - maybe show message and redirect
  } finally {
    loading.value = false
  }
}

function handleAnswer(data) {
  if (data.text_answer !== undefined) {
    store.saveTextAnswer(data.question_id, data.text_answer)
  } else {
    store.saveAnswer(data.question_id, data.answer)
  }
}

function confirmSubmit() {
  showSubmitModal.value = true
}

async function submitTest() {
  submitting.value = true
  try {
    const result = await store.submitTest()

    // Navigate to result page
    router.push({
      name: 'examination.result',
      params: { submissionId: submission.value.submission_id }
    })
  } catch (error) {
    console.error('Error submitting test:', error)
    Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: 'Có lỗi xảy ra khi nộp bài. Vui lòng thử lại.',
    })
  } finally {
    submitting.value = false
    showSubmitModal.value = false
  }
}

function scrollToQuestion(questionId) {
  currentQuestionId.value = questionId
  const element = document.getElementById(`question-${questionId}`)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }
}

function getQuestionNumber(index) {
  if (!currentSection.value) return index + 1

  // Calculate based on previous sections
  let count = 0
  for (let i = 0; i < currentSectionIndex.value; i++) {
    count += allQuestions.value.filter(q => q.section_id === sections.value[i].id).length
  }
  return count + index + 1
}

function getSectionProgress(section) {
  const sectionQuestions = allQuestions.value.filter(q => q.section_id === section.id)
  const answered = sectionQuestions.filter(q => answers.value[q.question_id]).length
  return `${answered}/${sectionQuestions.length} câu`
}

function getQuestionStatusClass(questionId) {
  if (currentQuestionId.value === questionId) return 'bg-blue-500 text-white'
  if (answers.value[questionId]) return 'bg-green-500 text-white'
  return 'bg-gray-200 text-gray-700 hover:bg-gray-300'
}

// Anti-cheat system
const antiCheat = useAntiCheat({
  onSubmit: submitTest,
  maxViolations: 3,
  enableFullscreen: true,
  enableCopyPaste: true,
  enableTabSwitch: true,
  logEndpoint: submission.value ? `/api/examination/submissions/${submission.value.id}/activity` : null,
})

// Lifecycle
onMounted(async () => {
  await initTest()
  
  // Initialize anti-cheat after test is loaded
  // Wait for user confirmation before starting timer
  if (submission.value) {
    const confirmed = await antiCheat.initialize()
    if (!confirmed) {
      // User cancelled - go back
      router.back()
    }
  }
})

onUnmounted(() => {
  store.reset()
  antiCheat.cleanup()
})

// Auto-submit when time runs out
watch(remainingTime, (newVal) => {
  if (newVal === 0 && submission.value) {
    submitTest()
  }
})
</script>

<style scoped>
.test-player {
  user-select: none;
}
</style>
