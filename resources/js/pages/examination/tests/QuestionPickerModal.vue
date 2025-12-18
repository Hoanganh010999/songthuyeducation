<template>
  <div class="modal modal-open">
    <div class="modal-box max-w-4xl max-h-[85vh]">
      <h3 class="font-bold text-lg mb-4">Chọn câu hỏi từ ngân hàng</h3>

      <!-- Search & Filters -->
      <div class="grid grid-cols-3 gap-4 mb-4">
        <div class="col-span-2">
          <input
            v-model="search"
            type="text"
            placeholder="Tìm kiếm câu hỏi..."
            class="input input-bordered w-full"
            @input="debouncedSearch"
          />
        </div>
        <div>
          <select v-model="filterType" class="select select-bordered w-full" @change="loadQuestions">
            <option value="">Tất cả loại</option>
            <option value="multiple_choice">Trắc nghiệm</option>
            <option value="fill_blanks">Điền chỗ trống</option>
            <option value="matching">Nối cột</option>
            <option value="essay">Tự luận</option>
          </select>
        </div>
      </div>

      <!-- Questions List -->
      <div class="overflow-y-auto max-h-[50vh] border rounded-lg">
        <div v-if="loading" class="text-center py-8">
          <span class="loading loading-spinner loading-lg"></span>
        </div>

        <div v-else-if="questions.length === 0" class="text-center py-8 text-gray-500">
          Không tìm thấy câu hỏi
        </div>

        <div v-else class="divide-y">
          <label
            v-for="question in questions"
            :key="question.id"
            class="flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer"
            :class="{ 'opacity-50': isExcluded(question.id) }"
          >
            <input
              type="checkbox"
              class="checkbox checkbox-primary"
              :disabled="isExcluded(question.id)"
              :checked="isSelected(question.id)"
              @change="toggleQuestion(question)"
            />
            <div class="flex-1">
              <div class="font-medium">{{ question.title }}</div>
              <div class="text-sm text-gray-500">
                <span class="badge badge-sm badge-ghost mr-2">{{ getTypeLabel(question.type) }}</span>
                <span>{{ question.points }} điểm</span>
                <span class="ml-2">• {{ getDifficultyLabel(question.difficulty) }}</span>
              </div>
            </div>
          </label>
        </div>
      </div>

      <!-- Selected Count -->
      <div class="flex justify-between items-center mt-4">
        <span class="text-sm text-gray-500">
          Đã chọn: {{ selectedQuestions.length }} câu hỏi
        </span>
        <div class="flex gap-2">
          <button @click="$emit('close')" class="btn btn-ghost">Hủy</button>
          <button
            @click="confirmSelection"
            class="btn btn-primary"
            :disabled="selectedQuestions.length === 0"
          >
            Thêm {{ selectedQuestions.length }} câu hỏi
          </button>
        </div>
      </div>
    </div>
    <div class="modal-backdrop" @click="$emit('close')"></div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { debounce } from 'lodash-es'
import examinationApi from '@/services/examinationApi'

const props = defineProps({
  excludedIds: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['close', 'select'])

const loading = ref(false)
const search = ref('')
const filterType = ref('')
const questions = ref([])
const selectedQuestions = ref([])

onMounted(() => {
  loadQuestions()
})

async function loadQuestions() {
  loading.value = true
  try {
    const response = await examinationApi.questions.list({
      search: search.value,
      type: filterType.value,
      per_page: 50,
    })
    questions.value = response.data.data
  } catch (error) {
    console.error('Failed to load questions:', error)
  } finally {
    loading.value = false
  }
}

const debouncedSearch = debounce(loadQuestions, 300)

function isExcluded(id) {
  return props.excludedIds.includes(id)
}

function isSelected(id) {
  return selectedQuestions.value.some(q => q.id === id)
}

function toggleQuestion(question) {
  if (isExcluded(question.id)) return

  const index = selectedQuestions.value.findIndex(q => q.id === question.id)
  if (index >= 0) {
    selectedQuestions.value.splice(index, 1)
  } else {
    selectedQuestions.value.push(question)
  }
}

function confirmSelection() {
  emit('select', selectedQuestions.value)
}

function getTypeLabel(type) {
  const labels = {
    multiple_choice: 'Trắc nghiệm',
    multiple_response: 'Nhiều đáp án',
    true_false: 'Đúng/Sai',
    fill_blanks: 'Điền chỗ trống',
    short_answer: 'Trả lời ngắn',
    matching: 'Nối cột',
    essay: 'Tự luận',
  }
  return labels[type] || type
}

function getDifficultyLabel(difficulty) {
  const map = { easy: 'Dễ', medium: 'TB', hard: 'Khó' }
  return map[difficulty] || difficulty
}
</script>
