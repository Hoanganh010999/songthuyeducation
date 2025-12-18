<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    @click.self="$emit('close')"
  >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b bg-gradient-to-r from-red-500 to-orange-600 rounded-t-lg">
        <h2 class="text-xl font-bold text-white">
          🚫 Báo nghỉ buổi học
        </h2>
        <button
          @click="$emit('close')"
          class="text-white hover:text-gray-200 transition"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="px-6 py-4">
        <!-- Session Info -->
        <div class="mb-4 p-4 bg-gray-50 rounded-lg">
          <h3 class="font-semibold text-gray-900 mb-2">{{ session.lesson_title || `Buổi ${session.session_number}` }}</h3>
          <div class="text-sm text-gray-600">
            <p>📚 Lớp: {{ className }}</p>
            <p>📅 {{ formatDate(session.scheduled_date) }}</p>
            <p>🕐 {{ formatTime(session.start_time) }} - {{ formatTime(session.end_time) }}</p>
          </div>
        </div>

        <!-- Cancellation Reason -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Lý do nghỉ học <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="cancellationReason"
            :disabled="submitting"
            rows="4"
            placeholder="Nhập lý do nghỉ học..."
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed resize-none"
          ></textarea>
        </div>

        <!-- Reschedule Option -->
        <div class="mb-4">
          <label class="flex items-center space-x-2 cursor-pointer">
            <input
              type="checkbox"
              v-model="rescheduleFutureSessions"
              :disabled="submitting"
              class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500"
            />
            <span class="text-sm text-gray-700">
              Tự động điều chỉnh lịch các buổi sau
            </span>
          </label>
          <p class="ml-6 mt-1 text-xs text-gray-500">
            Các buổi học sau sẽ được dời sang ngày tiếp theo theo lịch học của lớp
          </p>
        </div>

        <!-- Warning message -->
        <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-900">
          <p class="font-semibold mb-1">⚠️ Lưu ý:</p>
          <ul class="list-disc list-inside space-y-1 text-xs">
            <li>Thông báo sẽ được gửi vào nhóm Zalo của lớp</li>
            <li>Lịch học sẽ được cập nhật trên calendar</li>
            <li v-if="rescheduleFutureSessions">Tất cả các buổi sau sẽ được dời lại</li>
            <li v-else>Chỉ buổi này bị hủy, các buổi sau giữ nguyên</li>
            <li class="text-red-700 font-semibold">Không thể hủy buổi học đã có điểm danh!</li>
          </ul>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-end gap-3 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
        >
          Hủy bỏ
        </button>
        <button
          @click="handleSubmit"
          :disabled="!cancellationReason.trim() || submitting"
          class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <span v-if="submitting" class="animate-spin">⏳</span>
          {{ submitting ? 'Đang xử lý...' : 'Xác nhận báo nghỉ' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import dayjs from 'dayjs';
import api from '../../services/api';
import Swal from 'sweetalert2';

const props = defineProps({
  show: Boolean,
  session: {
    type: Object,
    default: () => ({})
  },
  classId: {
    type: Number,
    required: true
  },
  className: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['close', 'cancelled']);

const cancellationReason = ref('');
const rescheduleFutureSessions = ref(true);
const submitting = ref(false);

const formatDate = (date) => {
  return dayjs(date).format('DD/MM/YYYY');
};

const formatTime = (time) => {
  return dayjs(time, 'HH:mm:ss').format('HH:mm');
};

const handleSubmit = async () => {
  if (!cancellationReason.value.trim()) {
    await Swal.fire({
      icon: 'warning',
      title: 'Thiếu thông tin',
      text: 'Vui lòng nhập lý do nghỉ học',
      confirmButtonText: 'Đồng ý'
    });
    return;
  }

  const confirmMessage = rescheduleFutureSessions.value
    ? 'Xác nhận báo nghỉ buổi học này? Các buổi sau sẽ được điều chỉnh lịch tự động.'
    : 'Xác nhận báo nghỉ buổi học này? Các buổi sau sẽ giữ nguyên lịch.';

  const result = await Swal.fire({
    icon: 'question',
    title: 'Xác nhận báo nghỉ',
    text: confirmMessage,
    showCancelButton: true,
    confirmButtonText: 'Xác nhận',
    cancelButtonText: 'Hủy',
    confirmButtonColor: '#EF4444',
    cancelButtonColor: '#6B7280'
  });

  if (!result.isConfirmed) {
    return;
  }

  submitting.value = true;

  try {
    const response = await api.post(
      `/api/classes/${props.classId}/sessions/${props.session.id}/cancel`,
      {
        cancellation_reason: cancellationReason.value,
        reschedule_future_sessions: rescheduleFutureSessions.value
      }
    );

    if (response.data.success) {
      const rescheduledCount = response.data.data.rescheduled_sessions;
      let htmlMessage = '<p class="text-gray-700">Đã báo nghỉ buổi học thành công!</p>';
      htmlMessage += '<p class="text-sm text-gray-500 mt-2">✅ Lớp học đã nhận thông báo qua Zalo</p>';

      if (rescheduleFutureSessions.value && rescheduledCount > 0) {
        htmlMessage += `<p class="text-sm text-blue-600 mt-2">📅 Đã điều chỉnh ${rescheduledCount} buổi học tiếp theo</p>`;
      }

      await Swal.fire({
        icon: 'success',
        title: 'Thành công!',
        html: htmlMessage,
        confirmButtonText: 'Đóng',
        confirmButtonColor: '#3B82F6'
      });
      emit('cancelled', response.data.data);
      emit('close');
    }
  } catch (error) {
    console.error('❌ Error cancelling session:', error);
    const errorMessage = error.response?.data?.message || error.message;
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi!',
      text: errorMessage || 'Có lỗi xảy ra khi báo nghỉ',
      confirmButtonText: 'Đóng',
      confirmButtonColor: '#EF4444'
    });
  } finally {
    submitting.value = false;
  }
};

watch(() => props.show, (newVal) => {
  if (newVal) {
    // Reset form
    cancellationReason.value = '';
    rescheduleFutureSessions.value = true;
  }
});
</script>
