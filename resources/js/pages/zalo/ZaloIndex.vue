<template>
  <div class="flex h-[calc(100vh-3.5rem)] bg-gray-50 overflow-hidden -m-6" style="margin-left: -1.5rem; margin-right: -1.5rem; margin-top: -1.5rem; margin-bottom: -1.5rem; height: calc(100vh - 3.5rem);">
    <!-- Sidebar trái hẹp (icon navigation) - màu xanh -->
    <aside class="w-16 bg-blue-600 flex flex-col items-center py-4 space-y-4 flex-shrink-0">
      <!-- Profile avatar button -->
      <div 
        @click="showAccountManager = !showAccountManager"
        class="relative w-10 h-10 rounded-full bg-white flex items-center justify-center cursor-pointer hover:bg-blue-500 transition"
      >
        <img 
          v-if="currentAccount?.avatar_url" 
          :src="currentAccount.avatar_url" 
          alt="Profile"
          class="w-full h-full rounded-full object-cover"
        />
        <svg v-else class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        
        <!-- Connection indicator -->
        <div 
          class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-blue-600"
          :class="currentAccount?.is_connected ? 'bg-green-500' : 'bg-gray-400'"
          :title="currentAccount?.is_connected ? t('zalo.connected') : t('zalo.disconnected')"
        ></div>
      </div>

      <!-- Navigation icons -->
      <button
        v-for="nav in navigation"
        :key="nav.key"
        @click="handleNavClick(nav.key)"
        class="relative w-12 h-12 flex items-center justify-center rounded-lg transition-colors"
        :class="activeNav === nav.key
          ? 'bg-blue-700 text-white'
          : 'text-blue-100 hover:bg-blue-500'"
        :title="t(`zalo.${nav.key}`)"
      >
        <component :is="nav.icon" class="w-6 h-6" />
      </button>
    </aside>

    <!-- Account Manager (hiển thị khi click avatar) -->
    <div v-if="showAccountManager" class="w-80 bg-white border-r border-gray-200 flex flex-col flex-shrink-0 h-full overflow-hidden">
      <ZaloAccountManager
        :selected-account="selectedAccountForManager"
        :unread-counts="accountUnreadCounts"
        @account-selected="handleAccountSelectedFromManager"
        @add-account="showAddAccountForm = true"
      />
    </div>

    <!-- Cột danh sách (Groups/Friends/History/Settings) -->
    <div v-else class="w-80 bg-white border-r border-gray-200 flex flex-col flex-shrink-0 h-full overflow-hidden">
      <!-- Header với search (chỉ hiện khi không phải settings) -->
      <div v-if="activeNav !== 'settings'" class="p-4 border-b border-gray-200 flex-shrink-0">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-lg font-semibold text-gray-900">{{ t(`zalo.${activeNav}`) }}</h2>
          <div class="flex items-center gap-2">
            <!-- Add Friend button (chỉ hiện ở Friends tab) -->
            <button
              v-if="activeNav === 'friends'"
              @click="showAddFriendModal = true"
              class="inline-flex items-center gap-1 px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700"
              :title="t('zalo.add_friend')"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
              </svg>
              {{ t('zalo.add') }}
            </button>
            
            <!-- Create Group button (chỉ hiện ở Groups tab) -->
            <button
              v-if="activeNav === 'groups'"
              @click="showCreateGroupModal = true"
              class="inline-flex items-center gap-1 px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700"
              :title="t('zalo.create_group')"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              {{ t('zalo.create') }}
            </button>

            <!-- Resync button for friends, groups, and history -->
            <button
              v-if="activeNav === 'friends' || activeNav === 'groups' || activeNav === 'history'"
              @click="activeNav === 'friends' ? handleResync() : handleSyncHistory()"
              :disabled="loadingList || syncingHistory"
              class="inline-flex items-center gap-1 px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
              :title="activeNav === 'groups' ? (t('zalo.sync_groups') || 'Sync Groups') : activeNav === 'history' ? (t('zalo.sync_history') || 'Sync History') : (t('zalo.resync_friends') || 'Resync Friends')"
            >
              <svg v-if="!syncingHistory" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <svg v-else class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <span class="hidden md:inline">{{ activeNav === 'groups' ? (t('zalo.sync_groups') || 'Sync Groups') : activeNav === 'history' ? (t('zalo.sync_history') || 'Sync History') : (t('zalo.resync_friends') || 'Resync') }}</span>
            </button>
          </div>
        </div>
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="t('common.search')"
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
          <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>

      <!-- Tabs: Tất cả / Chưa đọc (chỉ hiện khi không phải settings) -->
      <div v-if="activeNav !== 'settings'" class="flex border-b border-gray-200 flex-shrink-0">
        <button
          @click="listFilter = 'all'"
          class="flex-1 px-4 py-2 text-sm font-medium transition-colors"
          :class="listFilter === 'all' 
            ? 'text-blue-600 border-b-2 border-blue-600' 
            : 'text-gray-600 hover:text-gray-900'"
        >
          {{ t('zalo.all') }}
        </button>
        <button
          @click="listFilter = 'unread'"
          class="flex-1 px-4 py-2 text-sm font-medium transition-colors"
          :class="listFilter === 'unread' 
            ? 'text-blue-600 border-b-2 border-blue-600' 
            : 'text-gray-600 hover:text-gray-900'"
        >
          {{ t('zalo.unread') }}
        </button>
      </div>

      <!-- Danh sách items hoặc Settings -->
      <div class="flex-1 overflow-y-auto min-h-0">
        <!-- Settings view -->
        <div v-if="activeNav === 'settings'" class="h-full">
          <ZaloSettingsList 
            :selected-key="selectedSettingsKey"
            @select="selectedSettingsKey = $event"
          />
        </div>
        
        <!-- List view (history, friends, groups) -->
        <template v-else>
          <div v-if="loadingList" class="p-4 text-center text-gray-500">
            {{ t('common.loading') }}...
          </div>
          <div v-else-if="filteredList.length === 0" class="p-4 text-center text-gray-500">
            {{ t('zalo.no_items_found') }}
          </div>
          <div v-else class="divide-y divide-gray-100">
            <div
              v-for="item in filteredList"
              :key="item.id"
              class="w-full hover:bg-gray-50 transition-colors relative"
              :class="selectedItem?.id === item.id ? 'bg-blue-50 border-l-4 border-blue-600' : ''"
            >
              <!-- Selection indicator overlay -->
              <div
                v-if="selectedItem?.id === item.id"
                class="absolute inset-0 bg-blue-100 bg-opacity-30 pointer-events-none"
              ></div>
              <button
                @click="selectItem(item)"
                class="w-full px-4 py-3 text-left flex items-center gap-3 relative z-1"
              >
                <div class="w-12 h-12 rounded-full flex-shrink-0 overflow-hidden bg-gray-200">
                  <img
                    v-if="item.avatar_url"
                    :src="item.avatar_url"
                    :alt="item.name"
                    class="w-full h-full object-cover"
                  />
                  <div v-else class="w-full h-full flex items-center justify-center bg-blue-100">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between">
                    <p class="font-medium text-gray-900 truncate">{{ item.name || item.displayName }}</p>
                    <span
                      v-if="item.unread_count && item.unread_count > 0"
                      class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-red-500 text-white"
                      :title="`${item.unread_count} ${t('zalo.unread')}`"
                    >
                      {{ item.unread_count }}
                    </span>
                  </div>
                  <p class="text-sm text-gray-500 truncate">{{ formatLastMessage(item.last_message) }}</p>
                </div>
              </button>

              <!-- Assignment info (chỉ hiện cho history tab) -->
              <div v-if="activeNav === 'history' && (item.department || (item.assigned_users && item.assigned_users.length > 0))" class="px-4 pb-2 flex flex-wrap gap-1">
                <!-- Department badge -->
                <span v-if="item.department" class="inline-flex items-center gap-1 px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                  {{ item.department.name }}
                </span>

                <!-- Assigned users badges -->
                <span
                  v-for="user in (item.assigned_users || []).slice(0, 2)"
                  :key="user.id"
                  class="inline-flex items-center gap-1 px-2 py-0.5 text-xs bg-green-100 text-green-700 rounded"
                  :title="user.email"
                >
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  {{ user.name }}
                </span>

                <!-- More users indicator -->
                <span
                  v-if="item.assigned_users && item.assigned_users.length > 2"
                  class="inline-flex items-center px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded"
                >
                  +{{ item.assigned_users.length - 2 }}
                </span>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Account Detail (hiển thị khi showAccountManager) -->
    <div v-if="showAccountManager" class="flex-1 bg-white flex flex-col overflow-hidden">
      <ZaloAccountDetail 
        :account="selectedAccountForManager"
        :show-add-form="showAddAccountForm"
        @account-updated="handleAccountUpdated"
        @close-add-form="showAddAccountForm = false"
      />
    </div>

    <!-- Cột chat chính hoặc Settings Detail -->
    <div v-else class="flex-1 flex flex-col bg-white min-w-0 h-full overflow-hidden">
      <ZaloChatView
        v-if="selectedItem && activeNav !== 'settings'"
        :item="selectedItem"
        :account-id="currentAccount?.id"
        :item-type="selectedItem.itemType || activeNav"
        @message-sent="handleMessageSent"
      />
      <ZaloSettingsDetail 
        v-else-if="activeNav === 'settings'"
        :selected-key="selectedSettingsKey"
      />
      <div v-else class="flex-1 flex items-center justify-center bg-gray-50">
        <div class="text-center">
          <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <p class="text-gray-500">{{ t('zalo.select_conversation') }}</p>
        </div>
      </div>
    </div>

    <!-- Cột thông tin bên phải -->
    <div v-if="selectedItem && activeNav !== 'settings'" class="w-80 bg-white border-l border-gray-200 flex-shrink-0 h-full overflow-hidden flex flex-col">
      <ZaloChatInfo
        :item="selectedItem"
        :item-type="selectedItem.itemType || activeNav"
        @assignment-updated="handleAssignmentUpdated"
      />
    </div>

    <!-- Add Friend Modal - Zalo Style -->
    <Teleport to="body">
      <div v-if="showAddFriendModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 max-h-[90vh] overflow-hidden flex flex-col">
          <!-- Header -->
          <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-900">{{ t('zalo.add_friend') }}</h3>
              <button @click="closeAddFriendModal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
          
          <!-- Search Input -->
          <div class="p-4 border-b border-gray-200">
            <div class="flex items-center gap-2">
              <!-- Country Code Selector -->
              <div class="flex items-center gap-1 px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                <img src="https://upload.wikimedia.org/wikipedia/commons/2/21/Flag_of_Vietnam.svg" alt="VN" class="w-5 h-3" />
                <span class="text-sm font-medium text-gray-700">(+84)</span>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
              
              <!-- Phone Input -->
              <input
                v-model="addFriendForm.phoneNumber"
                @input="handlePhoneInput"
                type="tel"
                :placeholder="t('zalo.phone_number')"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>
          
          <!-- Search Results -->
          <div class="flex-1 overflow-y-auto">
            <!-- Loading State -->
            <div v-if="addFriendForm.searching" class="p-8 text-center">
              <svg class="inline w-8 h-8 animate-spin text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <p class="mt-2 text-sm text-gray-500">{{ t('zalo.searching') }}</p>
            </div>
            
            <!-- Search Result -->
            <div v-else-if="addFriendForm.searchResult" class="p-4">
              <h5 class="text-sm font-medium text-gray-500 mb-3">{{ t('zalo.search_result_closest') }}</h5>
              <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-200 flex-shrink-0">
                  <img v-if="addFriendForm.searchResult.avatar" :src="addFriendForm.searchResult.avatar" :alt="addFriendForm.searchResult.display_name" class="w-full h-full object-cover" />
                  <div v-else class="w-full h-full flex items-center justify-center bg-blue-100 text-blue-600 font-semibold text-lg">
                    {{ (addFriendForm.searchResult.display_name || 'U').charAt(0).toUpperCase() }}
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 truncate">{{ addFriendForm.searchResult.display_name }}</p>
                  <p class="text-xs text-gray-500 truncate">{{ formatPhoneNumber(addFriendForm.searchResult.phone) }}</p>
                </div>
                <button
                  @click="handleSendFriendRequest(addFriendForm.searchResult)"
                  :disabled="addFriendForm.submitting"
                  class="px-4 py-1.5 text-sm font-medium text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50 disabled:opacity-50"
                >
                  {{ addFriendForm.submitting ? t('zalo.friend_request_sending') : t('zalo.send_friend_request') }}
                </button>
              </div>
            </div>
            
            <!-- Not Found -->
            <div v-else-if="addFriendForm.searched && !addFriendForm.searchResult" class="p-8 text-center">
              <svg class="inline w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <p class="mt-3 text-sm text-gray-500">{{ t('zalo.user_not_found') }}</p>
              <p class="mt-1 text-xs text-gray-400">{{ t('zalo.please_check_phone') }}</p>
            </div>
            
            <!-- Initial State -->
            <div v-else class="p-8 text-center">
              <svg class="inline w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <p class="mt-3 text-sm text-gray-500">{{ t('zalo.enter_phone_to_search') }}</p>
              <p class="mt-1 text-xs text-gray-400">{{ t('zalo.example_phone') }}</p>
            </div>
            
            <!-- Suggestions Section (Optional) -->
            <div v-if="!addFriendForm.searching && !addFriendForm.searchResult" class="border-t border-gray-200 p-4">
              <div class="flex items-center gap-2 text-xs text-gray-500 mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span>{{ t('zalo.people_you_may_know') }}</span>
              </div>
              <p class="text-xs text-gray-400">{{ t('zalo.enter_phone_to_search_friends') }}</p>
            </div>
          </div>
          
          <!-- Footer -->
          <div class="p-4 border-t border-gray-200 bg-gray-50">
            <button
              @click="closeAddFriendModal"
              class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              {{ t('zalo.cancel') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Create Group Modal -->
    <Teleport to="body">
      <div v-if="showCreateGroupModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden flex flex-col">
          <div class="p-6 border-b">
            <div class="flex items-center justify-between">
              <h3 class="text-xl font-semibold text-gray-900">{{ t('zalo.create_group') }}</h3>
              <button @click="showCreateGroupModal = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
          
          <form @submit.prevent="handleCreateGroup" class="flex-1 overflow-y-auto">
            <div class="p-6 space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  {{ t('zalo.group_name_optional') }}
                </label>
                <input
                  v-model="createGroupForm.name"
                  type="text"
                  :placeholder="t('zalo.enter_group_name')"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  {{ t('zalo.select_members') }} <span class="text-red-500">*</span>
                  <span class="text-xs text-gray-500 ml-2">({{ createGroupForm.selectedMembers.length }} {{ t('zalo.selected') }})</span>
                </label>
                
                <!-- Search members -->
                <div class="relative mb-3">
                  <input
                    v-model="createGroupForm.searchQuery"
                    type="text"
                    :placeholder="t('zalo.search_friends')"
                    class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                  <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </div>
                
                <!-- Members list -->
                <div class="border border-gray-300 rounded-lg max-h-64 overflow-y-auto">
                  <div v-if="!createGroupForm.loadingFriends && createGroupForm.friends.length === 0" class="p-4 text-center text-gray-500">
                    {{ t('zalo.no_friends_add_first') }}
                  </div>
                  <div v-else-if="createGroupForm.loadingFriends" class="p-4 text-center text-gray-500">
                    <svg class="inline w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ t('zalo.loading_friends') }}
                  </div>
                  <label
                    v-for="friend in filteredFriendsForGroup"
                    :key="friend.id"
                    class="flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                  >
                    <input
                      type="checkbox"
                      :value="friend.id"
                      v-model="createGroupForm.selectedMembers"
                      class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    />
                    <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200 flex-shrink-0">
                      <img v-if="friend.avatar" :src="friend.avatar" :alt="friend.name" class="w-full h-full object-cover" />
                      <div v-else class="w-full h-full flex items-center justify-center bg-blue-100 text-blue-600 font-semibold">
                        {{ (friend.name || 'U').charAt(0).toUpperCase() }}
                      </div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900 truncate">{{ friend.name || friend.displayName }}</p>
                      <p class="text-xs text-gray-500 truncate">{{ friend.phone || friend.id }}</p>
                    </div>
                  </label>
                </div>
              </div>
            </div>
            
            <div class="p-6 border-t bg-gray-50">
              <div class="flex justify-end gap-3">
                <button
                  type="button"
                  @click="showCreateGroupModal = false"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                >
                  {{ t('zalo.cancel') }}
                </button>
                <button
                  type="submit"
                  :disabled="createGroupForm.selectedMembers.length === 0 || createGroupForm.submitting"
                  class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50 inline-flex items-center gap-2"
                >
                  <svg v-if="createGroupForm.submitting" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  {{ createGroupForm.submitting ? t('zalo.creating') : t('zalo.create_group') }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, h, provide } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useI18n } from '../../composables/useI18n';
import { useZaloAccount } from '../../composables/useZaloAccount';
import { useZaloSocket } from '../../composables/useZaloSocket';
import { useSwal } from '../../composables/useSwal';
import { useAuthStore } from '../../stores/auth';
import ZaloChatView from './components/ZaloChatView.vue';
import ZaloChatInfo from './components/ZaloChatInfo.vue';
import ZaloSettingsList from './components/ZaloSettingsList.vue';
import ZaloSettingsDetail from './components/ZaloSettingsDetail.vue';
import ZaloAccountManager from './components/ZaloAccountManager.vue';
import ZaloAccountDetail from './components/ZaloAccountDetail.vue';
import axios from 'axios';
import { getPlainText } from '../../utils/zaloRichTextParser';

const { t } = useI18n();
const zaloAccount = useZaloAccount();
const zaloSocket = useZaloSocket();
const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

// Navigation state
const activeNav = ref('history'); // 'history', 'friends', 'groups', or 'settings'
const searchQuery = ref('');
const listFilter = ref('all'); // 'all' or 'unread'
const selectedItem = ref(null);
const loadingList = ref(false);
const listItems = ref([]);
const syncingHistory = ref(false);
const currentAccount = ref(null);
const selectedSettingsKey = ref('connection'); // 'connection', 'notifications', 'documentation'
const showAccountManager = ref(false);
const selectedAccountForManager = ref(null);
const showAddAccountForm = ref(false);

// Unread counts
const totalUnreadCount = ref(0); // Total across all accounts
const accountUnreadCounts = ref({}); // Per account: { accountId: count }
const unreadPollingInterval = ref(null);

// 🔥 FIX: Race condition prevention - track current request to prevent stale data
let currentLoadRequestId = 0;

// New feature modals
const showAddFriendModal = ref(false);
const showCreateGroupModal = ref(false);

// Add Friend form - Zalo Style
const addFriendForm = ref({
  phoneNumber: '',
  searchResult: null,
  searching: false,
  searched: false,
  submitting: false,
});
let searchTimeout = null;

// Create Group form
const createGroupForm = ref({
  name: '',
  selectedMembers: [],
  searchQuery: '',
  friends: [],
  loadingFriends: false,
  submitting: false,
});

// Provide account state to child components
provide('zaloAccount', zaloAccount);

// Navigation icons
const ChatIcon = () => h('svg', {
  class: 'w-6 h-6',
  fill: 'none',
  stroke: 'currentColor',
  viewBox: '0 0 24 24'
}, [
  h('path', {
    'stroke-linecap': 'round',
    'stroke-linejoin': 'round',
    'stroke-width': '2',
    d: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'
  })
]);

// Friends icon - contact book (danh bạ)
const FriendsIcon = () => h('svg', {
  class: 'w-6 h-6',
  fill: 'none',
  stroke: 'currentColor',
  viewBox: '0 0 24 24'
}, [
  h('path', {
    'stroke-linecap': 'round',
    'stroke-linejoin': 'round',
    'stroke-width': '2',
    d: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'
  })
]);

// Groups icon - multiple users (icon friends cũ)
const GroupsIcon = () => h('svg', {
  class: 'w-6 h-6',
  fill: 'none',
  stroke: 'currentColor',
  viewBox: '0 0 24 24'
}, [
  h('path', {
    'stroke-linecap': 'round',
    'stroke-linejoin': 'round',
    'stroke-width': '2',
    d: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'
  })
]);

const SettingsIcon = () => h('svg', {
  class: 'w-6 h-6',
  fill: 'none',
  stroke: 'currentColor',
  viewBox: '0 0 24 24'
}, [
  h('path', {
    'stroke-linecap': 'round',
    'stroke-linejoin': 'round',
    'stroke-width': '2',
    d: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'
  }),
  h('path', {
    'stroke-linecap': 'round',
    'stroke-linejoin': 'round',
    'stroke-width': '2',
    d: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z'
  })
]);

// History icon - bubble talk (chat bubbles)
const HistoryIcon = () => h('svg', {
  class: 'w-6 h-6',
  fill: 'none',
  stroke: 'currentColor',
  viewBox: '0 0 24 24'
}, [
  h('path', {
    'stroke-linecap': 'round',
    'stroke-linejoin': 'round',
    'stroke-width': '2',
    d: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'
  })
]);

const navigation = [
  { key: 'history', icon: HistoryIcon },
  { key: 'friends', icon: FriendsIcon },
  { key: 'groups', icon: GroupsIcon },
  { key: 'settings', icon: SettingsIcon },
];

// Filtered list
const filteredList = computed(() => {
  let items = listItems.value;
  
  // Filter by search
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    items = items.filter(item => 
      (item.name || item.displayName || '').toLowerCase().includes(query)
    );
  }
  
  // Filter by unread
  if (listFilter.value === 'unread') {
    items = items.filter(item => (item.unread_count || 0) > 0);
  }
  
  return items;
});

