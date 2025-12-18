<template>
  <Transition name="modal">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 p-4"
      @click.self="close"
    >
      <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between rounded-t-lg">
          <div>
            <h2 class="text-xl font-bold text-gray-900">Đăng Ký Học Thử</h2>
            <p class="text-sm text-gray-600 mt-1">{{ trialableName }}</p>
          </div>
          <button @click="close" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Steps -->
        <div class="px-6 py-4 border-b">
          <div class="flex items-center justify-center space-x-4">
            <div :class="['flex items-center', step === 1 ? 'text-teal-600' : 'text-gray-400']">
              <div :class="['w-8 h-8 rounded-full flex items-center justify-center', step === 1 ? 'bg-teal-600 text-white' : 'bg-gray-200']">1</div>
              <span class="ml-2 text-sm font-medium">Chọn lớp</span>
            </div>
            <div class="w-16 h-0.5 bg-gray-300"></div>
            <div :class="['flex items-center', step === 2 ? 'text-teal-600' : 'text-gray-400']">
              <div :class="['w-8 h-8 rounded-full flex items-center justify-center', step === 2 ? 'bg-teal-600 text-white' : 'bg-gray-200']">2</div>
              <span class="ml-2 text-sm font-medium">Chọn buổi học</span>
            </div>
          </div>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6">
          <!-- Step 1: Select Class -->
          <div v-if="step === 1">
            <div class="mb-4">
              <input
                v-model="searchClass"
                type="text"
                placeholder="Tìm kiếm lớp..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500"
              />
            </div>

            <div v-if="loadingClasses" class="text-center py-8">
              <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
            </div>

            <div v-else-if="filteredClasses.length === 0" class="text-center py-8 text-gray-500">
              Không tìm thấy lớp học nào
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div
                v-for="cls in filteredClasses"
                :key="cls.id"
                @click="selectClass(cls)"
                :class="[
                  'border rounded-lg p-4 cursor-pointer transition',
                  selectedClass?.id === cls.id
                    ? 'border-teal-600 bg-teal-50'
                    : 'border-gray-200 hover:border-teal-400 hover:bg-gray-50'
                ]"
              >
                <div class="flex items-center justify-between mb-2">
                  <h4 class="font-semibold text-gray-900">{{ cls.name }}</h4>
                  <span v-if="selectedClass?.id === cls.id" class="text-teal-600">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                  </span>
                </div>
                <div class="space-y-1 text-sm text-gray-600">
                  <div><span class="font-medium">Mã lớp:</span> {{ cls.code }}</div>
                  <div><span class="font-medium">Giáo viên:</span> {{ cls.homeroom_teacher?.name || 'N/A' }}</div>
                  <div><span class="font-medium">Học viên:</span> {{ cls.current_students }}/{{ cls.capacity }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 2: Select Sessions -->
          <div v-if="step === 2">
            <div class="mb-4">
              <button @click="step = 1" class="text-teal-600 hover:text-teal-800 flex items-center gap-2 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Quay lại chọn lớp
              </button>
            </div>

            <div class="bg-teal-50 border border-teal-200 rounded-lg p-4 mb-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-teal-600 rounded-full flex items-center justify-center text-white text-xl">🎓</div>
                <div>
                  <h4 class="font-semibold text-gray-900">{{ selectedClass?.name }}</h4>
                  <p class="text-sm text-gray-600">{{ selectedClass?.code }}</p>
                </div>
              </div>
            </div>

            <div v-if="loadingSessions" class="text-center py-8">
              <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
            </div>

            <div v-else-if="availableSessions.length === 0" class="text-center py-8 text-gray-500">
              Không có buổi học nào sắp tới
            </div>

            <div v-else>
              <div class="mb-4">
                <label class="flex items-center gap-2">
                  <input
                    v-model="selectAll"
                    type="checkbox"
                    @change="toggleSelectAll"
                    class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                  />
                  <span class="text-sm font-medium text-gray-700">Chọn tất cả ({{ availableSessions.length }} buổi)</span>
                </label>
              </div>

              <div class="space-y-3 max-h-96 overflow-y-auto">
                <label
                  v-for="session in availableSessions"
                  :key="session.id"
                  :class="[
                    'flex items-center gap-4 p-4 border rounded-lg cursor-pointer transition',
                    selectedSessions.includes(session.id)
                      ? 'border-teal-600 bg-teal-50'
                      : 'border-gray-200 hover:border-teal-400 hover:bg-gray-50'
                  ]"
                >
                  <input
                    v-model="selectedSessions"
                    type="checkbox"
                    :value="session.id"
                    class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                  />
                  <div class="flex-1">
                    <div class="flex items-center justify-between">
                      <h5 class="font-semibold text-gray-900">Buổi {{ session.session_number }}: {{ session.lesson_title || 'N/A' }}</h5>
                      <span v-if="session.trial_count > 0" class="px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full">
                        👤 {{ session.trial_count }}
                      </span>
                    </div>
                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-600">
                      <span>📅 {{ formatDate(session.scheduled_date) }}</span>
                      <span>🕐 {{ formatTime(session.start_time) }} - {{ formatTime(session.end_time) }}</span>
                    </div>
                  </div>
                </label>
              </div>

              <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú (tùy chọn)</label>
                <textarea
                  v-model="notes"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500"
                  placeholder="Ghi chú về học thử..."
                ></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="border-t px-6 py-4 flex justify-between">
          <button @click="close" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Hủy</button>
          <button
            v-if="step === 1"
            @click="goToStep2"
            :disabled="!selectedClass"
            class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Tiếp theo →
          </button>
          <button
            v-if="step === 2"
            @click="register"
            :disabled="selectedSessions.length === 0 || registering"
            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ registering ? 'Đang đăng ký...' : `Đăng ký (${selectedSessions.length} buổi)` }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useSwal } from '../../composables/useSwal';
