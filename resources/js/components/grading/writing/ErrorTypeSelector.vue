<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30" @click="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden" @click.stop>
      <!-- Header -->
      <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-white">
            {{ t('course.select_error_type') || 'Select Error Type' }}
          </h3>
          <button @click="$emit('close')" class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Content -->
      <div class="p-6 overflow-y-auto max-h-[calc(80vh-80px)]">
        <!-- Comment Input -->
        <div v-if="selectedError" class="mb-4 p-4 bg-orange-50 rounded-lg border border-orange-200">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: selectedError.color }"></div>
              <span class="font-semibold text-orange-900">{{ selectedError.code }}</span>
            </div>
            <button @click="selectedError = null" class="text-orange-600 hover:text-orange-800">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <textarea
            v-model="errorComment"
            ref="commentInput"
            class="w-full border border-orange-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-transparent resize-none text-sm"
            rows="3"
            :placeholder="t('course.error_comment_placeholder') || 'Nhập nhận xét về lỗi...'"
            @keydown.enter.prevent="submitError"
          ></textarea>
          <div class="flex justify-end gap-2 mt-2">
            <button
              @click="selectedError = null; errorComment = ''"
              class="px-3 py-1.5 text-sm border border-gray-300 text-gray-700 rounded hover:bg-gray-50"
            >
              {{ t('common.cancel') || 'Hủy' }}
            </button>
            <button
              @click="submitError"
              :disabled="!errorComment.trim()"
              class="px-3 py-1.5 text-sm bg-orange-500 text-white rounded hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ t('common.submit') || 'Xác nhận' }}
            </button>
          </div>
        </div>

        <!-- Category Tabs -->
        <div v-if="!selectedError" class="flex gap-2 mb-4 overflow-x-auto pb-2">
          <button
            v-for="category in categories"
            :key="category"
            @click="selectedCategory = category"
            :class="[
              'px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-colors',
              selectedCategory === category
                ? 'bg-orange-500 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            {{ getCategoryName(category, currentLanguageCode) }}
          </button>
        </div>

        <!-- Error Types Grid -->
        <div v-if="!selectedError" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <button
            v-for="errorType in filteredErrorTypes"
            :key="errorType.code"
            @click="selectErrorType(errorType)"
            class="text-left p-4 border-2 border-gray-200 rounded-lg hover:border-orange-500 hover:bg-orange-50 transition-all group"
          >
            <div class="flex items-start gap-3">
              <!-- Color Indicator -->
              <div
                class="w-4 h-4 rounded-full mt-1 flex-shrink-0"
                :style="{ backgroundColor: errorType.color }"
              ></div>

              <!-- Error Info -->
              <div class="flex-1">
                <div class="font-semibold text-gray-900 group-hover:text-orange-600">
                  {{ errorType.code }}
                </div>
                <div class="text-sm text-gray-600 mt-1">
                  {{ getLocalizedText(errorType.name, currentLanguageCode) }}
                </div>
              </div>
            </div>
          </button>
        </div>

        <!-- Empty State -->
        <div v-if="filteredErrorTypes.length === 0" class="text-center py-12 text-gray-500">
          {{ t('course.no_error_types') || 'No error types found' }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'
import { useI18n } from '../../../composables/useI18n'
import {
  ERROR_CATEGORIES,
  ERROR_TYPES,
  getCategoryName,
  getLocalizedText,
  getErrorTypesByCategory
} from '../../../constants/writingErrorTypes'

defineProps({
  position: {
    type: Object,
    default: () => ({ x: 0, y: 0 })
  }
})

const emit = defineEmits(['select', 'close'])

const { t, currentLanguageCode } = useI18n()

// State
const selectedCategory = ref(ERROR_CATEGORIES.VOCABULARY)
const selectedError = ref(null)
const errorComment = ref('')
const commentInput = ref(null)

// Get all categories
const categories = computed(() => Object.values(ERROR_CATEGORIES))

// Get error types by category
const errorTypesByCategory = computed(() => getErrorTypesByCategory())

// Filtered error types based on selected category
const filteredErrorTypes = computed(() => {
  return errorTypesByCategory.value[selectedCategory.value] || []
})

// Select error type
const selectErrorType = (errorType) => {
  selectedError.value = errorType
  nextTick(() => {
    if (commentInput.value) {
      commentInput.value.focus()
    }
  })
}

// Submit error with comment
const submitError = () => {
  if (errorComment.value.trim() && selectedError.value) {
    emit('select', {
      ...selectedError.value,
      comment: errorComment.value.trim()
    })
    selectedError.value = null
    errorComment.value = ''
  }
}
</script>
