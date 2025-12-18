<template>
  <div class="assignments-management">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Quản lý giao bài</h1>
        <p class="text-gray-600">Giao bài test cho học viên</p>
      </div>
      <button
        @click="openCreateModal"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center"
      >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        Giao bài mới
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input
          v-model="filters.search"
          type="text"
          placeholder="Tìm kiếm..."
          class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          @input="debouncedSearch"
        />

        <select v-model="filters.status" @change="fetchAssignments" class="px-4 py-2 border rounded-lg">
          <option value="">Tất cả trạng thái</option>
          <option value="scheduled">Lên lịch</option>
          <option value="active">Đang diễn ra</option>
          <option value="completed">Đã kết thúc</option>
        </select>

        <select v-model="filters.test_type" @change="fetchAssignments" class="px-4 py-2 border rounded-lg">
          <option value="">Tất cả loại test</option>
          <option value="ielts">IELTS</option>
          <option value="cambridge">Cambridge</option>
          <option value="toeic">TOEIC</option>
          <option value="custom">Tự tạo</option>
        </select>

        <button @click="resetFilters" class="px-4 py-2 text-gray-600 border rounded-lg hover:bg-gray-50">
          Xóa bộ lọc
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
    </div>

    <!-- Empty state -->
    <div v-else-if="assignments.length === 0" class="text-center py-12 bg-white rounded-lg">
      <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
      </svg>
      <p class="mt-4 text-gray-600">Chưa có bài giao nào</p>
      <button @click="openCreateModal" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg">
        Tạo bài giao đầu tiên
      </button>
    </div>

    <!-- Assignments Table -->
    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bài test</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Đối tượng</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thời gian</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tiến độ</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thao tác</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="assignment in assignments" :key="assignment.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <div>
                <p class="font-medium text-gray-800">{{ assignment.title }}</p>
                <p class="text-sm text-gray-500">{{ assignment.test?.title }}</p>
              </div>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center">
                <span v-if="assignment.target_type === 'branch'" class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded">
                  {{ assignment.targets_count }} chi nhánh
                </span>
                <span v-else-if="assignment.target_type === 'class'" class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                  {{ assignment.targets_count }} lớp
                </span>
                <span v-else class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">
                  {{ assignment.targets_count }} học viên
                </span>
              </div>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">
              <div>
                <p>Từ: {{ formatDate(assignment.available_from) }}</p>
                <p v-if="assignment.due_date">Đến: {{ formatDate(assignment.due_date) }}</p>
              </div>
            </td>
            <td class="px-4 py-3">
              <div class="w-32">
                <div class="flex items-center justify-between text-sm mb-1">
                  <span>{{ assignment.completed_count }}/{{ assignment.total_assigned }}</span>
                  <span>{{ getCompletionPercent(assignment) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div
                    class="bg-blue-600 h-2 rounded-full"
                    :style="{ width: `${getCompletionPercent(assignment)}%` }"
                  ></div>
                </div>
              </div>
            </td>
            <td class="px-4 py-3">
              <span
                class="px-2 py-1 text-xs rounded-full"
                :class="getStatusClass(assignment.status)"
              >
                {{ getStatusName(assignment.status) }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex justify-end space-x-2">
                <button
                  @click="viewSubmissions(assignment)"
                  class="p-1 text-blue-600 hover:bg-blue-50 rounded"
                  title="Xem bài nộp"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                  </svg>
                </button>
                <button
                  @click="viewStatistics(assignment)"
                  class="p-1 text-green-600 hover:bg-green-50 rounded"
                  title="Thống kê"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                  </svg>
                </button>
                <button
                  @click="editAssignment(assignment)"
                  class="p-1 text-gray-600 hover:bg-gray-100 rounded"
                  title="Chỉnh sửa"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button
                  @click="deleteAssignment(assignment)"
                  class="p-1 text-red-600 hover:bg-red-50 rounded"
                  title="Xóa"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.per_page" class="px-4 py-3 border-t flex items-center justify-between">
        <p class="text-sm text-gray-600">
          Hiển thị {{ assignments.length }} / {{ pagination.total }}
        </p>
        <div class="flex space-x-2">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="px-3 py-1 border rounded disabled:opacity-50"
          >
            Trước
          </button>
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-1 border rounded disabled:opacity-50"
          >
            Sau
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <AssignmentFormModal
      v-if="showModal"
      :assignment="selectedAssignment"
      @close="closeModal"
      @saved="onAssignmentSaved"
    />

    <!-- Submissions Modal -->
    <SubmissionsModal
      v-if="showSubmissionsModal"
      :assignment="selectedAssignment"
      @close="showSubmissionsModal = false"
    />

    <!-- Statistics Modal -->
    <StatisticsModal
      v-if="showStatisticsModal"
      :assignment="selectedAssignment"
      @close="showStatisticsModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, defineAsyncComponent } from 'vue'
import api from '@/api'

const AssignmentFormModal = defineAsyncComponent(() =>
  import('./AssignmentFormModal.vue')
)
const SubmissionsModal = defineAsyncComponent(() =>
  import('./SubmissionsModal.vue')
)
const StatisticsModal = defineAsyncComponent(() =>
  import('./StatisticsModal.vue')
)

const loading = ref(false)
const assignments = ref([])
const pagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  last_page: 1
})

const filters = ref({
  search: '',
  status: '',
  test_type: ''
})

const showModal = ref(false)
const showSubmissionsModal = ref(false)
const showStatisticsModal = ref(false)
const selectedAssignment = ref(null)

let searchTimeout = null

onMounted(() => {
  fetchAssignments()
})

async function fetchAssignments(page = 1) {
  loading.value = true
  try {
    const response = await api.get('/examination/assignments', {
      params: { ...filters.value, page }
    })
    assignments.value = response.data.data.data
    pagination.value = {
      current_page: response.data.data.current_page,
      per_page: response.data.data.per_page,
      total: response.data.data.total,
      last_page: response.data.data.last_page
    }
  } catch (error) {
    console.error('Error fetching assignments:', error)
  } finally {
    loading.value = false
  }
}

function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchAssignments(), 300)
}

