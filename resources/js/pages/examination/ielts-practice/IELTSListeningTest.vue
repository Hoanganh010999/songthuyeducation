<template>
  <div class="ielts-listening-test min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b sticky top-0 z-50 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
        <!-- Logo -->
        <div class="flex items-center gap-3">
          <div class="bg-gradient-to-br from-purple-500 to-pink-500 p-2 rounded-lg">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
            </svg>
          </div>
          <div>
            <h1 class="font-semibold text-gray-800">{{ testData.title }}</h1>
          </div>
        </div>

        <!-- Timer & Controls -->
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2 text-cyan-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-semibold">{{ formattedTime }} minutes remaining</span>
          </div>

          <button @click="toggleFullscreen" class="p-2 hover:bg-gray-100 rounded-lg">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
            </svg>
          </button>

          <button
            @click="showNotepad = !showNotepad"
            class="flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            <span>{{ showNotepad ? 'Ẩn' : 'Hiện' }} Notepad</span>
          </button>

          <button
            @click="submitTest"
            class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium py-2 px-6 rounded-lg transition-colors"
          >
            Submit ▶
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto p-6">
      <!-- Instructions -->
      <div class="bg-purple-50 border-l-4 border-purple-500 p-4 mb-6 rounded-r-lg">
        <p class="text-sm text-gray-700">
          <strong>IELTS Listening Test</strong> - You will have time to read the instructions and questions, and you will have a chance to check your work. All the recordings will be played <strong>ONCE only</strong>.
        </p>
      </div>

      <!-- Audio Player -->
      <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
        <div class="p-6">
          <div class="bg-gradient-to-r from-cyan-50 to-blue-50 rounded-xl p-6 border-2 border-cyan-200">
            <div class="flex items-center gap-4 mb-4">
              <div class="w-12 h-12 bg-cyan-600 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                </svg>
              </div>
              <div class="flex-1">
                <h3 class="font-semibold text-gray-800">{{ currentPart.title }} - Audio Recording</h3>
                <p class="text-sm text-gray-600">{{ audioPlaying ? 'Playing...' : 'Click Play to begin' }}</p>
              </div>
            </div>

            <!-- Audio Element -->
            <audio 
              ref="audioPlayer"
              :src="currentPartAudio"
              @ended="onAudioEnded"
              @play="audioPlaying = true"
              @pause="audioPlaying = false"
              class="w-full"
              controls
              controlsList="nodownload noseek"
            ></audio>

            <div class="flex items-center justify-between mt-3">
              <p class="text-xs text-gray-500">
                ⚠️ Audio will play ONCE only. You cannot rewind.
              </p>
              <div class="text-sm font-medium text-cyan-600">
                Part {{ currentPartIndex + 1 }} of {{ parts.length }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Layout: Questions + Notepad (optional) -->
      <div class="grid gap-6" :class="showNotepad ? 'grid-cols-1 lg:grid-cols-2' : 'grid-cols-1'">
        <!-- Questions -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden" :class="showNotepad ? '' : 'lg:col-span-1'">
          <!-- Part Navigation -->
          <div class="p-6 border-b border-gray-200 sticky top-0 bg-white z-10">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4 text-sm text-gray-700">
              <strong>Note:</strong> You can switch between parts to view questions, but the audio will continue playing automatically.
            </div>
            <div class="flex gap-2 mb-4">
              <button
                v-for="(part, idx) in parts"
                :key="idx"
                @click="switchPart(idx)"
                class="px-4 py-2 rounded-lg font-medium transition-all shadow-sm flex-1 relative"
                :class="currentPartIndex === idx 
                  ? 'bg-cyan-600 text-white transform scale-105' 
                  : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
              >
                <span>Part {{ idx + 1 }}</span>
                <span v-if="audioPlaying && currentPartIndex === idx" class="ml-2 inline-flex items-center">
                  <span class="animate-pulse">🎵</span>
                </span>
              </button>
            </div>

            <div class="flex items-center justify-between mb-2">
              <h3 class="font-semibold text-gray-800">{{ currentPart.title }}</h3>
              <span class="text-sm text-gray-500">
                Part {{ currentPartIndex + 1 }}: {{ currentPartAnsweredCount }} of {{ currentPartQuestions.length }} answered
              </span>
            </div>

            <!-- Question Numbers Navigation (Sticky) -->
            <div class="flex flex-wrap gap-1">
              <button
                v-for="num in currentPartQuestionNumbers"
                :key="num"
                @click="scrollToQuestion(num)"
                class="w-8 h-8 rounded-lg text-sm font-medium transition-colors"
                :class="answers[num] 
                  ? 'bg-cyan-600 text-white' 
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
              >
                {{ num }}
              </button>
            </div>
          </div>

          <!-- Questions List with separate scroll -->
          <div class="questions-scroll overflow-y-auto p-6" style="max-height: 60vh;">
            <div 
              ref="questionsTextRef"
              class="space-y-6 select-text relative"
              @mouseup="handleTextSelection"
            >
              <!-- Current Part Section -->
              <div class="question-section">
                <div v-if="currentPart.instruction" class="text-sm text-gray-600 mb-4 italic prose prose-sm max-w-none" v-html="currentPart.instruction"></div>

                <!-- Check if current part has labeling questions -->
                <template v-if="currentPartQuestions.some(q => q.type === 'labeling')">
                  <!-- Find first labeling question to get diagram and features -->
                  <template v-for="(question, qIdx) in currentPartQuestions" :key="'labeling-group-' + qIdx">
                    <div v-if="question.type === 'labeling' && shouldShowLabelingImage(question, qIdx)" class="mb-6">
                      <!-- Labeling Group: Image Left, Questions+Answers Right -->
                      <div class="grid grid-cols-2 gap-6">
                        <!-- Left: Diagram Image (sticky) -->
                        <div class="sticky top-4">
                          <div class="border-2 border-purple-300 rounded-lg p-4 bg-white shadow-lg">
                            <img :src="question.diagramImage" alt="Diagram" class="w-full h-auto rounded" />
                            <p v-if="question.diagramDescription" class="text-sm text-gray-600 italic mt-3">{{ question.diagramDescription }}</p>
                          </div>
                          
                          <!-- Features Reference - Only show if features have descriptions/text -->
                          <div v-if="question.features && question.features.some(f => f.description || f.text)" class="mt-4 bg-purple-50 rounded-lg border-2 border-purple-300 p-4">
                            <p class="text-sm font-semibold text-purple-800 mb-3">Available Options:</p>
                            <div class="space-y-2 text-sm">
                              <div v-for="feature in question.features.filter(f => f.description || f.text)" :key="feature.label" class="flex items-start gap-2 p-2 bg-white rounded border border-purple-200">
                                <span class="font-bold text-purple-700 bg-purple-100 px-2 py-1 rounded min-w-[32px] text-center">{{ feature.label }}</span>
                                <span class="text-gray-700">{{ feature.description || feature.text }}</span>
                              </div>
                            </div>
                          </div>
                        </div>
                        
                        <!-- Right: Questions and Answers -->
                        <div class="space-y-4">
                          <template v-for="(q, idx) in currentPartQuestions.filter(q => q.type === 'labeling')" :key="'labeling-q-' + idx">
                            <div class="bg-white border-2 border-gray-200 rounded-lg p-4 hover:border-purple-300 transition-colors">
                              <div class="mb-3">
                                <span class="font-semibold text-gray-700 text-lg">{{ q.number }}.</span>
                                <span class="text-gray-700 ml-2">{{ q.text }}</span>
                              </div>
                              <select
                                v-model="answers[q.number]"
                                class="w-full px-4 py-3 text-lg border-2 border-purple-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 bg-purple-50"
                              >
                                <option value="">-- Select --</option>
                                <option
                                  v-for="feature in q.features"
                                  :key="feature.label"
                                  :value="feature.label"
                                >
                                  {{ feature.label }}
                                </option>
                              </select>
                            </div>
                          </template>
                        </div>
                      </div>
                    </div>
                  </template>
                </template>

                <!-- Non-Labeling Questions -->
                <div class="space-y-4">
                  <div 
                    v-for="(question, qIdx) in currentPartQuestions.filter(q => q.type !== 'labeling')"
                    :key="qIdx"
                    :id="`question-${question.number}`"
                    class="question-item"
                  >
                    <!-- Two column layout: Question | Answer -->
                    <div class="grid grid-cols-12 gap-4 items-start">
                      <!-- Question Number -->
                      <div class="col-span-1">
                        <span class="font-semibold text-gray-700">{{ question.number }}.</span>
                      </div>
                      
                      <!-- Question Content (Left Column) -->
                      <div class="col-span-7">
                        <!-- Matching: Select from features list -->
                        <div v-if="question.type === 'matching'">
                          <!-- Features Reference (shown once per group) -->
                          <div v-if="question.features && question.features.length > 0 && shouldShowMatchingFeatures(question, qIdx)" class="mb-4 p-3 bg-green-50 rounded-lg border border-green-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                              <div v-for="feature in question.features" :key="feature.label" class="flex items-start gap-2">
                                <span class="font-bold text-green-700 bg-white px-2 py-0.5 rounded border border-green-300">{{ feature.label }}</span>
                                <span class="text-gray-600">{{ feature.text || feature.description }}</span>
                              </div>
                            </div>
                          </div>
                          
                          <!-- Matching Question Text -->
                          <p class="text-gray-700">{{ question.text }}</p>
                        </div>

                        <!-- Multiple Choice -->
                        <div v-else-if="question.type === 'mcq' || question.type === 'multiple_choice'">
                          <p class="mb-3 text-gray-700 font-medium">{{ question.text }}</p>
                          <div class="space-y-2">
                            <div
                              v-for="option in question.options"
                              :key="option.value || option.label"
                              class="flex items-start gap-2 text-gray-700"
                            >
                              <span class="font-semibold">{{ option.value || option.label }}.</span>
                              <span>{{ option.text || option.content }}</span>
                            </div>
                          </div>
                        </div>

                        <!-- Short Answer / Fill in the blank -->
                        <div v-else>
                          <p class="text-gray-700">{{ question.text }}</p>
                        </div>
                      </div>
                      
                      <!-- Answer Column (Right) -->
                      <div class="col-span-4">
                        <!-- Matching Answer -->
                        <select
                          v-if="question.type === 'matching'"
                          v-model="answers[question.number]"
                          class="w-full px-3 py-2 border border-green-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-green-50"
                        >
                          <option value="">--</option>
                          <option
                            v-for="feature in question.features"
                            :key="feature.label"
                            :value="feature.label"
                          >
                            {{ feature.label }}
                          </option>
                        </select>
                        
                        <!-- Multiple Choice Answer -->
                        <div v-else-if="question.type === 'mcq' || question.type === 'multiple_choice'" class="space-y-2">
                          <label
                            v-for="option in question.options"
                            :key="option.value || option.label"
                            class="flex items-center gap-2 p-2 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                            :class="answers[question.number] === (option.value || option.label) ? 'border-cyan-500 bg-cyan-50' : 'border-gray-200'"
                          >
                            <input
                              v-model="answers[question.number]"
                              type="radio"
                              :name="`q${question.number}`"
                              :value="option.value || option.label"
                              class="text-cyan-600"
                            />
                            <span class="font-semibold">{{ option.value || option.label }}</span>
                          </label>
                        </div>
                        
                        <!-- Short Answer -->
                        <input
                          v-else
                          v-model="answers[question.number]"
                          type="text"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500"
                          :placeholder="`Answer ${question.number}`"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Navigation (outside scroll area) -->
          <div class="p-6 border-t border-gray-200 flex justify-center gap-4">
            <button
              v-if="currentPartIndex > 0"
              @click="currentPartIndex--"
              class="p-3 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors"
            >
              <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <button
              v-if="currentPartIndex < parts.length - 1"
              @click="currentPartIndex++"
              class="p-3 bg-gray-100 hover:bg-gray-200 rounded-full transition-colors"
            >
              <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Right: Notes Sidebar (optional) -->
        <div v-if="showNotepad" class="bg-white rounded-xl shadow-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800">My Notes</h3>
            <button
              @click="showNotepad = false"
              class="text-gray-400 hover:text-gray-600"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Search Notes -->
          <div class="mb-4">
            <input
              type="text"
              v-model="noteSearch"
              placeholder="Search notes..."
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <!-- Notes List -->
          <div class="space-y-3 max-h-[600px] overflow-y-auto">
            <div
              v-for="(note, idx) in filteredNotes"
              :key="idx"
              class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50 cursor-pointer"
              @click="scrollToNote(note)"
            >
              <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium text-blue-600">Note {{ idx + 1 }}</span>
                <button
                  @click.stop="deleteNote(idx)"
                  class="text-gray-400 hover:text-red-500"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
              <p class="text-xs text-gray-600 mb-2 italic">"{{ note.selectedText.substring(0, 50) }}..."</p>
              <p class="text-sm text-gray-800">{{ note.content }}</p>
            </div>

            <div v-if="filteredNotes.length === 0" class="text-center py-8 text-gray-400">
              <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <p class="text-sm">Chưa có note nào</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Selection Popup (appears when text is selected) -->
    <div
      v-if="showSelectionPopup"
      :style="{ top: `${popupPosition.y}px`, left: `${popupPosition.x}px` }"
      class="fixed z-[100] bg-white rounded-lg shadow-xl border border-gray-200 p-2 flex gap-2 selection-popup"
    >
      <button
        @click="addNote"
        class="px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white rounded text-sm font-medium transition-colors flex items-center gap-1"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
        Note
      </button>
      <button
        @click="highlightSelected"
        class="px-3 py-1.5 bg-yellow-400 hover:bg-yellow-500 text-yellow-900 rounded text-sm font-medium transition-colors flex items-center gap-1"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
        </svg>
        Highlight
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'

// Props for Full Test mode
const props = defineProps({
  testId: {
    type: [String, Number],
    default: null
  },
  autoSubmit: {
    type: Boolean,
    default: false
  }
})

const route = useRoute()
const router = useRouter()

// Use prop testId if available, otherwise get from route
const activeTestId = computed(() => {
  const id = props.testId || route.params.testId
  console.log('🔍 Active testId:', id, { prop: props.testId, route: route.params.testId })
  return id
})

const testData = ref({
  title: 'IELTS Listening Practice Test',
  timeLimit: 30
})
const answers = ref({})
const timeRemaining = ref(30 * 60) // 30 minutes in seconds
const audioPlayer = ref(null)
const audioUrl = ref('')
const transcript = ref('')
const showTranscript = ref(false)
const showNotepad = ref(false)
const questionsTextRef = ref(null)
const isSubmitting = ref(false) // Prevent double submit
const currentPartIndex = ref(0)
const audioPlaying = ref(false)
const partAudioUrls = ref([])  // Array of audio URLs for each part

// Notes & Highlights
const notes = ref([])
const noteSearch = ref('')
const selectedText = ref('')
const selectionRange = ref(null)
const showSelectionPopup = ref(false)
const popupPosition = ref({ x: 0, y: 0 })

// Parts data
const parts = ref([
  {
    title: 'Part 1: Questions 1-10',
    instruction: 'Complete the notes below. Write NO MORE THAN TWO WORDS AND/OR A NUMBER for each answer.',
    questions: []
  }
])

const currentPart = computed(() => parts.value[currentPartIndex.value])
const currentPartQuestions = computed(() => currentPart.value.questions)
const currentPartQuestionNumbers = computed(() => {
  return currentPartQuestions.value.map(q => q.number)
})
const currentPartAnsweredCount = computed(() => {
  return currentPartQuestionNumbers.value.filter(num => answers.value[num]).length
})
const currentPartAudio = computed(() => {
  // If we have separate audio for each part, use it
  if (partAudioUrls.value.length > currentPartIndex.value) {
    return partAudioUrls.value[currentPartIndex.value]
  }
  // Otherwise use the main audio (all parts in one)
  return audioUrl.value
})

const questionNumbers = computed(() => {
  const numbers = []
  parts.value.forEach(part => {
    part.questions.forEach(q => {
      numbers.push(q.number)
    })
  })
  return numbers
})

const totalQuestions = computed(() => questionNumbers.value.length)

const answeredCount = computed(() => {
  return questionNumbers.value.filter(num => answers.value[num]).length
})

const filteredNotes = computed(() => {
  if (!noteSearch.value) return notes.value
  return notes.value.filter(note => 
    note.content.toLowerCase().includes(noteSearch.value.toLowerCase()) ||
    note.selectedText.toLowerCase().includes(noteSearch.value.toLowerCase())
  )
})

const formattedTime = computed(() => {
  const minutes = Math.floor(timeRemaining.value / 60)
  return minutes
})

onMounted(async () => {
  await loadTestData()
  startTimer()
})

let timerInterval = null

function startTimer() {
  timerInterval = setInterval(() => {
    if (timeRemaining.value > 0) {
      timeRemaining.value--
    } else {
      submitTest()
    }
  }, 1000)
}

onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval)
  }
})

