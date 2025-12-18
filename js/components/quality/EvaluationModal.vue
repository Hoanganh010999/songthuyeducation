<template>
  <TransitionRoot appear :show="isOpen" as="template">
    <Dialog as="div" @close="closeModal" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black bg-opacity-50" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-6xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
              <!-- Header -->
              <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900 mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                  </svg>
                  <span>Đánh giá chi tiết - Buổi {{ session?.session_number }}</span>
                </div>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </DialogTitle>

              <!-- Session Info -->
              <div class="bg-purple-50 rounded-lg p-3 mb-4 text-sm">
                <div class="flex items-center gap-4">
                  <div><strong>Ngày:</strong> {{ formatDate(session?.scheduled_date) }}</div>
                  <div><strong>Bài học:</strong> {{ session?.lesson_title }}</div>
                </div>
              </div>

              <!-- Loading State -->
              <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div>
                <span class="ml-3 text-gray-600">Đang tải...</span>
              </div>

              <!-- Evaluation List -->
              <div v-else class="space-y-4 max-h-[60vh] overflow-y-auto">
                <div v-for="(student, index) in attendanceData" :key="student.student_id"
                  class="bg-white border-2 border-gray-200 rounded-lg p-5 hover:shadow-lg transition">

                  <!-- Student Header -->
                  <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                      <div class="flex-shrink-0 w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center text-white font-semibold">
                        {{ index + 1 }}
                      </div>
                      <div>
                        <div class="flex items-center gap-2">
                          <h4 class="font-medium text-gray-900 text-lg">{{ student.student_name }}</h4>
                          <span class="text-xs text-gray-500">{{ student.student_code }}</span>
                        </div>

                        <!-- Student Stats -->
                        <div class="flex items-center gap-3 mt-1 text-xs">
                          <span class="text-gray-600">
                            📊 Điểm danh: <strong class="text-purple-600">{{ student.attendance_percentage }}%</strong>
                          </span>
                          <span class="text-gray-600">
                            📚 Hoàn thành BTVN: <strong class="text-purple-600">{{ student.homework_completion_rate }}%</strong>
                          </span>
                        </div>
                      </div>
                    </div>

                    <!-- Homework Submission Status & Links -->
                    <div class="flex items-center gap-2">
                      <div v-if="student.homework_submission_status === 'submitted' || student.homework_submission_status === 'graded'"
                        class="flex items-center gap-1 text-xs font-medium text-green-600 bg-green-100 px-2 py-1 rounded-full">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Đã nộp BTVN</span>
                      </div>
                      <div v-else
                        class="flex items-center gap-1 text-xs font-medium text-gray-500 bg-gray-200 px-2 py-1 rounded-full">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Chưa nộp</span>
                      </div>

                      <!-- Homework Links -->
                      <div v-if="student.homework_submission && (student.homework_submission.unit_folder_link || student.homework_submission.submission_link)"
                        class="flex items-center gap-1">
                        <a v-if="student.homework_submission.unit_folder_link"
                          :href="student.homework_submission.unit_folder_link"
                          target="_blank"
                          class="flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-2 py-1 rounded-full transition"
                          title="Xem folder bài tập">
                          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                          </svg>
                          <span>Folder</span>
                        </a>
                        <a v-if="student.homework_submission.submission_link"
                          :href="student.homework_submission.submission_link"
                          target="_blank"
                          class="flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-2 py-1 rounded-full transition"
                          title="Xem bài nộp">
                          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                          </svg>
                          <span>Bài nộp</span>
                        </a>
                      </div>
                    </div>
                  </div>

                  <!-- Evaluation Fields -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg">
                    <!-- Homework Score -->
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-2">
                        Điểm BTVN
                        <span v-if="!student.homework_submission_status || (student.homework_submission_status !== 'submitted' && student.homework_submission_status !== 'graded')"
                          class="text-xs text-red-500 font-normal">
                          (Cần nộp bài)
                        </span>
                      </label>
                      <div class="flex items-center gap-2">
                        <input
                          v-model.number="student.homework_achieved_points"
                          type="number"
                          min="0"
                          :max="lessonMaxPoints"
                          :disabled="!student.homework_submission_status || (student.homework_submission_status !== 'submitted' && student.homework_submission_status !== 'graded')"
                          :class="[
                            'w-full text-sm border rounded-md focus:ring-purple-500 focus:border-purple-500',
                            (!student.homework_submission_status || (student.homework_submission_status !== 'submitted' && student.homework_submission_status !== 'graded'))
                              ? 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed'
                              : 'border-gray-300'
                          ]"
                          :placeholder="`0-${lessonMaxPoints}`"
                        />
                        <span class="text-sm text-gray-500 whitespace-nowrap">/ {{ lessonMaxPoints }}</span>
                      </div>
                    </div>

                    <!-- Interaction Score -->
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-2">Điểm tương tác (1-10)</label>
                      <input
                        v-model.number="student.interaction_score"
                        type="number"
                        min="1"
                        max="10"
                        class="w-full text-sm border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500"
                        placeholder="1-10"
                      />
                    </div>

                    <!-- Participation Level -->
                    <div class="md:col-span-2">
                      <label class="block text-xs font-medium text-gray-700 mb-2">Mức độ tham gia</label>
                      <select
                        v-model="student.participation_score"
                        class="w-full text-sm border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500"
                      >
                        <option value="">Chọn mức độ</option>
                        <option value="5">Xuất sắc</option>
                        <option value="4">Tốt</option>
                        <option value="3">Trung bình</option>
                        <option value="2">Cần cải thiện</option>
                        <option value="1">Yếu</option>
                      </select>
                    </div>

                    <!-- Comment -->
                    <div class="md:col-span-3">
                      <label class="block text-xs font-medium text-gray-700 mb-2">Nhận xét</label>
                      <textarea
                        v-model="student.comment"
                        rows="3"
                        class="w-full text-sm border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500"
                        placeholder="Nhận xét về học viên trong buổi học này..."
                      ></textarea>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="mt-6 flex items-center justify-end gap-3 border-t pt-4">
                <button @click="closeModal"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                  Hủy
                </button>
                <button @click="saveEvaluation" :disabled="saving"
                  class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                  <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>{{ saving ? 'Đang lưu...' : 'Lưu đánh giá' }}</span>
                </button>
                <button v-if="saved" @click="sendZaloNotification" :disabled="sendingZalo"
                  class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                  <svg v-if="sendingZalo" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                  </svg>
                  <span>{{ sendingZalo ? 'Đang gửi...' : 'Gửi báo cáo Zalo' }}</span>
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  session: {
    type: Object,
    required: true
  },
  classData: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close', 'saved']);

