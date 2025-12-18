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

              <!-- Evaluation List - Compact Table Design -->
              <div v-else class="space-y-2 max-h-[60vh] overflow-y-auto">
                <div v-for="(student, index) in attendanceData" :key="student.student_id"
                  class="bg-white border border-gray-200 rounded-md hover:border-purple-300 transition">

                  <!-- Student Info Row - Compact -->
                  <div class="flex items-center justify-between px-3 py-2 bg-gray-50 border-b border-gray-100">
                    <div class="flex items-center gap-2 flex-1 min-w-0">
                      <!-- Number Badge -->
                      <div class="flex-shrink-0 w-6 h-6 bg-purple-500 rounded text-white text-xs font-semibold flex items-center justify-center">
                        {{ index + 1 }}
                      </div>

                      <!-- Name & Code -->
                      <div class="flex items-center gap-2 min-w-0">
                        <span class="font-medium text-gray-900 text-sm truncate">{{ student.student_name }}</span>
                        <span class="text-xs text-gray-500 flex-shrink-0">{{ student.student_code }}</span>
                      </div>

                      <!-- Stats - Inline -->
                      <div class="hidden md:flex items-center gap-3 text-xs text-gray-600 ml-2">
                        <span>📊 <strong class="text-purple-600">{{ student.attendance_percentage }}%</strong></span>
                        <span>📚 <strong class="text-purple-600">{{ student.homework_completion_rate }}%</strong></span>
                      </div>
                    </div>

                    <!-- Status & Links - Compact -->
                    <div class="flex items-center gap-1 flex-shrink-0">
                      <span v-if="student.homework_submission_status === 'submitted' || student.homework_submission_status === 'graded'"
                        class="text-xs font-medium text-green-600 bg-green-50 px-2 py-0.5 rounded">
                        ✓
                      </span>
                      <a v-if="student.homework_submission?.unit_folder_link"
                        :href="student.homework_submission.unit_folder_link"
                        target="_blank"
                        class="text-xs text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-2 py-0.5 rounded"
                        title="Folder">
                        📁
                      </a>
                      <a v-if="student.homework_submission?.submission_link"
                        :href="student.homework_submission.submission_link"
                        target="_blank"
                        class="text-xs text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-2 py-0.5 rounded"
                        title="Bài nộp">
                        📄
                      </a>
                    </div>
                  </div>

                  <!-- Evaluation Fields Row - Redesigned Layout -->
                  <div class="px-3 py-2">
                    <div class="grid grid-cols-12 gap-3 items-start">
                      <!-- Column 1: Homework Title + Score (2 rows) -->
                      <div class="col-span-12 md:col-span-3 space-y-2">
                        <!-- Row 1: Homework Title (only shown when submitted) -->
                        <div v-if="student.homework_submission_status === 'submitted' || student.homework_submission_status === 'graded'">
                          <label class="block text-xs text-gray-600 mb-1">Bài tập</label>
                          <div class="text-sm font-medium text-purple-700 bg-purple-50 px-2 py-1 rounded truncate" :title="session?.lesson_title || 'Bài tập buổi học'">
                            {{ session?.lesson_title || 'Bài tập buổi học' }}
                          </div>
                        </div>
                        <!-- Row 2: Homework Score -->
                        <div>
                          <label class="block text-xs text-gray-600 mb-1">
                            Điểm BTVN
                            <span v-if="!student.homework_submission_status || (student.homework_submission_status !== 'submitted' && student.homework_submission_status !== 'graded')"
                              class="text-red-500">*</span>
                          </label>
                          <div class="flex items-center gap-1">
                            <input
                              v-model.number="student.homework_achieved_points"
                              type="number"
                              min="0"
                              :max="lessonMaxPoints"
                              :disabled="!student.homework_submission_status || (student.homework_submission_status !== 'submitted' && student.homework_submission_status !== 'graded')"
                              :class="[
                                'w-full text-sm border rounded px-2 py-1 focus:ring-1 focus:ring-purple-500 focus:border-purple-500',
                                (!student.homework_submission_status || (student.homework_submission_status !== 'submitted' && student.homework_submission_status !== 'graded'))
                                  ? 'border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed'
                                  : 'border-gray-300'
                              ]"
                              :placeholder="`0-${lessonMaxPoints}`"
                            />
                            <span class="text-xs text-gray-500 whitespace-nowrap">/{{ lessonMaxPoints }}</span>
                          </div>
                        </div>
                      </div>

                      <!-- Column 2: Rating (Star Rating) with Half-Star Support -->
                      <div class="col-span-12 md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Đánh giá</label>
                        <div class="flex space-x-1">
                          <button
                            v-for="star in 5"
                            :key="star"
                            @click="handleStarClick($event, star, student)"
                            class="focus:outline-none relative"
                            style="width: 20px; height: 20px;"
                          >
                            <!-- Background (gray) star -->
                            <svg
                              class="w-5 h-5 absolute inset-0 text-gray-300"
                              fill="currentColor"
                              viewBox="0 0 20 20"
                            >
                              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <!-- Filled (yellow) star with clip-path for half-star -->
                            <svg
                              class="w-5 h-5 absolute inset-0 text-yellow-400 transition-all"
                              fill="currentColor"
                              viewBox="0 0 20 20"
                              :style="getStarStyle(star, student.rating)"
                            >
                              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                          </button>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                          {{ student.rating ? student.rating + '/5' : '-' }}
                        </div>
                      </div>

                      <!-- Column 3: Behavior + Participation (2 rows) -->
                      <div class="col-span-12 md:col-span-3 space-y-2">
                        <!-- Row 1: Behavior -->
                        <div>
                          <label class="block text-xs text-gray-600 mb-1">Hành vi</label>
                          <select
                            v-model="student.behavior"
                            class="w-full text-sm border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-purple-500 focus:border-purple-500"
                          >
                            <option value="">Chọn</option>
                            <option value="Tốt">Tốt</option>
                            <option value="TB">TB</option>
                            <option value="Cần nhắc nhở">Cần nhắc nhở</option>
                          </select>
                        </div>
                        <!-- Row 2: Participation -->
                        <div>
                          <label class="block text-xs text-gray-600 mb-1">Tham gia</label>
                          <select
                            v-model="student.participation"
                            class="w-full text-sm border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-purple-500 focus:border-purple-500"
                          >
                            <option value="">Chọn</option>
                            <option value="Tích cực">Tích cực</option>
                            <option value="Tốt">Tốt</option>
                            <option value="Bình thường">Bình thường</option>
                            <option value="Ít tham gia">Ít tham gia</option>
                            <option value="Thụ động">Thụ động</option>
                            <option value="Không tham gia">Không tham gia</option>
                          </select>
                        </div>
                      </div>

                      <!-- Column 4: Comment - Textarea with more rows -->
                      <div class="col-span-12 md:col-span-4">
                        <label class="block text-xs text-gray-600 mb-1">Nhận xét</label>
                        <textarea
                          v-model="student.comment"
                          rows="3"
                          class="w-full text-sm border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 resize-none"
                          placeholder="Nhập nhận xét về học viên..."
                        ></textarea>
                      </div>
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
    const [commentsResponse, studentsResponse, homeworkResponse, statsResponse] = await Promise.all([
      axios.get(`/api/quality/sessions/${props.session.id}/quick-comments`),
      axios.get(`/api/quality/classes/${props.classData.id}/students`),
      axios.get(`/api/quality/classes/${props.classData.id}/sessions/${props.session.id}/homework-submissions`),
      axios.get(`/api/quality/classes/${props.classData.id}/student-stats`)
    ]);

    const existingComments = commentsResponse.data.success ? commentsResponse.data.data : [];
    const students = studentsResponse.data.data.filter(s => s.status === 'active');
    const submissions = homeworkResponse.data.success ? homeworkResponse.data.data : [];
    const stats = statsResponse.data.success ? statsResponse.data.data : {};

    // Check if there are any saved evaluation records (not default values)
    const hasSavedRecords = existingComments.some(comm =>
      comm.rating !== null || comm.behavior !== null || comm.participation !== null || comm.comment !== null
    );

    // Build attendance data with stats
    attendanceData.value = students.map(student => {
      const existingComment = existingComments.find(c => c.user_id === student.user_id);
      const submission = submissions.find(s => s.student_id === student.user_id);
      const studentStats = stats[student.user_id] || {};

      console.log('👤 [EvaluationModal] Student:', student.student_name, {
        user_id: student.user_id,
        existingComment: existingComment,
        rating: existingComment?.rating,
        behavior: existingComment?.behavior,
        participation: existingComment?.participation,
        comment: existingComment?.comment
      });

      return {
        student_id: student.student_id,
        user_id: student.user_id,
        student_name: student.student_name || 'N/A',
        student_code: student.student_code || 'N/A',
        homework_achieved_points: null, // Not used in EvaluationModal
        rating: existingComment?.rating ?? null,
        behavior: existingComment?.behavior || '',
        participation: existingComment?.participation || '',
        comment: existingComment?.comment || '',
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
      comments: attendanceData.value.map(student => ({
        user_id: student.user_id,
        rating: student.rating || null,
        behavior: student.behavior || null,
        participation: student.participation || null,
        comment: student.comment || ''
      }))
    };

    const response = await axios.post(
      `/api/quality/sessions/${props.session.id}/quick-comments`,
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

const handleStarClick = (event, star, student) => {
  // Get click position within the star button
  const rect = event.currentTarget.getBoundingClientRect();
  const clickX = event.clientX - rect.left;
  const halfWidth = rect.width / 2;
  
  // Click on left half = half star, right half = full star
  if (clickX < halfWidth) {
    student.rating = star - 0.5;
  } else {
    student.rating = star;
  }
};

const getStarStyle = (starNumber, rating) => {
  if (!rating) {
    return { clipPath: 'inset(0 100% 0 0)' }; // Hide completely
  }
  
  if (rating >= starNumber) {
    return { clipPath: 'inset(0 0 0 0)' }; // Show full star
  } else if (rating >= starNumber - 0.5) {
    return { clipPath: 'inset(0 50% 0 0)' }; // Show half star (left half)
  } else {
    return { clipPath: 'inset(0 100% 0 0)' }; // Hide completely
  }
};

const closeModal = () => {
  console.log('🚪 [EvaluationModal] closeModal() called - Stack trace:', new Error().stack);
  emit('close');
};
</script>