async function loadTestData() {
  try {
    const response = await axios.get(`/api/examination/tests/${activeTestId.value}`)
    testData.value = response.data.data
    
    if (testData.value.settings) {
      const settings = typeof testData.value.settings === 'string' 
        ? JSON.parse(testData.value.settings) 
        : testData.value.settings
      
      // Support both new format (audio.url) and old format (audio_url)
      if (settings.audio && typeof settings.audio === 'object' && settings.audio.url) {
        audioUrl.value = settings.audio.url
      } else {
      audioUrl.value = settings.audio_url || ''
      }
      transcript.value = settings.transcript || ''
      
      // Load part-specific audio URLs if available
      if (settings.part_audios && Array.isArray(settings.part_audios)) {
        partAudioUrls.value = settings.part_audios
      } else if (settings.listening?.parts || settings.parts) {
        // Read from new namespaced structure with backward compatibility fallback
        const partsData = settings.listening?.parts || settings.parts
        partAudioUrls.value = partsData.map(p => {
          // Support both new format (audio.url) and old format (audio_url)
          if (p.audio && typeof p.audio === 'object' && p.audio.url) {
            return p.audio.url
          }
          return p.audio_url || null
        }).filter(Boolean)
      }
      
      // Check for new format (questionGroups in parts) or legacy format (flat questions array)
      const partsData = settings.listening?.parts || settings.parts || []
      
      if (partsData.length > 0 && partsData.some(p => p.questionGroups && p.questionGroups.length > 0)) {
        // NEW FORMAT: Load from questionGroups in each part
        console.log('📦 Loading from questionGroups format')
        
        parts.value = partsData.map((partData, idx) => {
          const allQuestions = []
          
          // Extract questions from questionGroups
          if (partData.questionGroups && Array.isArray(partData.questionGroups)) {
            partData.questionGroups.forEach(group => {
              if (group.questions && Array.isArray(group.questions)) {
                group.questions.forEach(q => {
                  allQuestions.push({
                    number: q.number,
                    text: q.content || q.question || q.statement || q.sentence || q.note,
                    type: group.type || 'short_answer',
                    options: q.options || [],
                    wordLimit: group.wordLimit,
                    instruction: group.instruction,
                    features: group.features, // For matching/labeling type
                    diagramImage: group.diagramImage, // For labeling type
                    diagramDescription: group.diagramDescription // For labeling type
                  })
                })
              }
            })
          }
          
          return {
            title: partData.title || `Part ${idx + 1}`,
            instruction: partData.questionGroups?.[0]?.instruction || 'Answer the questions.',
            questions: allQuestions
          }
        }).filter(part => part.questions.length > 0)
        
        console.log('🎧 Loaded from questionGroups:', parts.value.length, 'parts')
        parts.value.forEach((p, idx) => {
          console.log(`  Part ${idx + 1}: ${p.questions.length} questions`)
        })
        
      } else if (settings.questions && settings.questions.length > 0) {
        // LEGACY FORMAT: Load from flat questions array
        console.log('📦 Loading from legacy questions format')
        
        const normalizeType = (dbType) => {
          const typeMap = {
            'multiple_choice': 'mcq',
            'mcq': 'mcq'
          }
          return typeMap[dbType] || dbType || 'short_answer'
        }

        const allQuestions = settings.questions.map((q, idx) => ({
          number: idx + 1,
          text: q.content || q.question || q.statement,
          type: normalizeType(q.type),
          options: q.options || []
        }))

        // Split into 4 parts
        parts.value = [
          {
            title: 'Part 1: Questions 1-10',
            instruction: partsData[0]?.instruction || 'Complete the notes below. Write NO MORE THAN TWO WORDS AND/OR A NUMBER for each answer.',
            questions: allQuestions.slice(0, 10)
          },
          {
            title: 'Part 2: Questions 11-20',
            instruction: partsData[1]?.instruction || 'Choose the correct letter, A, B or C.',
            questions: allQuestions.slice(10, 20)
          },
          {
            title: 'Part 3: Questions 21-30',
            instruction: partsData[2]?.instruction || 'Choose the correct letter, A, B or C.',
            questions: allQuestions.slice(20, 30)
          },
          {
            title: 'Part 4: Questions 31-40',
            instruction: partsData[3]?.instruction || 'Complete the sentences below. Write NO MORE THAN TWO WORDS for each answer.',
            questions: allQuestions.slice(30, 40)
          }
        ].filter(part => part.questions.length > 0)

        console.log('🎧 Loaded Listening parts (legacy):', parts.value.length)
        parts.value.forEach((p, idx) => {
          console.log(`  Part ${idx + 1}: ${p.questions.length} questions`)
        })
      } else {
        console.error('⚠️ No questions found in Listening test - neither questionGroups nor legacy questions')
        console.log('Settings structure:', Object.keys(settings))
        console.log('Parts data:', partsData)
      }
    } else {
      console.warn('⚠️ No settings found in test data')
    }
  } catch (error) {
    console.error('❌ Failed to load test:', error)
  }
}

