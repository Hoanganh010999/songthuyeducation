<template>
  <div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-6 py-4 mb-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <button
              @click="goBack"
              class="text-gray-600 hover:text-gray-900"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
            </button>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Lesson Plan Editor</h1>
              <p class="text-sm text-gray-600 mt-1">
                Session: {{ sessionInfo.session_number || 'N/A' }} - {{ sessionInfo.session_name || 'Loading...' }}
              </p>
            </div>
          </div>
          <button
            @click="saveLessonPlan"
            :disabled="saving"
            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2"
          >
            <svg v-if="saving" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ saving ? 'Đang lưu...' : 'Lưu Lesson Plan' }}
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
        <svg class="animate-spin h-12 w-12 text-blue-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-600">Loading lesson plan...</p>
      </div>

      <!-- Lesson Plan Builder -->
      <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <LessonPlanBuilder
          :initial-blocks="blocks"
          :session-id="sessionId"
          :session-data="sessionInfo"
          @blocks-updated="blocks = $event"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import LessonPlanBuilder from './components/LessonPlanBuilder.vue';
import api from '@/api';
import Swal from 'sweetalert2';

const router = useRouter();
const route = useRoute();

const sessionId = ref(parseInt(route.params.sessionId));
const sessionInfo = ref({});
const blocks = ref([]);
const loading = ref(true);
const saving = ref(false);

onMounted(async () => {
  await loadSessionData();
});

async function loadSessionData() {
  try {
    loading.value = true;
    const response = await api.get(`/quality/sessions/${sessionId.value}`);

    if (response.data.success) {
      sessionInfo.value = response.data.data;

      // Process blocks to extract first problem/solution from arrays
      const loadedBlocks = response.data.data.blocks || [];
      blocks.value = loadedBlocks.map(block => ({
        ...block,
        stages: (block.stages || []).map(stage => {
          // Extract first problem/solution from arrays for UI
          const learnerProblems = stage.procedure?.learner_problems || [];
          const taskProblems = stage.procedure?.task_problems || [];

          return {
            ...stage,
            learner_problem: learnerProblems[0]?.problem || '',
            learner_solution: learnerProblems[0]?.solution || '',
            task_problem: taskProblems[0]?.problem || '',
            task_solution: taskProblems[0]?.solution || ''
          };
        })
      }));
    }
  } catch (error) {
    console.error('Error loading session:', error);
    Swal.fire('Lỗi', 'Không thể tải thông tin session!', 'error');
  } finally {
    loading.value = false;
  }
}

async function saveLessonPlan() {
  try {
    saving.value = true;

    const payload = {
      blocks: blocks.value
    };

    const response = await api.post(`/quality/sessions/${sessionId.value}/lesson-plan`, payload);

    if (response.data.success) {
      await Swal.fire('Thành công', 'Lesson plan đã được lưu!', 'success');
    }
  } catch (error) {
    console.error('Error saving lesson plan:', error);
    Swal.fire('Lỗi', error.response?.data?.message || 'Có lỗi xảy ra khi lưu!', 'error');
  } finally {
    saving.value = false;
  }
}

function goBack() {
  router.back();
}
</script>
