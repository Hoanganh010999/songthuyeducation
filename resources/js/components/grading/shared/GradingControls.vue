<template>
  <div class="grading-controls">
    <!-- Simple Correct/Incorrect (Binary Mode) -->
    <template v-if="mode === 'binary'">
      <div class="flex items-center space-x-2">
        <span class="text-sm text-gray-600 mr-2">Đánh giá:</span>
        <button
          @click="handleGrade(true)"
          :disabled="disabled"
          class="px-4 py-2 text-sm rounded-lg border-2 transition-all"
          :class="[
            value === true
              ? 'bg-green-600 text-white border-green-600'
              : 'bg-white text-green-600 border-green-300 hover:bg-green-50'
          ]"
        >
          ✓ Đúng
        </button>
        <button
          @click="handleGrade(false)"
          :disabled="disabled"
          class="px-4 py-2 text-sm rounded-lg border-2 transition-all"
          :class="[
            value === false
              ? 'bg-red-600 text-white border-red-600'
              : 'bg-white text-red-600 border-red-300 hover:bg-red-50'
          ]"
        >
          ✗ Sai
        </button>
      </div>
    </template>

    <!-- Band Score Selection (Band Mode) -->
    <template v-else-if="mode === 'band'">
      <div class="band-selector space-y-3">
        <!-- Band Score Dropdown -->
        <div class="flex items-center space-x-3">
          <label class="block text-sm font-medium text-gray-700">Band Score (0-9)</label>
          <select
            v-model="localBandScore"
            @change="handleBandChange"
            :disabled="disabled"
            class="w-32 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-lg font-bold"
          >
            <option :value="null">-</option>
            <option v-for="band in bandScores" :key="band" :value="band">
              {{ band }}
            </option>
          </select>
        </div>

        <!-- Quick Select Buttons -->
        <div v-if="showQuickSelect" class="flex flex-wrap gap-1">
          <button
            v-for="band in quickSelectBands"
            :key="band"
            @click="selectBand(band)"
            :disabled="disabled"
            class="px-3 py-1 text-sm rounded-lg border hover:bg-blue-100 transition"
            :class="localBandScore === band ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300'"
          >
            {{ band }}
          </button>
        </div>
      </div>
    </template>

    <!-- Criteria-based (Criteria Mode) - Slot for custom implementation -->
    <template v-else-if="mode === 'criteria'">
      <slot name="criteria">
        <div class="text-sm text-gray-500 italic">
          Use GradingCriteria component for criteria-based grading
        </div>
      </slot>
    </template>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { BAND_SCORES } from '../../../composables/grading/useScoring'

const props = defineProps({
  mode: {
    type: String,
    default: 'binary',
    validator: (v) => ['binary', 'band', 'criteria'].includes(v)
  },
  value: {
    type: [Boolean, Number],
    default: null
  },
  bandScore: {
    type: Number,
    default: null
  },
  disabled: {
    type: Boolean,
    default: false
  },
  showQuickSelect: {
    type: Boolean,
    default: true
  },
  quickSelectBands: {
    type: Array,
    default: () => [5, 5.5, 6, 6.5, 7, 7.5, 8]
  }
})

const emit = defineEmits(['grade', 'update:bandScore'])

// Band scores
const bandScores = BAND_SCORES

// Local band score
const localBandScore = ref(props.bandScore)

// Watch for external changes
watch(() => props.bandScore, (newVal) => {
  localBandScore.value = newVal
})

// Handle band score change
const handleBandChange = () => {
  emit('update:bandScore', localBandScore.value)
  if (localBandScore.value !== null) {
    emit('grade', localBandScore.value)
  }
}

// Quick select band
const selectBand = (band) => {
  if (props.disabled) return
  localBandScore.value = band
  handleBandChange()
}

// Handle grade for binary mode
const handleGrade = (isCorrect) => {
  console.log('[GradingControls] 🎯 Button clicked! isCorrect:', isCorrect)
  console.log('[GradingControls] 📊 disabled:', props.disabled)
  console.log('[GradingControls] 📊 current value:', props.value)

  if (props.disabled) {
    console.log('[GradingControls] ⚠️ Button is disabled, not emitting')
    return
  }

  console.log('[GradingControls] ✅ Emitting grade event with value:', isCorrect)
  emit('grade', isCorrect)
}
</script>

<style scoped>
.grading-controls {
  @apply relative;
}

button:disabled {
  @apply opacity-50 cursor-not-allowed;
}
</style>
