<template>
  <div class="audio-player bg-gray-100 rounded-lg p-4">
    <div class="flex items-center space-x-4">
      <!-- Play/Pause button -->
      <button
        @click="togglePlay"
        class="w-12 h-12 flex items-center justify-center rounded-full bg-blue-600 text-white hover:bg-blue-700 transition-colors"
      >
        <svg v-if="!isPlaying" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
        </svg>
        <svg v-else class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
      </button>

      <!-- Progress bar -->
      <div class="flex-1">
        <div class="flex items-center space-x-2 mb-1">
          <span class="text-sm text-gray-600 font-mono">{{ formatTime(currentTime) }}</span>
          <input
            type="range"
            :value="currentTime"
            :max="duration"
            @input="seek"
            class="flex-1 h-2 bg-gray-300 rounded-lg appearance-none cursor-pointer"
          />
          <span class="text-sm text-gray-600 font-mono">{{ formatTime(duration) }}</span>
        </div>

        <!-- Volume control -->
        <div class="flex items-center space-x-2">
          <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd" />
          </svg>
          <input
            type="range"
            v-model="volume"
            min="0"
            max="1"
            step="0.1"
            @input="setVolume"
            class="w-20 h-1 bg-gray-300 rounded-lg appearance-none cursor-pointer"
          />
        </div>
      </div>

      <!-- Playback speed -->
      <select
        v-model="playbackRate"
        @change="setPlaybackRate"
        class="text-sm border rounded px-2 py-1"
      >
        <option :value="0.5">0.5x</option>
        <option :value="0.75">0.75x</option>
        <option :value="1">1x</option>
        <option :value="1.25">1.25x</option>
        <option :value="1.5">1.5x</option>
      </select>
    </div>

    <!-- Transcript toggle -->
    <div v-if="transcript" class="mt-3">
      <button
        @click="showTranscript = !showTranscript"
        class="text-sm text-blue-600 hover:text-blue-800"
      >
        {{ showTranscript ? 'Ẩn transcript' : 'Hiện transcript' }}
      </button>

      <div v-if="showTranscript" class="mt-2 p-3 bg-white rounded border text-sm text-gray-700 max-h-48 overflow-y-auto">
        {{ transcript }}
      </div>
    </div>

    <!-- Hidden audio element -->
    <audio
      ref="audioRef"
      :src="src"
      @loadedmetadata="onLoaded"
      @timeupdate="onTimeUpdate"
      @ended="onEnded"
    ></audio>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  src: {
    type: String,
    required: true
  },
  transcript: {
    type: String,
    default: ''
  }
})

const audioRef = ref(null)
const isPlaying = ref(false)
const currentTime = ref(0)
const duration = ref(0)
const volume = ref(1)
const playbackRate = ref(1)
const showTranscript = ref(false)

function togglePlay() {
  if (audioRef.value.paused) {
    audioRef.value.play()
    isPlaying.value = true
  } else {
    audioRef.value.pause()
    isPlaying.value = false
  }
}

function seek(e) {
  const time = parseFloat(e.target.value)
  audioRef.value.currentTime = time
  currentTime.value = time
}

function setVolume() {
  audioRef.value.volume = volume.value
}

function setPlaybackRate() {
  audioRef.value.playbackRate = playbackRate.value
}

function onLoaded() {
  duration.value = audioRef.value.duration
}

function onTimeUpdate() {
  currentTime.value = audioRef.value.currentTime
}

function onEnded() {
  isPlaying.value = false
}

function formatTime(seconds) {
  if (!seconds || isNaN(seconds)) return '00:00'
  const mins = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
}
</script>

<style scoped>
input[type="range"] {
  -webkit-appearance: none;
}

input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 12px;
  height: 12px;
  background: #3b82f6;
  border-radius: 50%;
  cursor: pointer;
}
</style>
