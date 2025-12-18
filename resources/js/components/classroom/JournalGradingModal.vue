<template>
  <TransitionRoot as="template" :show="show">
    <Dialog as="div" class="relative z-50" @close="handleClose">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-6xl">
              <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                <div class="flex items-start justify-between mb-4">
                  <DialogTitle as="h3" class="text-xl font-semibold leading-6 text-gray-900">
                    Chấm Learning Journal
                  </DialogTitle>
                  <button
                    type="button"
                    class="rounded-md bg-white text-gray-400 hover:text-gray-500"
                    @click="handleClose"
                  >
                    <XMarkIcon class="h-6 w-6" />
                  </button>
                </div>

                <div v-if="journal" class="space-y-4">
                  <!-- Journal Info -->
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="grid grid-cols-3 gap-4 text-sm">
                      <div>
                        <span class="text-gray-500">Học viên:</span>
                        <span class="ml-2 font-medium">{{ journal.student?.name || 'N/A' }}</span>
                      </div>
                      <div>
                        <span class="text-gray-500">Ngày:</span>
                        <span class="ml-2 font-medium">{{ formatDate(journal.journal_date) }}</span>
                      </div>
                      <div>
                        <span class="text-gray-500">Trạng thái:</span>
                        <span class="ml-2 font-medium">{{ getStatusLabel(journal.status) }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- AI Grading -->
                  <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="flex items-center justify-between">
                      <div>
                        <div class="text-sm font-medium text-gray-700">Chấm bài tự động với AI</div>
                        <div v-if="loadingSettings" class="text-xs text-gray-500 mt-1">Đang tải cấu hình AI...</div>
                        <div v-else-if="aiSettings && aiSettings.openai" class="text-xs text-gray-500 mt-1">
                          Sử dụng {{ aiSettings.openai.provider.toUpperCase() }} - {{ aiSettings.openai.model }}
                        </div>
                        <div v-else-if="aiSettings && aiSettings.anthropic" class="text-xs text-gray-500 mt-1">
                          Sử dụng {{ aiSettings.anthropic.provider.toUpperCase() }} - {{ aiSettings.anthropic.model }}
                        </div>
                        <div v-else class="text-xs text-red-500 mt-1">Chưa cấu hình AI</div>
                      </div>
                      <button
                        @click="gradeWithAI"
                        :disabled="aiGrading || loadingSettings"
                        class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors disabled:opacity-50 flex items-center gap-2"
                      >
                        <svg v-if="aiGrading" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ aiGrading ? 'Đang chấm...' : 'Chấm bằng AI' }}
                      </button>
                    </div>
                  </div>

                  <!-- Journal Content -->
                  <div>
                    <WritingGradingEditor
                      :content="journal.content"
                      :initial-annotations="annotations"
                      :editable="true"
                      @update:annotations="annotations = $event"
                      @update:feedback="aiFeedback = $event"
                      @update:score="score = $event"
                    />
                  </div>

                  <!-- AI Feedback -->
                  <div v-if="aiFeedback" class="border border-purple-200 rounded-lg p-4 bg-purple-50">
                    <h4 class="font-semibold text-purple-900 mb-3">Nhận xét</h4>
                    <p class="text-purple-800 whitespace-pre-wrap">{{ aiFeedback }}</p>
                  </div>

                  <!-- Score Input -->
                  <div class="bg-green-50 p-4 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Điểm (0-100)</label>
                    <input
                      v-model.number="score"
                      type="number"
                      min="0"
                      max="100"
                      step="0.5"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                    />
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                <button
                  type="button"
                  @click="saveGrading"
                  :disabled="!score || saving"
                  class="inline-flex w-full justify-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700 sm:ml-3 sm:w-auto disabled:opacity-50"
                >
                  {{ saving ? 'Đang lưu...' : 'Lưu điểm' }}
                </button>
                <button
                  type="button"
                  @click="handleClose"
                  class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                >
                  Đóng
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import WritingGradingEditor from '@/components/grading/writing/WritingGradingEditor.vue'
import { format, parseISO } from 'date-fns'
import { vi } from 'date-fns/locale'

