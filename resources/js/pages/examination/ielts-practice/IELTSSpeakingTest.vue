<template>
  <div class="ielts-speaking-test min-h-screen bg-gradient-to-br from-rose-50 to-pink-50">
    <!-- Header -->
    <div class="bg-white border-b sticky top-0 z-50 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
        <!-- Logo -->
        <div class="flex items-center gap-3">
          <div class="bg-gradient-to-br from-rose-500 to-pink-500 p-2 rounded-lg">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
            </svg>
          </div>
          <div>
            <h1 class="font-semibold text-gray-800">{{ testData.title || 'IELTS Speaking Test' }}</h1>
            <p class="text-xs text-gray-500">Part {{ currentPart }} / 3</p>
          </div>
        </div>

        <!-- Timer & Controls -->
        <div class="flex items-center gap-4">
          <!-- Recording Indicator -->
          <div v-if="isRecording" class="flex items-center gap-3 bg-red-50 px-4 py-2 rounded-lg border border-red-200">
            <!-- Recording Status Dot -->
            <div
              class="w-3 h-3 rounded-full transition-all duration-300"
              :class="mediaRecorder && mediaRecorder.state === 'recording' ? 'bg-green-500 animate-pulse' : 'bg-gray-400'"
            ></div>

            <!-- Animated Mic Icon -->
            <svg
              class="text-red-500 transition-transform duration-75"
              :style="{ transform: `scale(${1 + audioLevel / 200})` }"
              fill="currentColor"
              viewBox="0 0 24 24"
              :class="audioLevel > 20 ? 'w-6 h-6' : 'w-5 h-5'"
            >
              <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
              <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
            </svg>
            <div class="flex flex-col">
              <span class="text-sm font-medium text-red-700">Recording {{ formatTime(recordingTime) }}</span>
              <div class="flex items-center gap-1">
                <div class="w-16 h-1.5 bg-red-200 rounded-full overflow-hidden">
                  <div
                    class="h-full bg-red-500 transition-all duration-75"
                    :style="{ width: `${audioLevel}%` }"
                  ></div>
                </div>
                <span class="text-xs text-red-600">{{ Math.round(audioLevel) }}%</span>
              </div>
            </div>
          </div>

          <!-- Timer -->
          <div class="flex items-center gap-2" :class="timeRemaining < 60 ? 'text-red-600' : 'text-rose-500'">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-semibold text-sm">{{ formatTime(timeRemaining) }}</span>
          </div>

          <!-- View Recordings Button -->
          <button
            v-if="testState === 'active' && recordings.length > 0"
            @click="showRecordingsModal = true"
            class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
            </svg>
            <span class="hidden sm:inline">Recordings ({{ recordings.length }})</span>
            <span class="sm:hidden">{{ recordings.length }}</span>
          </button>

          <button
            v-if="testState === 'active'"
            @click="finishTest"
            class="bg-rose-600 hover:bg-rose-700 text-white font-medium py-2 px-6 rounded-lg transition-colors"
            title="Submit test early if you cannot complete it"
          >
            Finish Test
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-5xl mx-auto p-6">
      <!-- Test State Indicator -->
      <div v-if="testState === 'waiting'" class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-r-lg mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-bold text-gray-800 mb-2">IELTS Speaking Test</h2>
            <p class="text-gray-700 mb-4">This test consists of 3 parts and takes 11-14 minutes.</p>
            <ul class="text-sm text-gray-600 space-y-1 ml-4">
              <li>• Part 1: Introduction & Interview (4-5 minutes)</li>
              <li>• Part 2: Individual Long Turn (3-4 minutes including preparation)</li>
              <li>• Part 3: Two-way Discussion (4-5 minutes)</li>
            </ul>
          </div>
          <button
            @click="startTest"
            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition-colors flex-shrink-0"
          >
            Start Test
          </button>
        </div>
      </div>

      <!-- Part Content -->
      <div v-if="testState !== 'waiting'" class="space-y-6">
        <!-- Part 1: Introduction & Interview -->
        <div v-if="currentPart === 1" class="bg-white rounded-xl shadow-lg p-8">
          <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Part 1: Introduction & Interview</h2>
            <p class="text-gray-600">The examiner will ask you general questions about yourself and familiar topics.</p>
          </div>

          <!-- Current Script Item (All Types) -->
          <div v-if="currentScriptItem" class="mb-6">
            <!-- Instruction / Transition -->
            <div v-if="currentScriptItem.type === 'instruction' || currentScriptItem.type === 'transition'"
                 class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-r-lg">
              <div class="flex items-center gap-3 mb-2">
                <svg class="w-5 h-5 text-blue-500" :class="{'animate-pulse': scriptState === 'speaking'}" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                <span class="text-sm font-medium text-blue-700">{{ scriptStateText }}</span>
              </div>
              <p class="text-lg text-gray-800">{{ currentScriptItem.text }}</p>
            </div>

            <!-- Question -->
            <div v-if="currentScriptItem.type === 'question'" class="bg-rose-50 border-2 border-rose-200 rounded-lg p-6">
              <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-rose-500 rounded-full flex items-center justify-center flex-shrink-0">
                  <span class="text-white font-bold">Q</span>
                </div>
                <div class="flex-1">
                  <p class="text-lg font-medium text-gray-800">{{ currentScriptItem.text }}</p>
                  <div class="mt-3 flex items-center gap-3">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                      <svg v-if="scriptState === 'speaking'" class="w-4 h-4 animate-pulse text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" />
                      </svg>
                      <span class="font-medium">{{ scriptStateText }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Recording Controls -->
          <div class="flex items-center justify-center gap-4">
            <button
              v-if="!isRecording && questionState === 'answered'"
              @click="nextQuestion"
              class="bg-rose-600 hover:bg-rose-700 text-white font-medium py-3 px-8 rounded-lg transition-colors"
            >
              Next Question →
            </button>
          </div>
        </div>

        <!-- Part 2: Cue Card -->
        <div v-if="currentPart === 2" class="bg-white rounded-xl shadow-lg p-8">
          <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Part 2: Individual Long Turn</h2>
            <p class="text-gray-600">You will have 1 minute to prepare and 2 minutes to speak.</p>
          </div>

          <!-- Preparation Phase -->
          <div v-if="part2State === 'preparation'" class="space-y-6">
            <div class="bg-yellow-50 border-2 border-yellow-300 rounded-lg p-6">
              <div class="flex items-center gap-3 mb-4">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-semibold text-yellow-800">Preparation Time: {{ formatTime(prepTime) }}</span>
              </div>
              <div class="prose max-w-none" v-html="cueCard"></div>
            </div>
            <div class="text-center text-gray-500">
              <p>Take notes and prepare your answer...</p>
            </div>
          </div>

          <!-- Speaking Phase -->
          <div v-if="part2State === 'speaking'" class="space-y-6">
            <div class="bg-rose-50 border-2 border-rose-200 rounded-lg p-6">
              <div class="flex items-center gap-3 mb-4">
                <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                <span class="font-semibold text-rose-800">Speaking Time: {{ formatTime(speakTime) }}</span>
              </div>
              <div class="prose max-w-none text-gray-600" v-html="cueCard"></div>
            </div>
            <div class="text-center">
              <p class="text-gray-600">Speak about the topic for 1-2 minutes...</p>
            </div>
          </div>
        </div>

        <!-- Part 3: Discussion -->
        <div v-if="currentPart === 3" class="bg-white rounded-xl shadow-lg p-8">
          <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Part 3: Two-way Discussion</h2>
            <p class="text-gray-600">The examiner will ask you more abstract questions related to Part 2 topic.</p>
          </div>

          <!-- Current Script Item (All Types) -->
          <div v-if="currentScriptItem" class="mb-6">
            <!-- Instruction / Transition -->
            <div v-if="currentScriptItem.type === 'instruction' || currentScriptItem.type === 'transition'"
                 class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-r-lg">
              <div class="flex items-center gap-3 mb-2">
                <svg class="w-5 h-5 text-blue-500" :class="{'animate-pulse': scriptState === 'speaking'}" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                <span class="text-sm font-medium text-blue-700">{{ scriptStateText }}</span>
              </div>
              <p class="text-lg text-gray-800">{{ currentScriptItem.text }}</p>
            </div>

            <!-- Question -->
            <div v-if="currentScriptItem.type === 'question'" class="bg-rose-50 border-2 border-rose-200 rounded-lg p-6">
              <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-rose-500 rounded-full flex items-center justify-center flex-shrink-0">
                  <span class="text-white font-bold">Q</span>
                </div>
                <div class="flex-1">
                  <p class="text-lg font-medium text-gray-800">{{ currentScriptItem.text }}</p>
                  <div class="mt-3 flex items-center gap-3">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                      <svg v-if="scriptState === 'speaking'" class="w-4 h-4 animate-pulse text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" />
                      </svg>
                      <span class="font-medium">{{ scriptStateText }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Recording Controls -->
          <div class="flex items-center justify-center gap-4">
            <button
              v-if="!isRecording && questionState === 'answered'"
              @click="nextQuestion"
              class="bg-rose-600 hover:bg-rose-700 text-white font-medium py-3 px-8 rounded-lg transition-colors"
            >
              Next Question →
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Audio Player (hidden) -->
    <audio ref="audioPlayer" @ended="onAudioEnded"></audio>

    <!-- Recordings Modal -->
    <div
      v-if="showRecordingsModal"
      class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
      @click.self="showRecordingsModal = false"
    >
      <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden flex flex-col">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-rose-500 to-pink-500 text-white px-6 py-4 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
            </svg>
            <h3 class="text-xl font-semibold">Your Recordings</h3>
          </div>
          <button
            @click="showRecordingsModal = false"
            class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Modal Body -->
        <div class="flex-1 overflow-y-auto p-6">
          <div v-if="recordings.length === 0" class="text-center py-12 text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
            </svg>
            <p class="text-lg">No recordings yet</p>
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="(recording, index) in recordings"
              :key="index"
              class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:border-rose-300 transition-colors"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="bg-rose-100 text-rose-700 text-xs font-medium px-2 py-1 rounded">
                      Part {{ recording.part }}
                    </span>
                    <span class="text-sm text-gray-600">Recording {{ index + 1 }}</span>
                  </div>
                  <p class="text-sm text-gray-500">Duration: {{ formatTime(recording.duration) }}</p>
                </div>

                <div class="flex items-center gap-2">
                  <!-- Play Button -->
                  <button
                    @click="playRecording(recording)"
                    class="flex items-center gap-2 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg transition-colors"
                  >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M8 5v14l11-7z"/>
                    </svg>
                    <span class="text-sm">Play</span>
                  </button>

                  <!-- Download Button -->
                  <button
                    @click="downloadRecording(recording, index)"
                    class="flex items-center gap-2 bg-rose-500 hover:bg-rose-600 text-white px-3 py-2 rounded-lg transition-colors"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span class="text-sm">Download</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="border-t px-6 py-4 bg-gray-50">
          <div class="flex justify-between items-center">
            <p class="text-sm text-gray-600">Total: {{ recordings.length }} recording(s)</p>
            <button
              @click="showRecordingsModal = false"
              class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium px-4 py-2 rounded-lg transition-colors"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'

const route = useRoute()
const router = useRouter()

// Props
const props = defineProps({
  testId: {
    type: [String, Number],
    default: null
  }
})

// Active test ID
const activeTestId = computed(() => {
  return props.testId || route.params.testId
})

// Practice test ID (from query params for full test)
const practiceTestId = computed(() => {
  return route.query.practiceTestId || null
})

// Test Data
const testData = ref({
  title: 'IELTS Speaking Practice Test',
  timeLimit: 14 * 60 // 14 minutes
})

// Test State
const testState = ref('waiting') // waiting, active, finished
const currentPart = ref(1) // 1, 2, 3
const part2State = ref('idle') // idle, preparation, speaking

// Script-based flow
const script = ref([]) // All script items
const currentScriptIndex = ref(0)
const scriptState = ref('idle') // idle, speaking, waitingResponse, completed

// Legacy data for display
const cueCard = ref('')
const part1Questions = ref([])
const part3Questions = ref([])

// Timers
const timeRemaining = ref(14 * 60) // Total test time
const prepTime = ref(60) // Part 2 preparation time
const speakTime = ref(120) // Part 2 speaking time
const recordingTime = ref(0)

// Recording
const isRecording = ref(false)
const mediaRecorder = ref(null)
const audioChunks = ref([])
const recordings = ref([])
const showRecordingsModal = ref(false)
const audioLevel = ref(0) // 0-100
const isSubmitting = ref(false) // Prevent double submit

// Audio
const audioPlayer = ref(null)
const speechSynthesis = window.speechSynthesis
const useAzureTTS = ref(false) // Flag to determine which TTS to use
let audioContext = null
let analyser = null
let animationFrame = null
let currentAudioElement = null // Track currently playing audio element
let currentRecordingAudio = null // Track recording playback audio element
let currentSpeechUtterance = null // Track current speech synthesis utterance
const audioCache = new Map() // Cache for preloaded audio

// Timers
let testTimer = null
let prepTimer = null
let speakTimer = null
let recordingTimer = null

// Computed
const currentScriptItem = computed(() => {
  return script.value[currentScriptIndex.value] || null
})

const scriptStateText = computed(() => {
  const states = {
    idle: 'Waiting...',
    speaking: '🔊 Examiner speaking...',
    waitingResponse: '🎤 Your turn to speak...',
    completed: 'Response recorded'
  }
  return states[scriptState.value] || ''
})

const canFinishTest = computed(() => {
  return currentScriptIndex.value >= script.value.length - 1 &&
         scriptState.value === 'completed'
})

// Legacy compatibility
const currentQuestion = computed(() => {
  if (currentScriptItem.value && currentScriptItem.value.type === 'question') {
    return { text: currentScriptItem.value.text }
  }
  return null
})

const questionStateText = computed(() => scriptStateText.value)

// Methods
async function loadTestData() {
  try {
    const response = await axios.get(`/api/examination/tests/${activeTestId.value}`)
    testData.value = response.data.data

    // Check if Azure TTS is configured
    try {
      const aiSettingsResponse = await axios.get('/api/examination/ai-settings', {
        params: { module: 'examination_grading' }
      })
      if (aiSettingsResponse.data.success && aiSettingsResponse.data.data) {
        // Check if azure provider exists in the settings
        const settings = aiSettingsResponse.data.data
        const hasAzure = settings.azure && settings.azure.has_api_key
        useAzureTTS.value = hasAzure
        console.log(`🔊 TTS: Using ${useAzureTTS.value ? 'Azure TTS' : 'Web Speech API'}`, settings)
      }
    } catch (err) {
      console.warn('⚠️ Could not check Azure TTS config, using Web Speech API', err)
      useAzureTTS.value = false
    }

    if (testData.value.settings) {
      const settings = typeof testData.value.settings === 'string'
        ? JSON.parse(testData.value.settings)
        : testData.value.settings

      // Load script array (primary method) - use new namespaced structure with fallback
      const scriptData = settings.speaking?.script || settings.script
      if (scriptData && Array.isArray(scriptData)) {
        script.value = scriptData
        console.log('✅ Script loaded:', script.value.length, 'items')
        console.log('   First script:', script.value[0]?.text?.substring(0, 50))
      } else {
        console.warn('⚠️ No script found in settings')
      }

      // Load parts data from new namespaced structure with backward compatibility fallback
      const partsData = settings.speaking?.parts || settings.parts
      console.log('🔍 DEBUG: partsData exists:', !!partsData)
      console.log('🔍 DEBUG: partsData length:', partsData?.length)
      console.log('🔍 DEBUG: partsData[1] exists:', !!partsData?.[1])
      if (partsData?.[1]) {
        console.log('🔍 DEBUG: partsData[1]:', JSON.stringify(partsData[1], null, 2))
      }

      // Load Part 2 cue card for display
      if (partsData && partsData[1]) {
        cueCard.value = partsData[1].cueCard || partsData[1].topic || ''
        prepTime.value = (partsData[1].prepTime || 1) * 60
        speakTime.value = (partsData[1].speakTime || 2) * 60
        console.log('🔍 DEBUG: cueCard loaded, length:', cueCard.value.length)
        console.log('🔍 DEBUG: cueCard content (first 200 chars):', cueCard.value.substring(0, 200))
        console.log('🔍 DEBUG: prepTime:', prepTime.value, 'speakTime:', speakTime.value)
      } else {
        console.warn('⚠️ Part 2 data not found in partsData')
      }

      // Legacy: Load questions for fallback
      if (partsData && partsData[0]) {
        part1Questions.value = (partsData[0].questions || []).map(q => ({
          ...q,
          text: q.text || q.content
        }))
      }
      if (partsData && partsData[2]) {
        part3Questions.value = (partsData[2].questions || []).map(q => ({
          ...q,
          text: q.text || q.content
        }))
      }

      console.log('✅ Speaking test data loaded', {
        script: script.value.length,
        part1Fallback: part1Questions.value.length,
        part3Fallback: part3Questions.value.length
      })
    }
  } catch (error) {
    console.error('❌ Failed to load test:', error)
    Swal.fire('Error', 'Failed to load test data', 'error')
  }
}

function formatTime(seconds) {
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

async function startTest() {
  testState.value = 'active'
  startTestTimer()

  // Start continuous recording for entire test
  await startContinuousRecording()

  await nextTick()

  // Start script-based flow
  if (script.value.length > 0) {
    currentScriptIndex.value = 0
    await playScriptItem()
  } else {
    console.error('❌ No script available')
    Swal.fire('Error', 'Test script not found', 'error')
  }
}

function startTestTimer() {
  testTimer = setInterval(() => {
    timeRemaining.value--
    if (timeRemaining.value <= 0) {
      autoFinishTest()
    }
  }, 1000)
}

// Main script playback function
async function playScriptItem() {
  // Stop if test is already finished
  if (testState.value === 'finished') {
    console.log('⏹️ Test finished, stopping playback')
    return
  }

  const item = currentScriptItem.value
  if (!item) {
    console.log('✅ Test completed - no more script items')
    return
  }

  // Update current part based on script
  if (item.partId) {
    currentPart.value = item.partId
  }

  console.log(`📜 Script [${currentScriptIndex.value}/${script.value.length}]:`, item.type, '-', item.text?.substring(0, 50))

  // Handle different script types
  switch (item.type) {
    case 'instruction':
      await playInstruction(item)
      break
    case 'question':
      await playQuestion(item)
      break
    case 'transition':
      await playTransition(item)
      break
    case 'timer':
      await handleTimer(item)
      break
    default:
      console.warn('⚠️ Unknown script type:', item.type)
      await moveToNextScript()
  }
}

async function playInstruction(item) {
  scriptState.value = 'speaking'
  await speakText(item.text)
  scriptState.value = 'idle'

  // Auto-advance to next script item
  await new Promise(resolve => setTimeout(resolve, 500))
  await moveToNextScript()
}

async function playTransition(item) {
  scriptState.value = 'speaking'
  await speakText(item.text)
  scriptState.value = 'idle'

  // Auto-advance to next script item
  await new Promise(resolve => setTimeout(resolve, 500))
  await moveToNextScript()
}

async function handleTimer(item) {
  if (item.isPreparation) {
    // Part 2 preparation time
    console.log('⏱️ Starting preparation timer:', item.duration, 'seconds')
    console.log('🔍 DEBUG: Setting part2State to preparation')
    console.log('🔍 DEBUG: cueCard value:', cueCard.value)
    console.log('🔍 DEBUG: cueCard length:', cueCard.value.length)

    // Set Part 2 state to show cue card
    part2State.value = 'preparation'
    prepTime.value = item.duration

    prepTimer = setInterval(() => {
      prepTime.value--
      if (prepTime.value <= 0) {
        clearInterval(prepTimer)
        // Move to speaking state
        part2State.value = 'speaking'
        // Reset speak time for display
        speakTime.value = 120 // 2 minutes
        moveToNextScript()
      }
    }, 1000)
  } else {
    // Other timers
    await new Promise(resolve => setTimeout(resolve, item.duration * 1000))
    await moveToNextScript()
  }
}

async function moveToNextScript() {
  // Stop if test is already finished
  if (testState.value === 'finished') {
    console.log('⏹️ Test finished, stopping script execution')
    return
  }

  currentScriptIndex.value++
  if (currentScriptIndex.value < script.value.length) {
    await playScriptItem()
  } else {
    console.log('✅ All script items completed')
    autoFinishTest()
  }
}

async function playQuestion(item) {
  console.log('❓ Question:', item.text)
  scriptState.value = 'speaking'

  // Speak the question
  await speakText(item.text)

  // Wait for student response
  if (item.waitForResponse) {
    scriptState.value = 'waitingResponse'
    const responseDuration = item.responseDuration || 30
    console.log('🎤 Waiting for response:', responseDuration, 'seconds')

    // Check if this is Part 2 long turn
    if (item.isLongTurn && currentPart.value === 2) {
      console.log('🔍 DEBUG: Part 2 long turn detected, setting part2State to speaking')
      part2State.value = 'speaking'
      speakTime.value = responseDuration

      // Countdown speaking time with visual feedback
      const speakTimer = setInterval(() => {
        speakTime.value--
        if (speakTime.value <= 0) {
          clearInterval(speakTimer)
        }
      }, 1000)

      // Wait for response duration
      await new Promise(resolve => setTimeout(resolve, responseDuration * 1000))
      clearInterval(speakTimer)
    } else {
      // Normal question - just wait
      await new Promise(resolve => setTimeout(resolve, responseDuration * 1000))
    }

    // Recording completed
    scriptState.value = 'completed'

    // Auto-advance to next script item
    await new Promise(resolve => setTimeout(resolve, 500))
    await moveToNextScript()
  } else {
    // No response needed, move to next
    scriptState.value = 'idle'
    await moveToNextScript()
  }
}

// Core TTS function
async function speakText(text) {
  // Pause recording while examiner is speaking
  pauseRecording()

  // Use Azure TTS if configured
  if (useAzureTTS.value) {
    await speakTextAzure(text)
  } else {
    // Fallback to Web Speech API
    await speakTextWebAPI(text)
  }

  // Resume recording after examiner finishes speaking
  resumeRecording()
}

// Preload audio for next 2-3 script items to reduce delay
async function preloadAudioForNextItems() {
  if (!useAzureTTS.value) return // Only preload for Azure TTS

  const itemsToPreload = 3
  for (let i = 1; i <= itemsToPreload; i++) {
    const nextIndex = currentScriptIndex.value + i
    if (nextIndex >= script.value.length) break

    const nextItem = script.value[nextIndex]
    if (!nextItem || !nextItem.text) continue

    // Skip if already cached
    if (audioCache.has(nextItem.text)) continue

    // Preload in background
    try {
      console.log(`📥 Preloading audio [${i}/${itemsToPreload}]:`, nextItem.text.substring(0, 50) + '...')
      const response = await axios.post('/api/examination/azure-tts', {
        text: nextItem.text,
        voice: 'en-GB-RyanNeural',
        rate: 1.075,
        pitch: 1.0
      })

      if (response.data.success) {
        const audioData = response.data.audio
        const audioBlob = base64ToBlob(audioData, 'audio/mpeg')
        audioCache.set(nextItem.text, audioBlob)
        console.log(`✅ Preloaded audio [${i}/${itemsToPreload}]`)
      }
    } catch (error) {
      console.warn('⚠️ Failed to preload audio:', error.message)
    }
  }
}

// Azure TTS implementation with caching
async function speakTextAzure(text) {
  try {
    console.log('🔊 Using Azure TTS')

    let audioBlob

    // Check cache first
    if (audioCache.has(text)) {
      console.log('⚡ Using cached audio')
      audioBlob = audioCache.get(text)
    } else {
      // Fetch from API
      const response = await axios.post('/api/examination/azure-tts', {
        text: text,
        voice: 'en-GB-RyanNeural', // British English male
        rate: 1.075, // Slightly faster (7.5% faster than normal)
        pitch: 1.0
      })

      if (!response.data.success) {
        console.error('❌ Azure TTS failed:', response.data.message)
        // Fallback to Web Speech API
        console.log('⚠️ Falling back to Web Speech API')
        useAzureTTS.value = false
        return await speakTextWebAPI(text)
      }

      // Decode base64 audio
      const audioData = response.data.audio
      audioBlob = base64ToBlob(audioData, 'audio/mpeg')

      // Cache for potential reuse
      audioCache.set(text, audioBlob)
    }

    const audioUrl = URL.createObjectURL(audioBlob)

    // Start preloading next items in background
    preloadAudioForNextItems().catch(err => console.warn('Preload error:', err))

    // Play audio and wait for completion
    return new Promise((resolve, reject) => {
      const audio = new Audio(audioUrl)
      currentAudioElement = audio // Store reference for stopping

      audio.onplay = () => {
        console.log('🔊 Azure speech started')
      }

      audio.onended = () => {
        console.log('✅ Azure speech ended')
        URL.revokeObjectURL(audioUrl)
        currentAudioElement = null
        resolve()
      }

      audio.onerror = (err) => {
        console.error('❌ Audio playback error:', err)
        URL.revokeObjectURL(audioUrl)
        currentAudioElement = null
        resolve() // Resolve anyway to continue flow
      }

      audio.play().catch(err => {
        console.error('❌ Audio play failed:', err)
        URL.revokeObjectURL(audioUrl)
        currentAudioElement = null
        resolve()
      })
    })

  } catch (error) {
    console.error('❌ Error in Azure TTS:', error)
    // Fallback to Web Speech API
    console.log('⚠️ Falling back to Web Speech API')
    useAzureTTS.value = false
    return await speakTextWebAPI(text)
  }
}

// Web Speech API implementation (fallback)
async function speakTextWebAPI(text) {
  return new Promise(async (resolve, reject) => {
    try {
      // Check if speech synthesis is available
      if (!window.speechSynthesis) {
        console.error('❌ Speech Synthesis not supported in this browser')
        await Swal.fire({
          title: 'TTS Not Supported',
          text: 'Your browser does not support text-to-speech. Please use Chrome, Edge, or Safari.',
          icon: 'error'
        })
        resolve()
        return
      }

      // Cancel any ongoing speech
      window.speechSynthesis.cancel()

      // Wait a bit for voices to load
      await new Promise(r => setTimeout(r, 100))

      // Use Web Speech API for TTS
      const utterance = new SpeechSynthesisUtterance(text)
      currentSpeechUtterance = utterance // Store reference for stopping
      utterance.lang = 'en-GB'
      utterance.rate = 0.9
      utterance.pitch = 1.0
      utterance.volume = 1.0

      // Try to use a British English voice if available
      const voices = window.speechSynthesis.getVoices()
      const britishVoice = voices.find(voice => voice.lang === 'en-GB') || voices.find(voice => voice.lang.startsWith('en-'))
      if (britishVoice) {
        utterance.voice = britishVoice
      }

      utterance.onstart = () => {
        console.log('🔊 Web Speech started')
      }

      utterance.onend = () => {
        console.log('✅ Web Speech ended')
        currentSpeechUtterance = null
        resolve()
      }

      utterance.onerror = (event) => {
        console.error('❌ Speech synthesis error:', event)
        currentSpeechUtterance = null
        resolve() // Resolve anyway to continue flow
      }

      window.speechSynthesis.speak(utterance)
    } catch (error) {
      console.error('❌ Error in speakTextWebAPI:', error)
      resolve() // Resolve anyway to continue flow
    }
  })
}

// Helper: Convert base64 to Blob
function base64ToBlob(base64, mimeType) {
  const byteCharacters = atob(base64)
  const byteNumbers = new Array(byteCharacters.length)
  for (let i = 0; i < byteCharacters.length; i++) {
    byteNumbers[i] = byteCharacters.charCodeAt(i)
  }
  const byteArray = new Uint8Array(byteNumbers)
  return new Blob([byteArray], { type: mimeType })
}

// Start continuous recording for entire test (called once at test start)
async function startContinuousRecording() {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
    
    // Select best supported audio format for recording
    // Priority: webm/opus (best compression) > webm/vp8 > mp4 > fallback
    let mimeType = 'audio/webm;codecs=opus'
    if (!MediaRecorder.isTypeSupported(mimeType)) {
      mimeType = 'audio/webm'
      if (!MediaRecorder.isTypeSupported(mimeType)) {
        mimeType = 'audio/mp4'
        if (!MediaRecorder.isTypeSupported(mimeType)) {
          mimeType = '' // Browser default
        }
      }
    }
    
    console.log('🎙️ Recording with mimeType:', mimeType || 'browser default')
    const options = mimeType ? { mimeType } : {}
    mediaRecorder.value = new MediaRecorder(stream, options)
    audioChunks.value = [] // Initialize chunks array once

    // Setup audio level detection
    audioContext = new (window.AudioContext || window.webkitAudioContext)()
    analyser = audioContext.createAnalyser()
    const source = audioContext.createMediaStreamSource(stream)
    source.connect(analyser)
    analyser.fftSize = 512
    analyser.smoothingTimeConstant = 0.8

    mediaRecorder.value.ondataavailable = (event) => {
      audioChunks.value.push(event.data)
    }

    mediaRecorder.value.onstop = () => {
      // Save single recording for entire test with actual recording format
      const actualMimeType = mediaRecorder.value.mimeType || mimeType || 'audio/webm'
      const audioBlob = new Blob(audioChunks.value, { type: actualMimeType })
      recordings.value = [{ // Replace array with single recording
        part: 0, // 0 indicates full test recording
        scriptIndex: 0, // Full test recording
        blob: audioBlob,
        duration: recordingTime.value
      }]
      console.log('💾 Full test recording saved:', recordingTime.value, 'seconds', 'format:', actualMimeType)

      isRecording.value = false
      audioLevel.value = 0

      // Stop audio analysis
      if (animationFrame) {
        cancelAnimationFrame(animationFrame)
        animationFrame = null
      }
      if (audioContext) {
        audioContext.close()
        audioContext = null
      }
    }

    mediaRecorder.value.start()
    isRecording.value = true
    recordingTime.value = 0
    console.log('🔴 Continuous recording started for entire test')

    // Start analyzing audio level
    detectAudioLevel()

    // Start recording timer (no auto-stop)
    recordingTimer = setInterval(() => {
      recordingTime.value++
    }, 1000)

  } catch (error) {
    console.error('❌ Failed to start recording:', error)
    Swal.fire('Error', 'Could not access microphone', 'error')
  }
}

// Pause recording (when examiner is speaking)
function pauseRecording() {
  if (mediaRecorder.value && mediaRecorder.value.state === 'recording') {
    mediaRecorder.value.pause()
    console.log('⏸️ Recording paused')
  }
}

// Resume recording (when student needs to speak)
function resumeRecording() {
  if (mediaRecorder.value && mediaRecorder.value.state === 'paused') {
    mediaRecorder.value.resume()
    console.log('▶️ Recording resumed')
  }
}

function detectAudioLevel() {
  if (!analyser) return

  const bufferLength = analyser.frequencyBinCount
  const dataArray = new Uint8Array(bufferLength)

  const analyze = () => {
    if (!isRecording.value) return

    analyser.getByteFrequencyData(dataArray)

    // Calculate RMS (Root Mean Square) for more accurate volume
    let sum = 0
    for (let i = 0; i < bufferLength; i++) {
      sum += dataArray[i] * dataArray[i]
    }
    const rms = Math.sqrt(sum / bufferLength)

    // Normalize to 0-100 with higher sensitivity
    // Max theoretical value is 255, but practical max is around 200
    audioLevel.value = Math.min(100, (rms / 200) * 100)

    // Debug: Log audio level every 10 frames
    if (Math.random() < 0.1) {
      console.log('🎤 Audio level:', audioLevel.value.toFixed(1), '% | RMS:', rms.toFixed(1))
    }

    animationFrame = requestAnimationFrame(analyze)
  }

  analyze()
}

function stopRecording() {
  return new Promise((resolve) => {
    if (mediaRecorder.value && isRecording.value) {
      // Save the current onstop handler
      const originalOnstop = mediaRecorder.value.onstop
      
      // Override onstop to call original handler then resolve
      mediaRecorder.value.onstop = () => {
        // Call original handler first (this saves the blob to recordings.value)
        if (originalOnstop) {
          originalOnstop()
        }
        console.log('✅ stopRecording: Recording fully saved and blob stored')
        resolve()
      }

      // Trigger stop
      mediaRecorder.value.stop()
      isRecording.value = false
      if (recordingTimer) {
        clearInterval(recordingTimer)
        recordingTimer = null
      }

      // Stop all tracks
      if (mediaRecorder.value.stream) {
      mediaRecorder.value.stream.getTracks().forEach(track => track.stop())
      }
    } else {
      // No active recording, resolve immediately
      console.log('⚠️ stopRecording: No active recording')
      resolve()
    }
  })
}

async function nextQuestion() {
  currentQuestionIndex.value++

  if (currentPart.value === 1) {
    if (currentQuestionIndex.value < part1Questions.value.length) {
      await playQuestion()
    } else {
      // Move to Part 2
      startPart2()
    }
  } else if (currentPart.value === 3) {
    if (currentQuestionIndex.value < part3Questions.value.length) {
      await playQuestion()
    } else {
      // Test complete
      finishTest()
    }
  }
}

function startPart2() {
  console.log('🎯 Starting Part 2')
  console.log('🔍 DEBUG: cueCard value at Part 2 start:', cueCard.value)
  console.log('🔍 DEBUG: cueCard length:', cueCard.value.length)
  console.log('🔍 DEBUG: prepTime:', prepTime.value, 'speakTime:', speakTime.value)

  currentPart.value = 2
  part2State.value = 'preparation'

  // Start preparation timer
  prepTimer = setInterval(() => {
    prepTime.value--
    if (prepTime.value <= 0) {
      clearInterval(prepTimer)
      startPart2Speaking()
    }
  }, 1000)
}

async function startPart2Speaking() {
  part2State.value = 'speaking'

  // Recording is continuous - no need to start/stop
  // Start speaking timer
  speakTimer = setInterval(async () => {
    speakTime.value--
    if (speakTime.value <= 0) {
      clearInterval(speakTimer)
      // Recording continues to Part 3
      startPart3()
    }
  }, 1000)
}

async function startPart3() {
  currentPart.value = 3
  currentQuestionIndex.value = 0
  questionState.value = 'idle'

  if (part3Questions.value.length > 0) {
    await playQuestion()
  } else {
    finishTest()
  }
}

// Stop all audio playback (TTS)
function stopAllAudio() {
  // Stop Azure TTS audio element
  if (currentAudioElement) {
    currentAudioElement.pause()
    currentAudioElement.currentTime = 0
    currentAudioElement = null
    console.log('🛑 Stopped Azure TTS audio')
  }

  // Stop Web Speech API
  if (window.speechSynthesis) {
    window.speechSynthesis.cancel()
    currentSpeechUtterance = null
    console.log('🛑 Stopped Web Speech API')
  }
}

async function finishTest() {
  const result = await Swal.fire({
    title: 'Finish Test?',
    text: 'Are you sure you want to finish the speaking test?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#f43f5e',
    confirmButtonText: 'Yes, Finish Test'
  })

  if (result.isConfirmed) {
    stopAllAudio() // Stop any playing audio

    // Wait for recording to be saved if active
    console.log('🔄 Waiting for recording to complete...')
    await stopRecording()
    console.log('✅ Recording completed and saved')

    testState.value = 'finished'
    await submitTest()
  }
}

async function autoFinishTest() {
  await Swal.fire('Time\'s Up!', 'The speaking test time has ended.', 'info')

  // Wait for recording to be saved if active
  console.log('🔄 Auto-finish: Waiting for recording to complete...')
  await stopRecording()
  console.log('✅ Auto-finish: Recording completed and saved')

  testState.value = 'finished'
  await submitTest()
}

async function submitTest() {
  // Prevent double submit
  if (isSubmitting.value) {
    console.log('⚠️ Already submitting, skipping...')
    return
  }
  
  // Clear timer immediately to prevent multiple calls
  if (testTimer) {
    clearInterval(testTimer)
    testTimer = null
  }
  
  try {
    // Check if we have at least one recording
    if (recordings.value.length === 0) {
      console.warn('⚠️ No recordings to submit')
      Swal.fire({
        title: 'No Recordings',
        text: 'You haven\'t recorded any answers. The test will not be submitted.',
        icon: 'warning'
      }).then(() => {
        router.push({ name: 'examination.ielts-practice' })
      })
      return
    }

    isSubmitting.value = true

    // Create FormData with recordings
    const formData = new FormData()
    formData.append('test_id', activeTestId.value)
    formData.append('total_duration', 14 * 60 - timeRemaining.value)

    // Add practice_test_id if available (for full tests)
    if (practiceTestId.value) {
      formData.append('practice_test_id', practiceTestId.value)
      console.log('📎 Linked to practice test:', practiceTestId.value)
    }

    recordings.value.forEach((recording, index) => {
      // Determine file extension based on blob type
      let extension = 'webm' // Default
      if (recording.blob.type.includes('mp4')) {
        extension = 'mp4'
      } else if (recording.blob.type.includes('wav')) {
        extension = 'wav'
      } else if (recording.blob.type.includes('ogg')) {
        extension = 'ogg'
      }
      
      formData.append(`recordings[${index}][part]`, recording.part)
      formData.append(`recordings[${index}][question_index]`, recording.scriptIndex)
      formData.append(`recordings[${index}][duration]`, recording.duration)
      formData.append(`recordings[${index}][audio]`, recording.blob, `recording_${index}.${extension}`)
      console.log(`📎 Recording ${index}: ${recording.blob.type} -> ${extension}`)
    })

    // Submit to API
    console.log('📤 Submitting test with', recordings.value.length, 'recordings...')
    const response = await axios.post('/api/examination/speaking-submissions', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    console.log('✅ Test submitted successfully!', response.data)

    Swal.fire({
      title: 'Test Submitted!',
      text: 'Your speaking test has been submitted successfully.',
      icon: 'success'
    }).then(() => {
      router.push({ name: 'examination.ielts-practice' })
    })

  } catch (error) {
    console.error('❌ Failed to submit test:', error)
    isSubmitting.value = false // Reset flag on error
    Swal.fire('Error', 'Failed to submit test', 'error')
  }
}

function playRecording(recording) {
  // Stop any currently playing recording first
  if (currentRecordingAudio) {
    currentRecordingAudio.pause()
    currentRecordingAudio.currentTime = 0
    currentRecordingAudio = null
  }

  const url = URL.createObjectURL(recording.blob)
  const audio = new Audio(url)
  currentRecordingAudio = audio // Track this audio element for cleanup
  audio.play()

  audio.onended = () => {
    URL.revokeObjectURL(url)
    currentRecordingAudio = null
  }
}

function downloadRecording(recording, index) {
  const url = URL.createObjectURL(recording.blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `ielts-speaking-part${recording.part}-recording${index + 1}.wav`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)

  console.log('💾 Downloaded recording:', a.download)
}

function onAudioEnded() {
  // Handle audio end if needed
}

// Lifecycle
onMounted(async () => {
  // Initialize speech synthesis voices
  if (window.speechSynthesis) {
    // Load voices
    let voices = window.speechSynthesis.getVoices()

    // If voices are not loaded yet, wait for them
    if (voices.length === 0) {
      window.speechSynthesis.onvoiceschanged = () => {
        voices = window.speechSynthesis.getVoices()
        console.log('✅ Voices loaded:', voices.length)
      }
    } else {
      console.log('✅ Voices already loaded:', voices.length)
    }
  } else {
    console.warn('⚠️ Speech Synthesis not supported in this browser')
  }

  await loadTestData()
})

// Cleanup function to stop all audio and timers
function cleanup() {
  console.log('🧹 Cleaning up audio and timers...')

  // Clear all timers
  if (testTimer) clearInterval(testTimer)
  if (prepTimer) clearInterval(prepTimer)
  if (speakTimer) clearInterval(speakTimer)
  if (recordingTimer) clearInterval(recordingTimer)

  // Stop recording
  stopRecording()

  // Cancel speech synthesis
  speechSynthesis.cancel()

  // Stop Azure audio if playing
  if (currentAudioElement) {
    currentAudioElement.pause()
    currentAudioElement.currentTime = 0
    currentAudioElement = null
  }

  // Stop recording playback audio if playing
  if (currentRecordingAudio) {
    currentRecordingAudio.pause()
    currentRecordingAudio.currentTime = 0
    currentRecordingAudio = null
  }

  // Stop audioPlayer ref if exists
  if (audioPlayer.value) {
    audioPlayer.value.pause()
    audioPlayer.value.currentTime = 0
  }

  console.log('✅ Cleanup complete')
}

// Call cleanup when navigating away (including browser back button)
onBeforeRouteLeave((to, from) => {
  cleanup()
  return true // Allow navigation
})

// Call cleanup when component is unmounted
onUnmounted(() => {
  cleanup()
})
</script>

<style scoped>
.prose {
  max-width: none;
}

.prose p {
  margin-bottom: 0.5rem;
}

.prose ul {
  list-style: disc;
  margin-left: 1.5rem;
}
</style>
