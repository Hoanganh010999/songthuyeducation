<template>
  <div class="test-builder">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <router-link :to="{ name: 'examination.tests' }" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
          ← Quay lại ngân hàng bài test
        </router-link>
        <h1 class="text-2xl font-bold text-gray-800">
          {{ isEditing ? 'Chỉnh sửa bài test' : 'Tạo bài test mới' }}
        </h1>
      </div>
      <div class="flex gap-3">
        <button @click="saveDraft" :disabled="saving" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 disabled:opacity-50">
          Lưu nháp
        </button>
        <button @click="saveAndPublish" :disabled="saving || !canPublish" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
          {{ saving ? 'Đang lưu...' : 'Lưu & Xuất bản' }}
        </button>
      </div>
    </div>

    <div class="grid grid-cols-3 gap-6">
      <!-- Left Panel: Test Info -->
      <div class="col-span-2 space-y-6">
        <!-- Basic Info Card -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-800 mb-4">Thông tin cơ bản</h2>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề bài test *</label>
              <input v-model="form.title" type="text" required
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                placeholder="Nhập tiêu đề bài test" />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Loại bài test *</label>
                <select v-model="form.type" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                  <option value="">Chọn loại</option>
                  <option value="ielts">IELTS</option>
                  <option value="cambridge">Cambridge</option>
                  <option value="toeic">TOEIC</option>
                  <option value="custom">Tự tạo</option>
                  <option value="quiz">Quiz</option>
                  <option value="practice">Luyện tập</option>
                  <option value="placement">Placement Test</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Thời gian (phút) *</label>
                <input v-model.number="form.time_limit" type="number" min="1"
                  class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                  placeholder="VD: 60" />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
              <textarea v-model="form.description" rows="3"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                placeholder="Mô tả về bài test"></textarea>
            </div>

            <div class="grid grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Điểm đạt (%)</label>
                <input v-model.number="form.passing_score" type="number" min="0" max="100"
                  class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                  placeholder="VD: 60" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Số lần làm tối đa</label>
                <input v-model.number="form.max_attempts" type="number" min="1"
                  class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                <select v-model="form.status" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                  <option value="draft">Nháp</option>
                  <option value="active">Hoạt động</option>
                  <option value="archived">Lưu trữ</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Sections & Questions -->
        <div class="bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Các phần & Câu hỏi</h2>
            <div class="flex gap-2">
              <button @click="addSection" class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">
                + Thêm phần
              </button>
              <button @click="openQuestionPicker(null)" class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                + Thêm câu hỏi
              </button>
            </div>
          </div>

          <div class="p-6">
            <!-- No sections mode - direct questions -->
            <div v-if="form.sections.length === 0">
              <div v-if="form.questions.length === 0" class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p>Chưa có câu hỏi nào</p>
                <button @click="openQuestionPicker(null)" class="mt-3 text-blue-600 hover:text-blue-800">
                  Thêm câu hỏi từ ngân hàng
                </button>
              </div>

              <draggable
                v-else
                v-model="form.questions"
                group="questions"
                item-key="id"
                handle=".drag-handle"
                class="space-y-2"
              >
                <template #item="{ element: question, index }">
                  <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <span class="drag-handle cursor-move text-gray-400">⋮⋮</span>
                    <span class="w-8 h-8 flex items-center justify-center bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                      {{ index + 1 }}
                    </span>
                    <div class="flex-1 min-w-0">
                      <p class="font-medium text-gray-800 truncate">{{ stripHtml(question.title) }}</p>
                      <p class="text-sm text-gray-500">
                        {{ getTypeName(question.type) }} • {{ question.points }} điểm
                      </p>
                    </div>
                    <button @click="removeQuestion(null, index)" class="p-1 text-red-600 hover:bg-red-50 rounded">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </template>
              </draggable>
            </div>

            <!-- Sections mode -->
            <div v-else class="space-y-6">
              <div
                v-for="(section, sectionIndex) in form.sections"
                :key="section.id || sectionIndex"
                class="border rounded-lg overflow-hidden"
              >
                <!-- Section header -->
                <div class="bg-gray-100 px-4 py-3 flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <span class="font-semibold text-gray-800">{{ section.title || `Phần ${sectionIndex + 1}` }}</span>
                    <button @click="editSection(sectionIndex)" class="text-sm text-blue-600 hover:text-blue-800">
                      Sửa
                    </button>
                  </div>
                  <div class="flex items-center gap-2">
                    <button @click="openQuestionPicker(sectionIndex)" class="text-sm text-blue-600 hover:text-blue-800">
                      + Câu hỏi
                    </button>
                    <button @click="removeSection(sectionIndex)" class="text-sm text-red-600 hover:text-red-800">
                      Xóa phần
                    </button>
                  </div>
                </div>

                <!-- Section info -->
                <div v-if="section.instructions || section.audio_track || section.reading_passage" class="px-4 py-2 bg-blue-50 text-sm text-blue-800">
                  <span v-if="section.audio_track">🎧 Có audio</span>
                  <span v-if="section.reading_passage" class="ml-3">📖 Có đoạn văn</span>
                </div>

                <!-- Section questions -->
                <div class="p-4">
                  <draggable
                    v-model="section.questions"
                    group="questions"
                    item-key="id"
                    handle=".drag-handle"
                    class="space-y-2 min-h-[60px]"
                  >
                    <template #item="{ element: question, index }">
                      <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                        <span class="drag-handle cursor-move text-gray-400">⋮⋮</span>
                        <span class="w-8 h-8 flex items-center justify-center bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                          {{ getQuestionNumber(sectionIndex, index) }}
                        </span>
                        <div class="flex-1 min-w-0">
                          <p class="font-medium text-gray-800 truncate">{{ stripHtml(question.title) }}</p>
                          <p class="text-sm text-gray-500">
                            {{ getTypeName(question.type) }} • {{ question.points }} điểm
                          </p>
                        </div>
                        <button @click="removeQuestion(sectionIndex, index)" class="p-1 text-red-600 hover:bg-red-50 rounded">
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                          </svg>
                        </button>
                      </div>
                    </template>
                  </draggable>

                  <p v-if="!section.questions?.length" class="text-center py-4 text-gray-400 text-sm">
                    Kéo thả câu hỏi vào đây hoặc click "Câu hỏi" để thêm
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Panel: Settings & Summary -->
      <div class="space-y-6">
        <!-- Summary Card -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="font-semibold text-gray-800 mb-4">Tóm tắt</h3>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600">Tổng câu hỏi:</span>
              <span class="font-medium">{{ totalQuestions }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Tổng điểm:</span>
              <span class="font-medium">{{ totalPoints }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Số phần:</span>
              <span class="font-medium">{{ form.sections.length || 'Không chia phần' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Thời gian:</span>
              <span class="font-medium">{{ form.time_limit || '-' }} phút</span>
            </div>
          </div>
        </div>

        <!-- Options Card -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="font-semibold text-gray-800 mb-4">Tùy chọn</h3>
          <div class="space-y-3">
            <label class="flex items-center">
              <input type="checkbox" v-model="form.shuffle_questions" class="rounded mr-2" />
              <span class="text-sm text-gray-700">Xáo trộn câu hỏi</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" v-model="form.shuffle_options" class="rounded mr-2" />
              <span class="text-sm text-gray-700">Xáo trộn đáp án</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" v-model="form.show_result_immediately" class="rounded mr-2" />
              <span class="text-sm text-gray-700">Hiển thị kết quả ngay</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" v-model="form.allow_review" class="rounded mr-2" />
              <span class="text-sm text-gray-700">Cho phép xem lại bài</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" v-model="form.prevent_copy" class="rounded mr-2" />
              <span class="text-sm text-gray-700">Chặn copy nội dung</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" v-model="form.track_tab_switch" class="rounded mr-2" />
              <span class="text-sm text-gray-700">Theo dõi chuyển tab</span>
            </label>
          </div>
        </div>

        <!-- IELTS Settings (if type is ielts) -->
        <div v-if="form.type === 'ielts'" class="bg-white rounded-lg shadow p-6">
          <h3 class="font-semibold text-gray-800 mb-4">Cài đặt IELTS</h3>
          <div class="space-y-3">
            <label class="flex items-center">
              <input type="checkbox" v-model="form.calculate_band_score" class="rounded mr-2" />
              <span class="text-sm text-gray-700">Tính Band Score</span>
            </label>
            <div>
              <label class="block text-sm text-gray-600 mb-1">Kỹ năng chính</label>
              <select v-model="form.primary_skill" class="w-full px-3 py-2 border rounded-lg text-sm">
                <option value="">Chọn kỹ năng</option>
                <option value="listening">Listening</option>
                <option value="reading">Reading</option>
                <option value="writing">Writing</option>
                <option value="speaking">Speaking</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Question Picker Modal -->
    <QuestionPickerModal
      v-if="showQuestionPicker"
      :selected-ids="getSelectedQuestionIds()"
      @close="showQuestionPicker = false"
      @select="onQuestionsSelected"
    />

    <!-- Section Editor Modal -->
    <SectionEditorModal
      v-if="showSectionEditor"
      :section="editingSection"
      @close="showSectionEditor = false"
      @save="onSectionSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, defineAsyncComponent } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import draggable from 'vuedraggable'
import api from '@/api'

const QuestionPickerModal = defineAsyncComponent(() =>
  import('./QuestionPickerModal.vue')
)
const SectionEditorModal = defineAsyncComponent(() =>
  import('./SectionEditorModal.vue')
)

const route = useRoute()
const router = useRouter()

const saving = ref(false)
const loading = ref(false)
const showQuestionPicker = ref(false)
const showSectionEditor = ref(false)
const editingSection = ref(null)
const editingSectionIndex = ref(null)
const targetSectionIndex = ref(null)

const isEditing = computed(() => !!route.params.id)

const defaultForm = {
  title: '',
  type: '',
  description: '',
  time_limit: 60,
  passing_score: 60,
  max_attempts: 1,
  status: 'draft',
  shuffle_questions: false,
  shuffle_options: false,
  show_result_immediately: true,
  allow_review: true,
  prevent_copy: true,
  track_tab_switch: true,
  calculate_band_score: false,
  primary_skill: '',
  sections: [],
  questions: []
}

const form = ref({ ...defaultForm })

const totalQuestions = computed(() => {
  if (form.value.sections.length > 0) {
    return form.value.sections.reduce((sum, s) => sum + (s.questions?.length || 0), 0)
  }
  return form.value.questions.length
})

const totalPoints = computed(() => {
  let points = 0
  if (form.value.sections.length > 0) {
    form.value.sections.forEach(s => {
      s.questions?.forEach(q => { points += q.points || 0 })
    })
  } else {
    form.value.questions.forEach(q => { points += q.points || 0 })
  }
  return points
})

const canPublish = computed(() => {
  return form.value.title && form.value.type && form.value.time_limit && totalQuestions.value > 0
})

onMounted(async () => {
  if (isEditing.value) {
    await fetchTest()
  }
})

async function fetchTest() {
  loading.value = true
  try {
    const response = await api.get(`/examination/tests/${route.params.id}`)
    const test = response.data.data

    form.value = {
      ...defaultForm,
      ...test,
      sections: test.sections || [],
      questions: test.questions || []
    }
  } catch (error) {
    console.error('Error fetching test:', error)
    alert('Không thể tải bài test')
    router.push({ name: 'examination.tests' })
  } finally {
    loading.value = false
  }
}

async function saveDraft() {
  form.value.status = 'draft'
  await saveTest()
}

async function saveAndPublish() {
  form.value.status = 'active'
  await saveTest()
}

async function saveTest() {
  saving.value = true
  try {
    const data = prepareFormData()

    if (isEditing.value) {
      await api.put(`/examination/tests/${route.params.id}`, data)
    } else {
      const response = await api.post('/examination/tests', data)
      router.replace({
        name: 'examination.tests.edit',
        params: { id: response.data.data.id }
      })
    }

    alert('Đã lưu thành công!')
  } catch (error) {
    console.error('Error saving test:', error)
    alert('Có lỗi xảy ra khi lưu')
  } finally {
    saving.value = false
  }
}

function prepareFormData() {
  const data = {
    title: form.value.title,
    type: form.value.type,
    description: form.value.description,
    time_limit: form.value.time_limit,
    passing_score: form.value.passing_score,
    max_attempts: form.value.max_attempts,
    status: form.value.status,
    settings: {
      shuffle_questions: form.value.shuffle_questions,
      shuffle_options: form.value.shuffle_options,
      show_result_immediately: form.value.show_result_immediately,
      allow_review: form.value.allow_review,
      prevent_copy: form.value.prevent_copy,
      track_tab_switch: form.value.track_tab_switch,
      calculate_band_score: form.value.calculate_band_score,
      primary_skill: form.value.primary_skill
    }
  }

  if (form.value.sections.length > 0) {
    data.sections = form.value.sections.map((s, idx) => ({
      id: s.id,
      title: s.title,
      instructions: s.instructions,
      audio_track_id: s.audio_track_id,
      reading_passage_id: s.reading_passage_id,
      order: idx,
      questions: s.questions?.map((q, qIdx) => ({
        question_id: q.id,
        order: qIdx,
        points: q.points
      }))
    }))
  } else {
    data.questions = form.value.questions.map((q, idx) => ({
      question_id: q.id,
      order: idx,
      points: q.points
    }))
  }

  return data
}

function addSection() {
  editingSection.value = null
  editingSectionIndex.value = null
  showSectionEditor.value = true
}

function editSection(index) {
  editingSection.value = form.value.sections[index]
  editingSectionIndex.value = index
  showSectionEditor.value = true
}

function onSectionSaved(section) {
  if (editingSectionIndex.value !== null) {
    form.value.sections[editingSectionIndex.value] = {
      ...form.value.sections[editingSectionIndex.value],
      ...section
    }
  } else {
    form.value.sections.push({
      ...section,
      questions: []
    })
  }
  showSectionEditor.value = false
}

function removeSection(index) {
  if (confirm('Bạn có chắc muốn xóa phần này?')) {
    form.value.sections.splice(index, 1)
  }
}

function openQuestionPicker(sectionIndex) {
  targetSectionIndex.value = sectionIndex
  showQuestionPicker.value = true
}

function onQuestionsSelected(questions) {
  if (targetSectionIndex.value !== null) {
    if (!form.value.sections[targetSectionIndex.value].questions) {
      form.value.sections[targetSectionIndex.value].questions = []
    }
    form.value.sections[targetSectionIndex.value].questions.push(...questions)
  } else {
    form.value.questions.push(...questions)
  }
  showQuestionPicker.value = false
}

function removeQuestion(sectionIndex, questionIndex) {
  if (sectionIndex !== null) {
    form.value.sections[sectionIndex].questions.splice(questionIndex, 1)
  } else {
    form.value.questions.splice(questionIndex, 1)
  }
}

function getSelectedQuestionIds() {
  const ids = []
  if (form.value.sections.length > 0) {
    form.value.sections.forEach(s => {
      s.questions?.forEach(q => ids.push(q.id))
    })
  } else {
    form.value.questions.forEach(q => ids.push(q.id))
  }
  return ids
}

function getQuestionNumber(sectionIndex, questionIndex) {
  let num = 0
  for (let i = 0; i < sectionIndex; i++) {
    num += form.value.sections[i].questions?.length || 0
  }
  return num + questionIndex + 1
}

function stripHtml(html) {
  return html?.replace(/<[^>]*>/g, '') || ''
}

function getTypeName(type) {
  const names = {
    multiple_choice: 'Trắc nghiệm',
    multiple_response: 'Nhiều đáp án',
    fill_blanks: 'Điền chỗ trống',
    matching: 'Nối cột',
    true_false: 'Đúng/Sai',
    essay: 'Viết luận',
    short_answer: 'Trả lời ngắn',
    audio_response: 'Trả lời audio'
  }
  return names[type] || type
}
</script>
