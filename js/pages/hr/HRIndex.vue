<template>
  <div class="flex h-full">
    <!-- HR Menu (Left Sidebar) -->
    <div class="w-80 bg-white border-r border-gray-200 overflow-y-auto">
      <div class="p-6 border-b border-gray-200">
        <h1 class="text-2xl font-bold text-gray-900">Quản lý nhân sự</h1>
        <p class="text-sm text-gray-600 mt-1">Tổ chức, nhân viên và phòng ban</p>
      </div>

      <!-- HR Menu Items -->
      <div class="p-4">
        <!-- Organizational Structure -->
        <div v-if="authStore.hasPermission('org_chart.view')" class="mb-2">
          <router-link
            to="/hr/org-chart"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-blue-50 text-blue-600': $route.path === '/hr/org-chart' }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">Sơ đồ tổ chức</div>
                <div class="text-xs text-gray-500">Cấu trúc phân cấp</div>
              </div>
            </div>
          </router-link>
        </div>

        <!-- Employees -->
        <div v-if="authStore.hasPermission('employees.view')" class="mb-2">
          <router-link
            to="/hr/employees"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-blue-50 text-blue-600': $route.path === '/hr/employees' }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">Nhân viên</div>
                <div class="text-xs text-gray-500">Danh sách nhân viên</div>
              </div>
            </div>
          </router-link>
        </div>

        <!-- Departments -->
        <div v-if="authStore.hasPermission('departments.view')" class="mb-2">
          <router-link
            to="/hr/departments"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-blue-50 text-blue-600': $route.path === '/hr/departments' }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">Phòng ban</div>
                <div class="text-xs text-gray-500">Quản lý phòng ban</div>
              </div>
            </div>
          </router-link>
        </div>

        <!-- Invitations -->
        <div v-if="authStore.hasPermission('invitations.view')" class="mb-2">
          <router-link
            to="/hr/invitations"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-blue-50 text-blue-600': $route.path === '/hr/invitations' }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">Lời mời</div>
                <div class="text-xs text-gray-500">Quản lý lời mời tham gia</div>
              </div>
            </div>
          </router-link>
        </div>

        <!-- Job Title Settings -->
        <div v-if="authStore.hasPermission('hr.manage')" class="mb-2">
          <router-link
            to="/hr/job-titles"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-gray-50 transition"
            :class="{ 'bg-blue-50 text-blue-600': $route.path === '/hr/job-titles' }"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </div>
              <div class="text-left">
                <div class="font-semibold text-gray-900">Chức danh</div>
                <div class="text-xs text-gray-500">Cài đặt chức danh công việc</div>
              </div>
            </div>
          </router-link>
        </div>
      </div>
    </div>

    <!-- Content Area (Right Panel) -->
    <div class="flex-1 bg-gray-50 overflow-y-auto">
      <div class="p-6">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();
</script>