// Filtered friends for Create Group modal
const filteredFriendsForGroup = computed(() => {
  let friends = createGroupForm.value.friends;
  
  if (createGroupForm.value.searchQuery) {
    const query = createGroupForm.value.searchQuery.toLowerCase();
    friends = friends.filter(friend =>
      (friend.name || friend.displayName || '').toLowerCase().includes(query)
    );
  }
  
  return friends;
});

// Load list based on active nav
const loadList = async (forceSync = false) => {
  // Don't load list for settings
  if (activeNav.value === 'settings') {
    listItems.value = [];
    loadingList.value = false;
    return;
  }

  // 🔥 FIX: Race condition prevention - increment request ID and capture current state
  const requestId = ++currentLoadRequestId;
  const targetNav = activeNav.value; // Capture current nav at start of request

  loadingList.value = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const params = branchId ? { branch_id: branchId } : {};

    // 🔥 FIX: Use currentAccount.value.id instead of zaloAccount.activeAccountId.value
    // This ensures we load data for the currently selected account
    const accountId = currentAccount.value?.id || zaloAccount?.activeAccountId.value;

    let newItems = [];

    if (targetNav === 'history') {
      if (accountId) {
        params.account_id = accountId;
      }
      console.log('📥 [ZaloIndex] Loading conversations for account:', accountId, '(request:', requestId, ')');
      const response = await axios.get('/api/zalo/conversations', { params });

      // 🔥 FIX: Check if this request is still the current one (prevents stale data)
      if (requestId !== currentLoadRequestId) {
        console.log('⚠️ [ZaloIndex] Ignoring stale history response (request:', requestId, ', current:', currentLoadRequestId, ')');
        return;
      }

      // Handle pagination: response.data.data is paginator object with data array inside
      const conversations = response.data.data?.data || response.data.data || [];
      newItems = conversations.map(conv => ({
        ...conv,
        id: conv.recipient_id, // Use recipient_id as the item ID (for matching with friends/groups)
        conversation_id: conv.id, // Store conversation ID for reference
        name: conv.recipient_name || conv.name || conv.displayName,
        avatar_url: conv.recipient_avatar_url || conv.avatar_url,
        last_message: getPlainText(conv.last_message_preview || conv.last_message),
        last_message_at: conv.last_message_at,
        unread_count: conv.unread_count || 0,
        itemType: conv.recipient_type === 'user' ? 'friends' : 'groups', // Store original type
        department: conv.department, // Include department assignment
        assigned_users: conv.assigned_users, // Include assigned users
      }));
    } else if (targetNav === 'friends') {
      if (accountId) {
        params.account_id = accountId;
      }
      console.log('📥 [ZaloIndex] Loading friends for account:', accountId, '(request:', requestId, ')');
      params.sync = forceSync;
      const response = await axios.get('/api/zalo/friends', { params });

      // 🔥 FIX: Check if this request is still the current one (prevents stale data)
      if (requestId !== currentLoadRequestId) {
        console.log('⚠️ [ZaloIndex] Ignoring stale friends response (request:', requestId, ', current:', currentLoadRequestId, ')');
        return;
      }

      newItems = (response.data.data || []).map(friend => ({
        ...friend,
        id: friend.userId || friend.id,
        name: friend.displayName || friend.name,
        avatar_url: friend.avatar_url,
        unread_count: 0, // TODO: Get from messages
        last_message: null, // TODO: Get from last message
        itemType: 'friends',
      }));
    } else if (targetNav === 'groups') {
      if (accountId) {
        params.account_id = accountId;
      }
      console.log('📥 [ZaloIndex] Loading groups for account:', accountId, '(request:', requestId, ')');
      params.sync = forceSync;
      const response = await axios.get('/api/zalo/groups', { params });

      // 🔥 FIX: Check if this request is still the current one (prevents stale data)
      if (requestId !== currentLoadRequestId) {
        console.log('⚠️ [ZaloIndex] Ignoring stale groups response (request:', requestId, ', current:', currentLoadRequestId, ')');
        return;
      }

      newItems = (response.data.data || []).map(group => ({
        ...group,
        id: group.groupId || group.id,
        name: group.name,
        avatar_url: group.avatar_url,
        members_count: group.members_count || 0,
        unread_count: group.unread_count || 0,
        last_message: getPlainText(group.last_message_preview || group.last_message),
        last_message_at: group.last_message_at,
        itemType: 'groups',
      }));
    }

    // 🔥 FIX: Final check before updating - verify tab hasn't changed during processing
    if (requestId === currentLoadRequestId && targetNav === activeNav.value) {
      listItems.value = newItems;
      console.log('✅ [ZaloIndex] List updated for', targetNav, '(', newItems.length, 'items)');
    } else {
      console.log('⚠️ [ZaloIndex] Skipping list update - tab changed during load');
    }
  } catch (error) {
    console.error('Failed to load list:', error);
  } finally {
    // 🔥 FIX: Only set loadingList to false if this is the current request
    if (requestId === currentLoadRequestId) {
      loadingList.value = false;
    }
  }
};

