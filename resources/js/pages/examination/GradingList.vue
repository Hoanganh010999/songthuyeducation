<template>
  <div class="grading-list">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Chấm bài</h1>
        <p class="text-gray-600">Danh sách bài làm cần chấm điểm</p>
      </div>

      <div class="flex gap-3">
        <button
          @click="showGradingPromptModal = true"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Thiết lập Prompt AI Grading
        </button>
      </div>
    </div>
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Chờ chấm</p>
            <p class="text-2xl font-bold text-orange-600">{{ stats.submitted }}</p>
          </div>
          <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Đang chấm</p>
            <p class="text-2xl font-bold text-blue-600">{{ stats.grading }}</p>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Tổng cần xử lý</p>
            <p class="text-2xl font-bold text-red-600">{{ stats.total }}</p>
          </div>
          <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Đã chấm hôm nay</p>
            <p class="text-2xl font-bold text-green-600">{{ stats.gradedToday || 0 }}</p>
          </div>
          <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <input
          v-model="filters.search"
          type="text"
          placeholder="Tìm học sinh..."
          class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          @input="debouncedSearch"
        />

        <select v-model="filters.status" @change="fetchSubmissions" class="px-4 py-2 border rounded-lg">
          <option value="">Tất cả trạng thái</option>
          <option value="submitted">Chờ chấm</option>
          <option value="grading">Đang chấm</option>
          <option value="graded">Đã chấm</option>
        </select>

        <select v-model="filters.type" @change="fetchSubmissions" class="px-4 py-2 border rounded-lg">
          <option value="">Tất cả loại đề</option>
          <option value="ielts">IELTS</option>
          <option value="cambridge">Cambridge</option>
          <option value="custom">Tự tạo</option>
        </select>

        <select v-model="filters.skill" @change="fetchSubmissions" class="px-4 py-2 border rounded-lg">
          <option value="">Tất cả kỹ năng</option>
          <option value="listening">Listening</option>
          <option value="reading">Reading</option>
          <option value="writing">Writing</option>
          <option value="speaking">Speaking</option>
        </select>

        <button @click="resetFilters" class="px-4 py-2 text-gray-600 border rounded-lg hover:bg-gray-50">
          Xóa bộ lọc
        </button>
      </div>
    </div>

    <!-- Submissions Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
      </div>

      <div v-else-if="submissions.length === 0" class="text-center py-12">
        <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="mt-4 text-gray-600">Không có bài làm nào</p>
      </div>

      <table v-else class="w-full table-fixed">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-52">Học sinh</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bài test</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-28">Kỹ năng</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-36">Nộp lúc</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">Điểm</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-28">Trạng thái</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase w-32">Thao tác</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="item in groupedSubmissions" :key="item.id || item.practiceTestId" class="hover:bg-gray-50">
            <!-- Full Test (Grouped) -->
            <template v-if="item.practiceTestId">
              <td class="px-6 py-4">
                <div class="flex items-center min-w-0">
                  <img
                    :src="item.user?.avatar || '/images/default-avatar.png'"
                    class="w-8 h-8 rounded-full mr-3 flex-shrink-0"
                    :alt="item.user?.name"
                  />
                  <div class="min-w-0 flex-1">
                    <div class="font-medium text-gray-900 truncate" :title="item.user?.name">{{ item.user?.name }}</div>
                    <div class="text-sm text-gray-500 truncate" :title="item.user?.email">{{ item.user?.email }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900 font-medium">{{ item.practiceTestTitle }}</div>
                <div class="text-xs text-gray-500">IELTS Full Test</div>
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-wrap gap-1">
                  <span v-for="sub in item.submissions" :key="sub.id"
                        class="px-2 py-1 text-xs rounded-full"
                        :class="getSkillClass(sub.assignment?.test?.subtype)">
                    {{ getSkillBadge(sub.assignment?.test?.subtype) }}
                  </span>
                  <span class="text-xs text-gray-500">({{ item.submissions.length }}/4)</span>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">
                {{ formatDate(item.submitted_at) }}
              </td>
              <td class="px-6 py-4">
                <span class="text-gray-400 text-sm">-</span>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(item.status)">
                  {{ getStatusName(item.status) }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <router-link
                  :to="{ name: 'examination.grading.detail', params: { id: item.submissions[0].id }, query: { practiceTestId: item.practiceTestId } }"
                  class="inline-block text-blue-600 hover:text-blue-800 font-medium whitespace-nowrap"
                >
                  Chấm Full Test
                </router-link>
              </td>
            </template>

            <!-- Standalone Submission -->
            <template v-else>
              <td class="px-6 py-4">
                <div class="flex items-center min-w-0">
                  <img
                    :src="item.user?.avatar || '/images/default-avatar.png'"
                    class="w-8 h-8 rounded-full mr-3 flex-shrink-0"
                    :alt="item.user?.name"
                  />
                  <div class="min-w-0 flex-1">
                    <div class="font-medium text-gray-900 truncate" :title="item.user?.name">{{ item.user?.name }}</div>
                    <div class="text-sm text-gray-500 truncate" :title="item.user?.email">{{ item.user?.email }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900">{{ item.assignment?.test?.title }}</div>
                <div class="text-xs text-gray-500">{{ getTypeName(item.assignment?.test?.type) }}</div>
              </td>
              <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs rounded-full" :class="getSkillClass(item.assignment?.test?.subtype)">
                  {{ getSkillName(item.assignment?.test?.subtype) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">
                {{ formatDate(item.submitted_at) }}
              </td>
              <td class="px-6 py-4">
                <!-- Speaking: Only show band_score (no raw score) -->
                <div v-if="item.assignment?.test?.subtype === 'speaking'" class="text-sm text-center">
                  <div v-if="item.band_score" class="space-y-0.5">
                    <div class="text-xs text-gray-500">(100%)</div>
                    <div class="font-medium text-blue-600">Band {{ formatScore(item.band_score) }}/9</div>
                  </div>
                  <div v-else class="text-gray-400">Chưa chấm</div>
                </div>
                
                <!-- Other tests: Show score + percentage + band_score -->
                <div v-else-if="item.score !== null" class="text-sm text-center">
                  <div class="font-medium">
                    {{ formatScore(item.score) }}/{{ formatScore(item.max_score) }}
                  </div>
                  <div class="text-xs text-gray-500">
                    ({{ formatPercentage(item) }}%)
                  </div>
                  <!-- Show IELTS Band Score if available -->
                  <div v-if="item.band_score" class="text-xs font-semibold text-blue-600 mt-1">
                    Band {{ formatScore(item.band_score) }}/9
                  </div>
                </div>
                
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(item.status)">
                  {{ getStatusName(item.status) }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                <router-link
                  :to="{ name: 'examination.grading.detail', params: { id: item.id } }"
                    class="inline-block text-blue-600 hover:text-blue-800 font-medium whitespace-nowrap"
                >
                  {{ item.status === 'graded' ? 'Xem' : 'Chấm điểm' }}
                </router-link>
                  
                  <!-- Publish/Unpublish button (only for graded submissions) -->
                  <button
                    v-if="item.status === 'graded'"
                    @click="togglePublish(item)"
                    :class="[
                      'px-3 py-1 text-xs font-medium rounded whitespace-nowrap transition-colors',
                      item.published_at
                        ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200'
                        : 'bg-green-100 text-green-700 hover:bg-green-200'
                    ]"
                    :title="item.published_at ? 'Thu hồi công bố' : 'Công bố điểm'"
                  >
                    {{ item.published_at ? 'Thu hồi' : 'Công bố' }}
                  </button>
                </div>
              </td>
            </template>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.per_page" class="px-6 py-4 border-t flex justify-between items-center">
        <span class="text-sm text-gray-600">
          Hiển thị {{ (pagination.current_page - 1) * pagination.per_page + 1 }} -
          {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}
          / {{ pagination.total }} bài
        </span>
        <nav class="flex space-x-2">
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
        </nav>
      </div>
    </div>

    <!-- AI Grading Prompt Modal (Consolidated) -->
    <div v-if="showGradingPromptModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl mx-4 max-h-[90vh] overflow-y-auto">
        <!-- Header -->
        <div class="p-6 border-b">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Thiết lập Prompt AI Grading</h3>
            <button @click="showGradingPromptModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6">
          <!-- Tab Navigation -->
          <div class="flex border-b border-gray-200 mb-6">
            <button
              v-for="skill in ['writing', 'speaking']"
              :key="skill"
              @click="activeGradingTab = skill"
              :class="[
                'px-6 py-3 font-medium text-sm focus:outline-none transition-colors',
                activeGradingTab === skill
                  ? 'border-b-2 border-blue-600 text-blue-600'
                  : 'text-gray-500 hover:text-gray-700'
              ]"
            >
              {{ skill.charAt(0).toUpperCase() + skill.slice(1) }}
            </button>
          </div>

          <!-- Tab Content -->
          <div class="space-y-4">
            <div v-show="activeGradingTab === 'writing'">
              <label class="block text-sm font-medium text-gray-700 mb-2">Prompt chấm bài IELTS Writing</label>
              <textarea
                v-model="writingPrompt"
                rows="15"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                placeholder="Nhập prompt cho AI chấm bài Writing..."
              ></textarea>
              <p class="mt-2 text-xs text-gray-500">
                Prompt này sẽ được sử dụng để AI chấm bài thi IELTS Writing. Đảm bảo prompt có yêu cầu về định dạng JSON đầu ra.
              </p>
            </div>

            <div v-show="activeGradingTab === 'speaking'">
              <label class="block text-sm font-medium text-gray-700 mb-2">Prompt chấm bài IELTS Speaking</label>
              <textarea
                v-model="speakingPrompt"
                rows="15"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                placeholder="Nhập prompt cho AI chấm bài Speaking..."
              ></textarea>
              <p class="mt-2 text-xs text-gray-500">
                Prompt này sẽ được sử dụng để AI chấm bài thi IELTS Speaking. Đảm bảo prompt có yêu cầu về định dạng JSON đầu ra.
              </p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
          <button
            @click="restoreDefaultGradingPrompt(activeGradingTab)"
            :disabled="savingPrompt"
            class="px-4 py-2 text-gray-600 border rounded-lg hover:bg-gray-100 disabled:opacity-50 flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Khôi phục mặc định
          </button>

          <div class="flex gap-3">
            <button
              @click="showGradingPromptModal = false"
              :disabled="savingPrompt"
              class="px-4 py-2 text-gray-600 hover:text-gray-800 disabled:opacity-50"
            >
              Hủy
            </button>
            <button
              @click="saveGradingPrompt(activeGradingTab)"
              :disabled="savingPrompt"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center"
            >
              <svg v-if="savingPrompt" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ savingPrompt ? 'Đang lưu...' : 'Lưu' }}
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import api from '@/api'
import Swal from 'sweetalert2'

const submissions = ref([])
const loading = ref(false)

// Prompt management
const showGradingPromptModal = ref(false)
const activeGradingTab = ref('writing')
const writingPrompt = ref('')
const speakingPrompt = ref('')
const savingPrompt = ref(false)

// Default prompts - SHORT INSTRUCTIONS ONLY (JSON formats handled by backend automatically)
const DEFAULT_PROMPTS = {
  writing: `You are an experienced IELTS examiner. Grade the following IELTS Writing response using the official IELTS Writing Band Descriptors.

Evaluate based on these 4 criteria (each worth 25%):
1. Task Achievement/Response (TA/TR) - fulfilling task requirements, presenting ideas
2. Coherence and Cohesion (CC) - logical organization, paragraphing, cohesive devices
3. Lexical Resource (LR) - vocabulary range, accuracy, appropriateness
4. Grammatical Range and Accuracy (GRA) - sentence structures, error-free sentences

TASK:
{task}

STUDENT RESPONSE:
{response}`,

  speaking: `You are an experienced IELTS Speaking examiner. Grade the following IELTS Speaking response using the official IELTS Speaking Band Descriptors.

Evaluate based on these 4 criteria (each worth 25%):
1. Fluency and Coherence (FC) - natural flow, pauses, self-correction, coherent ideas
2. Lexical Resource (LR) - vocabulary range, appropriateness, collocations
3. Grammatical Range and Accuracy (GRA) - sentence structures, grammar errors
4. Pronunciation (P) - clarity, individual sounds, word stress, intonation

QUESTION:
{question}

STUDENT TRANSCRIPT:
{transcript}

IMPORTANT INSTRUCTIONS:
- Provide detailed analysis in English for each criterion
- Focus on strengths, weaknesses, and specific improvement suggestions
- DO NOT quote or repeat the student's transcript in your feedback
- Analyze the content without copying the exact words
- For Pronunciation criterion, consider the Azure assessment scores as reference but use your expertise to determine the final IELTS band (0-9 scale)`
}
const stats = ref({
  total: 0,
  submitted: 0,
  grading: 0,
  gradedToday: 0
})
const pagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  last_page: 1
})
const filters = ref({
  search: '',
  status: '',
  type: '',
  skill: ''
})

