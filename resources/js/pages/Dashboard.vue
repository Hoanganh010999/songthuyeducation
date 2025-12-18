<template>
  <div class="space-y-6">
    <!-- Welcome Section & Period Selector -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 mb-2">
            {{ t('dashboard.welcome_message') }}, {{ authStore.currentUser?.name }}!
          </h1>
          <p class="text-gray-600">{{ t('dashboard.overview_description') }}</p>
        </div>
        <div class="flex items-center space-x-3">
          <select v-model="selectedPeriod" @change="loadDashboardData"
            class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="week">{{ t('dashboard.this_week') }}</option>
            <option value="month">{{ t('dashboard.this_month') }}</option>
            <option value="quarter">{{ t('dashboard.this_quarter') }}</option>
            <option value="year">{{ t('dashboard.this_year') }}</option>
          </select>
          <button @click="loadDashboardData" :disabled="loading"
            class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition">
            <svg class="w-5 h-5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <template v-else>
      <!-- Student/Parent View -->
      <div v-if="authStore.isStudent || authStore.isParent" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
        <svg class="w-16 h-16 mx-auto text-blue-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ t('dashboard.welcome_message') }}!</h2>
        <p class="text-gray-600">{{ t('dashboard.student_parent_message') || 'Chào mừng bạn đến với hệ thống quản lý trường học. Vui lòng sử dụng menu bên trái để truy cập các chức năng của bạn.' }}</p>
      </div>

      <!-- Staff Dashboard View -->
      <template v-else>
        <!-- Overview Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ t('dashboard.total_students') }}</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ formatNumber(dashboardData.overview?.students || 0) }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ t('dashboard.active_classes') }}</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ formatNumber(dashboardData.hr_and_teaching?.organization?.active_classes || 0) }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ t('dashboard.total_teachers') }}</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ formatNumber(dashboardData.hr_and_teaching?.staff?.teachers || 0) }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ t('dashboard.total_customers') }}</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ formatNumber(dashboardData.overview?.customers?.total || 0) }}</p>
              <p class="text-xs text-green-600 mt-1">{{ dashboardData.overview?.customers?.leads || 0 }} {{ t('dashboard.new_leads') }}</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Learning Quality -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            {{ t('dashboard.learning_quality') }}
          </h2>
          <div class="mb-4">
            <div class="flex justify-between items-center mb-1">
              <span class="text-sm text-gray-600">{{ t('dashboard.attendance_rate') }}</span>
              <span class="text-sm font-semibold" :class="getAttendanceRateColor(dashboardData.learning_quality?.attendance?.rate)">
                {{ dashboardData.learning_quality?.attendance?.rate || 0 }}%
              </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="h-2 rounded-full transition-all duration-500"
                :class="getAttendanceRateBarColor(dashboardData.learning_quality?.attendance?.rate)"
                :style="{ width: (dashboardData.learning_quality?.attendance?.rate || 0) + '%' }"></div>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-gray-50 rounded-lg p-3 text-center">
              <p class="text-2xl font-bold text-blue-600">{{ dashboardData.learning_quality?.scores?.avg_homework || 0 }}</p>
              <p class="text-xs text-gray-500">{{ t('dashboard.avg_homework') }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-center">
              <p class="text-2xl font-bold text-green-600">{{ dashboardData.learning_quality?.scores?.avg_participation || 0 }}</p>
              <p class="text-xs text-gray-500">{{ t('dashboard.avg_participation') }}</p>
            </div>
          </div>
        </div>

        <!-- HR & Teaching -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            {{ t('dashboard.hr_and_teaching') }}
          </h2>
          <div class="mb-4">
            <div class="grid grid-cols-3 gap-2">
              <div class="bg-blue-50 rounded-lg p-2 text-center">
                <p class="text-xl font-bold text-blue-600">{{ dashboardData.hr_and_teaching?.staff?.total || 0 }}</p>
                <p class="text-xs text-gray-500">{{ t('dashboard.total_staff') }}</p>
              </div>
              <div class="bg-green-50 rounded-lg p-2 text-center">
                <p class="text-xl font-bold text-green-600">{{ dashboardData.hr_and_teaching?.staff?.active || 0 }}</p>
                <p class="text-xs text-gray-500">{{ t('dashboard.active') }}</p>
              </div>
              <div class="bg-purple-50 rounded-lg p-2 text-center">
                <p class="text-xl font-bold text-purple-600">{{ dashboardData.hr_and_teaching?.staff?.teachers || 0 }}</p>
                <p class="text-xs text-gray-500">{{ t('dashboard.teachers') }}</p>
              </div>
            </div>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">{{ t('dashboard.completed_sessions') }}</span>
              <span class="text-sm font-semibold text-gray-900">{{ dashboardData.hr_and_teaching?.teaching_activity?.completed_sessions || 0 }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">{{ t('dashboard.teaching_hours') }}</span>
              <span class="text-sm font-semibold text-gray-900">{{ dashboardData.hr_and_teaching?.teaching_activity?.teaching_hours || 0 }}h</span>
            </div>
          </div>
        </div>

        <!-- Revenue -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ t('dashboard.revenue') }}
          </h2>
          <div class="mb-4">
            <div class="bg-green-50 rounded-lg p-4 mb-3">
              <p class="text-sm text-green-600 font-medium">{{ t('dashboard.total_income') }}</p>
              <p class="text-2xl font-bold text-green-700">{{ formatCurrency(dashboardData.revenue?.transactions?.income || 0) }}</p>
            </div>
            <div class="bg-red-50 rounded-lg p-4 mb-3">
              <p class="text-sm text-red-600 font-medium">{{ t('dashboard.total_expense') }}</p>
              <p class="text-2xl font-bold text-red-700">{{ formatCurrency(dashboardData.revenue?.transactions?.expense || 0) }}</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-4">
              <p class="text-sm text-blue-600 font-medium">{{ t('dashboard.balance') }}</p>
              <p class="text-2xl font-bold" :class="(dashboardData.revenue?.transactions?.balance || 0) >= 0 ? 'text-blue-700' : 'text-red-700'">
                {{ formatCurrency(dashboardData.revenue?.transactions?.balance || 0) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ t('dashboard.quick_actions') }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <router-link v-if="authStore.hasPermission('customers.view')" to="/customers"
            class="flex items-center p-3 hover:bg-gray-50 rounded-lg border border-gray-200 transition group">
            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
              <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <span class="font-medium text-gray-900 text-sm">{{ t('customers.title') }}</span>
          </router-link>
          <router-link v-if="authStore.hasPermission('classes.view')" to="/classes"
            class="flex items-center p-3 hover:bg-gray-50 rounded-lg border border-gray-200 transition group">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
              <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <span class="font-medium text-gray-900 text-sm">{{ t('classes.title') }}</span>
          </router-link>
          <router-link v-if="authStore.hasPermission('accounting.view')" to="/accounting"
            class="flex items-center p-3 hover:bg-gray-50 rounded-lg border border-gray-200 transition group">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
              <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <span class="font-medium text-gray-900 text-sm">{{ t('accounting.title') }}</span>
          </router-link>
          <router-link v-if="authStore.hasPermission('enrollments.view')" to="/enrollments"
            class="flex items-center p-3 hover:bg-gray-50 rounded-lg border border-gray-200 transition group">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
              <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
            </div>
            <span class="font-medium text-gray-900 text-sm">{{ t('enrollments.title') }}</span>
          </router-link>
        </div>
      </div>
      </template>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useI18n } from '../composables/useI18n';
