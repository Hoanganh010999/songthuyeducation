<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="$emit('close')"></div>

      <div class="relative bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <!-- Header -->
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between z-10">
          <div>
            <h2 class="text-lg font-semibold text-gray-800">Tạo câu hỏi bằng AI</h2>
            <p class="text-sm text-gray-500 mt-1">Sử dụng AI để tạo câu hỏi nhanh chóng</p>
          </div>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="generateQuestions" class="p-6 space-y-6">
          <!-- Topic -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Chủ đề / Nội dung *</label>
            <textarea v-model="form.topic" rows="3" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Nhập chủ đề hoặc nội dung cần tạo câu hỏi..."></textarea>
            <p class="text-xs text-gray-500 mt-1">Mô tả chi tiết về chủ đề cần tạo câu hỏi</p>
          </div>

          <!-- Settings -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Môn học</label>
              <select v-model="form.subject_id" @change="onSubjectChange" :disabled="loadingSubjects" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100">
                <option value="">{{ loadingSubjects ? 'Đang tải...' : 'Chọn môn học' }}</option>
                <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Phân loại</label>
              <select v-model="form.subject_category_id" :disabled="!form.subject_id || loadingCategories" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100">
                <option value="">{{ form.subject_id ? 'Không chọn' : 'Chọn môn học trước' }}</option>
                <option v-for="category in subjectCategories" :key="category.id" :value="category.id">{{ category.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Độ khó *</label>
              <select v-model="form.difficulty" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="easy">Dễ</option>
                <option value="medium">Trung bình</option>
                <option value="hard">Khó</option>
                <option value="expert">Chuyên gia</option>
              </select>
            </div>
          </div>

          <!-- Question Types -->
          <div class="border rounded-lg p-4 bg-gray-50">
            <div class="flex items-center justify-between mb-3">
              <label class="text-sm font-medium text-gray-700">Loại câu hỏi cần tạo *</label>
              <button type="button" @click="addQuestionType" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Thêm loại câu hỏi
              </button>
            </div>

            <div v-if="form.question_types.length === 0" class="text-center py-8 text-gray-500 text-sm">
              <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <p>Chưa có loại câu hỏi nào được chọn</p>
              <p class="text-xs mt-1">Nhấn "Thêm loại câu hỏi" để bắt đầu</p>
            </div>

            <div v-else class="space-y-3">
              <div v-for="(qt, index) in form.question_types" :key="index" class="flex items-center gap-3 p-3 bg-white border rounded-lg">
                <div class="flex-1">
                  <select v-model="qt.type" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">Chọn loại câu hỏi</option>
                    <optgroup label="Basic Types">
                      <option value="multiple_choice">Multiple Choice</option>
                      <option value="multiple_response">Multiple Response</option>
                      <option value="true_false">True/False</option>
                      <option value="true_false_ng">True/False/Not Given</option>
                      <option value="short_answer">Short Answer</option>
                      <option value="essay">Essay</option>
                      <option value="audio_response">Audio Response</option>
                    </optgroup>
                    <optgroup label="Fill & Complete">
                      <option value="fill_blanks">Fill in the Blanks</option>
                      <option value="sentence_completion">Sentence Completion</option>
                      <option value="summary_completion">Summary Completion</option>
                      <option value="note_completion">Note Completion</option>
                      <option value="table_completion">Table Completion</option>
                    </optgroup>
                    <optgroup label="Matching & Ordering">
                      <option value="matching">Matching</option>
                      <option value="matching_headings">Matching Headings</option>
                      <option value="matching_features">Matching Features</option>
                      <option value="matching_sentence_endings">Matching Sentence Endings</option>
                      <option value="ordering">Ordering</option>
                    </optgroup>
                  </select>
                </div>
                <div class="w-32">
                  <input v-model.number="qt.count" type="number" min="1" max="20" required placeholder="Số lượng" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" />
                </div>
                <button type="button" @click="removeQuestionType(index)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
              <div class="text-right text-sm text-gray-600">Tổng: <strong>{{ totalQuestionCount }}</strong> câu hỏi</div>
            </div>
          </div>

          <!-- Generated Questions Preview -->
          <div v-if="generatedQuestions.length > 0" class="border rounded-lg p-4 bg-gray-50">
            <h3 class="font-medium text-gray-800 mb-3">Đã tạo {{ generatedQuestions.length }} câu hỏi</h3>
            <div class="space-y-3 max-h-96 overflow-y-auto">
              <div v-for="(question, index) in generatedQuestions" :key="index" class="p-3 bg-white rounded border hover:border-blue-300" :class="{ 'border-blue-500 bg-blue-50': selectedQuestions.includes(index) }">
                <div class="flex items-start gap-3">
                  <input type="checkbox" :checked="selectedQuestions.includes(index)" class="mt-1" @click="toggleQuestionSelection(index)" />
                  <div class="flex-1 cursor-pointer" @click="toggleQuestionSelection(index)">
                    <div class="flex items-center gap-2 mb-1">
                      <span class="px-2 py-0.5 text-xs bg-purple-100 text-purple-700 rounded">{{ question.type }}</span>
                      <span class="font-medium text-gray-800">{{ index + 1 }}. {{ question.title }}</span>
                    </div>
                    <div v-if="question.options" class="mt-2 ml-4 text-sm space-y-1">
                      <div v-for="(option, optIdx) in question.options" :key="optIdx">
                        <span :class="{ 'text-green-600 font-medium': option.is_correct }">
                          {{ String.fromCharCode(65 + optIdx) }}. {{ option.text }}
                          <span v-if="option.is_correct" class="ml-1">✓</span>
                        </span>
                      </div>
                    </div>
                  </div>
                  <button type="button" @click.stop="openPreview(question)" class="flex-shrink-0 px-3 py-1.5 text-sm text-blue-600 hover:bg-blue-50 border border-blue-300 rounded-lg transition-colors">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Preview
                  </button>
                </div>
              </div>
            </div>
            <div class="mt-3 flex justify-between items-center">
              <button type="button" @click="selectAllQuestions" class="text-sm text-blue-600 hover:text-blue-800">
                {{ selectedQuestions.length === generatedQuestions.length ? 'Bỏ chọn tất cả' : 'Chọn tất cả' }}
              </button>
              <span class="text-sm text-gray-600">{{ selectedQuestions.length }} đã chọn</span>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="generating" class="flex items-center justify-center py-8">
            <div class="text-center">
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
              <p class="mt-4 text-gray-600">Đang tạo câu hỏi...</p>
            </div>
          </div>

          <!-- Error Message -->
          <div v-if="errorMessage" class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-800 text-sm">{{ errorMessage }}</p>
          </div>

          <!-- Footer -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <button type="button" @click="$emit('close')" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Hủy</button>
            <button v-if="generatedQuestions.length === 0" type="submit" :disabled="generating || form.question_types.length === 0" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
              {{ generating ? 'Đang tạo...' : 'Tạo câu hỏi' }}
            </button>
            <button v-else type="button" @click="saveSelectedQuestions" :disabled="selectedQuestions.length === 0 || saving" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50">
              {{ saving ? 'Đang lưu...' : 'Lưu ' + selectedQuestions.length + ' câu hỏi' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Preview Modal -->
    <div v-if="showPreview" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Preview Header -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4 flex justify-between items-center">
          <div>
            <h3 class="text-xl font-bold text-white">Preview Câu hỏi</h3>
            <p class="text-sm text-white/80 mt-1">Xem trước giao diện thực tế</p>
          </div>
          <button @click="closePreview" class="text-white/90 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Preview Content -->
        <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
          <QuestionRenderer
            v-if="previewQuestion"
            :question="previewQuestion"
            :question-number="1"
            :show-explanation="true"
            @answer="previewAnswer = $event"
          />
        </div>

        <!-- Preview Footer -->
        <div class="border-t px-6 py-4 bg-white flex justify-end">
          <button @click="closePreview" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            Đóng
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from '@/composables/useI18n'
import QuestionRenderer from '@/components/examination/questions/QuestionRenderer.vue'
import api from '@/api'

const { t } = useI18n()
const emit = defineEmits(['close', 'saved'])

const props = defineProps({
  subjectId: {
    type: Number,
    default: null
  },
  subject: {
    type: Object,
    default: null
  }
})

const form = ref({
  topic: '',
  subject_id: '',
  subject_category_id: '',
  difficulty: 'medium',
  tag_ids: [],
  question_types: []
})

const generating = ref(false)
const saving = ref(false)
const loadingCategories = ref(false)
const loadingSubjects = ref(false)
const errorMessage = ref('')
const generatedQuestions = ref([])
const selectedQuestions = ref([])
const subjects = ref([])
const subjectCategories = ref([])
const showPreview = ref(false)
const previewQuestion = ref(null)
const previewAnswer = ref(null)

const totalQuestionCount = computed(() => {
  return form.value.question_types.reduce((sum, qt) => sum + (qt.count || 0), 0)
})

onMounted(async () => {
  await fetchSubjects()

  // Auto-select subject if provided from props
  if (props.subjectId) {
    form.value.subject_id = props.subjectId
    await onSubjectChange()
  }
})

async function fetchSubjects() {
  loadingSubjects.value = true
  try {
    const response = await api.get('/examination/subjects')
    subjects.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching subjects:', error)
  } finally {
    loadingSubjects.value = false
  }
}

function addQuestionType() {
  form.value.question_types.push({ type: '', count: 5 })
}

function removeQuestionType(index) {
  form.value.question_types.splice(index, 1)
}

async function generateQuestions() {
  if (form.value.question_types.length === 0) {
    errorMessage.value = 'Vui lòng chọn ít nhất một loại câu hỏi'
    return
  }

  for (const qt of form.value.question_types) {
    if (!qt.type || !qt.count || qt.count < 1) {
      errorMessage.value = 'Vui lòng điền đầy đủ thông tin cho tất cả loại câu hỏi'
      return
    }
  }

  generating.value = true
  errorMessage.value = ''

  try {
    const allQuestions = []
    for (const qt of form.value.question_types) {
      const response = await api.post('/examination/generate-questions', {
        topic: form.value.topic,
        subject_id: form.value.subject_id,
        subject_category_id: form.value.subject_category_id,
        skill: 'general',
        type: qt.type,
        difficulty: form.value.difficulty,
        count: qt.count,
        tag_ids: form.value.tag_ids
      })

      if (response.data.success) {
        const questions = response.data.questions.map(q => ({ ...q, type: qt.type }))
        allQuestions.push(...questions)
      }
    }

    generatedQuestions.value = allQuestions
    selectedQuestions.value = generatedQuestions.value.map((_, index) => index)
  } catch (error) {
    console.error('Error generating questions:', error)
    errorMessage.value = error.response?.data?.message || 'Không thể kết nối với AI. Vui lòng thử lại.'
  } finally {
    generating.value = false
  }
}

function toggleQuestionSelection(index) {
  const idx = selectedQuestions.value.indexOf(index)
  if (idx > -1) {
    selectedQuestions.value.splice(idx, 1)
  } else {
    selectedQuestions.value.push(index)
  }
}

function selectAllQuestions() {
  if (selectedQuestions.value.length === generatedQuestions.value.length) {
    selectedQuestions.value = []
  } else {
    selectedQuestions.value = generatedQuestions.value.map((_, index) => index)
  }
}

function openPreview(question) {
  previewQuestion.value = question
  previewAnswer.value = null
  showPreview.value = true
}

function closePreview() {
  showPreview.value = false
  previewQuestion.value = null
  previewAnswer.value = null
}

async function onSubjectChange() {
  form.value.subject_category_id = ''
  subjectCategories.value = []

  if (!form.value.subject_id) return

  loadingCategories.value = true
  try {
    const response = await api.get(`/examination/subjects/${form.value.subject_id}/categories`)
    subjectCategories.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching categories:', error)
  } finally {
    loadingCategories.value = false
  }
}

async function saveSelectedQuestions() {
  if (selectedQuestions.value.length === 0) return

  saving.value = true
  errorMessage.value = ''

  try {
    const questionsToSave = selectedQuestions.value.map(index => {
      const question = generatedQuestions.value[index]
      
      // Base question data
      const questionData = {
        type: question.type,
        difficulty: form.value.difficulty,
        skill: 'general',
        title: question.title,
        content: question.content || null,
        explanation: question.explanation || null,
        subject_id: form.value.subject_id || null,
        subject_category_id: form.value.subject_category_id || null,
        status: 'draft',
        points: 1,
        tag_ids: form.value.tag_ids || []
      }

      // Handle different question types
      if (question.type === 'drag_drop' || question.type === 'hotspot' || question.type === 'labeling') {
        // For drag_drop types, settings should be in the settings field
        questionData.settings = question.settings || null
      } else if (question.options) {
        // For multiple choice types
        questionData.options = question.options.map(opt => ({
          content: opt.text,
          is_correct: opt.is_correct,
          feedback: null
        }))
      } else if (question.matching_pairs) {
        // For matching types
        questionData.settings = {
          matching_pairs: question.matching_pairs
        }
      } else if (question.blank_answers) {
        // For fill blanks types
        questionData.settings = {
          blank_answers: question.blank_answers
        }
      } else if (question.sample_answer) {
        // For short answer types
        questionData.settings = {
          sample_answer: question.sample_answer
        }
      }

      return questionData
    })

    const response = await api.post('/examination/questions/import', { questions: questionsToSave })

    if (response.data.success) {
      emit('saved')
      emit('close')
    } else {
      errorMessage.value = response.data.message || 'Có lỗi xảy ra khi lưu câu hỏi'
    }
  } catch (error) {
    console.error('Error saving questions:', error)
    errorMessage.value = error.response?.data?.message || 'Không thể lưu câu hỏi. Vui lòng thử lại.'
  } finally {
    saving.value = false
  }
}
</script>
