<template>
  <div class="question-renderer">
    <!-- Question number and points -->
    <div class="flex items-center justify-between mb-3">
      <span class="text-sm font-medium text-gray-500">
        Câu {{ questionNumber }}
      </span>
      <span v-if="question.points" class="text-sm text-gray-500">
        {{ question.points }} điểm
      </span>
    </div>

    <!-- Render appropriate question type -->
    <component
      :is="questionComponent"
      :question="question"
      :modelValue="modelValue"
      :disabled="disabled"
      :showResult="showResult"
      :gradingCriteria="gradingCriteria"
      :feedback="feedback"
      @update:modelValue="$emit('update:modelValue', $event)"
      @answer="$emit('answer', $event)"
    />
  </div>
</template>

<script setup>
import { computed, defineAsyncComponent } from 'vue'

// Question type components
import MultipleChoice from './MultipleChoice.vue'
import FillBlanks from './FillBlanks.vue'
import TrueFalse from './TrueFalse.vue'
import Essay from './Essay.vue'

// Lazy load less common components
const Matching = defineAsyncComponent(() => import('./Matching.vue'))
const Ordering = defineAsyncComponent(() => import('./Ordering.vue'))
const ShortAnswer = defineAsyncComponent(() => import('./ShortAnswer.vue'))
const AudioResponse = defineAsyncComponent(() => import('./AudioResponse.vue'))
const DragDrop = defineAsyncComponent(() => import('./DragDrop.vue'))
const TableCompletion = defineAsyncComponent(() => import('./TableCompletion.vue'))

const props = defineProps({
  question: {
    type: Object,
    required: true
  },
  questionNumber: {
    type: Number,
    default: 1
  },
  modelValue: {
    default: null
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

defineEmits(['update:modelValue', 'answer'])

// Map question types to components
const componentMap = {
  multiple_choice: MultipleChoice,
  multiple_response: MultipleChoice, // Same component, different behavior
  fill_blanks: FillBlanks,
  true_false: TrueFalse,
  true_false_ng: TrueFalse,
  essay: Essay,
  short_answer: ShortAnswer,
  matching: Matching,
  matching_headings: Matching,
  matching_features: Matching,
  matching_sentence_endings: Matching,
  ordering: Ordering,
  audio_response: AudioResponse,
  sentence_completion: FillBlanks,
  summary_completion: FillBlanks,
  note_completion: FillBlanks,
  table_completion: TableCompletion,
  drag_drop: DragDrop,
  labeling: DragDrop,
  hotspot: DragDrop
}

const questionComponent = computed(() => {
  return componentMap[props.question.type] || MultipleChoice
})
</script>
