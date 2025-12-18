<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="$emit('close')"></div>

      <div class="relative bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="px-6 py-4 border-b flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-800">Chọn câu hỏi từ ngân hàng</h2>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Filters -->
        <div class="px-6 py-4 border-b bg-gray-50">
          <div class="grid grid-cols-5 gap-3">
            <input
              v-model="filters.search"
              type="text"
              placeholder="Tìm kiếm..."
              class="col-span-2 px-3 py-2 border rounded-lg text-sm"
              @input="debouncedSearch"
            />
            <select v-model="filters.skill" @change="fetchQuestions" class="px-3 py-2 border rounded-lg text-sm">
              <option value="">Tất cả kỹ năng</option>
              <option value="listening">Listening</option>
              <option value="reading">Reading</option>
              <option value="writing">Writing</option>
              <option value="speaking">Speaking</option>
            </select>
            <select v-model="filters.type" @change="fetchQuestions" class="px-3 py-2 border rounded-lg text-sm">
              <option value="">Tất cả loại</option>
              <option value="multiple_choice">Trắc nghiệm</option>
              <option value="fill_blanks">Điền chỗ trống</option>
              <option value="matching">Nối cột</option>
              <option value="true_false">Đúng/Sai</option>
              <option value="essay">Viết luận</option>
            </select>
            <select v-model="filters.difficulty" @change="fetchQuestions" class="px-3 py-2 border rounded-lg text-sm">
              <option value="">Tất cả độ khó</option>
              <option value="easy">Dễ</option>
              <option value="medium">Trung bình</option>
              <option value="hard">Khó</option>
            </select>
          </div>
        </div>

        <!-- Questions list -->
        <div class="flex-1 overflow-y-auto p-6">
          <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          </div>

          <div v-else-if="questions.length === 0" class="text-center py-8 text-gray-500">
            Không tìm thấy câu hỏi nào
          </div>

          <div v-else class="space-y-2">
            <div
              v-for="question in questions"
              :key="question.id"
              @click="toggleQuestion(question)"
              class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-colors"
              :class="{
                'border-blue-500 bg-blue-50': isSelected(question.id),
                'hover:bg-gray-50': !isSelected(question.id),
                'opacity-50 cursor-not-allowed': isAlreadyAdded(question.id)
              }"
            >
              <input
                type="checkbox"
                :checked="isSelected(question.id)"
                :disabled="isAlreadyAdded(question.id)"
                class="rounded"
                @click.stop
              />
              <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-800 truncate">{{ stripHtml(question.title) }}</p>
                <div class="flex items-center gap-3 text-sm text-gray-500">
                  <span class="px-2 py-0.5 bg-gray-100 rounded">{{ getTypeName(question.type) }}</span>
                  <span class="capitalize">{{ question.skill }}</span>
                  <span>{{ question.points }} điểm</span>
                </div>
              </div>
              <span
                v-if="isAlreadyAdded(question.id)"
                class="text-xs text-gray-400"
              >
                Đã thêm
              </span>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="pagination.total > pagination.per_page" class="mt-4 flex justify-center gap-2">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="px-3 py-1 border rounded disabled:opacity-50"
            >
              Trước
            </button>
            <span class="px-3 py-1 text-gray-600">
              {{ pagination.current_page }} / {{ pagination.last_page }}
            </span>
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-1 border rounded disabled:opacity-50"
            >
              Sau
            </button>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t bg-gray-50 flex items-center justify-between">
          <span class="text-sm text-gray-600">
            Đã chọn: {{ selectedQuestions.length }} câu hỏi
          </span>
          <div class="flex gap-3">
            <button @click="$emit('close')" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
              Hủy
            </button>
            <button
              @click="confirmSelection"
              :disabled="selectedQuestions.length === 0"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              Thêm {{ selectedQuestions.length }} câu hỏi
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'

const props = defineProps({
  selectedIds: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'select'])

const loading = ref(false)
const questions = ref([])
const selectedQuestions = ref([])
const pagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  last_page: 1
})

const filters = ref({
  search: '',
  skill: '',
  type: '',
  difficulty: '',
  status: 'active'
})

let searchTimeout = null

onMounted(() => {
  fetchQuestions()
})

async function fetchQuestions(page = 1) {
  loading.value = true
  try {
    const response = await api.get('/examination/questions', {
      params: { ...filters.value, page }
    })
    questions.value = response.data.data.data || []
    pagination.value = {
      current_page: response.data.data.current_page,
      per_page: response.data.data.per_page,
      total: response.data.data.total,
      last_page: response.data.data.last_page
    }
  } catch (error) {
    console.error('Error fetching questions:', error)
  } finally {
    loading.value = false
  }
}

function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchQuestions(), 300)
}

function changePage(page) {
  fetchQuestions(page)
}

function isAlreadyAdded(id) {
  return props.selectedIds.includes(id)
}

function isSelected(id) {
  return selectedQuestions.value.some(q => q.id === id)
}

function toggleQuestion(question) {
  if (isAlreadyAdded(question.id)) return

  const index = selectedQuestions.value.findIndex(q => q.id === question.id)
  if (index === -1) {
    selectedQuestions.value.push(question)
  } else {
    selectedQuestions.value.splice(index, 1)
  }
}

function confirmSelection() {
  emit('select', selectedQuestions.value)
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
    short_answer: 'Trả lời ngắn'
  }
  return names[type] || type
}
</script>