// Group submissions by practice_test_id for full tests
const groupedSubmissions = computed(() => {
  const groups = {}
  const standalone = []

  submissions.value.forEach(submission => {
    if (submission.practice_test_id) {
      // Part of a practice test (full test)
      if (!groups[submission.practice_test_id]) {
        groups[submission.practice_test_id] = {
          practiceTestId: submission.practice_test_id,
          practiceTestTitle: submission.practice_test?.title || 'IELTS Full Test',
          user: submission.user,
          submissions: [],
          status: 'submitted', // will be updated
          submitted_at: submission.submitted_at
        }
      }
      groups[submission.practice_test_id].submissions.push(submission)

      // Update status to worst case (graded > grading > submitted)
      if (groups[submission.practice_test_id].status === 'graded' && submission.status !== 'graded') {
        groups[submission.practice_test_id].status = submission.status
      } else if (groups[submission.practice_test_id].status === 'grading' && submission.status === 'submitted') {
        groups[submission.practice_test_id].status = 'submitted'
      }
    } else {
      // Standalone submission
      standalone.push(submission)
    }
  })

  // Convert groups object to array and combine with standalone
  return [
    ...Object.values(groups),
    ...standalone
  ]
})

let searchTimeout = null
let pollingInterval = null

onMounted(() => {
  fetchSubmissions()
  fetchStats()
  loadWritingPrompt()
  loadSpeakingPrompt()
  // Poll for new submissions every 30 seconds
  pollingInterval = setInterval(fetchStats, 30000)
})

