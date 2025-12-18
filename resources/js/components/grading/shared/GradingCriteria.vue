<template>
  <div class="grading-criteria bg-blue-50 rounded-lg p-5">
    <h4 class="font-medium text-blue-800 mb-4">{{ criteriaTitle }}</h4>

    <!-- Dynamic criteria based on type -->
    <div class="space-y-3">
      <div
        v-for="criterion in criteriaList"
        :key="criterion.key"
        class="bg-white rounded-lg p-4 border-l-4"
        :class="`border-${criterion.color}-500`"
      >
        <!-- Criterion Header -->
        <div class="flex justify-between items-center mb-2">
          <span class="font-medium text-gray-800">{{ criterion.label }}</span>
          <input
            v-model.number="localCriteria[criterion.key].score"
            type="number"
            step="0.5"
            min="0"
            max="9"
            :disabled="disabled"
            class="w-20 px-2 py-1 text-center border rounded font-bold"
            :class="`bg-${criterion.color}-50`"
            @input="handleCriteriaChange"
          />
        </div>

        <!-- Criterion Feedback -->
        <textarea
          v-model="localCriteria[criterion.key].feedback"
          :placeholder="`Nhận xét ${criterion.label}...`"
          rows="3"
          :disabled="disabled"
          class="w-full text-sm text-gray-600 border rounded px-2 py-1 mt-2 resize-none focus:ring-2 focus:ring-blue-500"
          @input="handleCriteriaChange"
        ></textarea>
      </div>
    </div>

    <!-- Overall Band Score Display -->
    <div v-if="showOverallBand" class="mt-4 bg-white rounded-lg p-4 border-2 border-blue-300">
      <div class="flex justify-between items-center">
        <span class="text-sm font-medium text-gray-700">Overall Band Score:</span>
        <span class="text-2xl font-bold text-blue-600">
          {{ overallBandScore !== null ? overallBandScore.toFixed(1) : '-' }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { calculateCriteriaBand } from '../../../composables/grading/useScoring'

const props = defineProps({
  type: {
    type: String,
    required: true,
    validator: (v) => ['writing', 'speaking'].includes(v)
  },
  modelValue: {
    type: Object,
    default: () => ({})
  },
  disabled: {
    type: Boolean,
    default: false
  },
  showOverallBand: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:modelValue'])

// Criteria definitions
const criteriaDefinitions = {
  writing: [
    { key: 'task_achievement', label: 'Task Achievement', color: 'purple' },
    { key: 'coherence_cohesion', label: 'Coherence & Cohesion', color: 'blue' },
    { key: 'lexical_resource', label: 'Lexical Resource', color: 'green' },
    { key: 'grammatical_range', label: 'Grammatical Range & Accuracy', color: 'orange' }
  ],
  speaking: [
    { key: 'fluency_coherence', label: 'Fluency & Coherence', color: 'purple' },
    { key: 'lexical_resource', label: 'Lexical Resource', color: 'blue' },
    { key: 'grammatical_range', label: 'Grammatical Range & Accuracy', color: 'green' },
    { key: 'pronunciation', label: 'Pronunciation', color: 'orange' }
  ]
}

// Criteria list based on type
const criteriaList = computed(() => criteriaDefinitions[props.type])

// Criteria title
const criteriaTitle = computed(() =>
  props.type === 'writing' ? 'IELTS Writing Criteria' : 'IELTS Speaking Criteria'
)

// Initialize local criteria
const initializeCriteria = () => {
  const criteria = {}
  criteriaList.value.forEach(c => {
    criteria[c.key] = props.modelValue[c.key] || { score: null, feedback: '' }
  })
  return criteria
}

const localCriteria = ref(initializeCriteria())

// Watch for external changes
watch(() => props.modelValue, (newVal) => {
  if (newVal && Object.keys(newVal).length > 0) {
    localCriteria.value = initializeCriteria()
  }
}, { deep: true })

// Calculate overall band score
const overallBandScore = computed(() => {
  return calculateCriteriaBand(localCriteria.value)
})

// Handle criteria change
const handleCriteriaChange = () => {
  emit('update:modelValue', { ...localCriteria.value })
}
</script>

<style scoped>
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type="number"] {
  -moz-appearance: textfield;
}
</style>