const props = defineProps({
  show: {
    type: Boolean,
    required: true
  },
  journal: {
    type: Object,
    required: true
  },
  mode: {
    type: String,
    default: 'grading' // 'grading' or 'review'
  }
})

const emit = defineEmits(['close', 'graded'])

// State
const annotations = ref([])
const aiFeedback = ref('')
const score = ref(null)
const saving = ref(false)
const aiGrading = ref(false)

// AI settings from database
const aiSettings = ref(null)
const loadingSettings = ref(false)

// Load AI settings from database
const loadAISettings = async () => {
  try {
    loadingSettings.value = true
    // ✅ Load AI settings from examination_grading module (used for grading)
    const response = await axios.get('/api/quality/ai-settings', {
      params: {
        module: 'examination_grading'  // Use Examination grading AI settings
      }
    })

    if (response.data.success) {
      aiSettings.value = response.data.data
      console.log('[JournalGradingModal] AI settings loaded from examination_grading:', aiSettings.value)
    }
  } catch (error) {
    console.error('[JournalGradingModal] Error loading AI settings:', error)
  } finally {
    loadingSettings.value = false
  }
}

// Load settings on mount
onMounted(() => {
  loadAISettings()
})

// Load existing grading if available
watch(() => props.journal, (newJournal) => {
  if (newJournal) {
    annotations.value = newJournal.annotations || []
    aiFeedback.value = newJournal.ai_feedback || ''
    score.value = newJournal.score || null
  }
}, { immediate: true })

// Methods
const formatDate = (dateStr) => {
  try {
    return format(parseISO(dateStr), 'dd/MM/yyyy', { locale: vi })
  } catch (e) {
    return dateStr
  }
}

const getStatusLabel = (status) => {
  const labels = {
    draft: 'Bản nháp',
    submitted: 'Đã nộp',
    graded: 'Đã chấm'
  }
  return labels[status] || status
}

const gradeWithAI = async () => {
  try {
    // Get active AI provider from settings
    if (!aiSettings.value || Object.keys(aiSettings.value).length === 0) {
      alert('Chưa cấu hình AI. Vui lòng liên hệ Admin để thiết lập AI settings.')
      return
    }

    // Find first active provider (prioritize openai)
    let provider = 'openai'
    let model = 'gpt-5.2'

    if (aiSettings.value.openai && aiSettings.value.openai.is_active) {
      provider = 'openai'
      model = aiSettings.value.openai.model
    } else if (aiSettings.value.anthropic && aiSettings.value.anthropic.is_active) {
      provider = 'anthropic'
      model = aiSettings.value.anthropic.model
    } else if (aiSettings.value.azure && aiSettings.value.azure.is_active) {
      provider = 'azure'
      model = aiSettings.value.azure.model
    } else {
      alert('Không có AI provider nào được kích hoạt. Vui lòng liên hệ Admin.')
      return
    }

    console.log('[JournalGradingModal] Using AI settings:', { provider, model })

    aiGrading.value = true

    const response = await axios.post(
      `/api/course/learning-journals/${props.journal.id}/grade-with-ai`,
      {
        provider,
        model
      }
    )

    if (response.data.success) {
      const result = response.data.data
      annotations.value = result.annotations || []
      aiFeedback.value = result.feedback || ''
      score.value = result.score || null
    }
  } catch (error) {
    console.error('[JournalGradingModal] Error grading with AI:', error)
    alert('Lỗi khi chấm bài với AI: ' + (error.response?.data?.message || error.message))
  } finally {
    aiGrading.value = false
  }
}

const saveGrading = async () => {
  try {
    saving.value = true

    const response = await axios.post(
      `/api/course/learning-journals/${props.journal.id}/save-grading`,
      {
        score: score.value,
        annotations: annotations.value,
        ai_feedback: aiFeedback.value
      }
    )

    if (response.data.success) {
      emit('graded', response.data.data)
    }
  } catch (error) {
    console.error('[JournalGradingModal] Error saving grading:', error)
    alert('Lỗi khi lưu điểm: ' + (error.response?.data?.message || error.message))
  } finally {
    saving.value = false
  }
}

const handleAnnotationClick = (annotation) => {
  console.log('[JournalGradingModal] Annotation clicked:', annotation)
}

const handleClose = () => {
  emit('close')
}
</script>
