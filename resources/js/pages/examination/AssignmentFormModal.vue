<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="$emit('close')"></div>

      <div class="relative bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <!-- Header -->
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between z-10">
          <h2 class="text-lg font-semibold text-gray-800">
            {{ isEditing ? 'Chỉnh sửa bài giao' : 'Giao bài mới' }}
          </h2>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="saveAssignment" class="p-6 space-y-6">
          <!-- Title -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề *</label>
            <input v-model="form.title" type="text" required
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Nhập tiêu đề bài giao" />
          </div>

          <!-- Test Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Chọn bài test *</label>
            <select v-model="form.test_id" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
              <option value="">Chọn bài test</option>
              <option v-for="test in tests" :key="test.id" :value="test.id">
                {{ test.title }} ({{ test.type }})
              </option>
            </select>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
            <textarea v-model="form.description" rows="3"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Hướng dẫn hoặc ghi chú cho học viên"></textarea>
          </div>

          <!-- Time Settings -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Bắt đầu từ *</label>
              <input v-model="form.available_from" type="datetime-local" required
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Hạn nộp</label>
              <input v-model="form.due_date" type="datetime-local"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>

          <!-- Attempts & Time Limit Override -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Số lần làm tối đa</label>
              <input v-model.number="form.max_attempts" type="number" min="1"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Thời gian làm (phút)</label>
              <input v-model.number="form.time_limit" type="number" min="1"
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                placeholder="Để trống = theo bài test" />
            </div>
          </div>

          <!-- Target Selection -->
          <div class="border rounded-lg p-4">
            <h3 class="font-medium text-gray-800 mb-3">Giao cho</h3>

            <div class="flex space-x-4 mb-4">
              <label class="flex items-center">
                <input type="radio" v-model="form.target_type" value="user" class="mr-2" />
                <span>Học viên</span>
              </label>
              <label class="flex items-center">
                <input type="radio" v-model="form.target_type" value="class" class="mr-2" />
                <span>Lớp học</span>
              </label>
              <label class="flex items-center">
                <input type="radio" v-model="form.target_type" value="branch" class="mr-2" />
                <span>Chi nhánh</span>
              </label>
            </div>

            <!-- User Selection -->
            <div v-if="form.target_type === 'user'">
              <input
                v-model="userSearch"
                type="text"
                placeholder="Tìm học viên..."
                class="w-full px-3 py-2 border rounded-lg mb-2"
                @input="searchUsers"
              />
              <div v-if="searchResults.length" class="border rounded-lg max-h-40 overflow-y-auto">
                <button
                  v-for="user in searchResults"
                  :key="user.id"
                  type="button"
                  @click="addTarget(user)"
                  class="w-full px-3 py-2 text-left hover:bg-gray-50 flex items-center justify-between"
                >
                  <span>{{ user.name }} ({{ user.email }})</span>
                  <span v-if="isTargetSelected(user.id)" class="text-green-600">✓</span>
                </button>
              </div>
            </div>

            <!-- Class Selection -->
            <div v-else-if="form.target_type === 'class'">
              <select v-model="selectedClass" @change="addClassTarget" class="w-full px-3 py-2 border rounded-lg">
                <option value="">Chọn lớp</option>
                <option v-for="cls in classes" :key="cls.id" :value="cls">
                  {{ cls.name }} ({{ cls.students_count }} học viên)
                </option>
              </select>
            </div>

            <!-- Branch Selection -->
            <div v-else>
              <select v-model="selectedBranch" @change="addBranchTarget" class="w-full px-3 py-2 border rounded-lg">
                <option value="">Chọn chi nhánh</option>
                <option v-for="branch in branches" :key="branch.id" :value="branch">
                  {{ branch.name }}
                </option>
              </select>
            </div>

            <!-- Selected Targets -->
            <div v-if="form.targets.length" class="mt-3">
              <p class="text-sm text-gray-600 mb-2">Đã chọn ({{ form.targets.length }}):</p>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="target in form.targets"
                  :key="target.id"
                  class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm"
                >
                  {{ target.name }}
                  <button type="button" @click="removeTarget(target)" class="ml-2 text-blue-600 hover:text-blue-800">
                    ×
                  </button>
                </span>
              </div>
            </div>
          </div>

          <!-- Options -->
          <div class="space-y-3">
            <label class="flex items-center">
              <input type="checkbox" v-model="form.shuffle_questions" class="rounded mr-2" />
              <span class="text-sm text-gray-700">Xáo trộn câu hỏi</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" v-model="form.show_result" class="rounded mr-2" />
              <span class="text-sm text-gray-700">Cho phép xem kết quả sau khi nộp</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" v-model="form.show_answers" class="rounded mr-2" />
              <span class="text-sm text-gray-700">Cho phép xem đáp án đúng</span>
            </label>
          </div>

          <!-- Footer -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <button type="button" @click="$emit('close')"
              class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
              Hủy
            </button>
            <button type="submit" :disabled="saving || !form.targets.length"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
              {{ saving ? 'Đang lưu...' : (isEditing ? 'Cập nhật' : 'Giao bài') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '@/api'

const props = defineProps({
  assignment: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

const saving = ref(false)
const tests = ref([])
const classes = ref([])
const branches = ref([])
const searchResults = ref([])
const userSearch = ref('')
const selectedClass = ref('')
const selectedBranch = ref('')

let searchTimeout = null

const isEditing = computed(() => !!props.assignment)

const defaultForm = {
  title: '',
  test_id: '',
  description: '',
  available_from: '',
  due_date: '',
  max_attempts: 1,
  time_limit: null,
  target_type: 'user',
  targets: [],
  shuffle_questions: false,
  show_result: true,
  show_answers: false
}

const form = ref({ ...defaultForm })

onMounted(async () => {
  await Promise.all([
    fetchTests(),
    fetchClasses(),
    fetchBranches()
  ])

  if (props.assignment) {
    form.value = {
      ...defaultForm,
      ...props.assignment,
      targets: props.assignment.targets || []
    }
  }
})

async function fetchTests() {
  try {
    const response = await api.get('/examination/tests', { params: { status: 'active', per_page: 100 } })
    tests.value = response.data.data.data || []
  } catch (error) {
    console.error('Error fetching tests:', error)
  }
}

async function fetchClasses() {
  try {
    const response = await api.get('/classes', { params: { per_page: 100 } })
    classes.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching classes:', error)
  }
}

async function fetchBranches() {
  try {
    const response = await api.get('/branches')
    branches.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching branches:', error)
  }
}

function searchUsers() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(async () => {
    if (userSearch.value.length < 2) {
      searchResults.value = []
      return
    }

    try {
      const response = await api.get('/users', { params: { search: userSearch.value, per_page: 10 } })
      searchResults.value = response.data.data || []
    } catch (error) {
      console.error('Error searching users:', error)
    }
  }, 300)
}

function addTarget(target) {
  if (!isTargetSelected(target.id)) {
    form.value.targets.push(target)
  }
}

function removeTarget(target) {
  form.value.targets = form.value.targets.filter(t => t.id !== target.id)
}

function isTargetSelected(id) {
  return form.value.targets.some(t => t.id === id)
}

function addClassTarget() {
  if (selectedClass.value && !isTargetSelected(selectedClass.value.id)) {
    form.value.targets.push(selectedClass.value)
    selectedClass.value = ''
  }
}

function addBranchTarget() {
  if (selectedBranch.value && !isTargetSelected(selectedBranch.value.id)) {
    form.value.targets.push(selectedBranch.value)
    selectedBranch.value = ''
  }
}

watch(() => form.value.target_type, () => {
  form.value.targets = []
})

async function saveAssignment() {
  saving.value = true
  try {
    const data = {
      title: form.value.title,
      test_id: form.value.test_id,
      description: form.value.description,
      available_from: form.value.available_from,
      due_date: form.value.due_date || null,
      max_attempts: form.value.max_attempts,
      time_limit: form.value.time_limit || null,
      target_type: form.value.target_type,
      target_ids: form.value.targets.map(t => t.id),
      shuffle_questions: form.value.shuffle_questions,
      show_result: form.value.show_result,
      show_answers: form.value.show_answers
    }

    if (isEditing.value) {
      await api.put(`/examination/assignments/${props.assignment.id}`, data)
    } else {
      await api.post('/examination/assignments', data)
    }

    emit('saved')
  } catch (error) {
    console.error('Error saving assignment:', error)
    alert('Có lỗi xảy ra khi lưu')
  } finally {
    saving.value = false
  }
}
</script>
