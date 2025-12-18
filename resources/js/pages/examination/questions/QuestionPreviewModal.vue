<template>
  <div class="modal modal-open">
    <div class="modal-box max-w-3xl">
      <h3 class="font-bold text-lg mb-4">Xem trước câu hỏi</h3>

      <div class="bg-gray-50 rounded-lg p-6">
        <!-- Question Type Badge -->
        <div class="mb-4">
          <span class="badge badge-primary">{{ getTypeLabel(question.type) }}</span>
          <span class="badge badge-ghost ml-2">{{ getDifficultyLabel(question.difficulty) }}</span>
          <span class="badge badge-outline ml-2">{{ question.points }} điểm</span>
        </div>

        <!-- Question Title -->
        <h4 class="text-lg font-semibold mb-3">{{ question.title }}</h4>

        <!-- Question Content -->
        <div v-if="question.content" class="prose max-w-none mb-4" v-html="question.content"></div>

        <!-- Multiple Choice Options -->
        <div v-if="hasOptions" class="space-y-2">
          <div
            v-for="(option, index) in question.options"
            :key="index"
            class="flex items-center p-3 rounded-lg border"
            :class="option.is_correct ? 'border-success bg-success/10' : 'border-base-300'"
          >
            <span class="w-8 h-8 rounded-full flex items-center justify-center mr-3"
              :class="option.is_correct ? 'bg-success text-white' : 'bg-base-200'">
              {{ option.label || String.fromCharCode(65 + index) }}
            </span>
            <span>{{ option.content }}</span>
            <svg v-if="option.is_correct" class="w-5 h-5 ml-auto text-success" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
          </div>
        </div>

        <!-- Text Answer -->
        <div v-if="hasTextAnswer && question.correct_answer" class="mt-4">
          <div class="font-medium text-gray-600 mb-2">Đáp án đúng:</div>
          <div class="p-3 bg-success/10 border border-success rounded-lg">
            {{ question.correct_answer }}
          </div>
        </div>

        <!-- Essay/Audio Info -->
        <div v-if="isProductionType" class="mt-4 p-4 bg-info/10 rounded-lg">
          <div class="text-info font-medium mb-2">
            {{ question.type === 'essay' ? 'Bài tự luận' : 'Bài ghi âm' }}
          </div>
          <div v-if="question.settings?.min_words" class="text-sm">
            Số từ tối thiểu: {{ question.settings.min_words }}
          </div>
          <div v-if="question.settings?.max_words" class="text-sm">
            Số từ tối đa: {{ question.settings.max_words }}
          </div>
        </div>

        <!-- Explanation -->
        <div v-if="question.explanation" class="mt-4">
          <div class="font-medium text-gray-600 mb-2">Giải thích:</div>
          <div class="p-3 bg-base-200 rounded-lg text-sm">
            {{ question.explanation }}
          </div>
        </div>

        <!-- Tags -->
        <div v-if="question.tags?.length" class="mt-4">
          <div class="flex flex-wrap gap-1">
            <span v-for="tag in question.tags" :key="tag" class="badge badge-outline badge-sm">
              {{ tag }}
            </span>
          </div>
        </div>
      </div>

      <div class="modal-action">
        <button @click="$emit('close')" class="btn">Đóng</button>
      </div>
    </div>
    <div class="modal-backdrop" @click="$emit('close')"></div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  question: {
    type: Object,
    required: true,
  },
})

defineEmits(['close'])

const hasOptions = computed(() => {
  return ['multiple_choice', 'multiple_response', 'true_false', 'true_false_not_given'].includes(props.question.type)
})

const hasTextAnswer = computed(() => {
  return ['fill_blanks', 'fill_blanks_drag', 'short_answer'].includes(props.question.type)
})

const isProductionType = computed(() => {
  return ['essay', 'audio_response'].includes(props.question.type)
})

function getTypeLabel(type) {
  const labels = {
    multiple_choice: 'Trắc nghiệm 1 đáp án',
    multiple_response: 'Trắc nghiệm nhiều đáp án',
    true_false: 'Đúng/Sai',
    true_false_not_given: 'True/False/Not Given',
    fill_blanks: 'Điền vào chỗ trống',
    short_answer: 'Trả lời ngắn',
    matching: 'Nối cột',
    essay: 'Tự luận',
    audio_response: 'Ghi âm',
  }
  return labels[type] || type
}

function getDifficultyLabel(difficulty) {
  const map = { easy: 'Dễ', medium: 'Trung bình', hard: 'Khó' }
  return map[difficulty] || difficulty
}
</script>
