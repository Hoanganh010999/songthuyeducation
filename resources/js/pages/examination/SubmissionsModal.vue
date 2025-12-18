<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="$emit('close')"></div>

      <div class="relative bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <!-- Header -->
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between z-10">
          <div>
            <h2 class="text-lg font-semibold text-gray-800">Bài nộp</h2>
            <p class="text-sm text-gray-600">{{ assignment.title }}</p>
          </div>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Content -->
        <div class="p-6">
          <!-- Filters -->
          <div class="flex gap-4 mb-4">
            <input
              v-model="filters.search"
              type="text"
              placeholder="Tìm học viên..."
              class="flex-1 px-3 py-2 border rounded-lg"
              @input="debouncedSearch"
            />
            <select v-model="filters.status" @change="fetchSubmissions" class="px-3 py-2 border rounded-lg">
              <option value="">Tất cả</option>
              <option value="in_progress">Đang làm</option>
              <option value="submitted">Đã nộp</option>
              <option value="graded">Đã chấm</option>
            </select>
          </div>

          <!-- Loading -->
          <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          </div>

          <!-- Empty -->
          <div v-else-if="submissions.length === 0" class="text-center py-8 text-gray-500">
            Chưa có bài nộp nào
          </div>

          <!-- Submissions Table -->
          <table v-else class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Học viên</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thời gian</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Điểm</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thao tác</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="sub in submissions" :key="sub.id" class="hover:bg-gray-50">
                <td class="px-4 py-3">
                  <div>
                    <p class="font-medium text-gray-800">{{ sub.user?.name }}</p>
                    <p class="text-sm text-gray-500">{{ sub.user?.email }}</p>
                  </div>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">
                  <div>
                    <p>Bắt đầu: {{ formatDate(sub.started_at) }}</p>
                    <p v-if="sub.submitted_at">Nộp: {{ formatDate(sub.submitted_at) }}</p>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div v-if="sub.score !== null">
                    <p class="font-medium" :class="sub.passed ? 'text-green-600' : 'text-red-600'">
                      {{ parseFloat(sub.percentage || 0).toFixed(1) }}%
                    </p>
                    <p class="text-sm text-gray-500">{{ sub.score }}/{{ sub.max_score }}</p>
                  </div>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="px-4 py-3">
                  <span
                    class="px-2 py-1 text-xs rounded-full"
                    :class="getStatusClass(sub.status)"
                  >
                    {{ getStatusName(sub.status) }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right">
                  <button
                    v-if="sub.status === 'submitted' || sub.status === 'graded'"
                    @click="viewDetail(sub)"
                    class="text-blue-600 hover:text-blue-800 text-sm"
                  >
                    Xem chi tiết
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api'

const props = defineProps({
  assignment: {
    type: Object,
    required: true
  }
})

defineEmits(['close'])

const router = useRouter()
const loading = ref(false)
const submissions = ref([])
const filters = ref({
  search: '',
  status: ''
})

let searchTimeout = null

onMounted(() => {
  fetchSubmissions()
})

async function fetchSubmissions() {
  loading.value = true
  try {
    const response = await api.get(`/examination/assignments/${props.assignment.id}/submissions`, {
      params: filters.value
    })
    submissions.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching submissions:', error)
  } finally {
    loading.value = false
  }
}

function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchSubmissions(), 300)
}

function viewDetail(submission) {
  router.push({
    name: 'examination.result',
    params: { submissionId: submission.id }
  })
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('vi-VN')
}

function getStatusName(status) {
  const names = {
    in_progress: 'Đang làm',
    submitted: 'Đã nộp',
    graded: 'Đã chấm',
    timeout: 'Hết giờ'
  }
  return names[status] || status
}

function getStatusClass(status) {
  const classes = {
    in_progress: 'bg-yellow-100 text-yellow-800',
    submitted: 'bg-blue-100 text-blue-800',
    graded: 'bg-green-100 text-green-800',
    timeout: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}
</script>
