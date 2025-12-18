<template>
  <div class="essay-question">
    <!-- Question Title / Task -->
    <div class="mb-4">
      <p class="text-gray-800 font-medium" v-html="question.title"></p>

      <!-- Task content (e.g., IELTS Writing Task prompt) -->
      <div v-if="question.content?.task" class="mt-3 p-4 bg-gray-50 rounded-lg border">
        <div v-html="question.content.task"></div>
      </div>

      <!-- Image if any (e.g., graph for Task 1) -->
      <img v-if="question.image_url" :src="question.image_url" class="mt-3 max-w-lg rounded-lg border" />
    </div>

    <!-- Word count requirements -->
    <div v-if="minWords || maxWords" class="mb-3 text-sm text-gray-600">
      <span v-if="minWords">Tối thiểu: {{ minWords }} từ</span>
      <span v-if="minWords && maxWords" class="mx-2">|</span>
      <span v-if="maxWords">Tối đa: {{ maxWords }} từ</span>
    </div>

    <!-- Text area -->
    <div class="relative">
      <textarea
        v-model="textAnswer"
        :disabled="disabled || showResult"
        class="w-full h-64 p-4 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-y"
        :class="{
          'bg-gray-50': disabled || showResult,
          'border-yellow-500': !meetsMinWords && textAnswer.length > 0,
          'border-red-500': exceedsMaxWords
        }"
        placeholder="Nhập bài viết của bạn..."
        @input="handleInput"
      ></textarea>

      <!-- Word count -->
      <div class="absolute bottom-3 right-3 text-sm" :class="wordCountClass">
        {{ wordCount }} từ
      </div>
    </div>

    <!-- Word count warning -->
    <div v-if="!meetsMinWords && textAnswer.length > 0" class="mt-2 text-sm text-yellow-600">
      ⚠️ Bài viết cần tối thiểu {{ minWords }} từ (còn thiếu {{ minWords - wordCount }} từ)
    </div>
    <div v-if="exceedsMaxWords" class="mt-2 text-sm text-red-600">
      ⚠️ Bài viết vượt quá {{ maxWords }} từ (dư {{ wordCount - maxWords }} từ)
    </div>

    <!-- Grading criteria (show after grading) -->
    <div v-if="showResult && gradingCriteria" class="mt-4 p-4 bg-gray-50 rounded-lg">
      <h4 class="font-medium text-gray-800 mb-3">Đánh giá chi tiết:</h4>
      <div class="grid grid-cols-2 gap-4">
        <div v-for="(score, criterion) in gradingCriteria" :key="criterion" class="flex justify-between">
          <span class="text-gray-600">{{ formatCriterion(criterion) }}:</span>
          <span class="font-medium">{{ score }}</span>
        </div>
      </div>
    </div>

    <!-- Feedback -->
    <div v-if="showResult && feedback" class="mt-4 p-3 bg-blue-50 rounded-lg">
      <p class="text-sm text-blue-800">
        <strong>Nhận xét:</strong> {{ feedback }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

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
  gradingCriteria: {
    type: Object,
    default: null
  },
  feedback: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue', 'answer'])

const textAnswer = ref(props.modelValue || '')

// Word count requirements from question content
const minWords = computed(() => props.question.content?.min_words || props.question.content?.minWords || null)
const maxWords = computed(() => props.question.content?.max_words || props.question.content?.maxWords || null)

const wordCount = computed(() => {
  if (!textAnswer.value) return 0
  return textAnswer.value.trim().split(/\s+/).filter(w => w.length > 0).length
})

const meetsMinWords = computed(() => {
  if (!minWords.value) return true
  return wordCount.value >= minWords.value
})

const exceedsMaxWords = computed(() => {
  if (!maxWords.value) return false
  return wordCount.value > maxWords.value
})

const wordCountClass = computed(() => {
  if (exceedsMaxWords.value) return 'text-red-600'
  if (!meetsMinWords.value && textAnswer.value.length > 0) return 'text-yellow-600'
  if (meetsMinWords.value) return 'text-green-600'
  return 'text-gray-500'
})

watch(() => props.modelValue, (newVal) => {
  textAnswer.value = newVal || ''
})

function handleInput() {
  emit('update:modelValue', textAnswer.value)
  emit('answer', {
    question_id: props.question.id,
    text_answer: textAnswer.value,
    word_count: wordCount.value
  })
}

function formatCriterion(key) {
  const labels = {
    task_achievement: 'Task Achievement',
    coherence_cohesion: 'Coherence & Cohesion',
    lexical_resource: 'Lexical Resource',
    grammatical_range: 'Grammatical Range & Accuracy'
  }
  return labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}
</script>