import api from '../../services/api';
import dayjs from 'dayjs';

const props = defineProps({
  show: { type: Boolean, default: false },
  trialableType: { type: String, default: 'customer' },
  trialableId: { type: Number, default: null },
  trialableName: { type: String, default: '' },
});

const emit = defineEmits(['close', 'registered']);

const swal = useSwal();

const step = ref(1);
const searchClass = ref('');
const classes = ref([]);
const selectedClass = ref(null);
const availableSessions = ref([]);
const selectedSessions = ref([]);
const selectAll = ref(false);
const notes = ref('');
const loadingClasses = ref(false);
const loadingSessions = ref(false);
const registering = ref(false);

const filteredClasses = computed(() => {
  if (!searchClass.value) return classes.value;
  const search = searchClass.value.toLowerCase();
  return classes.value.filter(cls => 
    cls.name.toLowerCase().includes(search) || 
    cls.code.toLowerCase().includes(search)
  );
});

const loadClasses = async () => {
  loadingClasses.value = true;
  try {
    const response = await api.get('/api/trial-students/available-classes');
    if (response.data.success) {
      classes.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load classes:', error);
    swal.error('Không thể tải danh sách lớp');
  } finally {
    loadingClasses.value = false;
  }
};

const selectClass = (cls) => {
  selectedClass.value = cls;
};

const goToStep2 = async () => {
  if (!selectedClass.value) return;
  step.value = 2;
  await loadSessions();
};

const loadSessions = async () => {
  loadingSessions.value = true;
  try {
    const response = await api.get(`/api/trial-students/classes/${selectedClass.value.id}/sessions`);
    if (response.data.success) {
      availableSessions.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load sessions:', error);
    swal.error('Không thể tải danh sách buổi học');
  } finally {
    loadingSessions.value = false;
  }
};

const toggleSelectAll = () => {
  if (selectAll.value) {
    selectedSessions.value = availableSessions.value.map(s => s.id);
  } else {
    selectedSessions.value = [];
  }
};

const register = async () => {
  if (selectedSessions.value.length === 0) return;
  
  registering.value = true;
  try {
    const response = await api.post('/api/trial-students/register', {
      trialable_type: props.trialableType,
      trialable_id: props.trialableId,
      class_id: selectedClass.value.id,
      session_ids: selectedSessions.value,
      notes: notes.value,
    });
    
    if (response.data.success) {
      swal.success(response.data.message);
      emit('registered');
      close();
    }
  } catch (error) {
    console.error('Failed to register:', error);
    swal.error(error.response?.data?.message || 'Có lỗi xảy ra khi đăng ký');
  } finally {
    registering.value = false;
  }
};

const formatDate = (date) => {
  return dayjs(date).format('DD/MM/YYYY');
};

const formatTime = (time) => {
  if (!time) return 'N/A';
  return dayjs(time, 'HH:mm:ss').format('HH:mm');
};

const close = () => {
  step.value = 1;
  selectedClass.value = null;
  selectedSessions.value = [];
  notes.value = '';
  selectAll.value = false;
  searchClass.value = '';
  emit('close');
};

watch(() => props.show, (newVal) => {
  if (newVal) {
    loadClasses();
  }
});

watch(selectedSessions, () => {
  selectAll.value = selectedSessions.value.length === availableSessions.value.length && availableSessions.value.length > 0;
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>

