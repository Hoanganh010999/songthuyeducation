<template>
  <div class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl max-w-2xl w-full shadow-2xl overflow-hidden">
      <!-- Header -->
      <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-4 text-white">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-xl font-bold">🎴 {{ t('vocabulary.flashcard_review') }}</h3>
            <p class="text-sm opacity-90">{{ currentIndex + 1 }} / {{ flashcards.length }}</p>
          </div>
          <div class="flex items-center space-x-4">
            <!-- Mode Toggle - More visible -->
            <div class="flex bg-white rounded-lg shadow-md overflow-hidden border-2 border-white">
              <button
                @click="reviewMode = 'word-to-meaning'"
                :class="[
                  'px-4 py-2 text-sm font-semibold transition flex items-center space-x-2',
                  reviewMode === 'word-to-meaning'
                    ? 'bg-gradient-to-r from-blue-500 to-purple-500 text-white'
                    : 'bg-white text-gray-700 hover:bg-gray-100'
                ]"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
                <span>{{ t('vocabulary.mode_word_to_meaning') }}</span>
              </button>
              <button
                @click="reviewMode = 'meaning-to-word'"
                :class="[
                  'px-4 py-2 text-sm font-semibold transition flex items-center space-x-2',
                  reviewMode === 'meaning-to-word'
                    ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white'
                    : 'bg-white text-gray-700 hover:bg-gray-100'
                ]"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                </svg>
                <span>{{ t('vocabulary.mode_meaning_to_word') }}</span>
              </button>
            </div>
            
            <button
              @click="$emit('close')"
              class="text-white hover:bg-white hover:bg-opacity-20 rounded-full p-2 transition"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="mt-3 bg-white bg-opacity-20 rounded-full h-2 overflow-hidden">
          <div
            class="bg-white h-full transition-all duration-300"
            :style="{ width: progress + '%' }"
          ></div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="p-12 text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <p class="mt-4 text-gray-600">{{ t('vocabulary.loading_flashcards') }}</p>
      </div>

      <!-- Flashcard Content -->
      <div v-else-if="currentCard" class="p-8">
        <!-- Card Face -->
        <div
          class="flashcard-container"
          :class="{ flipped: isFlipped }"
          @click="flipCard"
        >
          <div class="flashcard">
            <!-- Front Face -->
            <div class="flashcard-face flashcard-front">
              <!-- Mode 1: Word → Meaning -->
              <div v-if="reviewMode === 'word-to-meaning'" class="text-center">
                <div class="mb-2 flex justify-center items-center space-x-3">
                  <h2 class="text-4xl font-bold text-gray-900">{{ currentCard.word }}</h2>
                  <button
                    @click.stop="speakWord(currentCard.word)"
                    class="p-3 bg-blue-100 text-blue-600 rounded-full hover:bg-blue-200 transition"
                    title="Pronounce"
                  >
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd"/>
                    </svg>
                  </button>
                </div>
                
                <!-- Word Form in parentheses, centered below word -->
                <p v-if="currentCard.word_form" class="text-base text-gray-500 italic mb-6">({{ currentCard.word_form }})</p>
                
                <p class="text-gray-400 text-sm mb-6">{{ t('vocabulary.click_to_reveal_meaning') }}</p>
                
                <!-- Hint Buttons -->
                <div class="flex justify-center space-x-3">
                  <button
                    v-if="currentCard.synonym"
                    @click.stop="showHint = 'synonym'"
                    :class="[
                      'px-4 py-2 rounded-lg text-sm transition',
                      showHint === 'synonym'
                        ? 'bg-green-600 text-white'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]"
                  >
                    {{ showHint === 'synonym' ? currentCard.synonym : 'Synonym' }}
                  </button>
                  <button
                    v-if="currentCard.antonym"
                    @click.stop="showHint = 'antonym'"
                    :class="[
                      'px-4 py-2 rounded-lg text-sm transition',
                      showHint === 'antonym'
                        ? 'bg-red-600 text-white'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]"
                  >
                    {{ showHint === 'antonym' ? currentCard.antonym : t('vocabulary.antonym') }}
                  </button>
                  <button
                    v-if="currentCard.example"
                    @click.stop="showHint = 'example'"
                    :class="[
                      'px-4 py-2 rounded-lg text-sm transition max-w-md',
                      showHint === 'example'
                        ? 'bg-blue-600 text-white'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]"
                  >
                    <span v-if="showHint === 'example'" class="inline-block text-left">{{ maskedExample(currentCard.example, currentCard.word) }}</span>
                    <span v-else>{{ t('vocabulary.example_hint') }}</span>
                  </button>
                </div>
              </div>

              <!-- Mode 2: Meaning → Word -->
              <div v-else class="text-center">
                <h3 class="text-xl font-semibold text-gray-700 mb-3">What's the word?</h3>
                <p class="text-2xl text-gray-900 mb-6">{{ currentCard.definition }}</p>
                <p v-if="currentCard.word_form" class="text-sm text-gray-500 italic mb-6">({{ currentCard.word_form }})</p>
                
                <p class="text-gray-400 text-sm mb-6">Click to reveal word</p>
                
                <!-- Hint Buttons -->
                <div class="flex justify-center space-x-3 mb-6">
                  <button
                    v-if="currentCard.synonym"
                    @click.stop="showHint = 'synonym'"
                    :class="[
                      'px-4 py-2 rounded-lg text-sm transition',
                      showHint === 'synonym'
                        ? 'bg-green-600 text-white'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]"
                  >
                    {{ showHint === 'synonym' ? currentCard.synonym : 'Synonym' }}
                  </button>
                  <button
                    v-if="currentCard.antonym"
                    @click.stop="showHint = 'antonym'"
                    :class="[
                      'px-4 py-2 rounded-lg text-sm transition',
                      showHint === 'antonym'
                        ? 'bg-red-600 text-white'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]"
                  >
                    {{ showHint === 'antonym' ? currentCard.antonym : 'Antonym' }}
                  </button>
                  <button
                    v-if="currentCard.example"
                    @click.stop="showHint = 'example'"
                    :class="[
                      'px-4 py-2 rounded-lg text-sm transition',
                      showHint === 'example'
                        ? 'bg-blue-600 text-white'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]"
                  >
                    {{ showHint === 'example' ? maskedExample(currentCard.example, currentCard.word) : 'Example' }}
                  </button>
                </div>
                
                <!-- Record Button -->
                <button
                  @click.stop="toggleRecording"
                  :class="[
                    'px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2 mx-auto',
                    isRecording
                      ? 'bg-red-600 text-white animate-pulse'
                      : 'bg-purple-600 text-white hover:bg-purple-700'
                  ]"
                >
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd"/>
                  </svg>
                  <span v-if="isRecording">Recording... {{ recordingCountdown }}s</span>
                  <span v-else>Record Pronunciation</span>
                </button>
              </div>
            </div>

            <!-- Back Face -->
            <div class="flashcard-face flashcard-back">
              <!-- Mode 1: Show Definition -->
              <div v-if="reviewMode === 'word-to-meaning'" class="text-center">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">{{ currentCard.word }}</h2>
                <p v-if="currentCard.word_form" class="text-sm text-gray-500 italic mb-4">{{ currentCard.word_form }}</p>
                <p class="text-xl text-gray-700 mb-6">{{ currentCard.definition }}</p>
                
                <div v-if="currentCard.synonym || currentCard.antonym" class="mb-6 space-y-2 text-left max-w-md mx-auto">
                  <p v-if="currentCard.synonym" class="text-sm"><span class="font-semibold text-green-600">Synonym:</span> {{ currentCard.synonym }}</p>
                  <p v-if="currentCard.antonym" class="text-sm"><span class="font-semibold text-red-600">Antonym:</span> {{ currentCard.antonym }}</p>
                </div>
                
                <p v-if="currentCard.example" class="text-gray-600 italic text-sm border-l-4 border-blue-400 pl-4 py-2 text-left max-w-md mx-auto">
                  {{ currentCard.example }}
                </p>
              </div>

              <!-- Mode 2: Show Word -->
              <div v-else class="text-center">
                <h3 class="text-lg text-gray-600 mb-3">The word is:</h3>
                <div class="flex justify-center items-center space-x-3 mb-4">
                  <h2 class="text-4xl font-bold text-gray-900">{{ currentCard.word }}</h2>
                  <button
                    @click.stop="speakWord(currentCard.word)"
                    class="p-3 bg-blue-100 text-blue-600 rounded-full hover:bg-blue-200 transition"
                  >
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd"/>
                    </svg>
                  </button>
                </div>
                
                <p class="text-lg text-gray-700 mb-4">{{ currentCard.definition }}</p>
                
                <div v-if="currentCard.synonym || currentCard.antonym" class="mb-4 space-y-2 text-left max-w-md mx-auto">
                  <p v-if="currentCard.synonym" class="text-sm"><span class="font-semibold text-green-600">Synonym:</span> {{ currentCard.synonym }}</p>
                  <p v-if="currentCard.antonym" class="text-sm"><span class="font-semibold text-red-600">Antonym:</span> {{ currentCard.antonym }}</p>
                </div>
                
                <p v-if="currentCard.example" class="text-gray-600 italic text-sm border-l-4 border-blue-400 pl-4 py-2 text-left max-w-md mx-auto">
                  {{ currentCard.example }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation & Feedback Buttons -->
      <div v-if="!loading && currentCard" class="border-t bg-gray-50 p-4">
        <div class="flex justify-between items-center max-w-xl mx-auto">
          <button
            @click="previousCard"
            :disabled="currentIndex === 0"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            ← Previous
          </button>
          
          <div class="flex space-x-3">
            <button
              @click="markAnswer(false)"
              class="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition flex items-center space-x-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
              <span>Wrong</span>
            </button>
            <button
              @click="markAnswer(true)"
              class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition flex items-center space-x-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              <span>Correct</span>
            </button>
          </div>
          
          <button
            @click="nextCard"
            :disabled="currentIndex === flashcards.length - 1"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            Next →
          </button>
        </div>
      </div>

      <!-- Session Complete -->
      <div v-if="sessionComplete" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-60">
        <div class="bg-white rounded-xl p-8 max-w-md text-center">
          <div class="text-6xl mb-4">🎉</div>
          <h3 class="text-2xl font-bold mb-4">Session Complete!</h3>
          <p class="text-gray-600 mb-6">You've reviewed all {{ flashcards.length }} words</p>
          <div class="bg-gray-100 rounded-lg p-4 mb-6">
            <div class="flex justify-between mb-2">
              <span>Correct:</span>
              <span class="font-bold text-green-600">{{ correctCount }}</span>
            </div>
            <div class="flex justify-between">
              <span>Wrong:</span>
              <span class="font-bold text-red-600">{{ wrongCount }}</span>
            </div>
          </div>
          <div class="flex space-x-3">
            <button
              @click="restartSession"
              class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              Review Again
            </button>
            <button
              @click="$emit('close')"
              class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pronunciation Feedback Inline (Compact) -->
    <div v-if="pronunciationFeedback" class="fixed bottom-4 left-1/2 transform -translate-x-1/2 w-full max-w-md px-4 z-[60]">
      <div class="bg-white rounded-lg shadow-2xl border-2" :class="{
        'border-blue-400': pronunciationFeedback.status === 'checking',
        'border-green-400': pronunciationFeedback.status === 'completed' && pronunciationFeedback.correct,
        'border-yellow-400': pronunciationFeedback.status === 'completed' && !pronunciationFeedback.correct,
        'border-red-400': pronunciationFeedback.status === 'error'
      }">
        <!-- Status Header -->
        <div class="p-3 text-center text-sm font-semibold flex items-center justify-between" :class="{
          'bg-blue-50 text-blue-800': pronunciationFeedback.status === 'checking',
          'bg-green-50 text-green-800': pronunciationFeedback.status === 'completed' && pronunciationFeedback.correct,
          'bg-yellow-50 text-yellow-800': pronunciationFeedback.status === 'completed' && !pronunciationFeedback.correct,
          'bg-red-50 text-red-800': pronunciationFeedback.status === 'error'
        }">
          <span class="flex-1">{{ pronunciationFeedback.message }}</span>
          <button @click="pronunciationFeedback = null" class="text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Compact Results -->
        <div v-if="pronunciationFeedback.status === 'completed'" class="p-3 space-y-2 max-h-96 overflow-y-auto">
          <!-- Scores - Compact Grid -->
          <div class="grid grid-cols-2 gap-2 text-xs">
            <div class="bg-gray-50 p-2 rounded text-center">
              <div class="text-gray-600">{{ t('vocabulary.accuracy') }}</div>
              <div class="font-bold text-lg" :class="Number(pronunciationFeedback.accuracy || 0) >= 70 ? 'text-green-600' : 'text-yellow-600'">
                {{ Number(pronunciationFeedback.accuracy || 0).toFixed(0) }}%
              </div>
            </div>
            <div class="bg-gray-50 p-2 rounded text-center">
              <div class="text-gray-600">{{ t('vocabulary.fluency') }}</div>
              <div class="font-bold text-lg" :class="Number(pronunciationFeedback.fluency || 0) >= 70 ? 'text-green-600' : 'text-yellow-600'">
                {{ Number(pronunciationFeedback.fluency || 0).toFixed(0) }}%
              </div>
            </div>
          </div>

          <!-- Transcribed - Compact -->
          <div class="bg-gray-50 p-2 rounded text-xs space-y-1">
            <div>
              <span class="text-gray-500">{{ t('vocabulary.you_said') }}:</span>
              <span class="font-medium text-gray-800 ml-1">{{ pronunciationFeedback.transcribed || t('vocabulary.not_recognized') }}</span>
            </div>
            <div>
              <span class="text-gray-500">{{ t('vocabulary.should_pronounce') }}:</span>
              <span class="font-medium text-green-700 ml-1">{{ pronunciationFeedback.expected }}</span>
            </div>
          </div>

          <!-- Errors Summary (if any) -->
          <div v-if="pronunciationFeedback.phoneme_errors && pronunciationFeedback.phoneme_errors.length > 0" 
               class="bg-red-50 border border-red-200 rounded p-2">
            <div class="text-xs font-semibold text-red-600 mb-1">
              ⚠ {{ pronunciationFeedback.total_errors }} lỗi phát âm
            </div>
            <div class="space-y-1 max-h-32 overflow-y-auto text-xs">
              <div v-for="(error, idx) in pronunciationFeedback.phoneme_errors.slice(0, 3)" :key="idx" 
                   class="text-gray-700">
                {{ idx + 1 }}. {{ error.description }}
              </div>
              <div v-if="pronunciationFeedback.phoneme_errors.length > 3" class="text-gray-500 italic">
                + {{ pronunciationFeedback.phoneme_errors.length - 3 }} lỗi khác...
              </div>
            </div>
          </div>

          <!-- Perfect -->
          <div v-else-if="pronunciationFeedback.correct" class="text-center text-green-700 py-2">
            <div class="text-2xl">🎉</div>
            <div class="text-xs font-semibold">{{ t('vocabulary.perfect_pronunciation') }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useI18n } from '../../composables/useI18n'

