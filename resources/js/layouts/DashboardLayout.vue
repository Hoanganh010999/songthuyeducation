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
      <aside :class="[sidebarCollapsed ? 'w-16' : 'w-52', 'hidden lg:block bg-white border-r border-gray-200 fixed h-[calc(100vh-3.5rem)] overflow-y-auto transition-all duration-300']">
        <nav class="p-3 space-y-1">
          <!-- Dashboard -->
          <router-link
            to="/dashboard"
            :class="[
              sidebarCollapsed ? 'justify-center space-x-0' : 'space-x-2',
              'flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition text-sm',
              { 'bg-gray-100 text-blue-600': $route.path === '/dashboard' }
            ]"
            :title="sidebarCollapsed ? t('common.dashboard') : ''"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span v-show="!sidebarCollapsed" class="font-medium whitespace-nowrap">{{ t('common.dashboard') }}</span>
          </router-link>

          <!-- Users -->
          <router-link
            v-if="authStore.hasPermission('users.view')"
            to="/users"
            :class="[
              sidebarCollapsed ? 'justify-center space-x-0' : 'space-x-2',
              'flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition text-sm',
              { 'bg-gray-100 text-blue-600': $route.path.startsWith('/users') }
            ]"
            :title="sidebarCollapsed ? t('users.title') : ''"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <span v-show="!sidebarCollapsed" class="font-medium whitespace-nowrap">{{ t('users.title') }}</span>
          </router-link>

          <!-- Branches -->
          <router-link
            v-if="authStore.hasPermission('branches.view')"
            to="/branches"
            :class="[
              sidebarCollapsed ? 'justify-center space-x-0' : 'space-x-2',
              'flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition text-sm',
              { 'bg-gray-100 text-blue-600': $route.path.startsWith('/branches') }
            ]"
            :title="sidebarCollapsed ? t('branches.title') : ''"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span v-show="!sidebarCollapsed" class="font-medium whitespace-nowrap">{{ t('branches.title') }}</span>
          </router-link>

          <!-- Sales (với sidebar thứ cấp bên trong) -->
          <router-link
            v-if="authStore.hasPermission('customers.view')"
            to="/sales"
            :class="[
              sidebarCollapsed ? 'justify-center space-x-0' : 'space-x-2',
              'flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition text-sm relative',
              { 'bg-gray-100 text-blue-600': $route.path.startsWith('/sales') }
            ]"
            :title="sidebarCollapsed ? t('sales.title') : ''"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span v-show="!sidebarCollapsed" class="font-medium whitespace-nowrap">{{ t('sales.title') }}</span>

            <!-- Customer Zalo Unread badge -->
            <span
              v-if="customerZaloUnreadCount > 0"
              :class="[sidebarCollapsed ? '-top-1 -right-1' : '-top-1 left-1', 'absolute flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold text-white bg-red-500 rounded-full']"
            >
              {{ customerZaloUnreadCount > 99 ? '99+' : customerZaloUnreadCount }}
            </span>
          </router-link>

          <!-- Work Management Module -->
          <router-link
            v-if="authStore.hasPermission('work_items.view_own')"
            to="/work"
            :class="[
              sidebarCollapsed ? 'justify-center space-x-0' : 'space-x-2',
              'flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition text-sm',
              { 'bg-gray-100 text-blue-600': $route.path.startsWith('/work') }
            ]"
            :title="sidebarCollapsed ? t('work.title') : ''"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            <span v-show="!sidebarCollapsed" class="font-medium whitespace-nowrap">{{ t('work.title') }}</span>
          </router-link>

          <!-- Calendar -->
          <router-link
            v-if="authStore.hasPermission('calendar.view')"
            to="/calendar"
            :class="[
              sidebarCollapsed ? 'justify-center space-x-0' : 'space-x-2',
              'flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition text-sm',
              { 'bg-gray-100 text-blue-600': $route.path.startsWith('/calendar') }
            ]"
            :title="sidebarCollapsed ? t('calendar.calendar') : ''"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span v-show="!sidebarCollapsed" class="font-medium whitespace-nowrap">{{ t('calendar.calendar') }}</span>
          </router-link>

          <!-- HR Module -->
          <router-link
            v-if="authStore.hasPermission('hr.view')"
            to="/hr"
            :class="[
              sidebarCollapsed ? 'justify-center space-x-0' : 'space-x-2',
              'flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition text-sm',
              { 'bg-gray-100 text-blue-600': $route.path.startsWith('/hr') }
            ]"
            :title="sidebarCollapsed ? t('hr.title') : ''"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <span v-show="!sidebarCollapsed" class="font-medium whitespace-nowrap">{{ t('hr.title') }}</span>
          </router-link>

          <!-- Quality Management -->
          <router-link
            v-if="authStore.hasPermission('quality.view')"
            to="/quality"
            :class="[
              sidebarCollapsed ? 'justify-center space-x-0' : 'space-x-2',
              'flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition text-sm',
              { 'bg-gray-100 text-blue-600': $route.path.startsWith('/quality') }
            ]"
            :title="sidebarCollapsed ? t('quality.title') : ''"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span v-show="!sidebarCollapsed" class="font-medium whitespace-nowrap">{{ t('quality.title') }}</span>
          </router-link>

          <!-- Course Module -->
          <router-link
            v-if="authStore.hasPermission('course.view')"
            to="/course"
            :class="[
              sidebarCollapsed ? 'justify-center space-x-0' : 'space-x-2',
              'flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition text-sm',
              { 'bg-gray-100 text-blue-600': $route.path.startsWith('/course') }
            ]"
            :title="sidebarCollapsed ? t('course.title') : ''"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
            </svg>
            <span v-show="!sidebarCollapsed" class="font-medium whitespace-nowrap">{{ t('course.title') }}</span>
          </router-link>

          <!-- Examination Module -->
          <router-link
            v-if="authStore.hasPermission('examination.view')"
            to="/examination"
            :class="[
              sidebarCollapsed ? 'justify-center space-x-0' : 'space-x-2',
              'flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition text-sm',
              { 'bg-gray-100 text-blue-600': $route.path.startsWith('/examination') }
            ]"
            :title="sidebarCollapsed ? 'Examination' : ''"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <span v-show="!sidebarCollapsed" class="font-medium whitespace-nowrap">Examination</span>
          </router-link>

          <!-- Zalo Module -->
          <router-link
            v-if="authStore.hasPermission('zalo.view')"
            to="/zalo"
            :class="[
              sidebarCollapsed ? 'justify-center space-x-0' : 'space-x-2',
              'flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition text-sm relative',
              { 'bg-gray-100 text-blue-600': $route.path.startsWith('/zalo') }
            ]"
            :title="sidebarCollapsed ? t('zalo.menu') : ''"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <span v-show="!sidebarCollapsed" class="font-medium whitespace-nowrap">{{ t('zalo.menu') }}</span>

            <!-- Unread badge -->
            <span
              v-if="zaloUnreadCount > 0"
              :class="[sidebarCollapsed ? '-top-1 -right-1' : '-top-1 left-1', 'absolute flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold text-white bg-red-500 rounded-full']"
            >
              {{ zaloUnreadCount > 99 ? '99+' : zaloUnreadCount }}
            </span>
          </router-link>

          <!-- Google Drive Module -->
          <router-link
            v-if="authStore.hasPermission('google-drive.view')"
            to="/google-drive"
            :class="[
              sidebarCollapsed ? 'justify-center space-x-0' : 'space-x-2',
              'flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition text-sm',
              { 'bg-gray-100 text-blue-600': $route.path.startsWith('/google-drive') }
            ]"
            :title="sidebarCollapsed ? t('google_drive.menu') : ''"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
            </svg>
            <span v-show="!sidebarCollapsed" class="font-medium whitespace-nowrap">{{ t('google_drive.menu') }}</span>
          </router-link>

          <!-- Holidays -->
          <router-link
            v-if="authStore.hasPermission('holidays.view')"
            to="/holidays"
            :class="[
              sidebarCollapsed ? 'justify-center space-x-0' : 'space-x-2',
              'flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition text-sm',
              { 'bg-gray-100 text-blue-600': $route.path.startsWith('/holidays') }
            ]"
            :title="sidebarCollapsed ? t('holidays.module_title') : ''"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span v-show="!sidebarCollapsed" class="font-medium whitespace-nowrap">{{ t('holidays.module_title') }}</span>
          </router-link>

          <!-- Accounting Module -->
          <router-link
            v-if="authStore.hasPermission('accounting.view')"
            to="/accounting"
            :class="[
              sidebarCollapsed ? 'justify-center space-x-0' : 'space-x-2',
              'flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition text-sm',
              { 'bg-gray-100 text-blue-600': $route.path.startsWith('/accounting') }
            ]"
            :title="sidebarCollapsed ? t('accounting.title') : ''"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span v-show="!sidebarCollapsed" class="font-medium whitespace-nowrap">{{ t('accounting.title') }}</span>
          </router-link>

          <!-- Settings (Super Admin only) -->
          <div v-if="authStore.hasRole('super-admin')" class="pt-4 mt-4 border-t border-gray-200">
            <div v-show="!sidebarCollapsed" class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              {{ t('settings.title') }}
            </div>
            
            <router-link
              to="/settings"
              :class="[
                sidebarCollapsed ? 'justify-center space-x-0' : 'space-x-2',
                'flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition text-sm',
                { 'bg-gray-100 text-blue-600': $route.path.startsWith('/settings') }
              ]"
              :title="sidebarCollapsed ? t('settings.title') : ''"
            >
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span v-show="!sidebarCollapsed" class="font-medium whitespace-nowrap">{{ t('settings.title') }}</span>
            </router-link>
          </div>
        </nav>
      </aside>

      <!-- Main Content -->
      <main :class="[sidebarCollapsed ? 'lg:ml-16' : 'lg:ml-52', 'flex-1 p-6 transition-all duration-300']">
        <div :class="[sidebarCollapsed ? 'max-w-full' : 'max-w-7xl', 'mx-auto transition-all duration-300']">
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

    <!-- Toast Notifications -->
    <ToastContainer />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useI18n } from '../composables/useI18n';
