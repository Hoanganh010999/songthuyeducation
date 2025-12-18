<template>
  <div class="flex flex-col h-full">
    <!-- Module Header -->
    <div class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Examination</h1>
          <p class="text-sm text-gray-600 mt-1">{{ t('examination.index.title') }}</p>
        </div>
      </div>
    </div>

    <!-- Main Content with Sidebar -->
    <div class="flex flex-1 overflow-hidden">
      <!-- Sidebar -->
      <div class="w-64 bg-white border-r border-gray-200 overflow-y-auto">
        <div class="p-4 space-y-2">
          <!-- Section: Bài tập -->
          <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">
            {{ t('examination.index.my_assignments') }}
          </div>

          <!-- My Assignments -->
          <router-link
            :to="{ name: 'examination.my-assignments' }"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50 text-blue-600': isActive('examination.my-assignments') }"
          >
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">{{ t('examination.index.my_assignments') }}</div>
              <div class="text-xs text-gray-500">{{ t('examination.index.my_assignments_desc') }}</div>
            </div>
            <span v-if="pendingCount > 0" class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
              {{ pendingCount }}
            </span>
          </router-link>

          <!-- THI IELTS -->
          <router-link
            :to="{ name: 'examination.ielts-practice' }"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-gradient-to-r from-purple-50 to-pink-50 text-purple-700': isActive('examination.ielts-practice') }"
          >
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 overflow-hidden">
              <img src="/images/logos/ielts-logo.svg" alt="IELTS" class="w-full h-full object-cover" />
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">THI IELTS</div>
              <div class="text-xs text-gray-500">{{ t('examination.index.ielts_practice') }}</div>
            </div>
            <span class="bg-gradient-to-r from-purple-500 to-pink-500 text-white text-xs px-2 py-0.5 rounded-full font-semibold">
              NEW
            </span>
          </router-link>

          <!-- Divider -->
          <div class="my-4 border-t border-gray-200" v-if="canViewTests"></div>

          <!-- Section: Ngân hàng đề -->
          <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase" v-if="canViewTests">
            {{ t('examination.index.test_bank') }}
          </div>

          <!-- IELTS Test Bank (Ngân hàng đề) -->
          <router-link
            v-if="canViewIeltsTestBank"
            :to="{ name: 'examination.tests', query: { type: 'ielts' } }"
            @click.native="toggleIelts"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-purple-50': ieltsExpanded || isIeltsActive }"
          >
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 overflow-hidden">
              <img src="/images/logos/ielts-logo.svg" alt="IELTS" class="w-full h-full object-cover" />
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">IELTS</div>
              <div class="text-xs text-gray-500">Listening, Reading, Writing, Speaking</div>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-90': ieltsExpanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </router-link>

          <!-- IELTS Submenu -->
          <div v-if="ieltsExpanded && canViewIeltsTestBank" class="ml-6 space-y-1">
            <router-link
              :to="{ name: 'examination.tests', query: { type: 'ielts', skill: 'listening' } }"
              class="flex items-center px-4 py-2 text-sm rounded-lg hover:bg-gray-50"
              :class="{ 'bg-purple-50 text-purple-600': $route.query.skill === 'listening' && $route.query.type === 'ielts' }"
            >
              <span class="w-2 h-2 bg-purple-400 rounded-full mr-3"></span>
              Listening
            </router-link>
            <router-link
              :to="{ name: 'examination.tests', query: { type: 'ielts', skill: 'reading' } }"
              class="flex items-center px-4 py-2 text-sm rounded-lg hover:bg-gray-50"
              :class="{ 'bg-purple-50 text-purple-600': $route.query.skill === 'reading' && $route.query.type === 'ielts' }"
            >
              <span class="w-2 h-2 bg-purple-400 rounded-full mr-3"></span>
              Reading
            </router-link>
            <router-link
              :to="{ name: 'examination.tests', query: { type: 'ielts', skill: 'writing' } }"
              class="flex items-center px-4 py-2 text-sm rounded-lg hover:bg-gray-50"
              :class="{ 'bg-purple-50 text-purple-600': $route.query.skill === 'writing' && $route.query.type === 'ielts' }"
            >
              <span class="w-2 h-2 bg-purple-400 rounded-full mr-3"></span>
              Writing
            </router-link>
            <router-link
              :to="{ name: 'examination.tests', query: { type: 'ielts', skill: 'speaking' } }"
              class="flex items-center px-4 py-2 text-sm rounded-lg hover:bg-gray-50"
              :class="{ 'bg-purple-50 text-purple-600': $route.query.skill === 'speaking' && $route.query.type === 'ielts' }"
            >
              <span class="w-2 h-2 bg-purple-400 rounded-full mr-3"></span>
              Speaking
            </router-link>
          </div>

          <!-- Cambridge -->
          <button
            v-if="canViewCambridge"
            @click="toggleCambridge"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50': cambridgeExpanded || isCambridgeActive }"
          >
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 overflow-hidden">
              <img src="/images/logos/cambridge-logo.svg" alt="Cambridge" class="w-full h-full object-cover" />
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">Cambridge</div>
              <div class="text-xs text-gray-500">Starters, Movers, Flyers</div>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-90': cambridgeExpanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>

          <!-- Cambridge Submenu -->
          <div v-if="cambridgeExpanded && canViewCambridge" class="ml-6 space-y-1">
            <router-link
              :to="{ name: 'examination.tests', query: { type: 'cambridge', level: 'starters' } }"
              class="flex items-center px-4 py-2 text-sm rounded-lg hover:bg-gray-50"
              :class="{ 'bg-blue-50 text-blue-600': $route.query.level === 'starters' && $route.query.type === 'cambridge' }"
            >
              <span class="w-2 h-2 bg-green-400 rounded-full mr-3"></span>
              Starters
            </router-link>
            <router-link
              :to="{ name: 'examination.tests', query: { type: 'cambridge', level: 'movers' } }"
              class="flex items-center px-4 py-2 text-sm rounded-lg hover:bg-gray-50"
              :class="{ 'bg-blue-50 text-blue-600': $route.query.level === 'movers' && $route.query.type === 'cambridge' }"
            >
              <span class="w-2 h-2 bg-yellow-400 rounded-full mr-3"></span>
              Movers
            </router-link>
            <router-link
              :to="{ name: 'examination.tests', query: { type: 'cambridge', level: 'flyers' } }"
              class="flex items-center px-4 py-2 text-sm rounded-lg hover:bg-gray-50"
              :class="{ 'bg-blue-50 text-blue-600': $route.query.level === 'flyers' && $route.query.type === 'cambridge' }"
            >
              <span class="w-2 h-2 bg-orange-400 rounded-full mr-3"></span>
              Flyers
            </router-link>
          </div>

          <!-- Divider -->
          <div class="my-4 border-t border-gray-200" v-if="canViewQuestions"></div>

          <!-- Section: Quản lý -->
          <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase" v-if="canViewQuestions">
            {{ t('examination.index.management') }}
          </div>

          <!-- Question Bank -->
          <router-link
            v-if="canViewQuestions"
            :to="{ name: 'examination.questions' }"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50 text-blue-600': isActive('examination.questions') }"
          >
            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">{{ t('examination.index.question_bank') }}</div>
              <div class="text-xs text-gray-500">{{ t('examination.index.question_bank_desc') }}</div>
            </div>
          </router-link>

          <!-- Test Bank -->
          <router-link
            v-if="canViewTests"
            :to="{ name: 'examination.tests' }"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50 text-blue-600': isActive('examination.tests') && !$route.query.type }"
          >
            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">{{ t('examination.index.test_bank') }}</div>
              <div class="text-xs text-gray-500">{{ t('examination.index.test_bank_desc') }}</div>
            </div>
          </router-link>

          <!-- Assignments Management -->
          <router-link
            v-if="canViewAssignments"
            :to="{ name: 'examination.assignments' }"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50 text-blue-600': isActive('examination.assignments') }"
          >
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">{{ t('examination.index.assignments') }}</div>
              <div class="text-xs text-gray-500">{{ t('examination.index.assignments_desc') }}</div>
            </div>
          </router-link>

          <!-- Grading -->
          <router-link
            v-if="canViewGrading"
            :to="{ name: 'examination.grading' }"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50 text-blue-600': isActive('examination.grading') }"
          >
            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0 relative">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">{{ t('examination.index.grading') }}</div>
              <div class="text-xs text-gray-500">{{ t('examination.index.grading_desc') }}</div>
            </div>
            <span v-if="gradingPendingCount > 0" class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full font-semibold animate-pulse">
              {{ gradingPendingCount }}
            </span>
          </router-link>

          <!-- Quick Stats -->
          <div class="mt-6 mx-4 p-4 bg-gray-50 rounded-lg">
            <h3 class="text-sm font-medium text-gray-600 mb-3">{{ t('examination.index.statistics') }}</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">{{ t('examination.index.not_completed') }}:</span>
                <span class="font-medium text-red-600">{{ pendingCount }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">{{ t('examination.index.completed') }}:</span>
                <span class="font-medium text-green-600">{{ completedCount }}</span>
              </div>
            </div>
          </div>

          <!-- Divider -->
          <div class="my-4 border-t border-gray-200" v-if="canManageSettings"></div>

          <!-- Settings Menu Item -->
          <router-link
            v-if="canManageSettings"
            :to="{ name: 'examination.settings' }"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50 text-blue-600': isActive('examination.settings') }"
          >
            <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">{{ t('examination.index.settings') }}</div>
              <div class="text-xs text-gray-500">{{ t('examination.index.settings_desc') }}</div>
            </div>
          </router-link>
        </div>
      </div>

      <!-- Content Area -->
      <div class="flex-1 bg-gray-50 overflow-y-auto p-6">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAssignmentStore } from '@/stores/examination'
