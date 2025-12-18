<template>
  <div class="my-assignments">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Bài tập của tôi</h1>
      <p class="text-gray-500 mt-1">Danh sách các bài test được giao cho bạn</p>
    </div>

    <!-- Tabs -->
    <div class="tabs tabs-boxed mb-6 bg-white p-1 inline-flex">
      <a
        class="tab"
        :class="{ 'tab-active': activeTab === 'pending' }"
        @click="activeTab = 'pending'"
      >
        Chưa làm
        <span v-if="pendingCount" class="badge badge-error badge-sm ml-2">{{ pendingCount }}</span>
      </a>
      <a
        class="tab"
        :class="{ 'tab-active': activeTab === 'in_progress' }"
        @click="activeTab = 'in_progress'"
      >
        Đang làm
      </a>
      <a
        class="tab"
        :class="{ 'tab-active': activeTab === 'completed' }"
        @click="activeTab = 'completed'"
      >
        Đã hoàn thành
      </a>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <span class="loading loading-spinner loading-lg"></span>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredAssignments.length === 0" class="text-center py-12">
      <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
      <p class="text-gray-500">{{ getEmptyMessage() }}</p>
    </div>

    <!-- Assignment Cards -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="assignment in filteredAssignments"
        :key="assignment.id"
        class="card bg-white shadow-lg hover:shadow-xl transition-all"
        :class="{ 'border-l-4 border-error': isOverdue(assignment) }"
      >
        <div class="card-body">
          <!-- Test Type Badge -->
          <div class="flex items-center gap-2 mb-2">
            <span class="badge" :class="getTypeBadgeClass(assignment.test?.type)">
              {{ assignment.test?.type?.toUpperCase() }}
            </span>
            <span v-if="isOverdue(assignment)" class="badge badge-error">Quá hạn</span>
            <span v-else-if="getTimeRemaining(assignment)" class="badge badge-warning">
              {{ getTimeRemaining(assignment) }}
            </span>
          </div>

          <!-- Title -->
          <h2 class="card-title">{{ assignment.title || assignment.test?.title }}</h2>

          <!-- Test Info -->
          <div class="text-sm text-gray-600 space-y-1">
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              {{ assignment.test?.questions_count || 0 }} câu hỏi
            </div>
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              {{ getTimeLimitText(assignment) }}
            </div>
            <div v-if="assignment.end_date" class="flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              Hạn: {{ formatDate(assignment.end_date) }}
            </div>
          </div>

          <!-- Latest Submission (if any) -->
          <div v-if="assignment.latest_submission" class="mt-3 p-3 bg-base-200 rounded-lg">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Lần thử gần nhất:</span>
              <span class="badge" :class="getScoreBadgeClass(assignment.latest_submission)">
                {{ assignment.latest_submission.score }}/{{ assignment.latest_submission.max_score }}
              </span>
            </div>
            <div class="text-xs text-gray-500 mt-1">
              {{ formatDateTime(assignment.latest_submission.submitted_at) }}
            </div>
          </div>

          <!-- Attempts Info -->
          <div v-if="assignment.max_attempts" class="text-sm text-gray-500 mt-2">
            Số lần làm: {{ assignment.attempts_count || 0 }}/{{ assignment.max_attempts }}
          </div>

          <!-- Actions -->
          <div class="card-actions justify-end mt-4">
            <button
              v-if="canStartTest(assignment)"
              @click="startTest(assignment)"
              class="btn btn-primary"
            >
              {{ assignment.latest_submission ? 'Làm lại' : 'Bắt đầu làm' }}
            </button>
            <button
              v-else-if="hasInProgressSubmission(assignment)"
              @click="continueTest(assignment)"
              class="btn btn-warning"
            >
              Tiếp tục làm
            </button>
            <button
              v-if="assignment.latest_submission"
              @click="viewResult(assignment)"
              class="btn btn-ghost"
            >
              Xem kết quả
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import examinationApi from '@/services/examinationApi'

const router = useRouter()

