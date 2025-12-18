<template>
  <div class="p-6">
    <!-- Week Navigation -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
      <div class="flex items-center justify-between">
        <button
          @click="previousWeek"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
        >
          {{ t('class_detail.previous_week') }}
        </button>
        <div class="text-center">
          <h3 class="text-lg font-semibold text-gray-900">{{ t('class_detail.weekly_schedule') }}</h3>
          <p class="text-sm text-gray-500">{{ weekStartDate }} - {{ weekEndDate }}</p>
        </div>
        <div class="flex space-x-2">
          <button
            @click="thisWeek"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
          >
            {{ t('class_detail.this_week') }}
          </button>
          <button
            @click="nextWeek"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
          >
            {{ t('class_detail.next_week') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Weekly Calendar Grid -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <div class="grid grid-cols-8 divide-x divide-gray-200 border-b-2 border-gray-300">
        <!-- Time Column Header -->
        <div class="bg-gradient-to-b from-gray-100 to-gray-50 p-2 text-center font-bold text-gray-800 border-r-2 border-gray-300">
          <div class="text-sm">{{ t('class_detail.time') || 'Khung giờ' }}</div>
        </div>
        <!-- Day Headers -->
        <div
          v-for="day in daysOfWeek"
          :key="day.key"
          class="bg-gradient-to-b from-blue-50 to-white p-2 text-center"
        >
          <div class="font-bold text-gray-800 text-sm">{{ t(`class_detail.${day.key}`) }}</div>
          <div class="text-xs text-gray-500">{{ day.date }}</div>
        </div>
      </div>

      <!-- Schedule Grid -->
      <div class="divide-y divide-gray-200">
        <div
          v-for="timeSlot in timeSlots"
          :key="timeSlot"
          class="grid grid-cols-8 divide-x divide-gray-200 hover:bg-gray-50 transition"
        >
          <!-- Time Column -->
          <div class="p-2 text-sm font-bold text-gray-700 text-center bg-gradient-to-r from-gray-50 to-white border-r-2 border-gray-200 flex items-center justify-center min-h-[70px]">
            <div class="bg-blue-100 text-blue-800 px-3 py-1 rounded-lg shadow-sm border border-blue-200">
              {{ timeSlot }}
            </div>
          </div>
          <!-- Day Columns -->
          <div
            v-for="day in daysOfWeek"
            :key="day.key"
            class="p-1.5"
          >
            <div
              v-for="schedule in getScheduleForDayAndTime(day.key, timeSlot)"
              :key="schedule.id"
              class="relative bg-gradient-to-br from-blue-50 to-blue-100 border-2 border-blue-400 rounded-md p-2 mb-1 text-xs cursor-pointer hover:from-blue-100 hover:to-blue-200 hover:border-blue-600 hover:shadow-lg transition-all duration-200 h-full"
              @click="editSchedule(schedule)"
            >
              <!-- Time Badge -->
              <div class="absolute top-0.5 right-0.5 bg-blue-600 text-white px-1.5 py-0.5 rounded text-[9px] font-bold shadow">
                {{ schedule.start_time?.substring(0, 5) || '' }}-{{ schedule.end_time?.substring(0, 5) || '' }}
              </div>
              
              <div class="font-bold text-blue-900 text-xs mb-1 pr-12 truncate">{{ schedule.subject?.name || 'N/A' }}</div>
              
              <div class="space-y-0.5">
                <div class="flex items-center text-blue-800 text-[10px]">
                  <svg class="w-2.5 h-2.5 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  <span class="font-medium truncate">{{ schedule.teacher?.full_name || 'N/A' }}</span>
                </div>
                
                <div v-if="schedule.room" class="flex items-center text-blue-700 text-[10px]">
                  <svg class="w-2.5 h-2.5 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                  <span class="truncate">{{ schedule.room?.name || 'N/A' }}</span>
                </div>
              </div>
              
              <!-- Click to edit hint -->
              <div class="mt-1 pt-1 border-t border-blue-300 text-center text-blue-600 text-[9px] font-medium opacity-0 group-hover:opacity-100">
                {{ t('class_detail.click_to_edit') }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- No Schedule Message -->
    <div v-if="!schedules || schedules.length === 0" class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
      <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      <p class="text-gray-500">{{ t('class_detail.no_schedule') || 'No schedule available' }}</p>
    </div>

    <!-- Edit Schedule Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">{{ t('class_detail.edit_schedule_time') || 'Chỉnh sửa giờ học' }}</h3>
          <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <div class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('class_detail.day_of_week') || 'Thứ' }}</label>
            <select v-model="editForm.day_of_week" class="w-full px-3 py-2 border border-gray-300 rounded-md">
              <option value="monday">{{ t('class_detail.monday') }}</option>
              <option value="tuesday">{{ t('class_detail.tuesday') }}</option>
              <option value="wednesday">{{ t('class_detail.wednesday') }}</option>
              <option value="thursday">{{ t('class_detail.thursday') }}</option>
              <option value="friday">{{ t('class_detail.friday') }}</option>
              <option value="saturday">{{ t('class_detail.saturday') }}</option>
              <option value="sunday">{{ t('class_detail.sunday') }}</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('class_detail.start_time') || 'Giờ bắt đầu' }}</label>
            <input v-model="editForm.start_time" type="time" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('class_detail.end_time') || 'Giờ kết thúc' }}</label>
            <input v-model="editForm.end_time" type="time" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
          </div>
          
          <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
            <p class="text-xs text-yellow-800">
              <strong>{{ t('common.note') || 'Lưu ý' }}:</strong> Thay đổi này chỉ áp dụng cho <strong>lịch học cố định</strong> của lớp. Để chỉnh sửa một buổi học cụ thể, vui lòng sử dụng tab "Chi tiết bài học".
            </p>
          </div>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
          <button @click="showEditModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
            {{ t('common.cancel') }}
          </button>
          <button @click="saveSchedule" :disabled="saving" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50">
            {{ saving ? t('common.saving') : t('common.save') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import api from '../../../api';
import dayjs from 'dayjs';
import weekday from 'dayjs/plugin/weekday';
import isoWeek from 'dayjs/plugin/isoWeek';

dayjs.extend(weekday);
dayjs.extend(isoWeek);

const { t } = useI18n();

const props = defineProps({
  classId: {
    type: [String, Number],
    required: true
  },
  classData: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['refresh']);

const schedules = ref([]);
const currentWeekStart = ref(dayjs().startOf('isoWeek'));
const showEditModal = ref(false);
const saving = ref(false);
const editForm = ref({
  id: null,
  day_of_week: '',
  start_time: '',
  end_time: ''
});

const daysOfWeek = computed(() => {
  const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
  return days.map((day, index) => ({
    key: day,
    date: currentWeekStart.value.add(index, 'day').format('DD/MM')
  }));
});

const weekStartDate = computed(() => currentWeekStart.value.format('DD/MM/YYYY'));
const weekEndDate = computed(() => currentWeekStart.value.add(6, 'day').format('DD/MM/YYYY'));

// Generate time slots dynamically based on actual schedules
const timeSlots = computed(() => {
  if (!schedules.value || schedules.value.length === 0) {
    // Default common time slots if no schedules
    return ['07:00', '08:00', '09:00', '10:00', '13:00', '14:00', '15:00', '16:00', '17:00'];
  }
  
  // Extract unique start time hours from schedules
  const uniqueTimes = new Set();
  schedules.value.forEach(schedule => {
    if (schedule.start_time) {
      const hour = schedule.start_time.substring(0, 2);
      uniqueTimes.add(`${hour}:00`);
    }
  });
  
  // Sort and return
  return Array.from(uniqueTimes).sort();
});

const getScheduleForDayAndTime = (day, time) => {
  if (!schedules.value) return [];
  
  return schedules.value.filter(schedule => {
    if (schedule.day_of_week !== day) return false;
    
    // Match by start_time hour
    const scheduleTime = schedule.start_time?.substring(0, 5) || '';
    return scheduleTime.startsWith(time.substring(0, 2));
  });
};

const loadSchedule = async () => {
  try {
    const response = await api.classes.getWeeklySchedule(props.classId, {
      week_start: currentWeekStart.value.format('YYYY-MM-DD')
    });
    
    // Flatten the grouped schedules
    const grouped = response.data.data.schedules;
    schedules.value = Object.values(grouped).flat();
  } catch (error) {
    console.error('Error loading schedule:', error);
  }
};

const previousWeek = () => {
  currentWeekStart.value = currentWeekStart.value.subtract(1, 'week');
  loadSchedule();
};

const nextWeek = () => {
  currentWeekStart.value = currentWeekStart.value.add(1, 'week');
  loadSchedule();
};

const thisWeek = () => {
  currentWeekStart.value = dayjs().startOf('isoWeek');
  loadSchedule();
};

const editSchedule = (schedule) => {
  editForm.value = {
    id: schedule.id,
    day_of_week: schedule.day_of_week,
    start_time: schedule.start_time?.substring(0, 5) || '',
    end_time: schedule.end_time?.substring(0, 5) || ''
  };
  showEditModal.value = true;
};

const saveSchedule = async () => {
  try {
    saving.value = true;
    
    await api.classes.updateSchedule(props.classId, editForm.value.id, {
      day_of_week: editForm.value.day_of_week,
      start_time: editForm.value.start_time,
      end_time: editForm.value.end_time
    });
    
    showEditModal.value = false;
    
    // Reload schedule to get fresh data with teacher relationship
    await loadSchedule();
    
    // Emit refresh event to parent to reload class data (including calendar)
    emit('refresh');
    
    const Swal = (await import('sweetalert2')).default;
    Swal.fire({
      icon: 'success',
      title: t('common.success') || 'Success',
      text: t('class_detail.schedule_updated') || 'Lịch học đã được cập nhật. Calendar và các buổi học chưa điểm danh đã được đồng bộ.',
      timer: 3000,
      showConfirmButton: false
    });
  } catch (error) {
    console.error('Error saving schedule:', error);
    const Swal = (await import('sweetalert2')).default;
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Failed to update schedule'
    });
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadSchedule();
});
</script>

