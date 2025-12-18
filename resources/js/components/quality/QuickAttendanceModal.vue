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
            <DialogPanel class="w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
              <!-- Header -->
              <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900 mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                  </svg>
                  <span>Điểm danh nhanh - Buổi {{ session?.session_number }}</span>
                </div>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </DialogTitle>

              <!-- Session Info -->
              <div class="bg-blue-50 rounded-lg p-3 mb-4 text-sm">
                <div class="flex items-center gap-4">
                  <div><strong>Ngày:</strong> {{ formatDate(session?.scheduled_date) }}</div>
                  <div><strong>Bài học:</strong> {{ session?.lesson_title }}</div>
                </div>
              </div>

              <!-- Loading State -->
              <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                <span class="ml-3 text-gray-600">Đang tải...</span>
              </div>

              <!-- Attendance List -->
              <div v-else class="space-y-3 max-h-96 overflow-y-auto">
                <div v-for="student in attendanceData" :key="student.student_id"
                  class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                  <div class="flex items-start gap-4">
                    <!-- Student Name -->
                    <div class="flex-1">
                      <h4 class="font-medium text-gray-900">{{ student.student_name }}</h4>
                    </div>

                    <!-- Attendance Status -->
                    <div class="flex-1">
                      <label class="block text-xs text-gray-500 mb-1">Trạng thái</label>
                      <select v-model="student.status"
                        @change="onStatusChange(student)"
                        class="w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Chọn trạng thái --</option>
                        <option value="present">✅ Có mặt</option>
                        <option value="late">⏰ Đi muộn</option>
                        <option value="absent">❌ Vắng mặt</option>
                        <option value="excused">📋 Có phép</option>
                      </select>
                    </div>

                    <!-- Check-in Time -->
                    <div class="flex-1">
                      <label class="block text-xs text-gray-500 mb-1">Giờ đến</label>
                      <input type="time" v-model="student.check_in_time"
                        @change="onCheckInTimeChange(student)"
                        :disabled="student.status === 'absent'"
                        class="w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:text-gray-400">
                    </div>

                    <!-- Late Minutes - Always rendered to maintain layout -->
                    <div class="flex-1" :class="{ 'invisible': student.status !== 'late' }">
                      <label class="block text-xs text-gray-500 mb-1">Số phút muộn (tự động)</label>
                      <input type="number" v-model.number="student.late_minutes" min="0"
                        readonly
                        class="w-full text-sm border-gray-200 rounded-md bg-gray-50 text-gray-600 cursor-not-allowed"
                        placeholder="0">
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="mt-6 flex items-center justify-end gap-3">
                <button @click="closeModal"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                  Hủy
                </button>
                <button @click="saveAttendance" :disabled="saving"
                  class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                  <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>{{ saving ? 'Đang lưu...' : 'Lưu điểm danh' }}</span>
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
                  <span>{{ sendingZalo ? 'Đang gửi...' : 'Gửi thông báo Zalo' }}</span>
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

// Watch for modal open
watch(() => props.isOpen, (newValue) => {
  if (newValue) {
    saved.value = false; // Reset saved state when opening modal
    sendingZalo.value = false; // Reset Zalo sending state
    loadAttendanceData();
  }
});

const loadAttendanceData = async () => {
  loading.value = true;
  try {
    console.log('📊 [QuickAttendance] Loading data for session:', props.session.id, 'Session number:', props.session.session_number);

    // Get existing attendance records (use quick-attendance route with permission check)
    const response = await axios.get(`/api/quality/sessions/${props.session.id}/quick-attendance`);

    console.log('📥 [QuickAttendance] API Response:', response.data);

    if (response.data.success) {
      // Check if there are any saved attendance records (not default values)
      const hasSavedRecords = response.data.data.some(student =>
        student.check_in_time !== null || student.status !== null
      );

      attendanceData.value = response.data.data.map(student => {
        const hasCheckInTime = student.check_in_time !== null && student.check_in_time !== '';

        // Format time from HH:MM:SS to HH:MM for HTML time input
        // ONLY set time if there's actually saved data
        let formattedTime = '';
        if (hasCheckInTime) {
          // If time is in HH:MM:SS format, extract HH:MM
          formattedTime = student.check_in_time.substring(0, 5);
        }
        // Don't set default time for unsaved sessions

        return {
          ...student,
          status: student.status || '', // Convert null to empty string for select
          check_in_time: formattedTime, // Will be empty string if no saved time
          late_minutes: student.late_minutes || 0
        };
      });

      // If there are saved records, show the "Send Zalo" button
      if (hasSavedRecords) {
        saved.value = true;
        console.log('✅ [QuickAttendance] Found existing attendance records, enabling Zalo button');
      }

      console.log('✅ [QuickAttendance] Loaded attendance data:', attendanceData.value.length, 'students');
      console.log('📊 [QuickAttendance] Sample data:', attendanceData.value[0]);
    }
  } catch (error) {
    console.error('❌ [QuickAttendance] Error loading data:', error);
    Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: 'Không thể tải dữ liệu điểm danh'
    });
  } finally {
    loading.value = false;
  }
};

