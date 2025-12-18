<template>
  <div>
    <!-- Welcome Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
      <h1 class="text-2xl font-bold text-gray-900 mb-2">
        {{ t('dashboard.welcome_message') }}, {{ authStore.currentUser?.name }}! 👋
      </h1>
      <p class="text-gray-600">
        {{ t('common.welcome') }}
      </p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <!-- Total Users -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">{{ t('dashboard.total_users') }}</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.totalUsers }}</p>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Total Roles -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">{{ t('dashboard.total_roles') }}</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.totalRoles }}</p>
          </div>
          <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Total Permissions -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">{{ t('dashboard.total_permissions') }}</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.totalPermissions }}</p>
          </div>
          <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Your Permissions -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">{{ t('dashboard.your_permissions') }}</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ authStore.userPermissions.length }}</p>
          </div>
          <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- User Info & Permissions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Your Roles -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ t('dashboard.your_roles') }}</h2>
        <div class="space-y-2">
          <div
            v-for="role in authStore.userRoles"
            :key="role.id"
            class="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200"
          >
            <div>
              <p class="font-medium text-blue-900">{{ role.display_name }}</p>
              <p class="text-sm text-blue-700">{{ role.description }}</p>
            </div>
            <span class="px-3 py-1 bg-blue-600 text-white text-xs font-medium rounded-full">
              {{ role.name }}
            </span>
          </div>
          <div v-if="authStore.userRoles.length === 0" class="text-center py-8 text-gray-500">
            {{ t('common.no_data') }}
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ t('dashboard.quick_actions') }}</h2>
        <div class="space-y-2">
          <router-link
            v-if="authStore.hasPermission('users.view')"
            to="/users"
            class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg border border-gray-200 transition group"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              </div>
              <span class="font-medium text-gray-900">{{ t('users.title') }}</span>
            </div>
            <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </router-link>

          <router-link
            v-if="authStore.hasPermission('roles.view')"
            to="/roles"
            class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg border border-gray-200 transition group"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
              <span class="font-medium text-gray-900">{{ t('roles.title') }}</span>
            </div>
            <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </router-link>

          <router-link
            to="/permissions"
            class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg border border-gray-200 transition group"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
              <span class="font-medium text-gray-900">{{ t('permissions.title') }}</span>
            </div>
            <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useI18n } from '../composables/useI18n';
import { usersApi, rolesApi, permissionsApi } from '../services/api';

const authStore = useAuthStore();
const { t } = useI18n();

const stats = ref({
  totalUsers: 0,
  totalRoles: 0,
  totalPermissions: 0
});

const loadStats = async () => {
  try {
    const promises = [];
    const keys = [];
    
    // Only load stats for which user has permissions
    if (authStore.hasPermission('users.view')) {
      promises.push(usersApi.getAll({ per_page: 1 }));
      keys.push('users');
    }
    
    if (authStore.hasPermission('roles.view')) {
      promises.push(rolesApi.getAll({ per_page: 1 }));
      keys.push('roles');
    }
    
    if (authStore.hasPermission('permissions.view') || authStore.isSuperAdmin) {
      promises.push(permissionsApi.getAll({ per_page: 1 }));
      keys.push('permissions');
    }
    
    if (promises.length === 0) {
      // No permissions to load any stats
      stats.value = {
        totalUsers: 0,
        totalRoles: 0,
        totalPermissions: 0
      };
      return;
    }
    
    const results = await Promise.all(promises);
    
    // Map results back to stats
    const newStats = {
      totalUsers: 0,
      totalRoles: 0,
      totalPermissions: 0
    };
    
    keys.forEach((key, index) => {
      if (key === 'users') {
        newStats.totalUsers = results[index].data.data.total || 0;
      } else if (key === 'roles') {
        newStats.totalRoles = results[index].data.data.total || 0;
      } else if (key === 'permissions') {
        newStats.totalPermissions = results[index].data.data.total || 0;
      }
    });
    
    stats.value = newStats;
  } catch (error) {
    console.error('Load stats error:', error);
    // Set to 0 on error instead of showing error
    stats.value = {
      totalUsers: 0,
      totalRoles: 0,
      totalPermissions: 0
    };
  }
};

onMounted(() => {
  loadStats();
});
</script>