// Handle resync button click
const handleResync = () => {
  loadList(true); // Force sync from Zalo
};

// 🔥 NEW: Fetch unread counts
const fetchUnreadCounts = async () => {
  try {
    console.log('🔄 [ZaloIndex] Fetching total unread counts...');
    const branchId = localStorage.getItem('current_branch_id');

    // Fetch total unread across all accounts
    const response = await axios.get('/api/zalo/unread-counts', {
      params: { branch_id: branchId }
    });

    console.log('📥 [ZaloIndex] Total unread response:', response.data);

    if (response.data.success) {
      const data = response.data.data;
      totalUnreadCount.value = data.total_unread || 0;

      console.log('📊 [ZaloIndex] Total unread count:', totalUnreadCount.value);

      // Build per-account map
      const accountCounts = {};
      if (data.by_account && Array.isArray(data.by_account)) {
        data.by_account.forEach(item => {
          accountCounts[item.zalo_account_id] = item.unread_count || 0;
        });
      }
      accountUnreadCounts.value = accountCounts;

      console.log('📊 [ZaloIndex] Account unread counts:', accountUnreadCounts.value);

      // If on history tab and have current account, also update conversation unread counts
      if (activeNav.value === 'history' && currentAccount.value?.id) {
        console.log('📌 [ZaloIndex] On history tab, fetching conversation counts...');
        await fetchConversationUnreadCounts(currentAccount.value.id);
      }
    }
  } catch (error) {
    console.error('❌ [ZaloIndex] Error fetching unread counts:', error);
  }
};