const onStatusChange = (student) => {
  // Handle empty status (no selection)
  if (!student.status || student.status === '') {
    student.check_in_time = '';
    student.late_minutes = 0;
    return;
  }

  // Clear check-in time if absent
  if (student.status === 'absent') {
    student.check_in_time = '';
    student.late_minutes = 0;
  } else if (!student.check_in_time) {
    // Set default time if switching from absent/empty to other status
    student.check_in_time = props.session.start_time || '08:00';
  }

  // Auto-calculate late minutes when status is late
  if (student.status === 'late' && student.check_in_time && props.session.start_time) {
    student.late_minutes = calculateLateMinutes(student.check_in_time, props.session.start_time);
  } else if (student.status !== 'late') {
    student.late_minutes = 0;
  }
};

const onCheckInTimeChange = (student) => {
  // Auto-calculate late minutes when check-in time changes and status is late
  if (student.status === 'late' && student.check_in_time && props.session.start_time) {
    student.late_minutes = calculateLateMinutes(student.check_in_time, props.session.start_time);
  }
};

const calculateLateMinutes = (checkInTime, startTime) => {
  if (!checkInTime || !startTime) return 0;

  // Parse times (format: "HH:MM")
  const [checkInHour, checkInMinute] = checkInTime.split(':').map(Number);
  const [startHour, startMinute] = startTime.split(':').map(Number);

  const checkInMinutes = checkInHour * 60 + checkInMinute;
  const startMinutes = startHour * 60 + startMinute;

  const lateMinutes = checkInMinutes - startMinutes;

  return lateMinutes > 0 ? lateMinutes : 0;
};

const saveAttendance = async () => {
  saving.value = true;
  try {
    console.log('💾 [QuickAttendance] Saving attendance...');

    const payload = {
      attendance: attendanceData.value.map(student => ({
        student_id: student.student_id,
        user_id: student.user_id,
        status: student.status,
        check_in_time: student.status !== 'absent' ? student.check_in_time : null,
        late_minutes: student.status === 'late' ? student.late_minutes : 0
      }))
    };

    const response = await axios.post(
      `/api/quality/sessions/${props.session.id}/quick-attendance`,
      payload
    );

    if (response.data.success) {
      console.log('✅ [QuickAttendance] Saved successfully');
      saved.value = true;

      Swal.fire({
        icon: 'success',
        title: 'Thành công',
        text: 'Đã lưu điểm danh',
        timer: 2000,
        showConfirmButton: false
      });

      emit('saved');
    }
  } catch (error) {
    console.error('❌ [QuickAttendance] Error saving:', error);
    Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: error.response?.data?.message || 'Không thể lưu điểm danh'
    });
  } finally {
    saving.value = false;
  }
};

const sendZaloNotification = async () => {
  sendingZalo.value = true;
  try {
    console.log('📱 [QuickAttendance] Sending Zalo notification...');

    const response = await axios.post(
      `/api/quality/classes/${props.classData.id}/sessions/${props.session.id}/send-attendance-notification`
    );

    if (response.data.success) {
      console.log('✅ [QuickAttendance] Zalo notification sent');

      Swal.fire({
        icon: 'success',
        title: 'Thành công',
        text: 'Đã gửi thông báo Zalo',
        timer: 1500,
        showConfirmButton: false
      });

      // Don't auto-close - let user close manually
    }
  } catch (error) {
    console.error('❌ [QuickAttendance] Error sending Zalo:', error);
    Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: error.response?.data?.message || 'Không thể gửi thông báo Zalo'
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
  console.log('🚪 [QuickAttendance] closeModal() called - Stack trace:', new Error().stack);
  emit('close');
};
</script>