import { useToast } from '../composables/useToast';
import { useZaloSocket } from '../composables/useZaloSocket';
import api from '../services/api';
import LanguageSwitcher from '../components/LanguageSwitcher.vue';
import NotificationBell from '../components/NotificationBell.vue';
import BranchSwitcher from '../components/BranchSwitcher.vue';
import ChangePasswordModal from '../components/users/ChangePasswordModal.vue';
import ToastContainer from '../components/ToastContainer.vue';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const { t } = useI18n();
const toast = useToast();
const zaloSocket = useZaloSocket();
const showUserMenu = ref(false);
const userMenuRef = ref(null);
const userWallet = ref(null);
const childrenWallets = ref([]);
const showChangePasswordModal = ref(false);
const zaloUnreadCount = ref(0);
const customerZaloUnreadCount = ref(0); // Unread count for customers
const zaloUnreadPollingInterval = ref(null);
const sidebarCollapsed = ref(false); // Toggle sidebar state
const zaloMessageUnsubscribe = ref(null); // WebSocket unsubscribe function

const userInitials = computed(() => {
  const name = authStore.currentUser?.name || '';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
});

const isParent = computed(() => {
  return authStore.isParent;
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

// Fetch customer Zalo unread count
const fetchCustomerZaloUnreadCount = async () => {
  try {
    console.log('🔄 [DashboardLayout] Fetching Customer Zalo unread count...');
    const branchId = localStorage.getItem('current_branch_id');
    const response = await api.get('/api/zalo/customers/unread-total', {
      params: { branch_id: branchId }
    });

    console.log('📥 [DashboardLayout] Customer Zalo unread response:', response.data);

    if (response.data.success) {
      customerZaloUnreadCount.value = response.data.data.total_unread || 0;
      console.log('📊 [DashboardLayout] Customer Zalo unread count set to:', customerZaloUnreadCount.value);
    }
  } catch (error) {
    console.error('❌ [DashboardLayout] Error fetching Customer Zalo unread count:', error);
  }
};

const startZaloUnreadPolling = () => {
  if (!authStore.hasPermission('zalo.view')) {
    console.log('⚠️ [DashboardLayout] No zalo.view permission, not starting polling');
    return;
  }

  console.log('🚀 [DashboardLayout] Starting Zalo unread count polling...');
  fetchZaloUnreadCount();
  
  // Also fetch customer Zalo unread count if has customers.view permission
  if (authStore.hasPermission('customers.view')) {
    fetchCustomerZaloUnreadCount();
  }

  if (zaloUnreadPollingInterval.value) {
    clearInterval(zaloUnreadPollingInterval.value);
  }

  zaloUnreadPollingInterval.value = setInterval(() => {
    fetchZaloUnreadCount();
    if (authStore.hasPermission('customers.view')) {
      fetchCustomerZaloUnreadCount();
    }
  }, 30000); // Poll every 30 seconds
  console.log('✅ [DashboardLayout] Zalo polling started (every 30s)');
};

const stopZaloUnreadPolling = () => {
  if (zaloUnreadPollingInterval.value) {
    clearInterval(zaloUnreadPollingInterval.value);
    zaloUnreadPollingInterval.value = null;
  }
};

// Zalo WebSocket message listener for toast notifications
const setupZaloMessageListener = () => {
  if (!authStore.hasPermission('zalo.view')) {
    console.log('⚠️ [DashboardLayout] No zalo.view permission, skipping message listener');
    return;
  }

  console.log('🔌 [DashboardLayout] Setting up Zalo WebSocket message listener...');

  // Connect to WebSocket
  zaloSocket.connect();

  // Subscribe to new messages
  zaloMessageUnsubscribe.value = zaloSocket.onMessage((data) => {
    console.log('📩 [DashboardLayout] New Zalo message received:', data);

    // Extract message from data
    const message = data.message;
    if (!message) {
      console.log('📩 [DashboardLayout] No message in data, skipping');
      return;
    }

    // Check if user is currently viewing this conversation
    const isOnZaloChat = route.path.startsWith('/zalo/chat');
    const currentAccountId = route.params.accountId;
    const currentRecipientId = route.params.recipientId;

    // Don't show toast if user is viewing the same conversation
    if (isOnZaloChat &&
        data.account_id == currentAccountId &&
        data.recipient_id == currentRecipientId) {
      console.log('📩 [DashboardLayout] User is viewing this conversation, skipping toast');
      return;
    }

    // Get sender name - use recipient name for incoming messages
    const senderName = message.sender_name || message.from_display_name || 'Zalo';

    // Get sender avatar
    const senderAvatar = message.sender_avatar || message.from_avatar || null;

    // Get message preview based on content type
    let messagePreview = message.content || '';
    const contentType = message.content_type || 'text';

    if (contentType === 'image') {
      messagePreview = '📷 Hình ảnh';
    } else if (contentType === 'file') {
      messagePreview = '📎 Tập tin';
    } else if (contentType === 'sticker') {
      messagePreview = '😀 Sticker';
    } else if (contentType === 'gif') {
      messagePreview = '🎬 GIF';
    } else if (contentType === 'voice') {
      messagePreview = '🎤 Tin nhắn thoại';
    } else if (contentType === 'video') {
      messagePreview = '🎬 Video';
    }

    console.log('📩 [DashboardLayout] Showing toast:', { senderName, senderAvatar, messagePreview });

    // Show toast notification
    toast.showZaloMessage({
      senderName: senderName,
      message: messagePreview,
      avatar: senderAvatar,
      onClick: () => {
        // Navigate to Zalo with query parameters to auto-select conversation
        router.push({
          name: 'zalo.index',
          query: {
            accountId: data.account_id,
            recipientId: data.recipient_id,
          },
        });
      },
    });

    // Update unread count
    fetchZaloUnreadCount();
  });

  console.log('✅ [DashboardLayout] Zalo message listener set up');
};

const cleanupZaloMessageListener = () => {
  if (zaloMessageUnsubscribe.value) {
    zaloMessageUnsubscribe.value();
    zaloMessageUnsubscribe.value = null;
    console.log('🔌 [DashboardLayout] Zalo message listener cleaned up');
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

    // 🔥 NEW: Start Zalo polling and WebSocket when user becomes available
    if (!oldUser && newUser && authStore.hasPermission('zalo.view')) {
      console.log('👤 [DashboardLayout] User loaded with zalo.view permission, starting polling and WebSocket...');
      startZaloUnreadPolling();
      setupZaloMessageListener();
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

    // 🔥 If user is already loaded with permission, start polling and WebSocket
    if (authStore.hasPermission('zalo.view')) {
      console.log('✅ [DashboardLayout] User already has zalo.view, starting polling and WebSocket...');
      startZaloUnreadPolling();
      setupZaloMessageListener();
    }
  }
  // If user not loaded yet, the watch() will handle it when user loads
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  stopZaloUnreadPolling();
  cleanupZaloMessageListener();
});
</script>