const { t } = useI18n()

const emit = defineEmits(['close'])

const loading = ref(true)
const flashcards = ref([])
const currentIndex = ref(0)
const reviewMode = ref('word-to-meaning') // 'word-to-meaning' or 'meaning-to-word'
const isFlipped = ref(false)
const showHint = ref(null)
const correctCount = ref(0)
const wrongCount = ref(0)
const sessionComplete = ref(false)
const isRecording = ref(false)
const recordingCountdown = ref(3)
const pronunciationFeedback = ref(null)
let mediaRecorder = null
let audioChunks = []
let countdownInterval = null

const currentCard = computed(() => flashcards.value[currentIndex.value])
const progress = computed(() => ((currentIndex.value + 1) / flashcards.value.length) * 100)

const loadFlashcards = async () => {
  try {
    const response = await axios.get('/api/course/vocabulary/review/random', {
      params: { limit: 10 }
    })
    flashcards.value = response.data.data
    loading.value = false
  } catch (error) {
    console.error('Failed to load flashcards:', error)
    Swal.fire('Error', 'Failed to load flashcards', 'error')
    emit('close')
  }
}

const flipCard = () => {
  isFlipped.value = !isFlipped.value
}

const nextCard = () => {
  if (currentIndex.value < flashcards.value.length - 1) {
    currentIndex.value++
    resetCard()
  } else {
    sessionComplete.value = true
  }
}