const loading = ref(false)
const assignments = ref([])
const activeTab = ref('pending')

const pendingCount = computed(() => {
  return assignments.value.filter(a => !a.latest_submission && isAvailable(a)).length
})

const filteredAssignments = computed(() => {
  return assignments.value.filter(a => {
    switch (activeTab.value) {
      case 'pending':
        return !a.latest_submission && isAvailable(a)
      case 'in_progress':
        return hasInProgressSubmission(a)
      case 'completed':
        return a.latest_submission?.status === 'submitted' || a.latest_submission?.status === 'graded'
      default:
        return true
    }
  })
})

onMounted(() => {
  loadMyAssignments()
})

async function loadMyAssignments() {
  loading.value = true
  try {
    const response = await examinationApi.assignments.my()
    assignments.value = response.data.data
  } catch (error) {
    console.error('Failed to load assignments:', error)
  } finally {
    loading.value = false
  }
}

function isAvailable(assignment) {
  const now = new Date()
  const start = assignment.start_date ? new Date(assignment.start_date) : null
  const end = assignment.end_date ? new Date(assignment.end_date) : null

  if (start && now < start) return false
  if (end && now > end) return false
  return assignment.is_active
}

function isOverdue(assignment) {
  if (!assignment.end_date) return false
  return new Date() > new Date(assignment.end_date) && !assignment.latest_submission
}

function canStartTest(assignment) {
  if (!isAvailable(assignment)) return false
  if (assignment.max_attempts && assignment.attempts_count >= assignment.max_attempts) return false
  if (hasInProgressSubmission(assignment)) return false
  return true
}

function hasInProgressSubmission(assignment) {
  return assignment.latest_submission?.status === 'in_progress'
}

function getTimeRemaining(assignment) {
  if (!assignment.end_date) return null
  const end = new Date(assignment.end_date)
  const now = new Date()
  const diff = end - now

  if (diff < 0) return null

  const hours = Math.floor(diff / (1000 * 60 * 60))
  const days = Math.floor(hours / 24)

  if (days > 0) return `Còn ${days} ngày`
  if (hours > 0) return `Còn ${hours} giờ`
  return 'Sắp hết hạn'
}

function getTimeLimitText(assignment) {
  const limit = assignment.time_limit || assignment.test?.time_limit
  return limit ? `${limit} phút` : 'Không giới hạn'
}

function getTypeBadgeClass(type) {
  const map = {
    ielts: 'badge-error',
    cambridge: 'badge-primary',
    toeic: 'badge-warning',
    custom: 'badge-info',
    quiz: 'badge-success',
  }
  return map[type] || 'badge-ghost'
}

function getScoreBadgeClass(submission) {
  if (!submission) return 'badge-ghost'
  const percent = (submission.score / submission.max_score) * 100
  if (percent >= 80) return 'badge-success'
  if (percent >= 60) return 'badge-warning'
  return 'badge-error'
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

function formatDateTime(date) {
  return new Date(date).toLocaleString('vi-VN')
}

function getEmptyMessage() {
  switch (activeTab.value) {
    case 'pending': return 'Không có bài tập chưa làm'
    case 'in_progress': return 'Không có bài đang làm dở'
    case 'completed': return 'Chưa có bài hoàn thành'
    default: return 'Không có bài tập'
  }
}

async function startTest(assignment) {
  try {
    const response = await examinationApi.submissions.start(assignment.id)
    router.push({
      name: 'examination.take-test',
      params: { submissionId: response.data.data.submission_id }
    })
  } catch (error) {
    console.error('Failed to start test:', error)
    alert(error.response?.data?.message || 'Không thể bắt đầu làm bài')
  }
}

function continueTest(assignment) {
  router.push({
    name: 'examination.take-test',
    params: { submissionId: assignment.latest_submission.id }
  })
}

function viewResult(assignment) {
  router.push({
    name: 'examination.result',
    params: { submissionId: assignment.latest_submission.id }
  })
}
</script>

<style scoped>
.my-assignments {
  @apply p-6;
}
</style>
