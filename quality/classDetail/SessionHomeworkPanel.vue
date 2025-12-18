<template>
  <div v-if="homework && homework.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mt-4">
    <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
      <svg class="w-4 h-4 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
      Bài tập về nhà
    </h4>

    <div class="space-y-2">
      <div
        v-for="hw in homework"
        :key="hw.id"
        class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition"
      >
        <div class="flex-1">
          <div class="flex items-center space-x-2">
            <h5 class="text-sm font-medium text-gray-900">{{ hw.title }}</h5>
            <span
              v-if="hw.status === 'graded'"
              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800"
            >
              <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              Đã chấm
            </span>
            <span
              v-else-if="hw.status === 'submitted'"
              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800"
            >
              <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
              </svg>
              Chờ chấm ({{ hw.submitted_count }}/{{ hw.total_students }})
            </span>
            <span
              v-else
              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800"
            >
              Chưa nộp ({{ hw.not_submitted_count }}/{{ hw.total_students }})
            </span>
          </div>
          <p v-if="hw.deadline" class="text-xs text-gray-500 mt-1">
            Hạn nộp: {{ formatDate(hw.deadline) }}
          </p>
        </div>

        <div class="flex items-center space-x-2">
          <!-- View Submissions Button -->
          <button
            v-if="hw.submitted_count > 0 || hw.graded_count > 0"
            @click="openSubmissionsModal(hw)"
            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-purple-600 rounded-md hover:bg-purple-700 transition"
            title="Xem và chấm bài nộp"
          >
            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Chấm bài ({{ hw.submitted_count + hw.graded_count }})
          </button>

          <!-- Homework Folder Link -->
          <a
            v-if="hw.homework_url"
            :href="hw.homework_url"
            target="_blank"
            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-purple-700 bg-purple-50 rounded-md hover:bg-purple-100 transition"
            title="Xem đề bài"
          >
            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            Đề bài
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Homework Submissions Modal -->
  <HomeworkSubmissionsModal
    v-if="showSubmissionsModal"
    :homework="selectedHomework"
    :session="session"
    :class-data="classData"
    @close="closeSubmissionsModal"
    @graded="handleGraded"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import dayjs from 'dayjs';
import HomeworkSubmissionsModal from './HomeworkSubmissionsModal.vue';

const props = defineProps({
  session: {
    type: Object,
    required: true
  },
  classData: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['reload']);

const homework = ref([]);
const loading = ref(false);
const showSubmissionsModal = ref(false);
const selectedHomework = ref(null);

const loadHomework = async () => {
  if (!props.session.id) return;

  loading.value = true;
  try {
    const response = await axios.get(
      `/api/quality/classes/${props.classData.id}/sessions/${props.session.id}/homework-submissions`
    );

    if (response.data.success) {
      homework.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Error loading homework:', error);
  } finally {
    loading.value = false;
  }
};

const formatDate = (date) => {
  return dayjs(date).format('DD/MM/YYYY HH:mm');
};

const openSubmissionsModal = (hw) => {
  selectedHomework.value = hw;
  showSubmissionsModal.value = true;
};

const closeSubmissionsModal = () => {
  showSubmissionsModal.value = false;
  selectedHomework.value = null;
};

const handleGraded = () => {
  loadHomework(); // Reload to get updated counts
  emit('reload');
};

onMounted(() => {
  loadHomework();
});
</script>