function scrollToQuestion(num) {
  const element = document.getElementById(`question-${num}`)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }
}

function handleTextSelection(event) {
  setTimeout(() => {
    const selection = window.getSelection()
    const text = selection.toString().trim()
    
    if (text.length > 0) {
      showSelectionPopup.value = true
      selectedText.value = text
      selectionRange.value = selection.getRangeAt(0).cloneRange()
      
      const rect = selection.getRangeAt(0).getBoundingClientRect()
      popupPosition.value = {
        x: rect.left + (rect.width / 2) - 100,
        y: rect.top + window.scrollY - 60
      }
    } else {
      showSelectionPopup.value = false
    }
  }, 10)
}

function highlightSelected() {
  if (!selectionRange.value) return
  
  try {
    const span = document.createElement('span')
    span.className = 'bg-yellow-200 cursor-pointer transition-colors hover:bg-yellow-300'
    span.setAttribute('data-highlight', 'true')
    span.setAttribute('title', 'Highlighted text')
    
    selectionRange.value.surroundContents(span)
    
    showSelectionPopup.value = false
    window.getSelection().removeAllRanges()
  } catch (error) {
    console.error('Failed to highlight:', error)
    Swal.fire({
      icon: 'error',
      title: 'Cannot highlight',
      text: 'Please select simpler text (not across multiple elements)',
      timer: 2000
    })
  }
}