const loading = ref(false);
const saving = ref(false);
const saved = ref(false);
const sendingZalo = ref(false);
const attendanceData = ref([]);
const lessonMaxPoints = ref(100); // Default, will be loaded from lesson plan

// Watch for modal open
watch(() => props.isOpen, (newValue) => {
  if (newValue) {
    saved.value = false; // Reset saved state when opening modal
    sendingZalo.value = false; // Reset Zalo sending state
    loadEvaluationData();
  }
});

const loadEvaluationData = async () => {
  loading.value = true;
  try {
    console.log('📊 [EvaluationModal] Loading data for session:', props.session.id, 'Session number:', props.session.session_number);

    // Get lesson max points from session prop
    lessonMaxPoints.value = props.session.lesson_max_points || 100;

    // Load multiple data sources in parallel
    const [attendanceResponse, studentsResponse, homeworkResponse, statsResponse] = await Promise.all([
      axios.get(`/api/quality/classes/${props.classData.id}/sessions/${props.session.id}/attendance`),
      axios.get(`/api/quality/classes/${props.classData.id}/students`),
      axios.get(`/api/quality/classes/${props.classData.id}/sessions/${props.session.id}/homework-submissions`),
      axios.get(`/api/quality/classes/${props.classData.id}/student-stats`)
    ]);

    const existingAttendances = attendanceResponse.data.success ? attendanceResponse.data.data : [];
    const students = studentsResponse.data.data.filter(s => s.status === 'active');
    const submissions = homeworkResponse.data.success ? homeworkResponse.data.data : [];
    const stats = statsResponse.data.success ? statsResponse.data.data : {};

    // Check if there are any saved evaluation records (not default values)
    const hasSavedRecords = existingAttendances.some(att =>
      att.homework_score !== null || att.participation_score !== null || att.notes !== null
    );

    // Build attendance data with stats
    attendanceData.value = students.map(student => {
      const existingAttendance = existingAttendances.find(a => a.user_id === student.user_id);
      const submission = submissions.find(s => s.student_id === student.user_id);
      const studentStats = stats[student.user_id] || {};

      console.log('👤 [EvaluationModal] Student:', student.student_name, {
        user_id: student.user_id,
        existingAttendance: existingAttendance,
        homework_score: existingAttendance?.homework_score,
        participation_score: existingAttendance?.participation_score,
        notes: existingAttendance?.notes
      });

      return {
        student_id: student.student_id,
        user_id: student.user_id,
        student_name: student.student_name || 'N/A',
        student_code: student.student_code || 'N/A',
        homework_achieved_points: existingAttendance?.homework_score ?? null,
        interaction_score: existingAttendance?.participation_score ?? null,
        participation_score: existingAttendance?.participation_score ?? null,
        comment: existingAttendance?.notes || '',
        homework_submission_status: submission ? submission.status : 'not_submitted',
        homework_submission: submission || null,
        attendance_percentage: studentStats.attendance_percentage || 0,
        homework_completion_rate: studentStats.homework_completion_rate || 0
      };
    });

    // If there are saved records, show the "Send Zalo" button
    if (hasSavedRecords) {
      saved.value = true;
      console.log('✅ [EvaluationModal] Found existing evaluation records, enabling Zalo button');
    }

    console.log('✅ [EvaluationModal] Loaded data:', attendanceData.value.length, 'students');
  } catch (error) {
    console.error('❌ [EvaluationModal] Error loading data:', error);
    Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: 'Không thể tải dữ liệu đánh giá'
    });
  } finally {
    loading.value = false;
  }
};