// 🔥 NEW: Fetch unread counts for conversations of current account
const fetchConversationUnreadCounts = async (accountId) => {
  try {
    console.log('🔍 [ZaloIndex] Fetching conversation unread counts for account:', accountId);
    const branchId = localStorage.getItem('current_branch_id');
    const response = await axios.get('/api/zalo/unread-counts', {
      params: {
        branch_id: branchId,
        account_id: accountId
      }
    });

    console.log('📥 [ZaloIndex] Unread counts response:', response.data);

    if (response.data.success) {
      const data = response.data.data;
      const byConversation = data.by_conversation || [];

      console.log('📊 [ZaloIndex] By conversation:', byConversation);
      console.log('📋 [ZaloIndex] Current listItems count:', listItems.value.length);

      // Update unread_count in listItems
      listItems.value = listItems.value.map(item => {
        // Match by ID - item.id should match recipient_id in conversation
        const itemId = item.id || item.zalo_user_id || item.zalo_group_id;
        const conv = byConversation.find(c => c.recipient_id === itemId);

        const unreadCount = conv ? conv.unread_count : 0;

        if (unreadCount > 0) {
          console.log('📌 [ZaloIndex] Found unread for:', item.name || item.displayName, 'count:', unreadCount);
        }

        return {
          ...item,
          unread_count: unreadCount
        };
      });

      console.log('✅ [ZaloIndex] Updated listItems with unread counts');
    }
  } catch (error) {
    console.error('❌ [ZaloIndex] Error fetching conversation unread counts:', error);
  }
};