async function addNote() {
  if (!selectedText.value || !selectionRange.value) return
  
  const { value: noteContent } = await Swal.fire({
    title: 'Add Note',
    input: 'textarea',
    inputLabel: 'Your note',
    inputPlaceholder: 'Type your note here...',
    inputAttributes: {
      'aria-label': 'Type your note here'
    },
    showCancelButton: true,
    confirmButtonText: 'Save',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#3b82f6'
  })
  
  if (!noteContent) {
    showSelectionPopup.value = false
    return
  }
  
  try {
    const span = document.createElement('span')
    span.className = 'border-b-2 border-blue-400 cursor-help transition-colors hover:bg-blue-50'
    span.setAttribute('data-note', 'true')
    span.setAttribute('data-note-id', notes.value.length)
    span.setAttribute('title', noteContent)
    
    selectionRange.value.surroundContents(span)
    
    notes.value.push({
      id: notes.value.length,
      selectedText: selectedText.value,
      content: noteContent,
      timestamp: new Date().toISOString(),
      element: span
    })
    
    span.addEventListener('click', () => {
      showNotepad.value = true
    })
    
    showSelectionPopup.value = false
    showNotepad.value = true
    window.getSelection().removeAllRanges()
  } catch (error) {
    console.error('Failed to add note:', error)
    Swal.fire({
      icon: 'error',
      title: 'Cannot add note',
      text: 'Please select simpler text (not across multiple elements)',
      timer: 2000
    })
  }
}