const previousCard = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--
    resetCard()
  }
}

const resetCard = () => {
  isFlipped.value = false
  showHint.value = null
  pronunciationFeedback.value = null
}

const markAnswer = async (correct) => {
  if (correct) {
    correctCount.value++
  } else {
    wrongCount.value++
  }
  
  // Record review result
  try {
    await axios.post(`/api/course/vocabulary/${currentCard.value.id}/review`, {
      correct
    })
  } catch (error) {
    console.error('Failed to record review:', error)
  }
  
  nextCard()
}

const speakWord = (word) => {
  if ('speechSynthesis' in window) {
    const utterance = new SpeechSynthesisUtterance(word)
    utterance.lang = 'en-US'
    utterance.rate = 0.8
    window.speechSynthesis.speak(utterance)
  }
}

const maskedExample = (example, word) => {
  if (!example || !word) return example
  const regex = new RegExp(word, 'gi')
  return example.replace(regex, '___')
}

const toggleRecording = async () => {
  if (isRecording.value) {
    stopRecording()
  } else {
    await startRecording()
  }
}

const startRecording = async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
    mediaRecorder = new MediaRecorder(stream)
    audioChunks = []
    
    mediaRecorder.ondataavailable = (event) => {
      audioChunks.push(event.data)
    }
    
    mediaRecorder.onstop = async () => {
      const audioBlob = new Blob(audioChunks, { type: 'audio/webm' })
      await uploadAndCheckPronunciation(audioBlob)
      stream.getTracks().forEach(track => track.stop())
    }
    
    mediaRecorder.start()
    isRecording.value = true
    recordingCountdown.value = 3
    
    // Countdown timer
    countdownInterval = setInterval(() => {
      recordingCountdown.value--
      if (recordingCountdown.value <= 0) {
        clearInterval(countdownInterval)
      }
    }, 1000)
    
    // Auto-stop after 3 seconds
    setTimeout(() => {
      if (isRecording.value) {
        stopRecording()
      }
    }, 3000)
  } catch (error) {
    console.error('Failed to start recording:', error)
    Swal.fire('Error', 'Could not access microphone', 'error')
  }
}

