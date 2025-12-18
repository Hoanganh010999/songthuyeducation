<template>
  <div class="p-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
      <h3 class="text-lg font-semibold text-gray-900">{{ t('class_detail.class_overview') }}</h3>
    </div>

    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <!-- Total Sessions -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">{{ t('class_detail.total_sessions') }}</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ overview.total_sessions || 0 }}</p>
          </div>
          <div class="p-3 bg-blue-100 rounded-full">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Completed Sessions -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">{{ t('class_detail.completed_sessions') }}</p>
            <p class="text-3xl font-bold text-green-600 mt-2">{{ overview.completed_sessions || 0 }}</p>
          </div>
          <div class="p-3 bg-green-100 rounded-full">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Remaining Sessions -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">{{ t('class_detail.remaining_sessions') }}</p>
            <p class="text-3xl font-bold text-orange-600 mt-2">{{ overview.remaining_sessions || 0 }}</p>
          </div>
          <div class="p-3 bg-orange-100 rounded-full">
            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Active Students -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">{{ t('class_detail.active_students') }}</p>
            <p class="text-3xl font-bold text-purple-600 mt-2">{{ overview.active_students || 0 }}</p>
          </div>
          <div class="p-3 bg-purple-100 rounded-full">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Progress and Details -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Progress Card -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ t('class_detail.progress_percentage') }}</h4>
        
        <div class="mb-4">
          <div class="flex justify-between items-center mb-2">
            <span class="text-sm font-medium text-gray-700">{{ t('class_detail.completed_sessions') }}</span>
            <span class="text-sm font-semibold text-gray-900">{{ overview.progress_percentage || 0 }}%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-4">
            <div
              class="bg-gradient-to-r from-blue-500 to-blue-600 h-4 rounded-full transition-all duration-500"
              :style="{ width: (overview.progress_percentage || 0) + '%' }"
            ></div>
          </div>
        </div>

        <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t border-gray-200">
          <div class="text-center">
            <p class="text-2xl font-bold text-green-600">{{ overview.completed_sessions || 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ t('class_detail.completed_sessions') }}</p>
          </div>
          <div class="text-center">
            <p class="text-2xl font-bold text-blue-600">{{ overview.scheduled_sessions || 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ t('class_detail.scheduled_sessions') }}</p>
          </div>
          <div class="text-center">
            <p class="text-2xl font-bold text-red-600">{{ overview.cancelled_sessions || 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ t('class_detail.cancelled_sessions') }}</p>
          </div>
        </div>
      </div>

      <!-- Class Details Card -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ t('class_detail.class_details') || 'Class Details' }}</h4>
        
        <div class="space-y-4">
          <div class="flex justify-between items-center py-2 border-b border-gray-100">
            <span class="text-sm text-gray-600">{{ t('class_detail.start_date') }}</span>
            <span class="text-sm font-medium text-gray-900">{{ formatDate(overview.start_date) }}</span>
          </div>
          
          <div class="flex justify-between items-center py-2 border-b border-gray-100">
            <span class="text-sm text-gray-600">{{ t('class_detail.end_date') }}</span>
            <span class="text-sm font-medium text-gray-900">{{ formatDate(overview.end_date) || 'N/A' }}</span>
          </div>
          
          <div class="flex justify-between items-center py-2 border-b border-gray-100">
            <span class="text-sm text-gray-600">{{ t('class_detail.class_status') }}</span>
            <span class="px-3 py-1 text-xs font-semibold rounded-full" :class="statusClass(overview.status)">
              {{ overview.status }}
            </span>
          </div>
          
          <div class="flex justify-between items-center py-2 border-b border-gray-100">
            <span class="text-sm text-gray-600">{{ t('class_detail.capacity') }}</span>
            <span class="text-sm font-medium text-gray-900">{{ overview.capacity || 'N/A' }}</span>
          </div>
          
          <div class="flex justify-between items-center py-2 border-b border-gray-100">
            <span class="text-sm text-gray-600">{{ t('class_detail.occupancy_rate') }}</span>
            <span class="text-sm font-medium text-gray-900">{{ overview.occupancy_rate || 0 }}%</span>
          </div>
          
          <div class="flex justify-between items-center py-2">
            <span class="text-sm text-gray-600">{{ t('class_detail.expected_revenue') }}</span>
            <span class="text-sm font-bold text-green-600">{{ formatCurrency(overview.expected_revenue) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Status Chart (optional - you can add a chart here if needed) -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6">
      <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ t('class_detail.class_summary') || 'Class Summary' }}</h4>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="text-center p-4 bg-blue-50 rounded-lg">
          <svg class="w-12 h-12 text-blue-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-2xl font-bold text-blue-900">{{ overview.progress_percentage || 0 }}%</p>
          <p class="text-sm text-blue-700 mt-1">{{ t('class_detail.completion_rate') || 'Completion Rate' }}</p>
        </div>
        
        <div class="text-center p-4 bg-purple-50 rounded-lg">
          <svg class="w-12 h-12 text-purple-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <p class="text-2xl font-bold text-purple-900">{{ overview.active_students || 0 }}/{{ overview.total_students || 0 }}</p>
          <p class="text-sm text-purple-700 mt-1">{{ t('class_detail.student_enrollment') || 'Student Enrollment' }}</p>
        </div>
        
        <div class="text-center p-4 bg-green-50 rounded-lg">
          <svg class="w-12 h-12 text-green-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-2xl font-bold text-green-900">{{ formatCurrency(overview.expected_revenue) }}</p>
          <p class="text-sm text-green-700 mt-1">{{ t('class_detail.expected_revenue') }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import api from '../../../api';
import dayjs from 'dayjs';

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

const overview = ref({});

const statusClass = (status) => {
  if (status === 'active') return 'bg-green-100 text-green-800';
  if (status === 'completed') return 'bg-blue-100 text-blue-800';
  if (status === 'cancelled') return 'bg-red-100 text-red-800';
  if (status === 'draft') return 'bg-gray-100 text-gray-800';
  return 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return dayjs(date).format('DD/MM/YYYY');
};

const formatCurrency = (amount) => {
  if (!amount) return '0 VNĐ';
  // Format with thousand separators, no decimal for VND
  return new Intl.NumberFormat('vi-VN').format(Math.round(amount)) + ' VNĐ';
};

const loadOverview = async () => {
  try {
    const response = await api.classes.getOverview(props.classId);
    overview.value = response.data.data;
  } catch (error) {
    console.error('Error loading overview:', error);
  }
};

onMounted(() => {
  loadOverview();
});
</script>