// 🔥 NEW: Mark conversation as read
const markConversationAsRead = async (recipientId) => {
  if (!currentAccount.value?.id || !recipientId) return;

  try {
    const branchId = localStorage.getItem('current_branch_id');
    await axios.post('/api/zalo/mark-as-read', {
      branch_id: branchId,
      account_id: currentAccount.value.id,
      recipient_id: recipientId
    });

    // Refresh unread counts after marking as read
    await fetchUnreadCounts();
  } catch (error) {
    console.error('[ZaloIndex] Error marking as read:', error);
  }
};

// 🔥 NEW: Start polling for unread counts
const startUnreadPolling = () => {
  // Fetch immediately
  fetchUnreadCounts();

  // Then poll every 30 seconds
  if (unreadPollingInterval.value) {
    clearInterval(unreadPollingInterval.value);
  }
  unreadPollingInterval.value = setInterval(fetchUnreadCounts, 30000);
};

// 🔥 NEW: Stop polling for unread counts
const stopUnreadPolling = () => {
  if (unreadPollingInterval.value) {
    clearInterval(unreadPollingInterval.value);
    unreadPollingInterval.value = null;
  }
};

// Handle sync history button click
const handleSyncHistory = async () => {
  if (syncingHistory.value) return;

  // 🔥 FIX: Use currentAccount instead of zaloAccount
  const accountId = currentAccount.value?.id || zaloAccount?.activeAccountId.value;
  if (!accountId) {
    useSwal().fire({
      icon: 'warning',
      title: t('zalo.no_active_account') || 'No Active Account',
      text: t('zalo.please_select_account') || 'Please select an active Zalo account first.',
    });
    return;
  }

  syncingHistory.value = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const params = branchId ? { branch_id: branchId } : {};
    
    const response = await axios.post('/api/zalo/messages/sync-history', {
      account_id: accountId,
      ...params
    });

    if (response.data.success) {
      useSwal().fire({
        icon: 'success',
        title: t('zalo.sync_history_success') || 'Sync History Success',
        text: response.data.message || 'Chat history sync completed. All future messages will be saved automatically.',
        timer: 3000,
      });
      
      // Reload list (history, groups, or friends)
      if (['history', 'groups', 'friends'].includes(activeNav.value)) {
        loadList(false);
      }
    } else {
      throw new Error(response.data.message || 'Failed to sync history');
    }
  } catch (error) {
    console.error('Failed to sync history:', error);
    useSwal().fire({
      icon: 'error',
      title: t('zalo.sync_history_failed') || 'Sync History Failed',
      text: error.response?.data?.message || error.message || 'Failed to sync chat history',
    });
  } finally {
    syncingHistory.value = false;
  }
};

// Debounce timer for selectItem to prevent rapid conversation switching
let selectItemDebounceTimer = null;
const DEBOUNCE_DELAY = 150; // 150ms debounce delay

// Select item with debounce to prevent race condition when clicking too fast
const selectItem = (item) => {
  // Clear any pending selection
  if (selectItemDebounceTimer) {
    clearTimeout(selectItemDebounceTimer);
  }

  // Set a short debounce to prevent rapid switches
  selectItemDebounceTimer = setTimeout(() => {
    console.log('🎯 [ZaloIndex] Selecting conversation:', item.id, item.name);
    selectedItem.value = item;
    selectItemDebounceTimer = null;
    // Note: Mark as read is now handled in ZaloChatView.vue when joining conversation room
  }, DEBOUNCE_DELAY);
};

// Provide selectItem function to child components (after it's defined)
provide('selectItem', selectItem);

// Handle message sent
const handleMessageSent = () => {
  // Update selected conversation's timestamp without full reload
  // This prevents page blink while keeping conversation list updated
  if (selectedItem.value) {
    const conversation = listItems.value.find(item => item.id === selectedItem.value.id);
    if (conversation) {
      conversation.last_message_at = new Date().toISOString();
      // Sort conversations by last_message_at (most recent first)
      listItems.value.sort((a, b) => {
        const dateA = new Date(a.last_message_at || 0);
        const dateB = new Date(b.last_message_at || 0);
        return dateB - dateA;
      });
    }
  }
  console.log('✅ [ZaloIndex] Message sent, conversation list updated locally');
};

// Handle account selected from manager
const handleAccountSelectedFromManager = (account) => {
  selectedAccountForManager.value = account;
};

// Handle navigation click
const handleNavClick = (navKey) => {
  // Close account manager if open
  if (showAccountManager.value) {
    showAccountManager.value = false;
    selectedAccountForManager.value = null;
    showAddAccountForm.value = false;
  }
  // Set active nav
  activeNav.value = navKey;
};

// Navigate to Group Assignment page
const navigateToGroupAssignment = () => {
  router.push({ name: 'zalo.group-assignment' });
};

// Format phone number for display
const formatPhoneNumber = (phone) => {
  if (!phone) return '';
  // If starts with +84, format it
  if (phone.startsWith('+84')) {
    return phone;
  }
  // If starts with 84, add +
  if (phone.startsWith('84')) {
    return '+' + phone;
  }
  // If starts with 0, replace with +84
  if (phone.startsWith('0')) {
    return '+84' + phone.substring(1);
  }
  // Otherwise, add +84
  return '+84' + phone;
};

// Handle phone input with debounce
const handlePhoneInput = () => {
  // Clear previous timeout
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }
  
  // Reset states
  addFriendForm.value.searchResult = null;
  addFriendForm.value.searched = false;
  
  // Get phone number (remove spaces, dashes, etc.)
  const phoneNumber = addFriendForm.value.phoneNumber.replace(/[\s\-]/g, '');
  
  // Must have at least 9 digits
  if (phoneNumber.length < 9) {
    return;
  }
  
  // Debounce search (wait 800ms after user stops typing)
  searchTimeout = setTimeout(() => {
    searchUser(phoneNumber);
  }, 800);
};

