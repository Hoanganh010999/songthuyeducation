<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30" @click="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full" @click.stop>
      <!-- Header -->
      <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-white">
            {{ t('course.delete_comment') || 'Nhận xét về nội dung cần xóa' }}
          </h3>
          <button @click="$emit('close')" class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Content -->
      <div class="p-6">
        <p class="text-sm text-gray-600 mb-4">
          {{ t('course.delete_comment_hint') || 'Nhập lý do tại sao cần xóa phần text này:' }}
        </p>

        <textarea
          v-model="comment"
          ref="commentInput"
          class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none"
          rows="3"
          :placeholder="t('course.delete_comment_placeholder') || 'Ví dụ: Không cần thiết, lặp lại ý...'"
          @keydown.enter.prevent="submit"
        ></textarea>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 mt-4">
          <button
            @click="$emit('close')"
            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
          >
            {{ t('common.cancel') || 'Hủy' }}
          </button>
          <button
            @click="submit"
            :disabled="!comment.trim()"
            class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            {{ t('common.submit') || 'Xác nhận' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from '../../../composables/useI18n'

defineProps({
  position: {
    type: Object,
    default: () => ({ x: 0, y: 0 })
  }
})

const emit = defineEmits(['submit', 'close'])

const { t } = useI18n()

const comment = ref('')
const commentInput = ref(null)

onMounted(() => {
  // Auto-focus input
  if (commentInput.value) {
    commentInput.value.focus()
  }
})

const submit = () => {
  if (comment.value.trim()) {
    emit('submit', comment.value.trim())
    comment.value = ''
  }
}
</script>
