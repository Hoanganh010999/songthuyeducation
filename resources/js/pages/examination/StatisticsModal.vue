<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="$emit('close')"></div>

      <div class="relative bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <!-- Header -->
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between z-10">
          <div>
            <h2 class="text-lg font-semibold text-gray-800">Thống kê</h2>
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
          <!-- Loading -->
          <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          </div>

          <template v-else-if="stats">
            <!-- Overview Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
              <div class="bg-blue-50 rounded-lg p-4 text-center">
                <p class="text-sm text-blue-600">Tổng số</p>
                <p class="text-2xl font-bold text-blue-800">{{ stats.total_assigned }}</p>
              </div>
              <div class="bg-green-50 rounded-lg p-4 text-center">
                <p class="text-sm text-green-600">Đã nộp</p>
                <p class="text-2xl font-bold text-green-800">{{ stats.completed }}</p>
              </div>
              <div class="bg-yellow-50 rounded-lg p-4 text-center">
                <p class="text-sm text-yellow-600">Đang làm</p>
                <p class="text-2xl font-bold text-yellow-800">{{ stats.in_progress }}</p>
              </div>
              <div class="bg-red-50 rounded-lg p-4 text-center">
                <p class="text-sm text-red-600">Chưa làm</p>
                <p class="text-2xl font-bold text-red-800">{{ stats.not_started }}</p>
              </div>
            </div>

            <!-- Score Statistics -->
            <div class="bg-gray-50 rounded-lg p-6 mb-6">
              <h3 class="font-semibold text-gray-800 mb-4">Thống kê điểm</h3>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                  <p class="text-sm text-gray-500">Điểm TB</p>
                  <p class="text-xl font-bold">{{ stats.average_score ? parseFloat(stats.average_score).toFixed(1) : '-' }}%</p>
                </div>
                <div class="text-center">
                  <p class="text-sm text-gray-500">Điểm cao nhất</p>
                  <p class="text-xl font-bold text-green-600">{{ stats.highest_score ? parseFloat(stats.highest_score).toFixed(1) : '-' }}%</p>
                </div>
                <div class="text-center">
                  <p class="text-sm text-gray-500">Điểm thấp nhất</p>
                  <p class="text-xl font-bold text-red-600">{{ stats.lowest_score ? parseFloat(stats.lowest_score).toFixed(1) : '-' }}%</p>
                </div>
                <div class="text-center">
                  <p class="text-sm text-gray-500">Tỷ lệ đạt</p>
                  <p class="text-xl font-bold">{{ stats.pass_rate ? parseFloat(stats.pass_rate).toFixed(1) : '-' }}%</p>
                </div>
              </div>
            </div>

            <!-- Score Distribution -->
            <div class="mb-6">
              <h3 class="font-semibold text-gray-800 mb-4">Phân bố điểm</h3>
              <div class="space-y-2">
                <div v-for="(count, range) in stats.score_distribution" :key="range" class="flex items-center">
                  <span class="w-24 text-sm text-gray-600">{{ range }}</span>
                  <div class="flex-1 mx-3">
                    <div class="w-full bg-gray-200 rounded-full h-4">
                      <div
                        class="bg-blue-600 h-4 rounded-full"
                        :style="{ width: `${getDistributionPercent(count)}%` }"
                      ></div>
                    </div>
                  </div>
                  <span class="w-12 text-sm text-gray-600 text-right">{{ count }}</span>
                </div>
              </div>
            </div>

            <!-- Time Statistics -->
            <div class="bg-gray-50 rounded-lg p-6">
              <h3 class="font-semibold text-gray-800 mb-4">Thời gian làm bài</h3>
              <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                  <p class="text-sm text-gray-500">Trung bình</p>
                  <p class="text-lg font-semibold">{{ formatDuration(stats.average_time) }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Nhanh nhất</p>
                  <p class="text-lg font-semibold text-green-600">{{ formatDuration(stats.fastest_time) }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Chậm nhất</p>
                  <p class="text-lg font-semibold text-red-600">{{ formatDuration(stats.slowest_time) }}</p>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'

const props = defineProps({
  assignment: {
    type: Object,
    required: true
  }
})

defineEmits(['close'])

const loading = ref(false)
const stats = ref(null)

onMounted(() => {
  fetchStatistics()
})

async function fetchStatistics() {
  loading.value = true
  try {
    const response = await api.get(`/examination/assignments/${props.assignment.id}/statistics`)
    stats.value = response.data.data
  } catch (error) {
    console.error('Error fetching statistics:', error)
  } finally {
    loading.value = false
  }
}

function getDistributionPercent(count) {
  if (!stats.value?.completed) return 0
  return Math.round((count / stats.value.completed) * 100)
}

function formatDuration(seconds) {
  if (!seconds) return '-'
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
}
</script>