import { useI18n } from '@/composables/useI18n'
import api from '@/api'

const { t } = useI18n()
const route = useRoute()
const authStore = useAuthStore()
const assignmentStore = useAssignmentStore()

// Grading stats for badge
const gradingStats = ref({ total: 0, submitted: 0, grading: 0 })
let gradingPollingInterval = null

async function fetchGradingStats() {
  try {
    const response = await api.get('/examination/submissions/pending-count')
    gradingStats.value = response.data.data
  } catch (error) {
    console.error('Error fetching grading stats:', error)
  }
}

onMounted(() => {
  // Fetch grading stats if user can grade or view submissions
  if (authStore.hasPermission('examination.grading.view') || 
      authStore.hasPermission('examination.submissions.grade') ||
      authStore.hasPermission('examination.submissions.view')) {
    fetchGradingStats()
    // Poll every 30 seconds
    gradingPollingInterval = setInterval(fetchGradingStats, 30000)
  }
})

onUnmounted(() => {
  if (gradingPollingInterval) {
    clearInterval(gradingPollingInterval)
  }
})

const ieltsExpanded = ref(false)
const cambridgeExpanded = ref(false)

const canViewQuestions = computed(() => authStore.hasPermission('examination.questions.view'))
const canViewTests = computed(() => authStore.hasPermission('examination.tests.view'))
const canViewAssignments = computed(() => authStore.hasPermission('examination.assignments.view'))
const canViewGrading = computed(() => 
  authStore.hasPermission('examination.grading.view') || 
  authStore.hasPermission('examination.submissions.grade') ||
  authStore.hasPermission('examination.submissions.view')
)
// IELTS Test Bank (trong section Ngân hàng đề)
const canViewIeltsTestBank = computed(() => 
  authStore.hasPermission('examination.ielts.tests.view') ||
  authStore.hasPermission('examination.ielts.tests.create') ||
  authStore.hasPermission('examination.ielts.tests.edit') ||
  authStore.hasPermission('examination.tests.view')
)
// IELTS Practice (THI IELTS - làm bài)
const canViewIelts = computed(() => authStore.hasPermission('examination.ielts.view'))
const canViewCambridge = computed(() => authStore.hasPermission('examination.cambridge.view'))
const canManageSettings = computed(() => authStore.hasPermission('examination.settings.manage'))

const gradingPendingCount = computed(() => gradingStats.value.total || 0)

const pendingCount = computed(() => {
  const assignments = assignmentStore.myAssignments || []
  return assignments.filter(a => !a.latest_submission && a.is_available).length
})

const completedCount = computed(() => {
  const assignments = assignmentStore.myAssignments || []
  return assignments.filter(a => a.latest_submission).length
})

const isIeltsActive = computed(() => {
  return route.query.type === 'ielts'
})

const isCambridgeActive = computed(() => {
  return route.query.type === 'cambridge'
})

function isActive(routeName) {
  return route.name === routeName
}

function toggleIelts() {
  ieltsExpanded.value = !ieltsExpanded.value
}

function toggleCambridge() {
  cambridgeExpanded.value = !cambridgeExpanded.value
}
</script>