const saveEvaluation = async () => {
  saving.value = true;
  try {
    console.log('💾 [EvaluationModal] Saving evaluation...');

    const payload = {
      evaluations: attendanceData.value.map(student => ({
        student_id: student.student_id,
        user_id: student.user_id,
        homework_achieved_points: student.homework_achieved_points || 0,
        interaction_score: student.interaction_score || null,
        participation_score: student.participation_score || null,
        notes: student.comment || ''
      }))
    };

    const response = await axios.post(
      `/api/quality/classes/${props.classData.id}/sessions/${props.session.id}/evaluations`,
      payload
    );

    if (response.data.success) {
      console.log('✅ [EvaluationModal] Saved successfully');
      saved.value = true;

      Swal.fire({
        icon: 'success',
        title: 'Thành công',
        text: 'Đã lưu đánh giá',
        timer: 2000,
        showConfirmButton: false
      });

      emit('saved');
    }
  } catch (error) {
    console.error('❌ [EvaluationModal] Error saving:', error);
    Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: error.response?.data?.message || 'Không thể lưu đánh giá'
    });
  } finally {
    saving.value = false;
  }
};

const sendZaloNotification = async () => {
  sendingZalo.value = true;
  try {
    console.log('📱 [EvaluationModal] Sending Zalo notification...');

    const response = await axios.post(
      `/api/quality/classes/${props.classData.id}/sessions/${props.session.id}/send-evaluation-notification`
    );

    if (response.data.success) {
      console.log('✅ [EvaluationModal] Zalo notification sent');

      Swal.fire({
        icon: 'success',
        title: 'Thành công',
        text: 'Đã gửi báo cáo Zalo',
        timer: 2000,
        showConfirmButton: false
      });

      // Close modal after sending
      setTimeout(() => {
        closeModal();
      }, 2000);
    }
  } catch (error) {
    console.error('❌ [EvaluationModal] Error sending Zalo:', error);
    Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: error.response?.data?.message || 'Không thể gửi báo cáo Zalo'
    });
  } finally {
    sendingZalo.value = false;
  }
};

const formatDate = (date) => {
  if (!date) return '';
  const d = new Date(date);
  return d.toLocaleDateString('vi-VN', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const closeModal = () => {
  console.log('🚪 [EvaluationModal] closeModal() called - Stack trace:', new Error().stack);
  emit('close');
};
</script>