onUnmounted(() => {
  if (pollingInterval) {
    clearInterval(pollingInterval)
  }
})

async function fetchSubmissions(page = 1) {
  loading.value = true
  try {
    const response = await api.get('/examination/submissions', {
      params: {
        ...filters.value,
        page
      }
    })
    submissions.value = response.data.data.data
    pagination.value = {
      current_page: response.data.data.current_page,
      per_page: response.data.data.per_page,
      total: response.data.data.total,
      last_page: response.data.data.last_page
    }
  } catch (error) {
    console.error('Error fetching submissions:', error)
  } finally {
    loading.value = false
  }
}

async function fetchStats() {
  try {
    const response = await api.get('/examination/submissions/pending-count')
    stats.value = {
      ...stats.value,
      ...response.data.data
    }
  } catch (error) {
    console.error('Error fetching stats:', error)
  }
}

function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchSubmissions(), 300)
}

function resetFilters() {
  filters.value = { search: '', status: '', type: '', skill: '' }
  fetchSubmissions()
}

function changePage(page) {
  fetchSubmissions(page)
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('vi-VN')
}

function getTypeName(type) {
  const names = {
    ielts: 'IELTS',
    cambridge: 'Cambridge',
    toeic: 'TOEIC',
    custom: 'Tự tạo'
  }
  return names[type] || type
}

