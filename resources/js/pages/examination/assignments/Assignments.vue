<template>
  <div class="assignments-management">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Quản lý giao bài</h1>
        <p class="text-gray-500 mt-1">Giao bài test cho học viên hoặc lớp học</p>
      </div>
      <button @click="openCreateModal" class="btn btn-primary">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Giao bài mới
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-2">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Tìm kiếm..."
            class="input input-bordered w-full"
            @input="debouncedSearch"
          />
        </div>
        <div>
          <select v-model="filters.status" class="select select-bordered w-full" @change="loadAssignments">
            <option value="">Tất cả trạng thái</option>
            <option value="active">Đang diễn ra</option>
            <option value="upcoming">Sắp tới</option>
            <option value="ended">Đã kết thúc</option>
          </select>
        </div>
        <div>
          <select v-model="filters.test_type" class="select select-bordered w-full" @change="loadAssignments">
            <option value="">Tất cả loại test</option>
            <option value="ielts">IELTS</option>
            <option value="cambridge">Cambridge</option>
            <option value="custom">Tự tạo</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Assignments Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="table w-full">
          <thead>
            <tr>
              <th>Bài test</th>
              <th>Đối tượng</th>
              <th>Thời gian</th>
              <th>Tiến độ</th>
              <th>Trạng thái</th>
              <th class="w-24">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="6" class="text-center py-8">
                <span class="loading loading-spinner loading-lg"></span>
              </td>
            </tr>
            <tr v-else-if="assignments.length === 0">
              <td colspan="6" class="text-center py-8 text-gray-500">
                Chưa có bài giao nào
              </td>
            </tr>
            <tr v-for="assignment in assignments" :key="assignment.id" class="hover">
              <td>
                <div class="font-medium">{{ assignment.test?.title }}</div>
                <div class="text-sm text-gray-500">
                  <span class="badge badge-sm badge-ghost">{{ assignment.test?.type?.toUpperCase() }}</span>
                </div>
              </td>
              <td>
                <div class="flex flex-wrap gap-1">
                  <span v-for="target in assignment.targets?.slice(0, 3)" :key="target.id" class="badge badge-outline badge-sm">
                    {{ getTargetLabel(target) }}
                  </span>
                  <span v-if="assignment.targets?.length > 3" class="badge badge-ghost badge-sm">
                    +{{ assignment.targets.length - 3 }}
                  </span>
                </div>
              </td>
              <td>
                <div class="text-sm">
                  <div>Bắt đầu: {{ formatDateTime(assignment.start_date) }}</div>
                  <div v-if="assignment.end_date">Kết thúc: {{ formatDateTime(assignment.end_date) }}</div>
                </div>
              </td>
              <td>
                <div class="flex items-center gap-2">
                  <progress
                    class="progress progress-primary w-20"
                    :value="assignment.completion_rate || 0"
                    max="100"
                  ></progress>
                  <span class="text-sm">{{ assignment.completion_rate || 0 }}%</span>
                </div>
                <div class="text-xs text-gray-500">
                  {{ assignment.submissions_count || 0 }}/{{ assignment.targets_count || 0 }} đã nộp
                </div>
              </td>
              <td>
                <span class="badge" :class="getStatusBadgeClass(assignment)">
                  {{ getStatusLabel(assignment) }}
                </span>
              </td>
              <td>
                <div class="dropdown dropdown-end">
                  <label tabindex="0" class="btn btn-ghost btn-sm btn-circle">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01"/>
                    </svg>
                  </label>
                  <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-48">
                    <li><a @click="viewSubmissions(assignment)">Xem bài nộp</a></li>
                    <li><a @click="viewStatistics(assignment)">Thống kê</a></li>
                    <li><a @click="editAssignment(assignment)">Chỉnh sửa</a></li>
                    <li><a @click="deleteAssignment(assignment)" class="text-error">Xóa</a></li>
                  </ul>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.perPage" class="flex justify-center p-4 border-t">
        <div class="btn-group">
          <button class="btn btn-sm" :disabled="pagination.page === 1" @click="changePage(pagination.page - 1)">«</button>
          <button class="btn btn-sm">Trang {{ pagination.page }}</button>
          <button class="btn btn-sm" :disabled="pagination.page >= pagination.lastPage" @click="changePage(pagination.page + 1)">»</button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <AssignmentFormModal
      v-if="showFormModal"
      :assignment="editingAssignment"
      :initial-test-id="initialTestId"
      @close="closeFormModal"
      @saved="onAssignmentSaved"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { debounce } from 'lodash-es'