// Search user by phone number
const searchUser = async (phoneNumber) => {
  // 🔥 FIX: Use currentAccount instead of zaloAccount
  const accountId = currentAccount.value?.id || zaloAccount?.activeAccountId.value;
  if (!accountId) {
    useSwal().fire({
      icon: 'warning',
      title: t('zalo.no_account_selected'),
      text: t('zalo.please_select_zalo_account'),
    });
    return;
  }
  
  // Normalize phone number (remove leading 0, add 84)
  let normalizedPhone = phoneNumber;
  if (phoneNumber.startsWith('0')) {
    normalizedPhone = '84' + phoneNumber.substring(1);
  } else if (!phoneNumber.startsWith('84')) {
    normalizedPhone = '84' + phoneNumber;
  }
  
  addFriendForm.value.searching = true;
  addFriendForm.value.searched = false;
  addFriendForm.value.searchResult = null;
  
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const params = branchId ? { branch_id: branchId } : {};
    
    const response = await axios.post('/api/zalo/users/search', {
      account_id: accountId,
      phone_number: normalizedPhone,
      ...params
    });
    
    if (response.data.success) {
      addFriendForm.value.searchResult = response.data.data;
      console.log('✅ User found:', addFriendForm.value.searchResult);
    }
  } catch (error) {
    console.error('Failed to search user:', error);
    addFriendForm.value.searchResult = null;
    
    // Don't show error for 404 (user not found)
    if (error.response?.status !== 404) {
      useSwal().fire({
        icon: 'error',
        title: t('zalo.search_error'),
        text: error.response?.data?.message || t('zalo.cannot_search_user'),
        timer: 3000,
      });
    }
  } finally {
    addFriendForm.value.searching = false;
    addFriendForm.value.searched = true;
  }
};

// Send friend request to search result
const handleSendFriendRequest = async (userResult) => {
  // 🔥 FIX: Use currentAccount instead of zaloAccount
  const accountId = currentAccount.value?.id || zaloAccount?.activeAccountId.value;
  if (!accountId) return;

  addFriendForm.value.submitting = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const params = branchId ? { branch_id: branchId } : {};
    
    const response = await axios.post('/api/zalo/friends/send-request', {
      account_id: accountId,
      user_id: userResult.id,
      message: t('zalo.friend_request_default_message'),
      ...params
    });
    
    if (response.data.success) {
      useSwal().fire({
        icon: 'success',
        title: t('zalo.friend_request_sent'),
        text: t('zalo.friend_request_sent_to', { name: userResult.display_name }),
        timer: 3000,
      });
      
      // Close modal and reset
      closeAddFriendModal();
      
      // Optionally reload friends list
      if (activeNav.value === 'friends') {
        setTimeout(() => loadList(), 1000);
      }
    } else {
      throw new Error(response.data.message || 'Failed to send friend request');
    }
  } catch (error) {
    console.error('Failed to send friend request:', error);
    useSwal().fire({
      icon: 'error',
      title: t('zalo.send_request_failed'),
      text: error.response?.data?.message || error.message || t('zalo.cannot_send_request'),
    });
  } finally {
    addFriendForm.value.submitting = false;
  }
};

// Close Add Friend Modal
const closeAddFriendModal = () => {
  showAddFriendModal.value = false;
  addFriendForm.value.phoneNumber = '';
  addFriendForm.value.searchResult = null;
  addFriendForm.value.searching = false;
  addFriendForm.value.searched = false;
  addFriendForm.value.submitting = false;
  if (searchTimeout) {
    clearTimeout(searchTimeout);
    searchTimeout = null;
  }
};

// Handle Create Group
const handleCreateGroup = async () => {
  // 🔥 FIX: Use currentAccount instead of zaloAccount
  const accountId = currentAccount.value?.id || zaloAccount?.activeAccountId.value;
  if (!accountId) {
    useSwal().fire({
      icon: 'warning',
      title: t('zalo.no_active_account'),
      text: t('zalo.please_select_account'),
    });
    return;
  }

  if (createGroupForm.value.selectedMembers.length === 0) {
    useSwal().fire({
      icon: 'warning',
      title: t('zalo.no_members_selected'),
      text: t('zalo.select_at_least_one_member'),
    });
    return;
  }
  
  createGroupForm.value.submitting = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const params = branchId ? { branch_id: branchId } : {};
    
    const response = await axios.post('/api/zalo/groups/create', {
      account_id: accountId,
      name: createGroupForm.value.name || undefined,
      members: createGroupForm.value.selectedMembers,
      ...params
    });
    
    if (response.data.success) {
      const data = response.data.data;
      const successCount = data.successMembers?.length || 0;
      const errorCount = data.errorMembers?.length || 0;

      useSwal().fire({
        icon: 'success',
        title: t('zalo.group_created'),
        html: `${t('zalo.group_created_successfully')}<br>${t('zalo.added_members', { count: successCount })}${errorCount > 0 ? `<br>${t('zalo.failed_members', { count: errorCount })}` : ''}`,
        timer: 3000,
      });
      
      // Reset form and close modal
      createGroupForm.value.name = '';
      createGroupForm.value.selectedMembers = [];
      createGroupForm.value.searchQuery = '';
      showCreateGroupModal.value = false;
      
      // Reload groups list if on groups tab
      if (activeNav.value === 'groups') {
        loadList();
      }
    } else {
      throw new Error(response.data.message || 'Failed to create group');
    }
  } catch (error) {
    console.error('Failed to create group:', error);
    useSwal().fire({
      icon: 'error',
      title: t('zalo.failed'),
      text: error.response?.data?.message || error.message || t('zalo.create_group_failed'),
    });
  } finally {
    createGroupForm.value.submitting = false;
  }
};

// Load friends for Create Group modal
const loadFriendsForGroup = async () => {
  // 🔥 FIX: Use currentAccount instead of zaloAccount
  const accountId = currentAccount.value?.id || zaloAccount?.activeAccountId.value;
  if (!accountId) return;

  createGroupForm.value.loadingFriends = true;
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const params = { account_id: accountId };
    if (branchId) params.branch_id = branchId;
    
    const response = await axios.get('/api/zalo/friends', { params });
    createGroupForm.value.friends = (response.data.data || []).map(friend => ({
      id: friend.id,
      name: friend.name || friend.display_name,
      displayName: friend.display_name || friend.name,
      avatar: friend.avatar_url || friend.avatar,
      phone: friend.phone || null,
    }));
  } catch (error) {
    console.error('Failed to load friends:', error);
    createGroupForm.value.friends = [];
  } finally {
    createGroupForm.value.loadingFriends = false;
  }
};

// Watch showCreateGroupModal to load friends when modal opens
watch(showCreateGroupModal, (newValue) => {
  if (newValue) {
    loadFriendsForGroup();
  }
});

// Format last message - hide CDN URLs for images
const formatLastMessage = (lastMessage) => {
  if (!lastMessage) return t('zalo.no_messages');
  
  // Check if it's an image CDN URL
  if (lastMessage.includes('zdn.vn') ||
      (lastMessage.includes('http') && (lastMessage.includes('.jpg') || lastMessage.includes('.png') || lastMessage.includes('.jpeg') || lastMessage.includes('.gif')))) {
    return t('zalo.image');
  }
  
  return lastMessage;
};

// Handle account updated
const handleAccountUpdated = async () => {
  // Reload current account
  await zaloAccount.loadActiveAccount();
  currentAccount.value = zaloAccount.activeAccount.value;
  // Reload list
  loadList();
  // Close account manager if needed
  showAccountManager.value = false;
  selectedAccountForManager.value = null;
};