function getSkillName(skill) {
  const names = {
    listening: 'Listening',
    reading: 'Reading',
    writing: 'Writing',
    speaking: 'Speaking'
  }
  return names[skill] || skill || 'N/A'
}

function getSkillClass(skill) {
  const classes = {
    listening: 'bg-purple-100 text-purple-800',
    reading: 'bg-blue-100 text-blue-800',
    writing: 'bg-green-100 text-green-800',
    speaking: 'bg-orange-100 text-orange-800'
  }
  return classes[skill] || 'bg-gray-100 text-gray-800'
}

function getStatusName(status) {
  const names = {
    submitted: 'Chờ chấm',
    grading: 'Đang chấm',
    graded: 'Đã chấm',
    late: 'Nộp muộn'
  }
  return names[status] || status
}

function getStatusClass(status) {
  const classes = {
    submitted: 'bg-orange-100 text-orange-800',
    grading: 'bg-blue-100 text-blue-800',
    graded: 'bg-green-100 text-green-800',
    late: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function getSkillBadge(skill) {
  const badges = {
    listening: '🎧 L',
    reading: '📖 R',
    writing: '✍️ W',
    speaking: '🗣️ S'
  }
  return badges[skill] || skill
}

// Format score: remove .00 from integers
function formatScore(score) {
  if (score === null || score === undefined) return '-'
  // Convert to number and check if it's an integer
  const num = parseFloat(score)
  return num % 1 === 0 ? Math.floor(num) : num.toFixed(2).replace(/\.?0+$/, '')
}

// Format percentage: 100% for Speaking if submitted, otherwise calculate normally
function formatPercentage(item) {
  const skill = item.assignment?.test?.subtype
  
  // Speaking: if has submission, show 100% completion (not score-based)
  if (skill === 'speaking') {
    // Speaking always 100% if submitted (only 1 question)
    // The actual score is band_score / 9.00
    if (item.score !== null && item.max_score > 0) {
      const percentage = (item.score / item.max_score) * 100
      return percentage % 1 === 0 ? Math.floor(percentage) : percentage.toFixed(1).replace(/\.?0+$/, '')
    }
    return '100'
  }
  
  // Other skills: normal percentage
  const percentage = parseFloat(item.percentage || 0)
  return percentage % 1 === 0 ? Math.floor(percentage) : percentage.toFixed(1).replace(/\.?0+$/, '')
}

// Prompt Management Functions
async function loadWritingPrompt() {
  try {
    const response = await api.get('/examination/ai-prompts', {
      params: { module: 'prompt_writing_grading' }
    })
    if (response.data.success && response.data.data) {
      writingPrompt.value = response.data.data.prompt || DEFAULT_PROMPTS.writing
    } else {
      writingPrompt.value = DEFAULT_PROMPTS.writing
    }
  } catch (error) {
    console.error('Error loading writing prompt:', error)
    writingPrompt.value = DEFAULT_PROMPTS.writing
  }
}

async function loadSpeakingPrompt() {
  try {
    const response = await api.get('/examination/ai-prompts', {
      params: { module: 'prompt_speaking_grading' }
    })
    if (response.data.success && response.data.data) {
      speakingPrompt.value = response.data.data.prompt || DEFAULT_PROMPTS.speaking
    } else {
      speakingPrompt.value = DEFAULT_PROMPTS.speaking
    }
  } catch (error) {
    console.error('Error loading speaking prompt:', error)
    speakingPrompt.value = DEFAULT_PROMPTS.speaking
  }
}

async function saveGradingPrompt(skill) {
  savingPrompt.value = true
  try {
    const moduleMap = {
      writing: 'prompt_writing_grading',
      speaking: 'prompt_speaking_grading'
    }

    const promptMap = {
      writing: writingPrompt.value,
      speaking: speakingPrompt.value
    }

    const response = await api.post('/examination/ai-prompts', {
      module: moduleMap[skill],
      prompt: promptMap[skill]
    })

    if (response.data.success) {
      const skillName = skill.charAt(0).toUpperCase() + skill.slice(1)
      await Swal.fire('Thành công', `Prompt ${skillName} đã được lưu!`, 'success')
      showGradingPromptModal.value = false
    }
  } catch (error) {
    console.error(`Error saving ${skill} prompt:`, error)
    Swal.fire('Lỗi', 'Có lỗi xảy ra khi lưu prompt!', 'error')
  } finally {
    savingPrompt.value = false
  }
}

async function togglePublish(submission) {
  try {
    const isPublished = !!submission.published_at
    const action = isPublished ? 'unpublish' : 'publish'
    const actionText = isPublished ? 'thu hồi công bố' : 'công bố điểm'
    
    // Confirm action
    const result = await Swal.fire({
      title: isPublished ? 'Thu hồi công bố?' : 'Công bố điểm?',
      text: isPublished 
        ? 'Học sinh sẽ không còn xem được điểm của mình.'
        : 'Học sinh sẽ được xem điểm của mình.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: isPublished ? 'Thu hồi' : 'Công bố',
      cancelButtonText: 'Hủy',
      confirmButtonColor: isPublished ? '#f59e0b' : '#10b981',
    })
    
    if (!result.isConfirmed) return
    
    // Call API
    const response = await api.post(`/examination/submissions/${submission.id}/${action}`)
    
    if (response.data.success) {
      // Update local data
      if (isPublished) {
        submission.published_at = null
        submission.published_by = null
      } else {
        submission.published_at = response.data.data.published_at
        submission.published_by = response.data.data.published_by
      }
      
      await Swal.fire({
        title: 'Thành công!',
        text: `Đã ${actionText} thành công`,
        icon: 'success',
        timer: 2000,
        showConfirmButton: false,
      })
    }
  } catch (error) {
    console.error('Error toggling publish:', error)
    Swal.fire({
      title: 'Lỗi!',
      text: error.response?.data?.message || 'Không thể thực hiện thao tác',
      icon: 'error',
    })
  }
}

async function restoreDefaultGradingPrompt(skill) {
  const result = await Swal.fire({
    title: 'Khôi phục prompt mặc định?',
    text: 'Prompt hiện tại sẽ bị thay thế bằng prompt mặc định.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#2563eb',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Khôi phục',
    cancelButtonText: 'Hủy'
  })

  if (result.isConfirmed) {
    if (skill === 'writing') {
      writingPrompt.value = DEFAULT_PROMPTS.writing
    } else if (skill === 'speaking') {
      speakingPrompt.value = DEFAULT_PROMPTS.speaking
    }
    await Swal.fire('Đã khôi phục', 'Prompt mặc định đã được khôi phục. Nhớ bấm Lưu để lưu thay đổi.', 'success')
  }
}

</script>
