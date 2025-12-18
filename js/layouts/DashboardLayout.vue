<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Top Navigation Bar (LinkedIn style) -->
    <nav class="bg-white border-b border-gray-200 fixed w-full top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-14">
          <!-- Left side -->
          <div class="flex items-center space-x-4">
            <!-- Toggle Sidebar Button -->
            <button
              @click="sidebarCollapsed = !sidebarCollapsed"
              class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors hidden lg:block"
              :title="sidebarCollapsed ? 'Mở sidebar' : 'Đóng sidebar'"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
            
            <!-- Logo -->
            <router-link to="/dashboard" class="flex items-center">
              <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center">
                <span class="text-white font-bold text-sm">S</span>
              </div>
            </router-link>

            <!-- Search -->
            <div class="hidden md:block">
              <div class="relative">
                <input
                  type="text"
                  placeholder="Tìm kiếm..."
                  class="w-64 pl-10 pr-4 py-1.5 bg-gray-100 border-0 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <svg class="absolute left-3 top-2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
            </div>
          </div>

          <!-- Right side -->
          <div class="flex items-center space-x-2">
            <!-- Notifications -->
            <NotificationBell />
            
            <!-- Branch Switcher -->
            <BranchSwitcher />
            
            <!-- Language Switcher -->
            <LanguageSwitcher />
            
            <!-- User Menu -->
            <div class="relative" ref="userMenuRef">
              <button
                @click="showUserMenu = !showUserMenu"
                class="flex items-center space-x-2 px-3 py-1.5 rounded-md hover:bg-gray-100 transition"
              >
                <div class="w-7 h-7 bg-blue-600 rounded-full flex items-center justify-center">
                  <span class="text-white text-xs font-semibold">
                    {{ userInitials }}
                  </span>
                </div>
                <span class="hidden md:block text-sm font-medium text-gray-700">
                  {{ authStore.currentUser?.name }}
                </span>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <!-- Dropdown Menu -->
              <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
              >
                <div
                  v-if="showUserMenu"
                  class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 py-1"
                >
                  <div class="px-4 py-3 border-b border-gray-200">
                    <p class="text-sm font-medium text-gray-900">{{ authStore.currentUser?.name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ authStore.currentUser?.email }}</p>
                    
                    <!-- My Wallet Balance -->
                    <div v-if="userWallet" class="mt-2 pt-2 border-t border-gray-100">
                      <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600">{{ t('wallets.balance') }}:</span>
                        <span class="text-sm font-semibold text-green-600">{{ formatCurrency(userWallet.balance) }}</span>
                      </div>
                    </div>
                    
                    <!-- Children Wallets (for parents) -->
                    <div v-if="isParent && childrenWallets.length > 0" class="mt-2 pt-2 border-t border-gray-100">
                      <p class="text-xs text-gray-600 mb-1.5">{{ t('wallets.children_balances') }}:</p>
                      <div v-for="child in childrenWallets" :key="child.student_id" class="flex items-center justify-between mb-1">
                        <span class="text-xs text-gray-700">{{ child.student_name }}:</span>
                        <span class="text-xs font-semibold text-blue-600">{{ formatCurrency(child.wallet.balance) }}</span>
                      </div>
                    </div>
                  </div>
                  
                  <button
                    @click="openChangePasswordModal"
                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition flex items-center space-x-2"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                    <span>Đổi mật khẩu</span>
                  </button>
                  
                  <button
                    @click="handleLogout"
                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition flex items-center space-x-2"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>{{ t('auth.logout') }}</span>
                  </button>
                </div>
              </transition>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content Area -->
    <div class="pt-14 flex">
      <!-- Sidebar (LinkedIn style) - Compact -->
      <aside :class="[sidebarCollapsed ? 'w-0 -translate-x-full' : 'w-52', 'hidden lg:block bg-white border-r border-gray-200 fixed h-[calc(100vh-3.5rem)] overflow-y-auto transition-all duration-300']">
        <nav class="p-3 space-y-1">
          <!-- Dashboard -->
          <router-link
            to="/dashboard"
            class="flex items-center space-x-2 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition text-sm"
            :class="{ 'bg-gray-100 text-blue-600': $route.path === '/dashboard' }"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span class="font-medium">{{ t('common.dashboard') }}</span>
          </router-link>

          <!-- Users -->
          <router-link
            v-if="authStore.hasPermission('users.view')"
            to="/users"
            class="flex items-center space-x-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition"
            :class="{ 'bg-gray-100 text-blue-600': $route.path.startsWith('/users') }"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <span class="font-medium">{{ t('users.title') }}</span>
          </router-link>

          <!-- Branches -->
          <router-link
            v-if="authStore.hasPermission('branches.view')"
            to="/branches"
            class="flex items-center space-x-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition"
            :class="{ 'bg-gray-100 text-blue-600': $route.path.startsWith('/branches') }"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span class="font-medium">{{ t('branches.title') }}</span>
          </router-link>

          <!-- Sales (với sidebar thứ cấp bên trong) -->
          <router-link
            v-if="authStore.hasPermission('customers.view')"
            to="/sales"
            class="flex items-center space-x-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition"
            :class="{ 'bg-gray-100 text-blue-600': $route.path.startsWith('/sales') }"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span class="font-medium">{{ t('sales.title') }}</span>
          </router-link>

          <!-- Calendar -->
          <router-link
            v-if="authStore.hasPermission('calendar.view')"
            to="/calendar"
            class="flex items-center space-x-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition"
            :class="{ 'bg-gray-100 text-blue-600': $route.path.startsWith('/calendar') }"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span class="font-medium">{{ t('calendar.calendar') }}</span>
          </router-link>

          <!-- HR Module -->
          <router-link
            v-if="authStore.hasPermission('hr.view')"
            to="/hr"
            class="flex items-center space-x-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition"
            :class="{ 'bg-gray-100 text-blue-600': $route.path.startsWith('/hr') }"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <span class="font-medium">{{ t('hr.title') }}</span>
          </router-link>

          <!-- Quality Management -->
          <router-link
            v-if="authStore.hasPermission('quality.view')"
            to="/quality"
            class="flex items-center space-x-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition"
            :class="{ 'bg-gray-100 text-blue-600': $route.path.startsWith('/quality') }"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-medium">{{ t('quality.title') }}</span>
          </router-link>

          <!-- Course Module -->
          <router-link
            v-if="authStore.hasPermission('course.view')"
            to="/course"
            class="flex items-center space-x-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition"
            :class="{ 'bg-gray-100 text-blue-600': $route.path.startsWith('/course') }"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <span class="font-medium">{{ t('course.title') }}</span>
          </router-link>

          <!-- Zalo Module -->
          <router-link
            v-if="authStore.hasPermission('zalo.view')"
            to="/zalo"
            class="flex items-center space-x-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition relative"
            :class="{ 'bg-gray-100 text-blue-600': $route.path.startsWith('/zalo') }"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <span class="font-medium">{{ t('zalo.menu') }}</span>

            <!-- Unread badge -->
            <span
              v-if="zaloUnreadCount > 0"
              class="absolute -top-1 left-1 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold text-white bg-red-500 rounded-full"
            >
              {{ zaloUnreadCount > 99 ? '99+' : zaloUnreadCount }}
            </span>
          </router-link>

          <!-- Google Drive Module -->
          <router-link
            v-if="authStore.hasPermission('google-drive.view')"
            to="/google-drive"
            class="flex items-center space-x-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition"
            :class="{ 'bg-gray-100 text-blue-600': $route.path.startsWith('/google-drive') }"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
            </svg>
            <span class="font-medium">{{ t('google_drive.menu') }}</span>
          </router-link>

          <!-- Holidays -->
          <router-link
            v-if="authStore.hasPermission('holidays.view')"
            to="/holidays"
            class="flex items-center space-x-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition"
            :class="{ 'bg-gray-100 text-blue-600': $route.path.startsWith('/holidays') }"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="font-medium">{{ t('holidays.module_title') }}</span>
          </router-link>

          <!-- Accounting Module -->
          <router-link
            v-if="authStore.hasPermission('accounting.view')"
            to="/accounting"
            class="flex items-center space-x-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition"
            :class="{ 'bg-gray-100 text-blue-600': $route.path.startsWith('/accounting') }"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="font-medium">{{ t('accounting.title') }}</span>
          </router-link>

          <!-- Settings (Super Admin only) -->
          <div v-if="authStore.hasRole('super-admin')" class="pt-4 mt-4 border-t border-gray-200">
            <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              {{ t('settings.title') }}
            </div>
            
            <router-link
              to="/settings"
              class="flex items-center space-x-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition"
              :class="{ 'bg-gray-100 text-blue-600': $route.path.startsWith('/settings') }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span class="font-medium">{{ t('settings.title') }}</span>
            </router-link>
          </div>
        </nav>
      </aside>

      <!-- Main Content -->
      <main :class="[sidebarCollapsed ? 'lg:ml-0' : 'lg:ml-52', 'flex-1 p-6 transition-all duration-300']">
        <div class="max-w-7xl mx-auto">
          <router-view />
        </div>
      </main>
    </div>

    <!-- Change Password Modal -->
    <ChangePasswordModal
      :show="showChangePasswordModal"
      @close="showChangePasswordModal = false"
      @success="showChangePasswordModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useI18n } from '../composables/useI18n';