function scrollToNote(note) {
  if (note.element) {
    note.element.scrollIntoView({ behavior: 'smooth', block: 'center' })
    note.element.classList.add('bg-blue-100')
    setTimeout(() => {
      note.element.classList.remove('bg-blue-100')
    }, 1000)
  }
}

async function deleteNote(index) {
  const result = await Swal.fire({
    title: 'Delete this note?',
    text: 'This action cannot be undone',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel'
  })
  
  if (result.isConfirmed) {
    const note = notes.value[index]
    if (note.element) {
      const parent = note.element.parentNode
      if (parent) {
        parent.replaceChild(document.createTextNode(note.element.textContent), note.element)
        parent.normalize()
      }
    }
    notes.value.splice(index, 1)
  }
}

function switchPart(idx) {
  currentPartIndex.value = idx
  // Note: We don't change the audio - it keeps playing
  // User can view questions from any part while audio continues
}

async function onAudioEnded() {
  audioPlaying.value = false
  
  // Auto-advance to next part if available
  if (currentPartIndex.value < parts.value.length - 1) {
    const Swal = (await import('sweetalert2')).default
    
    await Swal.fire({
      icon: 'info',
      title: `${currentPart.value.title} completed!`,
      text: 'Moving to next part...',
      timer: 2000,
      showConfirmButton: false
    })
    
    currentPartIndex.value++
    // Auto-play next part audio
    setTimeout(() => {
      if (audioPlayer.value) {
        audioPlayer.value.play().catch(err => {
          console.log('Auto-play blocked, user needs to click play')
        })
      }
    }, 500)
  } else {
    // All parts completed
    showTranscript.value = true
    const Swal = (await import('sweetalert2')).default
    
    await Swal.fire({
      icon: 'success',
      title: 'Audio completed!',
      text: 'You can now review your answers before submitting.',
      confirmButtonColor: '#0891b2'
    })
  }
}

