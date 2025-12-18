<template>
  <div class="audio-response-question">
    <!-- Question Title / Prompt -->
    <div class="mb-4">
      <p class="text-gray-800 font-medium" v-html="question.title"></p>

      <!-- Speaking prompt details -->
      <div v-if="question.content?.prompt" class="mt-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <p class="text-blue-800" v-html="question.content.prompt"></p>
      </div>

      <!-- Sample audio (if any) -->
      <div v-if="question.content?.sample_audio" class="mt-3">
        <p class="text-sm text-gray-600 mb-2">Nghe mẫu:</p>
        <audio :src="question.content.sample_audio" controls class="w-full"></audio>
      </div>
    </div>

    <!-- Preparation time -->
    <div v-if="prepTime > 0 && !isRecording && !audioUrl" class="mb-4 p-4 bg-yellow-50 rounded-lg">
      <p class="text-yellow-800 font-medium">
        ⏱️ Thời gian chuẩn bị: {{ formatTime(prepTimeRemaining) }}
      </p>
      <p class="text-sm text-yellow-700 mt-1">
        Hãy chuẩn bị câu trả lời của bạn. Ghi âm sẽ bắt đầu sau {{ prepTimeRemaining }} giây.
      </p>
    </div>

    <!-- Recording interface -->
    <div class="p-6 bg-gray-50 rounded-lg border">
      <!-- Not recording yet -->
      <div v-if="!isRecording && !audioUrl" class="text-center">
        <button
          @click="startRecording"
          :disabled="disabled || prepTimeRemaining > 0"
          class="px-6 py-3 bg-red-600 text-white rounded-full hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <svg class="w-6 h-6 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd" />
          </svg>
          Bắt đầu ghi âm
        </button>
        <p class="text-sm text-gray-500 mt-2">
          Thời gian tối đa: {{ formatTime(maxDuration) }}
        </p>
      </div>

      <!-- Recording in progress -->
      <div v-if="isRecording" class="text-center">
        <div class="mb-4">
          <div class="w-16 h-16 mx-auto bg-red-100 rounded-full flex items-center justify-center animate-pulse">
            <div class="w-8 h-8 bg-red-600 rounded-full"></div>
          </div>
        </div>

        <p class="text-lg font-mono text-red-600 mb-4">
          {{ formatTime(recordingDuration) }} / {{ formatTime(maxDuration) }}
        </p>

        <!-- Audio visualization -->
        <div class="h-12 bg-gray-200 rounded mb-4 flex items-center justify-center">
          <div
            v-for="i in 20"
            :key="i"
            class="w-1 mx-0.5 bg-red-500 rounded"
            :style="{ height: Math.random() * 100 + '%' }"
          ></div>
        </div>

        <button
          @click="stopRecording"
          class="px-6 py-3 bg-gray-800 text-white rounded-full hover:bg-gray-900 transition-colors"
        >
          <svg class="w-6 h-6 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd" />
          </svg>
          Dừng ghi âm
        </button>
      </div>

      <!-- Playback -->
      <div v-if="audioUrl && !isRecording" class="text-center">
        <audio ref="audioPlayer" :src="audioUrl" controls class="w-full mb-4"></audio>

        <div class="flex justify-center space-x-3">
          <button
            v-if="!disabled && !showResult"
            @click="reRecord"
            class="px-4 py-2 text-gray-600 border rounded-lg hover:bg-gray-100"
          >
            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Ghi lại
          </button>
        </div>

        <p class="text-sm text-gray-500 mt-2">
          Thời lượng: {{ formatTime(recordingDuration) }}
        </p>
      </div>
    </div>

    <!-- Grading feedback (for Speaking) -->
    <div v-if="showResult && feedback" class="mt-4 p-4 bg-blue-50 rounded-lg">
      <h4 class="font-medium text-blue-800 mb-2">Nhận xét của giáo viên:</h4>
      <p class="text-blue-700">{{ feedback }}</p>

      <div v-if="gradingCriteria" class="mt-3 grid grid-cols-2 gap-3">
        <div v-for="(score, criterion) in gradingCriteria" :key="criterion" class="bg-white p-2 rounded">
          <span class="text-sm text-gray-600">{{ formatCriterion(criterion) }}:</span>
          <span class="ml-2 font-medium">{{ score }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  question: {
    type: Object,
    required: true
  },
  modelValue: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  showResult: {
    type: Boolean,
    default: false
  },
  feedback: {
    type: String,
    default: ''
  },
  gradingCriteria: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'answer', 'audioRecorded'])

const audioUrl = ref(props.modelValue)
const isRecording = ref(false)
const recordingDuration = ref(0)
const prepTimeRemaining = ref(0)
const audioPlayer = ref(null)

let mediaRecorder = null
let audioChunks = []
let recordingInterval = null
let prepInterval = null

const prepTime = computed(() => props.question.content?.prep_time || 0)
const maxDuration = computed(() => props.question.content?.max_duration || 120) // 2 minutes default

onMounted(() => {
  if (prepTime.value > 0 && !props.modelValue) {
    startPrepTimer()
  }
})

onUnmounted(() => {
  cleanup()
})

function startPrepTimer() {
  prepTimeRemaining.value = prepTime.value
  prepInterval = setInterval(() => {
    prepTimeRemaining.value--
    if (prepTimeRemaining.value <= 0) {
      clearInterval(prepInterval)
      // Auto start recording after prep time
      // startRecording()
    }
  }, 1000)
}

async function startRecording() {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
    mediaRecorder = new MediaRecorder(stream)
    audioChunks = []

    mediaRecorder.ondataavailable = (e) => {
      audioChunks.push(e.data)
    }

    mediaRecorder.onstop = () => {
      const audioBlob = new Blob(audioChunks, { type: 'audio/webm' })
      audioUrl.value = URL.createObjectURL(audioBlob)

      emit('answer', {
        question_id: props.question.id,
        audio_blob: audioBlob,
        duration: recordingDuration.value
      })

      emit('audioRecorded', {
        blob: audioBlob,
        duration: recordingDuration.value
      })

      // Stop all tracks
      stream.getTracks().forEach(track => track.stop())
    }

    mediaRecorder.start()
    isRecording.value = true
    recordingDuration.value = 0

    // Start duration counter
    recordingInterval = setInterval(() => {
      recordingDuration.value++
      if (recordingDuration.value >= maxDuration.value) {
        stopRecording()
      }
    }, 1000)

  } catch (error) {
    console.error('Error accessing microphone:', error)
    alert('Không thể truy cập microphone. Vui lòng cấp quyền và thử lại.')
  }
}

function stopRecording() {
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    mediaRecorder.stop()
    isRecording.value = false
    clearInterval(recordingInterval)
  }
}

function reRecord() {
  audioUrl.value = ''
  recordingDuration.value = 0
}

function cleanup() {
  clearInterval(recordingInterval)
  clearInterval(prepInterval)
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    mediaRecorder.stop()
  }
}

function formatTime(seconds) {
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

function formatCriterion(key) {
  const labels = {
    fluency: 'Fluency & Coherence',
    lexical: 'Lexical Resource',
    grammar: 'Grammatical Range',
    pronunciation: 'Pronunciation'
  }
  return labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}
</script>