import axios from 'axios';

const authStore = useAuthStore();
const { t } = useI18n();

const loading = ref(true);
const selectedPeriod = ref('month');
const dashboardData = ref({
  learning_quality: {},
  hr_and_teaching: {},
  revenue: {},
  overview: {},
  trends: {}
});

const loadDashboardData = async () => {
  loading.value = true;
  try {
    const params = {
      period: selectedPeriod.value,
      branch_id: authStore.currentBranchId || undefined
    };
    const response = await axios.get('/api/dashboard', { params });
    if (response.data.success) {
      dashboardData.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load dashboard data:', error);
  } finally {
    loading.value = false;
  }
};

const formatNumber = (num) => new Intl.NumberFormat('vi-VN').format(num || 0);

const formatCurrency = (amount) => new Intl.NumberFormat('vi-VN', {
  style: 'currency',
  currency: 'VND',
  maximumFractionDigits: 0
}).format(amount || 0);

const getAttendanceRateColor = (rate) => {
  if (rate >= 90) return 'text-green-600';
  if (rate >= 75) return 'text-yellow-600';
  return 'text-red-600';
};

const getAttendanceRateBarColor = (rate) => {
  if (rate >= 90) return 'bg-green-500';
  if (rate >= 75) return 'bg-yellow-500';
  return 'bg-red-500';
};

onMounted(() => {
  loadDashboardData();
});
</script>