onMounted(() => {
  document.addEventListener('click', (e) => {
    if (showSelectionPopup.value && !e.target.closest('.selection-popup')) {
      const selection = window.getSelection()
      if (selection.toString().trim() === '') {
        showSelectionPopup.value = false
      }
    }
  })
  
  document.addEventListener('mousedown', (e) => {
    if (showSelectionPopup.value && !e.target.closest('.selection-popup')) {
      setTimeout(() => {
        const selection = window.getSelection()
        if (selection.toString().trim() === '') {
          showSelectionPopup.value = false
        }
      }, 50)
    }
  })
})

function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen()
  } else {
    document.exitFullscreen()
  }
}

async function submitTest() {
  // Prevent double submit
  if (isSubmitting.value) {
    console.log('⚠️ Already submitting, skipping...')
    return
  }
  
  // Clear timer immediately to prevent multiple calls
  if (timerInterval) {
    clearInterval(timerInterval)
    timerInterval = null
  }
  
  const result = await Swal.fire({
    title: 'Submit Test?',
    html: `
      <p>You have answered <strong>${answeredCount.value}/${totalQuestions.value}</strong> questions.</p>
      <p class="text-sm text-gray-600 mt-2">Are you sure you want to submit?</p>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#0891b2',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Submit',
    cancelButtonText: 'Continue'
  })
  
  if (!result.isConfirmed) {
    // Restart timer if user cancels
    startTimer()
    return
  }
  
  isSubmitting.value = true
  
  try {
    console.log('📤 Submitting Listening test...', {
      test_id: route.params.testId,
      answers_count: Object.keys(answers.value).length
    })
    
    const response = await axios.post(`/api/examination/submissions`, {
      test_id: route.params.testId,
      answers: answers.value,
      time_taken: (testData.value.timeLimit * 60) - timeRemaining.value
    })

    const submissionId = response.data.data.submission_id
    
    console.log('✅ Listening test submitted successfully:', submissionId)

    Swal.fire({
      icon: 'success',
      title: 'Submitted!',
      text: 'Your answers have been submitted successfully.',
      timer: 1500,
      showConfirmButton: false
    })

    setTimeout(() => {
      router.push({
        name: 'examination.ielts-practice.result',
        params: { submissionId: submissionId }
      })
    }, 1500)
  } catch (error) {
    console.error('❌ Failed to submit test:', error)
    isSubmitting.value = false // Reset flag on error
    Swal.fire({
      icon: 'error',
      title: 'Submission Failed',
      text: 'Please try again later',
      confirmButtonColor: '#0891b2'
    })
  }
}

// Helper function to determine if image should be shown for a labeling question
function shouldShowLabelingImage(question, qIdx) {
  const questions = currentPartQuestions.value
  
  // If it's the first question, show image
  if (qIdx === 0) return true
  
  // Check if previous question is different type or has different image
  const prevQuestion = questions[qIdx - 1]
  if (!prevQuestion) return true
  
  // Show image if:
  // 1. Previous question is not labeling type, OR
  // 2. Previous question has different diagramImage URL
  return prevQuestion.type !== 'labeling' || prevQuestion.diagramImage !== question.diagramImage
}

// Helper function to determine if features list should be shown for a matching question
function shouldShowMatchingFeatures(question, qIdx) {
  const questions = currentPartQuestions.value
  
  // If it's the first question, show features
  if (qIdx === 0) return true
  
  // Check if previous question is different type or has different features
  const prevQuestion = questions[qIdx - 1]
  if (!prevQuestion) return true
  
  // Show features if:
  // 1. Previous question is not matching type, OR
  // 2. Previous question has different features list
  return prevQuestion.type !== 'matching' || JSON.stringify(prevQuestion.features) !== JSON.stringify(question.features)
}
</script>

<style scoped>
.question-item {
  scroll-margin-top: 100px;
}
</style>
