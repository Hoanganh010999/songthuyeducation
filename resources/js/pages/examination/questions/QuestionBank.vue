<template>
  <div class="question-bank">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Ngân hàng câu hỏi</h1>
        <p class="text-gray-500 mt-1">Quản lý và tạo câu hỏi cho các bài test</p>
      </div>
      <div class="flex gap-3">
        <button @click="showImportModal = true" class="btn btn-outline">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
          </svg>
          Import
        </button>
        <button @click="openCreateModal" class="btn btn-primary">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Tạo câu hỏi
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <!-- Search -->
        <div class="md:col-span-2">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Tìm kiếm câu hỏi..."
            class="input input-bordered w-full"
            @input="debouncedSearch"
          />
        </div>

        <!-- Category Filter -->
        <div>
          <select v-model="filters.category_id" class="select select-bordered w-full" @change="loadQuestions">
            <option value="">Tất cả danh mục</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>
        </div>

        <!-- Type Filter -->
        <div>
          <select v-model="filters.type" class="select select-bordered w-full" @change="loadQuestions">
            <option value="">Tất cả loại</option>
            <option v-for="type in questionTypes" :key="type.value" :value="type.value">
              {{ type.label }}
            </option>
          </select>
        </div>

        <!-- Difficulty Filter -->
        <div>
          <select v-model="filters.difficulty" class="select select-bordered w-full" @change="loadQuestions">
            <option value="">Tất cả độ khó</option>
            <option value="easy">Dễ</option>
            <option value="medium">Trung bình</option>
            <option value="hard">Khó</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Questions Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="table w-full">
          <thead>
            <tr>
              <th class="w-12">
                <input type="checkbox" class="checkbox" v-model="selectAll" @change="toggleSelectAll" />
              </th>
              <th>Câu hỏi</th>
              <th class="w-32">Loại</th>
              <th class="w-24">Độ khó</th>
              <th class="w-20">Điểm</th>
              <th class="w-32">Ngày tạo</th>
              <th class="w-24">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center py-8">
                <span class="loading loading-spinner loading-lg"></span>
              </td>
            </tr>
            <tr v-else-if="questions.length === 0">
              <td colspan="7" class="text-center py-8 text-gray-500">
                Không có câu hỏi nào
              </td>
            </tr>
            <tr v-for="question in questions" :key="question.id" class="hover">
              <td>
                <input type="checkbox" class="checkbox" v-model="selectedIds" :value="question.id" />
              </td>
              <td>
                <div class="font-medium">{{ truncate(question.title, 60) }}</div>
                <div class="text-sm text-gray-500" v-if="question.category">
                  {{ question.category.name }}
                </div>
              </td>
              <td>
                <span class="badge" :class="getTypeBadgeClass(question.type)">
                  {{ getTypeLabel(question.type) }}
                </span>
              </td>
              <td>
                <span class="badge" :class="getDifficultyBadgeClass(question.difficulty)">
                  {{ getDifficultyLabel(question.difficulty) }}
                </span>
              </td>
              <td>{{ question.points }}</td>
              <td>{{ formatDate(question.created_at) }}</td>
              <td>
                <div class="dropdown dropdown-end">
                  <label tabindex="0" class="btn btn-ghost btn-sm btn-circle">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                    </svg>
                  </label>
                  <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-40">
                    <li><a @click="editQuestion(question)">Sửa</a></li>
                    <li><a @click="duplicateQuestion(question)">Nhân bản</a></li>
                    <li><a @click="previewQuestion(question)">Xem trước</a></li>
                    <li><a @click="deleteQuestion(question)" class="text-error">Xóa</a></li>
                  </ul>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="flex justify-between items-center p-4 border-t">
        <div class="text-sm text-gray-500">
          Hiển thị {{ (pagination.page - 1) * pagination.perPage + 1 }} -
          {{ Math.min(pagination.page * pagination.perPage, pagination.total) }}
          trong {{ pagination.total }} câu hỏi
        </div>
        <div class="btn-group">
          <button
            class="btn btn-sm"
            :disabled="pagination.page === 1"
            @click="changePage(pagination.page - 1)"
          >«</button>
          <button class="btn btn-sm">Trang {{ pagination.page }}</button>
          <button
            class="btn btn-sm"
            :disabled="pagination.page >= pagination.lastPage"
            @click="changePage(pagination.page + 1)"
          >»</button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <QuestionFormModal
      v-if="showFormModal"
      :question="editingQuestion"
      :categories="categories"
      :question-types="questionTypes"
      @close="closeFormModal"
      @saved="onQuestionSaved"
    />

    <!-- Preview Modal -->
    <QuestionPreviewModal
      v-if="showPreviewModal"
      :question="previewingQuestion"
      @close="showPreviewModal = false"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { debounce } from 'lodash-es'
import examinationApi from '@/services/examinationApi'
import QuestionFormModal from './QuestionFormModal.vue'
import QuestionPreviewModal from './QuestionPreviewModal.vue'

const loading = ref(false)
const questions = ref([])
const categories = ref([])
const selectedIds = ref([])
const selectAll = ref(false)

const showFormModal = ref(false)
const showPreviewModal = ref(false)
const showImportModal = ref(false)
const editingQuestion = ref(null)
const previewingQuestion = ref(null)

