<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    @click.self="$emit('close')"
  >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b bg-gradient-to-r from-blue-500 to-indigo-600 rounded-t-lg">
        <h2 class="text-xl font-bold text-white">
          Thay đổi giáo viên dạy
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
            <p>📅 {{ formatDate(session.scheduled_date) }}</p>
            <p>🕐 {{ formatTime(session.start_time) }} - {{ formatTime(session.end_time) }}</p>
            <p v-if="currentTeacher" class="mt-2">
              👨‍🏫 Giáo viên hiện tại: <span class="font-medium">{{ currentTeacher }}</span>
            </p>
          </div>
        </div>

        <!-- Teacher Select -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Chọn giáo viên mới <span class="text-red-500">*</span>
          </label>
          <select
            v-model="selectedTeacherId"
            :disabled="loading"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <option v-if="loading" :value="null">⏳ Đang tải danh sách giáo viên...</option>
            <option v-else :value="null">-- Chọn giáo viên --</option>
            <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
              {{ teacher.name }} - {{ teacher.position_name }} ({{ teacher.department_name }})
            </option>
          </select>

          <!-- Warning if no teachers found -->
          <p v-if="teachers.length === 0 && !loading" class="mt-2 text-sm text-amber-600">
            ⚠️ Chưa có giáo viên nào được phân công dạy bộ môn này. Vui lòng vào <strong>Quản lý bộ môn</strong> để thêm giáo viên.
          </p>
        </div>

        <!-- Warning message -->
        <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-900">
          <p class="font-semibold mb-1">💡 Lưu ý:</p>
          <ul class="list-disc list-inside space-y-1 text-xs">
            <li>Giáo viên mới sẽ nhận thông báo Zalo về lịch dạy</li>
            <li>Lớp học sẽ nhận thông báo thay đổi giáo viên</li>
            <li>Thay đổi này chỉ áp dụng cho buổi học này</li>
          </ul>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-end gap-3 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
        >
          Hủy
        </button>
        <button
          @click="handleSubmit"
          :disabled="loading || !selectedTeacherId || submitting"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <span v-if="submitting" class="animate-spin">⏳</span>
          {{ submitting ? 'Đang xử lý...' : 'Thay đổi giáo viên' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue';
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
  }
});

const emit = defineEmits(['close', 'changed']);

const selectedTeacherId = ref(null);
const teachers = ref([]);
const loading = ref(false);
const submitting = ref(false);

const currentTeacher = computed(() => {
  if (props.session.teacher) {
    return props.session.teacher.name;
  }
  // Add logic to get effective teacher from classSchedule if needed
  return null;
});

const formatDate = (date) => {
  return dayjs(date).format('DD/MM/YYYY');
};

const formatTime = (time) => {
  return dayjs(time, 'HH:mm:ss').format('HH:mm');
};

const fetchTeachers = () => {
  loading.value = true;
  try {
    console.log('🔍 [fetchTeachers] Starting...');
    console.log('🔍 [fetchTeachers] session:', props.session);
    console.log('🔍 [fetchTeachers] session.class:', props.session?.class);
    console.log('🔍 [fetchTeachers] session.class_schedule:', props.session?.class_schedule);

    // Get subject from session
    // Priority: classSchedule.subject > class.subject
    const subject = props.session.class_schedule?.subject || props.session.class?.subject;

    console.log('🔍 [fetchTeachers] subject:', subject);

    if (!subject) {
      console.warn('⚠️ No subject found for this session');
      console.log('🔍 Debug - session.class_schedule:', props.session?.class_schedule);
      console.log('🔍 Debug - session.class:', props.session?.class);
      teachers.value = [];
      loading.value = false;
      return;
    }

    // Get active teachers from subject
    const activeTeachers = subject.active_teachers || [];

    console.log('🔍 [fetchTeachers] active_teachers:', activeTeachers);

    if (activeTeachers.length === 0) {
      console.warn('⚠️ No active teachers found for subject:', subject.name);
      teachers.value = [];
      loading.value = false;
      return;
    }

    teachers.value = activeTeachers;
    console.log('✅ Loaded teachers from subject:', subject.name, '- Count:', teachers.value.length);
  } catch (error) {
    console.error('❌ Error loading teachers:', error);
    teachers.value = [];
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  if (!selectedTeacherId.value) {
    await Swal.fire({
      icon: 'warning',
      title: 'Thiếu thông tin',
      text: 'Vui lòng chọn giáo viên',
      confirmButtonText: 'Đồng ý'
    });
    return;
  }

  const result = await Swal.fire({
    icon: 'question',
    title: 'Xác nhận thay đổi',
    text: 'Xác nhận thay đổi giáo viên cho buổi học này?',
    showCancelButton: true,
    confirmButtonText: 'Xác nhận',
    cancelButtonText: 'Hủy',
    confirmButtonColor: '#3B82F6',
    cancelButtonColor: '#6B7280'
  });

  if (!result.isConfirmed) {
    return;
  }

  submitting.value = true;

  try {
    const response = await api.post(
      `/api/classes/${props.classId}/sessions/${props.session.id}/change-teacher`,
      {
        teacher_id: selectedTeacherId.value
      }
    );

    if (response.data.success) {
      await Swal.fire({
        icon: 'success',
        title: 'Thành công!',
        html: `
          <p class="text-gray-700">Đã thay đổi giáo viên thành công!</p>
          <p class="text-sm text-gray-500 mt-2">✅ Giáo viên mới đã nhận thông báo Zalo</p>
          <p class="text-sm text-gray-500">✅ Lớp học đã nhận thông báo thay đổi</p>
        `,
        confirmButtonText: 'Đóng',
        confirmButtonColor: '#3B82F6'
      });
      emit('changed', response.data.data);
      emit('close');
    }
  } catch (error) {
    console.error('❌ Error changing teacher:', error);
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi!',
      text: error.response?.data?.message || error.message || 'Có lỗi xảy ra khi thay đổi giáo viên',
      confirmButtonText: 'Đóng',
      confirmButtonColor: '#EF4444'
    });
  } finally {
    submitting.value = false;
  }
};

// Fetch teachers when component mounts (for v-if case)
onMounted(() => {
  if (props.show) {
    console.log('🎬 [EditSessionTeacherModal] Component mounted with show=true, fetching teachers...');
    selectedTeacherId.value = props.session.teacher_id || null;
    fetchTeachers();
  }
});

// Also watch for show prop changes
watch(() => props.show, (newVal) => {
  if (newVal) {
    console.log('👁️ [EditSessionTeacherModal] Show changed to true, fetching teachers...');
    selectedTeacherId.value = props.session.teacher_id || null;
    fetchTeachers();
  }
});
</script>