// Handle assignment updated (branch, department, or users)
const handleAssignmentUpdated = ({ itemId, updatedData }) => {
  console.log('[ZaloIndex] Assignment updated:', { itemId, updatedData });

  // Find and update the item in listItems
  const itemIndex = listItems.value.findIndex(item => item.id === itemId);
  if (itemIndex !== -1) {
    // Update the item with new data
    if (updatedData) {
      listItems.value[itemIndex] = {
        ...listItems.value[itemIndex],
        branch_id: updatedData.branch_id,
        branch: updatedData.branch,
        department_id: updatedData.department_id,
        department: updatedData.department,
      };

      console.log('[ZaloIndex] Updated list item:', listItems.value[itemIndex]);
    }
  }

  // Also update selectedItem if it's the same item
  if (selectedItem.value && selectedItem.value.id === itemId && updatedData) {
    selectedItem.value = {
      ...selectedItem.value,
      branch_id: updatedData.branch_id,
      branch: updatedData.branch,
      department_id: updatedData.department_id,
      department: updatedData.department,
    };

    console.log('[ZaloIndex] Updated selectedItem:', selectedItem.value);
  }
};

// Watch active nav
const watchActiveNav = async () => {
  selectedItem.value = null;
  await loadList(false); // Don't force sync on nav change

  // 🔥 NEW: When switching to history, fetch conversation unread counts
  if (activeNav.value === 'history' && currentAccount.value?.id) {
    console.log('📌 [ZaloIndex watchActiveNav] Switched to history, fetching conversation counts...');
    await fetchConversationUnreadCounts(currentAccount.value.id);
  }
};

// 🔥 FIX: Store unsubscribe functions to cleanup later
const unsubscribeConversationRef = ref(null);
const unsubscribeConversationsUpdatedRef = ref(null);

// 🔥 FIX: Register lifecycle hooks BEFORE any async operations
onUnmounted(() => {
  if (unsubscribeConversationRef.value) unsubscribeConversationRef.value();
  if (unsubscribeConversationsUpdatedRef.value) unsubscribeConversationsUpdatedRef.value();
  if (currentAccount.value?.id) {
    zaloSocket.leaveAccount(currentAccount.value.id);
  }
  zaloSocket.disconnect();
  stopUnreadPolling();
});

// Validate sessions for all accounts and update status
const validateSessions = async () => {
  try {
    console.log('🔍 [ZaloIndex] Validating sessions for all accounts...');
    const branchId = localStorage.getItem('current_branch_id');
    const response = await axios.post('/api/zalo/validate-all-sessions', {
      branch_id: branchId
    });

    if (response.data.success) {
      const { summary, results } = response.data;
      console.log('📊 [ZaloIndex] Session validation results:', summary);

      // Check if any sessions are expired
      const expiredAccounts = results.filter(r => r.error_code === 'SESSION_EXPIRED');
      if (expiredAccounts.length > 0) {
        console.warn('⚠️ [ZaloIndex] Found expired sessions:', expiredAccounts.map(a => a.account_name));

        // Show warning to user
        const accountNames = expiredAccounts.map(a => a.account_name).join(', ');
        useSwal().fire({
          icon: 'warning',
          title: 'Phiên Zalo hết hạn',
          html: `<div class="text-left">
            <p class="mb-2">Các tài khoản sau đã hết hạn phiên đăng nhập:</p>
            <ul class="list-disc ml-4 mb-3">
              ${expiredAccounts.map(a => `<li><strong>${a.account_name}</strong></li>`).join('')}
            </ul>
            <p class="text-sm text-gray-600">Vui lòng đăng nhập lại các tài khoản này để tiếp tục sử dụng.</p>
          </div>`,
          confirmButtonText: 'Đã hiểu',
        });
      }

      // Update account list to reflect new status
      if (summary.expired > 0 || summary.valid > 0) {
        // Trigger reload of account list
        window.dispatchEvent(new CustomEvent('zalo-accounts-status-updated'));
      }
    }
  } catch (error) {
    console.error('❌ [ZaloIndex] Failed to validate sessions:', error);
  }
};

