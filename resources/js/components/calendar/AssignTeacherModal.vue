<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    @click.self="$emit('close')"
  >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b bg-gradient-to-r from-cyan-500 to-blue-600 rounded-t-lg">
        <h2 class="text-xl font-bold text-white">
          {{ t('calendar.assign_teacher_modal_title') }}
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
        <!-- Event Info -->
        <div class="mb-4 p-4 bg-gray-50 rounded-lg">
          <h3 class="font-semibold text-gray-900 mb-2">{{ event.title }}</h3>
          <div class="text-sm text-gray-600">
            <p>📅 {{ formatDate(event.start_date) }}</p>
            <p v-if="event.customer_info" class="mt-1">
              👤 {{ event.customer_info.name }}
            </p>
          </div>
        </div>

        <!-- Teacher Select -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('calendar.select_teacher') }} <span class="text-red-500">*</span>
          </label>
          <select
            v-model="selectedTeacherId"
            :disabled="loading"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <option v-if="loading" :value="null">⏳ Đang tải danh sách giáo viên...</option>
            <option v-else :value="null">-- {{ t('calendar.select_teacher') }} --</option>
            <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
              {{ teacher.name }} - {{ teacher.position_name }} ({{ teacher.department_name }})
            </option>
          </select>

          <!-- Warning if no teachers configured -->
          <p v-if="teachers.length === 0 && !loading" class="mt-2 text-sm text-amber-600">
            ⚠️ Chưa có giáo viên nào được cấu hình. Vui lòng thiết lập mã vị trí giáo viên trong module Quản lý chất lượng.
          </p>
        </div>

        <!-- Current assigned teacher if any -->
        <div v-if="event.raw?.assigned_teacher" class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm">
          <p class="text-blue-900">
            <span class="font-semibold">{{ t('calendar.assigned_teacher') }}:</span> 
            {{ event.raw.assigned_teacher.name }}
          </p>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-end gap-3 px-6 py-4 border-t bg-gray-50 rounded-b-lg">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
        >
          {{ t('calendar.cancel') }}
        </button>
        <button
          @click="handleSubmit"
          :disabled="loading || !selectedTeacherId"
          class="px-6 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <span v-if="loading" class="animate-spin">⏳</span>
          {{ t('calendar.assign_teacher') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useI18n } from '../../composables/useI18n';
import dayjs from 'dayjs';
import api from '../../services/api';
import Swal from 'sweetalert2';

const { t } = useI18n();

const props = defineProps({
  show: Boolean,
  event: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['close', 'assigned']);

const selectedTeacherId = ref(null);
const teachers = ref([]);
const loading = ref(false);

const formatDate = (date) => {
  return dayjs(date).format('DD/MM/YYYY HH:mm');
};

const fetchTeachers = async () => {
  loading.value = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    if (!branchId) {
      console.warn('⚠️ No branch selected');
      teachers.value = [];
      return;
    }

    // Step 1: Load teacher department IDs from settings
    const settingsResponse = await api.get('/api/quality/teachers/settings', {
      params: { branch_id: branchId }
    });

    const departmentIds = settingsResponse.data.data?.department_ids || [];

    if (departmentIds.length === 0) {
      console.warn('⚠️ No teacher departments configured for this branch');
      teachers.value = [];
      return;
    }

    // Step 2: Fetch teachers based on department IDs
    const response = await api.get('/api/quality/teachers', {
      params: {
        department_ids: departmentIds,
        branch_id: branchId
      }
    });

    const teachersData = response.data.data || [];
    teachers.value = Array.isArray(teachersData) ? teachersData : [];

    console.log('✅ Loaded teachers:', teachers.value.length, 'with departments:', departmentIds);
  } catch (error) {
    console.error('❌ Error fetching teachers:', error);
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
      text: t('calendar.teacher_required') || 'Vui lòng chọn giáo viên',
      confirmButtonText: 'Đồng ý'
    });
    return;
  }

  emit('assigned', selectedTeacherId.value);
};

watch(() => props.show, (newVal) => {
  if (newVal) {
    selectedTeacherId.value = props.event.raw?.assigned_teacher_id || null;
    fetchTeachers();
  }
});
</script>