const filters = reactive({
  search: '',
  category_id: '',
  type: '',
  difficulty: '',
})

const pagination = reactive({
  page: 1,
  perPage: 20,
  total: 0,
  lastPage: 1,
})

const questionTypes = [
  { value: 'multiple_choice', label: 'Trắc nghiệm 1 đáp án' },
  { value: 'multiple_response', label: 'Trắc nghiệm nhiều đáp án' },
  { value: 'true_false', label: 'Đúng/Sai' },
  { value: 'true_false_not_given', label: 'True/False/Not Given' },
  { value: 'fill_blanks', label: 'Điền vào chỗ trống' },
  { value: 'fill_blanks_drag', label: 'Kéo thả điền chỗ trống' },
  { value: 'short_answer', label: 'Trả lời ngắn' },
  { value: 'matching', label: 'Nối cột' },
  { value: 'matching_headings', label: 'Nối tiêu đề' },
  { value: 'ordering', label: 'Sắp xếp thứ tự' },
  { value: 'drag_drop', label: 'Kéo thả' },
  { value: 'hotspot', label: 'Điểm nóng (click vùng)' },
  { value: 'labeling', label: 'Gắn nhãn' },
  { value: 'sentence_completion', label: 'Hoàn thành câu' },
  { value: 'summary_completion', label: 'Hoàn thành tóm tắt' },
  { value: 'note_completion', label: 'Hoàn thành ghi chú' },
  { value: 'table_completion', label: 'Hoàn thành bảng' },
  { value: 'flow_chart', label: 'Hoàn thành sơ đồ' },
  { value: 'essay', label: 'Tự luận (Writing)' },
  { value: 'audio_response', label: 'Ghi âm (Speaking)' },
]

onMounted(async () => {
  await Promise.all([
    loadQuestions(),
    loadCategories(),
  ])
})

async function loadQuestions() {
  loading.value = true
  try {
    const response = await examinationApi.questions.list({
      ...filters,
      page: pagination.page,
      per_page: pagination.perPage,
    })
    questions.value = response.data.data
    pagination.total = response.data.meta?.total || response.data.total || 0
    pagination.lastPage = response.data.meta?.last_page || Math.ceil(pagination.total / pagination.perPage)
  } catch (error) {
    console.error('Failed to load questions:', error)
  } finally {
    loading.value = false
  }
}

async function loadCategories() {
  try {
    const response = await examinationApi.getQuestionTypes()
    categories.value = response.data.categories || []
  } catch (error) {
    console.error('Failed to load categories:', error)
  }
}

const debouncedSearch = debounce(() => {
  pagination.page = 1
  loadQuestions()
}, 300)

function changePage(page) {
  pagination.page = page
  loadQuestions()
}

function toggleSelectAll() {
  if (selectAll.value) {
    selectedIds.value = questions.value.map(q => q.id)
  } else {
    selectedIds.value = []
  }
}

function openCreateModal() {
  editingQuestion.value = null
  showFormModal.value = true
}

function editQuestion(question) {
  editingQuestion.value = question
  showFormModal.value = true
}

function closeFormModal() {
  showFormModal.value = false
  editingQuestion.value = null
}

function onQuestionSaved() {
  closeFormModal()
  loadQuestions()
}

function previewQuestion(question) {
  previewingQuestion.value = question
  showPreviewModal.value = true
}

async function duplicateQuestion(question) {
  if (!confirm('Bạn có chắc muốn nhân bản câu hỏi này?')) return

  try {
    await examinationApi.questions.duplicate(question.id)
    loadQuestions()
  } catch (error) {
    console.error('Failed to duplicate question:', error)
    alert('Không thể nhân bản câu hỏi')
  }
}

async function deleteQuestion(question) {
  if (!confirm('Bạn có chắc muốn xóa câu hỏi này?')) return

  try {
    await examinationApi.questions.delete(question.id)
    loadQuestions()
  } catch (error) {
    console.error('Failed to delete question:', error)
    alert('Không thể xóa câu hỏi')
  }
}

function truncate(str, length) {
  if (!str) return ''
  return str.length > length ? str.substring(0, length) + '...' : str
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('vi-VN')
}

function getTypeLabel(type) {
  const found = questionTypes.find(t => t.value === type)
  return found ? found.label : type
}

function getTypeBadgeClass(type) {
  const map = {
    multiple_choice: 'badge-primary',
    multiple_response: 'badge-primary',
    true_false: 'badge-secondary',
    fill_blanks: 'badge-accent',
    short_answer: 'badge-info',
    matching: 'badge-warning',
    essay: 'badge-error',
    audio_response: 'badge-error',
  }
  return map[type] || 'badge-ghost'
}

function getDifficultyLabel(difficulty) {
  const map = { easy: 'Dễ', medium: 'TB', hard: 'Khó' }
  return map[difficulty] || difficulty
}

function getDifficultyBadgeClass(difficulty) {
  const map = {
    easy: 'badge-success',
    medium: 'badge-warning',
    hard: 'badge-error',
  }
  return map[difficulty] || 'badge-ghost'
}
</script>

<style scoped>
.question-bank {
  @apply p-6;
}
</style>