const stopRecording = () => {
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    mediaRecorder.stop()
    isRecording.value = false
    recordingCountdown.value = 3
    if (countdownInterval) {
      clearInterval(countdownInterval)
      countdownInterval = null
    }
  }
}

const uploadAndCheckPronunciation = async (audioBlob) => {
  const formData = new FormData()
  formData.append('audio', audioBlob, 'pronunciation.webm')
  
  pronunciationFeedback.value = {
    status: 'checking',
    message: '🔄 Đang kiểm tra phát âm...'
  }
  
  try {
    const response = await axios.post(`/api/course/vocabulary/${currentCard.value.id}/pronunciation-audio`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    console.log('[FlashcardReview] Pronunciation check result:', response.data)
    
    const checkResult = response.data.data?.pronunciation_check
    
    if (checkResult && checkResult.status === 'completed') {
      const score = checkResult.overall_score || 0
      const isGood = score >= 70
      
      pronunciationFeedback.value = {
        status: 'completed',
        correct: isGood,
        score: score,
        accuracy: checkResult.accuracy_score,
        fluency: checkResult.fluency_score,
        completeness: checkResult.completeness_score,
        transcribed: checkResult.transcribed_text,
        expected: checkResult.expected_text,
        feedback: checkResult.feedback,
        phoneme_errors: checkResult.phoneme_errors || [],
        total_errors: checkResult.total_errors || 0,
        message: isGood 
          ? `✓ Xuất sắc! Điểm: ${score}/100` 
          : `⚠ Cần cải thiện. Điểm: ${score}/100`
      }
      
      // No auto-hide - user manually closes
    } else if (checkResult && checkResult.status === 'failed') {
      pronunciationFeedback.value = {
        status: 'error',
        correct: false,
        message: '⚠ Kiểm tra phát âm thất bại: ' + (checkResult.error || 'Unknown error')
      }
    } else {
      pronunciationFeedback.value = {
        status: 'success',
        correct: true,
        message: '✓ Đã ghi âm (không thể kiểm tra phát âm)'
      }
    }
    
  } catch (error) {
    console.error('[FlashcardReview] Failed to upload audio:', error)
    pronunciationFeedback.value = {
      status: 'error',
      correct: false,
      message: '❌ Lỗi: ' + (error.response?.data?.message || error.message)
    }
    
    // Keep error visible for user to read
  }
}

const restartSession = () => {
  currentIndex.value = 0
  correctCount.value = 0
  wrongCount.value = 0
  sessionComplete.value = false
  resetCard()
  loadFlashcards()
}

// Handle keyboard shortcuts
const handleKeyPress = (event) => {
  if (sessionComplete.value) return
  
  switch (event.key) {
    case ' ':
    case 'Enter':
      event.preventDefault()
      flipCard()
      break
    case 'ArrowLeft':
      previousCard()
      break
    case 'ArrowRight':
      nextCard()
      break
    case '1':
      markAnswer(false)
      break
    case '2':
      markAnswer(true)
      break
  }
}

onMounted(() => {
  loadFlashcards()
  window.addEventListener('keydown', handleKeyPress)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyPress)
  if (mediaRecorder) {
    mediaRecorder.stop()
  }
})
</script>

<style scoped>
.flashcard-container {
  perspective: 1000px;
  cursor: pointer;
  min-height: 400px;
}

.flashcard {
  position: relative;
  width: 100%;
  height: 100%;
  transition: transform 0.6s;
  transform-style: preserve-3d;
}

.flashcard-container.flipped .flashcard {
  transform: rotateY(180deg);
}

.flashcard-face {
  position: absolute;
  width: 100%;
  backface-visibility: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  min-height: 400px;
}

.flashcard-front {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 1rem;
}

.flashcard-front h2 {
  color: white;
}

.flashcard-front p {
  color: rgba(255, 255, 255, 0.9);
}

.flashcard-back {
  background: white;
  transform: rotateY(180deg);
  border: 2px solid #e5e7eb;
  border-radius: 1rem;
}
</style>

