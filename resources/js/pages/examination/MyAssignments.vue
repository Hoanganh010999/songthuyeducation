<template>
  <div class="my-assignments">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Bài tập của tôi</h1>
      <p class="text-gray-600">Các bài test được giao cho bạn</p>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b">
      <nav class="flex space-x-8">
        <button
          @click="activeTab = 'pending'"
          class="py-3 px-1 border-b-2 font-medium text-sm"
          :class="activeTab === 'pending' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        >
          Chưa làm
          <span v-if="pendingCount" class="ml-2 px-2 py-0.5 bg-red-100 text-red-800 rounded-full text-xs">
            {{ pendingCount }}
          </span>
        </button>
        <button
          @click="activeTab = 'completed'"
          class="py-3 px-1 border-b-2 font-medium text-sm"
          :class="activeTab === 'completed' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        >
          Đã hoàn thành
        </button>
        <button
          @click="activeTab = 'all'"
          class="py-3 px-1 border-b-2 font-medium text-sm"
          :class="activeTab === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        >
          Tất cả
        </button>
      </nav>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
      <p class="mt-4 text-gray-600">Đang tải...</p>
    </div>

    <!-- Empty state -->
    <div v-else-if="filteredAssignments.length === 0" class="text-center py-12">
      <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
      </svg>
      <p class="mt-4 text-gray-600">Không có bài tập nào</p>
    </div>

    <!-- Assignments grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="assignment in filteredAssignments"
        :key="assignment.id"
        class="bg-white rounded-lg shadow-sm border overflow-hidden hover:shadow-md transition-shadow"
      >
        <!-- Card header -->
        <div class="p-4 border-b" :class="getHeaderClass(assignment)">
          <div class="flex items-start justify-between">
            <div>
              <span class="text-xs font-medium uppercase" :class="getTypeTextClass(assignment.test?.type)">
                {{ assignment.test?.type }}
              </span>
              <h3 class="mt-1 font-semibold text-gray-800">{{ assignment.title }}</h3>
            </div>
            <span
              v-if="assignment.is_past_due && !assignment.latest_submission"
              class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded"
            >
              Quá hạn
            </span>
          </div>
        </div>

        <!-- Card body -->
        <div class="p-4">
          <p v-if="assignment.description" class="text-sm text-gray-600 mb-3 line-clamp-2">
            {{ assignment.description }}
          </p>

          <div class="space-y-2 text-sm">
            <!-- Time limit -->
            <div v-if="assignment.test?.time_limit" class="flex items-center text-gray-600">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {{ assignment.test.time_limit }} phút
            </div>

            <!-- Due date -->
            <div v-if="assignment.due_date" class="flex items-center" :class="assignment.is_past_due ? 'text-red-600' : 'text-gray-600'">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              Hạn: {{ formatDate(assignment.due_date) }}
            </div>

            <!-- Attempts -->
            <div class="flex items-center text-gray-600">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Lần làm: {{ assignment.my_attempts }} / {{ assignment.max_attempts }}
            </div>

            <!-- Latest score -->
            <div v-if="assignment.latest_submission" class="flex items-center">
              <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="text-green-600">
                Điểm: {{ parseFloat(assignment.latest_submission.percentage || 0).toFixed(1) }}%
              </span>
            </div>
          </div>
        </div>

        <!-- Card footer -->
        <div class="px-4 py-3 bg-gray-50 border-t">
          <!-- Nếu đã có bài nộp (submitted/graded/grading) → Ưu tiên xem kết quả -->
          <div v-if="assignment.latest_submission && ['submitted', 'graded', 'grading'].includes(assignment.latest_submission?.status)" class="space-y-2">
            <button
              @click="viewResult(assignment)"
              class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              Xem kết quả
            </button>
            
            <!-- Toggle Special View Button -->
            <button
              @click="toggleSpecialView(assignment.latest_submission)"
              class="w-full px-3 py-2 flex items-center justify-center gap-2 border rounded-lg transition-colors text-sm"
              :class="assignment.latest_submission.allow_special_view 
                ? 'border-green-300 bg-green-50 text-green-700 hover:bg-green-100' 
                : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="assignment.latest_submission.allow_special_view" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path v-if="assignment.latest_submission.allow_special_view" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
              </svg>
              <span>
                {{ assignment.latest_submission.allow_special_view ? 'Đang chia sẻ' : 'Chia sẻ bài làm' }}
              </span>
            </button>
            
            <button
              v-if="assignment.can_attempt"
              @click="startTest(assignment)"
              class="w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors text-sm"
            >
              Làm lại
            </button>
          </div>
          <!-- Nếu chưa có bài nộp hoặc đang làm dở → Nút bắt đầu -->
          <button
            v-else-if="assignment.can_attempt"
            @click="startTest(assignment)"
            class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            {{ assignment.my_attempts > 0 ? 'Tiếp tục làm' : 'Bắt đầu làm bài' }}
          </button>
          <p v-else class="text-center text-sm text-gray-500">
            Đã hết lượt làm bài
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAssignmentStore } from '@/stores/examination'
import api from '@/api'
import Swal from 'sweetalert2'

const router = useRouter()
const store = useAssignmentStore()

const activeTab = ref('pending')
const loading = computed(() => store.loading)
const assignments = computed(() => store.myAssignments)

const pendingCount = computed(() => {
  const list = assignments.value || []
  return list.filter(a => !a.latest_submission && a.is_available).length
})

const filteredAssignments = computed(() => {
  const list = assignments.value || []
  switch (activeTab.value) {
    case 'pending':
      // Chưa làm: Chưa có submission hoặc submission chưa nộp (in_progress)
      return list.filter(a => !a.latest_submission || a.latest_submission?.status === 'in_progress')
    case 'completed':
      // Đã hoàn thành: Có submission đã nộp (submitted/graded)
      return list.filter(a => a.latest_submission && ['submitted', 'graded', 'grading'].includes(a.latest_submission?.status))
    default:
      return list
  }
})

onMounted(() => {
  store.fetchMyAssignments()
})

function startTest(assignment) {
  router.push({
    name: 'examination.take-test',
    params: { assignmentId: assignment.id }
  })
}

function viewResult(assignment) {
  router.push({
    name: 'examination.result',
    params: { submissionId: assignment.latest_submission.id }
  })
}

async function toggleSpecialView(submission) {
  try {
    const response = await api.post(`/examination/submissions/${submission.id}/toggle-special-view`)
    
    if (response.data.success) {
      // Update local state
      submission.allow_special_view = response.data.data.allow_special_view
      
      // Show success message
      await Swal.fire({
        title: 'Thành công',
        text: response.data.message,
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      })
      
      // Refresh assignments to update UI
      await store.fetchMyAssignments()
    }
  } catch (error) {
    console.error('Error toggling special view:', error)
    Swal.fire({
      title: 'Lỗi',
      text: error.response?.data?.message || 'Không thể cập nhật cài đặt chia sẻ',
      icon: 'error'
    })
  }
}

function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function getHeaderClass(assignment) {
  if (assignment.latest_submission) {
    return 'bg-green-50'
  }
  if (assignment.is_past_due) {
    return 'bg-red-50'
  }
  return 'bg-gray-50'
}

function getTypeTextClass(type) {
  const classes = {
    ielts: 'text-purple-600',
    cambridge: 'text-blue-600',
    toeic: 'text-green-600',
    custom: 'text-gray-600'
  }
  return classes[type] || 'text-gray-600'
}
</script>