import api from '../services/api';
import LanguageSwitcher from '../components/LanguageSwitcher.vue';
import NotificationBell from '../components/NotificationBell.vue';
import BranchSwitcher from '../components/BranchSwitcher.vue';
import ChangePasswordModal from '../components/users/ChangePasswordModal.vue';

const router = useRouter();
const authStore = useAuthStore();
const { t } = useI18n();
const showUserMenu = ref(false);
const userMenuRef = ref(null);
const userWallet = ref(null);
const childrenWallets = ref([]);
const showChangePasswordModal = ref(false);
const zaloUnreadCount = ref(0);
const zaloUnreadPollingInterval = ref(null);
const sidebarCollapsed = ref(false); // Toggle sidebar state

const userInitials = computed(() => {
  const name = authStore.currentUser?.name || '';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
});

const isParent = computed(() => {
  return authStore.currentUser?.roles?.some(role => role.name === 'parent') || false;
});

const loadUserWallet = async () => {
  try {
    const response = await api.get('/api/wallets/my-wallet');
    if (response.data.success) {
      userWallet.value = response.data.data;
      // Log message if user doesn't have a wallet
      if (!response.data.data) {
        console.log('ℹ️', response.data.message);
      }
    }
  } catch (error) {
    console.error('Error loading user wallet:', error);
    userWallet.value = null;
  }
};