function resetFilters() {
  filters.value = { search: '', status: '', test_type: '' }
  fetchAssignments()
}

function changePage(page) {
  fetchAssignments(page)
}

function openCreateModal() {
  selectedAssignment.value = null
  showModal.value = true
}

function editAssignment(assignment) {
  selectedAssignment.value = assignment
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  selectedAssignment.value = null
}

function onAssignmentSaved() {
  closeModal()
  fetchAssignments()
}

async function deleteAssignment(assignment) {
  if (!confirm('Bạn có chắc muốn xóa bài giao này?')) return

  try {
    await api.delete(`/examination/assignments/${assignment.id}`)
    fetchAssignments()
  } catch (error) {
    console.error('Error deleting assignment:', error)
    Swal.fire('Lỗi', 'Có lỗi xảy ra khi xóa', 'error')
  }
}

function viewSubmissions(assignment) {
  selectedAssignment.value = assignment
  showSubmissionsModal.value = true
}

function viewStatistics(assignment) {
  selectedAssignment.value = assignment
  showStatisticsModal.value = true
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function getCompletionPercent(assignment) {
  if (!assignment.total_assigned) return 0
  return Math.round((assignment.completed_count / assignment.total_assigned) * 100)
}

function getStatusName(status) {
  const names = {
    scheduled: 'Lên lịch',
    active: 'Đang diễn ra',
    completed: 'Đã kết thúc',
    cancelled: 'Đã hủy'
  }
  return names[status] || status
}

function getStatusClass(status) {
  const classes = {
    scheduled: 'bg-yellow-100 text-yellow-800',
    active: 'bg-green-100 text-green-800',
    completed: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}
</script>