import examinationApi from '@/services/examinationApi'
import AssignmentFormModal from './AssignmentFormModal.vue'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const assignments = ref([])
const showFormModal = ref(false)
const editingAssignment = ref(null)
const initialTestId = ref(null)

const filters = reactive({
  search: '',
  status: '',
  test_type: '',
})

const pagination = reactive({
  page: 1,
  perPage: 20,
  total: 0,
  lastPage: 1,
})

onMounted(() => {
  // Check for test_id in query params
  if (route.query.test_id) {
    initialTestId.value = route.query.test_id
    showFormModal.value = true
  }
  loadAssignments()
})

async function loadAssignments() {
  loading.value = true
  try {
    const response = await examinationApi.assignments.list({
      ...filters,
      page: pagination.page,
      per_page: pagination.perPage,
    })
    assignments.value = response.data.data
    pagination.total = response.data.meta?.total || 0
    pagination.lastPage = response.data.meta?.last_page || 1
  } catch (error) {
    console.error('Failed to load assignments:', error)
  } finally {
    loading.value = false
  }
}

const debouncedSearch = debounce(() => {
  pagination.page = 1
  loadAssignments()
}, 300)

function changePage(page) {
  pagination.page = page
  loadAssignments()
}

function openCreateModal() {
  editingAssignment.value = null
  initialTestId.value = null
  showFormModal.value = true
}

function editAssignment(assignment) {
  editingAssignment.value = assignment
  showFormModal.value = true
}

function closeFormModal() {
  showFormModal.value = false
  editingAssignment.value = null
  initialTestId.value = null
  // Clear query params
  if (route.query.test_id) {
    router.replace({ query: {} })
  }
}

function onAssignmentSaved() {
  closeFormModal()
  loadAssignments()
}

function viewSubmissions(assignment) {
  router.push({
    name: 'examination.assignments.submissions',
    params: { id: assignment.id }
  })
}

function viewStatistics(assignment) {
  router.push({
    name: 'examination.assignments.statistics',
    params: { id: assignment.id }
  })
}

async function deleteAssignment(assignment) {
  if (!confirm('Bạn có chắc muốn xóa bài giao này?')) return

  try {
    await examinationApi.assignments.delete(assignment.id)
    loadAssignments()
  } catch (error) {
    console.error('Failed to delete assignment:', error)
    alert('Không thể xóa bài giao')
  }
}

function getTargetLabel(target) {
  if (target.targetable_type?.includes('User')) {
    return target.targetable?.name || 'User'
  }
  if (target.targetable_type?.includes('Class')) {
    return target.targetable?.name || 'Lớp'
  }
  return 'Unknown'
}

function getStatusLabel(assignment) {
  const now = new Date()
  const start = assignment.start_date ? new Date(assignment.start_date) : null
  const end = assignment.end_date ? new Date(assignment.end_date) : null

  if (start && now < start) return 'Sắp tới'
  if (end && now > end) return 'Đã kết thúc'
  return 'Đang diễn ra'
}

function getStatusBadgeClass(assignment) {
  const status = getStatusLabel(assignment)
  const map = {
    'Sắp tới': 'badge-warning',
    'Đang diễn ra': 'badge-success',
    'Đã kết thúc': 'badge-ghost',
  }
  return map[status] || 'badge-ghost'
}

function formatDateTime(date) {
  if (!date) return 'Không giới hạn'
  return new Date(date).toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<style scoped>
.assignments-management {
  @apply p-6;
}
</style>