const loadChildrenWallets = async () => {
  console.log('🔍 [Wallet] Checking if user is parent...');
  console.log('   - isParent:', isParent.value);
  console.log('   - Current user:', authStore.currentUser);
  console.log('   - User roles:', authStore.currentUser?.roles);
  
  if (!isParent.value) {
    console.log('⚠️  [Wallet] User is NOT a parent, skipping children wallets');
    return;
  }
  
  console.log('✅ [Wallet] User IS a parent, loading children wallets...');
  
  try {
    const response = await api.get('/api/wallets/my-children');
    console.log('📦 [Wallet] API Response:', response.data);
    
    if (response.data.success) {
      childrenWallets.value = response.data.data || [];
      console.log('✅ [Wallet] Loaded children wallets:', childrenWallets.value.length);
      console.log('📋 [Wallet] Children wallets data:', childrenWallets.value);
    }
  } catch (error) {
    console.error('❌ [Wallet] Error loading children wallets:', error);
    console.error('   Response:', error.response?.data);
    childrenWallets.value = [];
  }
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value || 0);
};

const openChangePasswordModal = () => {
  showUserMenu.value = false;
  showChangePasswordModal.value = true;
};

const handleLogout = async () => {
  await authStore.logout();
  router.push({ name: 'login' });
};

// Zalo unread count
const fetchZaloUnreadCount = async () => {
  if (!authStore.hasPermission('zalo.view')) {
    console.log('⚠️ [DashboardLayout] No zalo.view permission, skipping unread count fetch');
    return;
  }

  try {
    console.log('🔄 [DashboardLayout] Fetching Zalo unread count...');
    const branchId = localStorage.getItem('current_branch_id');
    const response = await api.get('/api/zalo/unread-counts', {
      params: { branch_id: branchId }
    });

    console.log('📥 [DashboardLayout] Zalo unread response:', response.data);

    if (response.data.success) {
      zaloUnreadCount.value = response.data.data.total_unread || 0;
      console.log('📊 [DashboardLayout] Zalo unread count set to:', zaloUnreadCount.value);
    }
  } catch (error) {
    console.error('❌ [DashboardLayout] Error fetching Zalo unread count:', error);
  }
};

const startZaloUnreadPolling = () => {
  if (!authStore.hasPermission('zalo.view')) {
    console.log('⚠️ [DashboardLayout] No zalo.view permission, not starting polling');
    return;
  }

  console.log('🚀 [DashboardLayout] Starting Zalo unread count polling...');
  fetchZaloUnreadCount();

  if (zaloUnreadPollingInterval.value) {
    clearInterval(zaloUnreadPollingInterval.value);
  }

  zaloUnreadPollingInterval.value = setInterval(fetchZaloUnreadCount, 30000); // Poll every 30 seconds
  console.log('✅ [DashboardLayout] Zalo polling started (every 30s)');
};

const stopZaloUnreadPolling = () => {
  if (zaloUnreadPollingInterval.value) {
    clearInterval(zaloUnreadPollingInterval.value);
    zaloUnreadPollingInterval.value = null;
  }
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (userMenuRef.value && !userMenuRef.value.contains(event.target)) {
    showUserMenu.value = false;
  }
};

// Watch for user changes and reload wallets
watch(() => authStore.currentUser, (newUser, oldUser) => {
  if (newUser) {
    console.log('👤 [Wallet] User changed, reloading wallets...');
    loadUserWallet();
    loadChildrenWallets();

    // 🔥 NEW: Start Zalo polling when user becomes available
    if (!oldUser && newUser && authStore.hasPermission('zalo.view')) {
      console.log('👤 [DashboardLayout] User loaded with zalo.view permission, starting polling...');
      startZaloUnreadPolling();
    }
  }
}, { immediate: true });

onMounted(async () => {
  console.log('🎬 [DashboardLayout] Component mounted!');
  console.log('   - Current user:', authStore.currentUser?.name);
  console.log('   - Has zalo.view permission:', authStore.hasPermission('zalo.view'));

  document.addEventListener('click', handleClickOutside);

  // If user is already loaded, load wallets immediately
  if (authStore.currentUser) {
    console.log('✅ [Wallet] User already loaded, loading wallets...');
    loadUserWallet();
    loadChildrenWallets();

    // 🔥 If user is already loaded with permission, start polling immediately
    if (authStore.hasPermission('zalo.view')) {
      console.log('✅ [DashboardLayout] User already has zalo.view, starting polling...');
      startZaloUnreadPolling();
    }
  }
  // If user not loaded yet, the watch() will handle it when user loads
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  stopZaloUnreadPolling();
});
</script>