// Initialize
onMounted(async () => {
  // Connect to WebSocket
  zaloSocket.connect();

  await zaloAccount.init();
  currentAccount.value = zaloAccount.activeAccount.value;
  await loadList();

  // Join account room for conversation updates
  if (currentAccount.value?.id) {
    zaloSocket.joinAccount(currentAccount.value.id);
  }

  // 🔥 NEW: Validate all sessions when entering Zalo module
  await validateSessions();

  // 🔥 NEW: Start polling for unread counts
  startUnreadPolling();

  // Listen for account changes
  window.addEventListener('zalo-account-changed', (event) => {
    const oldAccountId = currentAccount.value?.id;
    const newAccount = event.detail?.account || zaloAccount.activeAccount.value;
    currentAccount.value = newAccount;
    
    // Leave old account room
    if (oldAccountId) {
      zaloSocket.leaveAccount(oldAccountId);
    }
    
    // Join new account room
    if (currentAccount.value?.id) {
      zaloSocket.joinAccount(currentAccount.value.id);
    }
    
    loadList();
  });
  
  // Listen for switch to history tab (e.g., when opening chat with member)
  window.addEventListener('zalo-switch-to-history', () => {
    console.log('🔄 [ZaloIndex] Switching to history tab...');
    activeNav.value = 'history';
  });
  
  // Listen for group list refresh (e.g., after avatar change)
  window.addEventListener('refresh-group-list', (event) => {
    console.log('🔄 [ZaloIndex] Refreshing group list after avatar change...');
    
    // If event includes group ID and new avatar URL, update immediately
    if (event.detail?.groupId && event.detail?.newAvatarUrl) {
      const groupId = event.detail.groupId;
      const newAvatarUrl = event.detail.newAvatarUrl;
      
      console.log('✨ [ZaloIndex] Updating avatar for group:', groupId, newAvatarUrl);
      
      // Update in listItems
      const groupInList = listItems.value.find(item => item.id === groupId);
      if (groupInList) {
        groupInList.avatar_url = newAvatarUrl;
        console.log('✅ [ZaloIndex] Updated avatar in list');
      }
      
      // Update in selectedItem
      if (selectedItem.value?.id === groupId) {
        selectedItem.value.avatar_url = newAvatarUrl;
        console.log('✅ [ZaloIndex] Updated avatar in selected item');
      }
    } else {
      // Fallback: reload entire list
      if (activeNav.value === 'groups') {
        loadList(false); // Reload groups without clearing selection
      }
    }
  });
  
  // Listen for conversation updates and update list locally (no reload = no blink)
  // 🔥 FIX: Store unsubscribe function in ref for cleanup
  unsubscribeConversationRef.value = zaloSocket.onConversationUpdate(async (data) => {
    if (data.account_id === currentAccount.value?.id) {
      console.log('📥 [ZaloIndex] Conversation updated via WebSocket:', data);

      // Only update conversation list if we're on the history tab
      if (activeNav.value === 'history') {
        // Update conversation in list without full reload
        const conversation = listItems.value.find(item => item.id === data.recipient_id);
        if (conversation) {
          // Update existing conversation
          if (data.last_message) conversation.last_message = getPlainText(data.last_message);
          if (data.last_message_at) conversation.last_message_at = data.last_message_at;
          if (data.unread_count !== undefined) conversation.unread_count = data.unread_count;
          if (data.recipient_name) conversation.name = data.recipient_name;
          if (data.recipient_avatar_url) conversation.avatar_url = data.recipient_avatar_url;

          // Re-sort by last_message_at (most recent first)
          listItems.value.sort((a, b) => {
            const dateA = new Date(a.last_message_at || 0);
            const dateB = new Date(b.last_message_at || 0);
            return dateB - dateA;
          });

          console.log('✅ [ZaloIndex] Conversation updated locally (no reload)');
        } else {
          // 🔥 NEW: Fetch and add new/restored conversation to list (smooth UX, no blink!)
          console.log('⚠️  [ZaloIndex] Conversation not in list (new or restored), fetching details...', {
            recipient_id: data.recipient_id,
            current_list_count: listItems.value.length,
          });
          
          try {
            const branchId = localStorage.getItem('current_branch_id');
            const response = await axios.get('/api/zalo/conversations', {
              params: {
                account_id: currentAccount.value?.id,
                branch_id: branchId,
              }
            });

            const conversations = response.data.data?.data || response.data.data || [];
            console.log('📥 [ZaloIndex] Fetched conversations:', {
              total: conversations.length,
              looking_for: data.recipient_id,
              first_3_recipients: conversations.slice(0, 3).map(c => c.recipient_id),
            });
            
            const newConv = conversations.find(c => c.recipient_id === data.recipient_id);
            
            if (newConv) {
              // Check if already exists (edge case: might have been added by another event)
              const existingIndex = listItems.value.findIndex(item => item.id === newConv.recipient_id);
              
              if (existingIndex === -1) {
                // Add to list (prepend to show at top)
                listItems.value.unshift({
                  ...newConv,
                  id: newConv.recipient_id,
                  conversation_id: newConv.id,
                  name: newConv.recipient_name || newConv.name || newConv.displayName,
                  avatar_url: newConv.recipient_avatar_url || newConv.avatar_url,
                  last_message: getPlainText(newConv.last_message_preview || newConv.last_message),
                  last_message_at: newConv.last_message_at,
                  unread_count: newConv.unread_count || 0,
                  itemType: newConv.recipient_type === 'user' ? 'friends' : 'groups',
                  department: newConv.department,
                  assigned_users: newConv.assigned_users,
                });
                console.log('✅ [ZaloIndex] Conversation added to list smoothly (no reload)');
              } else {
                console.log('ℹ️  [ZaloIndex] Conversation already in list, updating instead');
                // Update existing
                const existing = listItems.value[existingIndex];
                if (data.last_message) existing.last_message = getPlainText(data.last_message);
                if (data.last_message_at) existing.last_message_at = data.last_message_at;
                if (data.unread_count !== undefined) existing.unread_count = data.unread_count;
                
                // Re-sort
                listItems.value.sort((a, b) => {
                  const dateA = new Date(a.last_message_at || 0);
                  const dateB = new Date(b.last_message_at || 0);
                  return dateB - dateA;
                });
              }
            } else {
              console.warn('⚠️  [ZaloIndex] Conversation not found in API response', {
                recipient_id: data.recipient_id,
                total_conversations: conversations.length,
              });
            }
          } catch (error) {
            console.error('[ZaloIndex] Failed to fetch conversation:', error);
            // Fallback to reload if fetch fails
            console.log('🔄 [ZaloIndex] Falling back to full reload');
            loadList();
          }
        }
      } else {
        // We're on a different tab (friends/groups), just log it
        console.log('📝 [ZaloIndex] Conversation updated but not on history tab, skipping list update');
      }

      // 🔥 NEW: Refresh total unread count when conversation updates (regardless of active tab)
      fetchUnreadCounts();
    }
  });

  // 🔔 Listen for conversations updated event (after sync history fixes Unknown)
  // 🔥 FIX: Store unsubscribe function in ref for cleanup
  unsubscribeConversationsUpdatedRef.value = zaloSocket.onConversationsUpdated((data) => {
    if (data.account_id === currentAccount.value?.id) {
      console.log('📥 [ZaloIndex] Conversations updated via Sync History:', data);
      console.log('   Fixed:', data.fixed_count, '| Fetched:', data.friends_fetched, 'friends,', data.groups_fetched, 'groups');

      // Reload conversation list to show updated names
      if (activeNav.value === 'history') {
        loadList(false);
        console.log('✅ [ZaloIndex] Conversation list reloaded after sync');
      }
    }
  });

  // 🔥 NEW: Listen for conversation deleted event
  const unsubscribeConversationDeletedRef = ref(null);
  unsubscribeConversationDeletedRef.value = zaloSocket.onConversationDeleted((data) => {
    if (data.account_id === currentAccount.value?.id) {
      console.log('🗑️  [ZaloIndex] Conversation deleted via WebSocket:', data);

      if (activeNav.value === 'history') {
        // Remove from list
        const index = listItems.value.findIndex(item => 
          item.id === data.recipient_id || item.conversation_id === data.conversation_id
        );
        
        if (index !== -1) {
          listItems.value.splice(index, 1);
          console.log('✅ [ZaloIndex] Conversation removed from list (realtime)');

          // If the deleted conversation was selected, clear selection
          if (selectedItem.value?.id === data.recipient_id || 
              selectedItem.value?.conversation_id === data.conversation_id) {
            selectedItem.value = null;
          }
        }
      }

      // Refresh unread counts
      fetchUnreadCounts();
    }
  });

  // Cleanup deleted listener on unmount
  onUnmounted(() => {
    if (unsubscribeConversationDeletedRef.value) unsubscribeConversationDeletedRef.value();
  });

  // 🔥 NEW: Handle query parameters for direct navigation to conversation (from toast notification)
  if (route.query.accountId && route.query.recipientId) {
    const targetAccountId = parseInt(route.query.accountId);
    const targetRecipientId = route.query.recipientId;

    console.log('🎯 [ZaloIndex] Direct navigation to conversation:', { accountId: targetAccountId, recipientId: targetRecipientId });

    // Switch to history tab
    activeNav.value = 'history';

    // If account doesn't match current account, switch account first
    if (currentAccount.value?.id !== targetAccountId) {
      console.log('🔄 [ZaloIndex] Switching to account:', targetAccountId);
      const targetAccount = zaloAccount.accounts.value.find(acc => acc.id === targetAccountId);
      if (targetAccount) {
        currentAccount.value = targetAccount;
        zaloAccount.setActiveAccount(targetAccount);
        await loadList(); // Reload list for new account
      }
    }

    // Wait a bit for list to load if needed, then select the conversation
    setTimeout(() => {
      const conversation = listItems.value.find(item => String(item.id) === String(targetRecipientId));
      if (conversation) {
        console.log('✅ [ZaloIndex] Auto-selecting conversation:', conversation.name);
        selectItem(conversation);
      } else {
        console.log('⚠️ [ZaloIndex] Conversation not found in list, may need to fetch it');
      }

      // Clear query parameters after navigation
      router.replace({ name: 'zalo.index' });
    }, 500);
  }
});

// Watch activeNav changes
watch(activeNav, watchActiveNav);

// 🔥 NEW: Watch currentAccount changes to update conversation unread counts
watch(currentAccount, async (newAccount) => {
  if (newAccount?.id && activeNav.value === 'history') {
    console.log('📌 [ZaloIndex watch currentAccount] Account changed, fetching conversation counts...');
    await fetchConversationUnreadCounts(newAccount.id);
  }
});
</script>
