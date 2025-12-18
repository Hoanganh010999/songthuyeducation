<template>
  <div class="p-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ t('google_drive.title') }}</h1>
          <p class="text-sm text-gray-600 mt-1">{{ currentFolderName }}</p>
        </div>
        <div class="flex items-center space-x-2">
          <button
            v-if="canManage"
            @click="showSyncDialog"
            :disabled="syncing"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition flex items-center space-x-2"
          >
            <svg class="w-5 h-5" :class="{ 'animate-spin': syncing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>{{ syncing ? t('google_drive.syncing') : t('google_drive.sync') }}</span>
          </button>
          <router-link
            v-if="hasSettingsPermission"
            to="/settings"
            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center space-x-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span>{{ t('common.settings') }}</span>
          </router-link>
        </div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <!-- Back Button -->
          <button
            v-if="breadcrumbs.length > 1"
            @click="goBack"
            class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition flex items-center space-x-1"
            :title="t('common.back')"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span class="text-sm">{{ t('common.back') }}</span>
          </button>
          
          <!-- Breadcrumb -->
          <button
            v-for="(item, index) in breadcrumbs"
            :key="index"
            @click="navigateToFolder(item.id)"
            class="text-sm text-blue-600 hover:underline"
          >
            {{ item.name }}
            <span v-if="index < breadcrumbs.length - 1" class="text-gray-400 mx-1">/</span>
          </button>
        </div>

        <div v-if="canManage" class="flex items-center space-x-2">
          <button
            @click="showUploadDialog"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center space-x-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <span>{{ t('google_drive.upload') }}</span>
          </button>
          <button
            @click="showCreateFolderDialog"
            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center space-x-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
            </svg>
            <span>{{ t('google_drive.new_folder') }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- View Toggle -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600">
          {{ items.length }} {{ items.length === 1 ? 'item' : 'items' }}
        </div>
        <div class="flex space-x-2">
          <button
            @click="viewMode = 'grid'"
            :class="viewMode === 'grid' ? 'bg-blue-100 text-blue-600' : 'text-gray-600 hover:bg-gray-100'"
            class="p-2 rounded transition"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
          </button>
          <button
            @click="viewMode = 'list'"
            :class="viewMode === 'list' ? 'bg-blue-100 text-blue-600' : 'text-gray-600 hover:bg-gray-100'"
            class="p-2 rounded transition"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Files List -->
    <div class="bg-white rounded-lg shadow">
      <div v-if="loading" class="p-12 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="mt-4 text-gray-600">{{ t('common.loading') }}...</p>
      </div>

      <div v-else-if="items.length === 0" class="p-12 text-center text-gray-500">
        <svg class="w-24 h-24 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
        </svg>
        <p class="text-lg font-medium text-gray-700">{{ t('google_drive.empty_folder') }}</p>
        <p class="text-sm text-gray-500 mt-1">{{ t('google_drive.no_files') }}</p>
      </div>

      <!-- Grid View -->
      <div v-else-if="viewMode === 'grid'" class="p-6">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          <div
            v-for="item in items"
            :key="item.id"
            @click="handleItemClick(item)"
            class="group relative bg-white border-2 border-gray-200 rounded-lg p-4 hover:border-blue-400 hover:shadow-md transition-all cursor-pointer"
          >
            <!-- Folder/File Icon -->
            <div class="flex justify-center mb-3">
              <svg v-if="item.type === 'folder'" class="w-16 h-16 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
              </svg>
              <div v-else class="relative">
                <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <div v-if="item.thumbnail_link" class="absolute inset-0 flex items-center justify-center">
                  <img :src="item.thumbnail_link" class="w-12 h-12 object-cover rounded" />
                </div>
              </div>
            </div>

            <!-- File Name -->
            <p class="text-sm text-center text-gray-900 font-medium truncate" :title="item.name">
              {{ item.name }}
            </p>

            <!-- Actions (show on hover) -->
            <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity" @click.stop>
              <div class="flex space-x-1 bg-white rounded-lg shadow-lg p-1">
                <a
                  v-if="item.web_view_link"
                  :href="item.web_view_link"
                  target="_blank"
                  class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition"
                  :title="t('google_drive.open_in_drive')"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                  </svg>
                </a>
                <button
                  @click="shareItem(item)"
                  class="p-1.5 text-green-600 hover:bg-green-50 rounded transition"
                  :title="t('google_drive.share')"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                  </svg>
                </button>
                <button
                  v-if="canManage"
                  @click="renameItem(item)"
                  class="p-1.5 text-gray-600 hover:bg-gray-100 rounded transition"
                  :title="t('google_drive.rename')"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button
                  v-if="canManage"
                  @click="deleteItem(item)"
                  class="p-1.5 text-red-600 hover:bg-red-50 rounded transition"
                  :title="t('common.delete')"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- List View -->
      <table v-else class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('google_drive.file_name') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('google_drive.size') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('google_drive.modified') }}
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ t('google_drive.actions') }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="item in items"
            :key="item.id"
            class="hover:bg-blue-50 cursor-pointer transition group"
            @click="handleItemClick(item)"
          >
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <svg v-if="item.type === 'folder'" class="w-10 h-10 text-blue-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                </svg>
                <div v-else class="relative mr-3 flex-shrink-0">
                  <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                  </svg>
                  <img v-if="item.thumbnail_link" :src="item.thumbnail_link" class="absolute inset-0 w-10 h-10 object-cover rounded" />
                </div>
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-medium text-gray-900 truncate group-hover:text-blue-600 transition">{{ item.name }}</div>
                  <div class="text-xs text-gray-500 truncate">{{ item.mime_type }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ item.type === 'folder' ? '—' : item.formatted_size }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(item.google_modified_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex items-center justify-end space-x-2" @click.stop>
                <a
                  v-if="item.web_view_link"
                  :href="item.web_view_link"
                  target="_blank"
                  class="p-2 text-blue-600 hover:bg-blue-100 rounded transition"
                  :title="t('google_drive.open_in_drive')"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                  </svg>
                </a>
                <button
                  @click="shareItem(item)"
                  class="p-2 text-green-600 hover:bg-green-100 rounded transition"
                  :title="t('google_drive.share')"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                  </svg>
                </button>
                <button
                  v-if="canManage"
                  @click="renameItem(item)"
                  class="p-2 text-gray-600 hover:bg-gray-100 rounded transition"
                  :title="t('google_drive.rename')"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button
                  v-if="canManage"
                  @click="deleteItem(item)"
                  class="p-2 text-red-600 hover:bg-red-100 rounded transition"
                  :title="t('common.delete')"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';
import { useAuthStore } from '../../stores/auth';
import axios from 'axios';
import Swal from 'sweetalert2';

const { t } = useI18n();
const { showSuccess, showError, confirmDelete } = useSwal();
const authStore = useAuthStore();

const items = ref([]);
const loading = ref(false);
const syncing = ref(false);
const currentFolderId = ref(null);
const breadcrumbs = ref([{ id: null, name: 'School Drive' }]);
const viewMode = ref('grid'); // 'grid' or 'list'

const canManage = computed(() => authStore.hasPermission('google-drive.manage'));
const hasSettingsPermission = computed(() => authStore.hasPermission('system-settings.edit') || authStore.hasPermission('google-drive.settings'));
const canViewRootFolder = computed(() => authStore.hasPermission('google-drive.view_root_folder'));
const currentFolderName = computed(() => breadcrumbs.value[breadcrumbs.value.length - 1]?.name || 'School Drive');

const loadFiles = async (folderId = null) => {
  loading.value = true;
  try {
    // If loading root folder, check if user has permission
    if (folderId === null) {
      // Use accessible folders API for users without root folder permission
      if (!canViewRootFolder.value) {
        const response = await axios.get('/api/google-drive/accessible-folders');
        if (response.data.success) {
          items.value = response.data.data;
        }
        return;
      }
    }

    // Load normal folder contents
    const response = await axios.get('/api/google-drive/files', {
      params: { folder_id: folderId }
    });
    if (response.data.success) {
      items.value = response.data.data;
    }
  } catch (error) {
    console.error('Error loading files:', error);
    showError(error.response?.data?.message || t('common.error_occurred'));
  } finally {
    loading.value = false;
  }
};

const navigateToFolder = (folderId) => {
  currentFolderId.value = folderId;
  
  // Update breadcrumbs
  if (folderId === null) {
    breadcrumbs.value = [{ id: null, name: 'School Drive' }];
  } else {
    const folderIndex = breadcrumbs.value.findIndex(b => b.id === folderId);
    if (folderIndex !== -1) {
      breadcrumbs.value = breadcrumbs.value.slice(0, folderIndex + 1);
    }
  }
  
  loadFiles(folderId);
};

const goBack = () => {
  if (breadcrumbs.value.length > 1) {
    // Go to the parent folder
    const parentBreadcrumb = breadcrumbs.value[breadcrumbs.value.length - 2];
    navigateToFolder(parentBreadcrumb.id);
  }
};

const handleItemClick = (item) => {
  if (item.type === 'folder') {
    breadcrumbs.value.push({ id: item.google_id, name: item.name });
    navigateToFolder(item.google_id);
  }
};

const showSyncDialog = async () => {
  console.log('[GoogleDrive] 🔵 showSyncDialog called');
  console.log('[GoogleDrive] 🔍 Current syncing state:', syncing.value);
  
  if (syncing.value) {
    console.warn('[GoogleDrive] ⚠️ Sync already in progress, ignoring click');
    return;
  }
  
  console.log('[GoogleDrive] ✅ Setting syncing = true');
  syncing.value = true;
  
  try {
    console.log('[GoogleDrive] 📤 Sending POST /api/google-drive/sync...');
    // Start sync job
    const response = await axios.post('/api/google-drive/sync');
    console.log('[GoogleDrive] 📥 Response received:', response.data);
    
    if (response.data.success) {
      console.log('[GoogleDrive] ✅ Response success = true');
      console.log('[GoogleDrive] 🔍 Checking response.data.data:', response.data.data);
      
      // Check if sync was completed immediately (synchronous mode)
      if (response.data.data && response.data.data.root_folder_name !== undefined) {
        console.log('[GoogleDrive] 🎯 Sync completed synchronously');
        // Sync ran synchronously and completed
        syncing.value = false;
        
        const data = response.data.data;
        const rootFolderName = data.root_folder_name || '';
        const rootFolderAction = data.root_folder_action || '';
        const rootFolderExisted = data.root_folder_existed || false;
        const filesSynced = data.files_synced || 0;
        const permissionsSynced = data.permissions_synced || 0;
        const foldersProcessed = data.folders_processed || 0;
        
        // Build root folder status message
        let rootFolderStatus = '';
        if (rootFolderName) {
          const statusIcon = rootFolderExisted ? '✓' : '🆕';
          rootFolderStatus = `
            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded">
              <p class="font-semibold text-blue-900 mb-1">${statusIcon} Root Folder:</p>
              <p class="text-blue-800">${rootFolderName}</p>
              <p class="text-sm text-blue-600 mt-1">(${rootFolderAction})</p>
            </div>
          `;
        }
        
        // Show success dialog
        await Swal.fire({
          icon: 'success',
          title: t('messages.sync_completed_successfully'),
          html: `
            ${rootFolderStatus}
            <div class="text-left space-y-2">
              <p><strong>${t('google_drive.files_synced')}:</strong> ${filesSynced}</p>
              <p><strong>${t('google_drive.permissions_synced')}:</strong> ${permissionsSynced}</p>
              <p><strong>${t('google_drive.folders_processed')}:</strong> ${foldersProcessed}</p>
            </div>
          `,
          confirmButtonText: t('common.ok'),
        });
        
        await loadFiles(currentFolderId.value);
        return;
      }
      
      // Sync is running asynchronously - poll for status
      console.log('[GoogleDrive] 🔄 Starting async polling...');
      let progressDialog;
      
      const pollInterval = setInterval(async () => {
        try {
          const statusResponse = await axios.get('/api/google-drive/sync-status');
          
          if (statusResponse.data.success) {
            const status = statusResponse.data.data;
            
            if (status.status === 'in_progress') {
              // Update progress
              const progress = status.progress || 0;
              const data = status.data || {};
              
              if (!progressDialog || !Swal.isVisible()) {
                // Create progress dialog
                progressDialog = Swal.fire({
                  title: t('google_drive.syncing'),
                  html: `
                    <div class="text-left">
                      <div class="mb-4">
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                          <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-500" style="width: ${progress}%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mt-2 text-center">${progress}%</p>
                      </div>
                      <div class="space-y-2 text-sm">
                        ${data.root_folder_name ? `<p>📁 <strong>Root Folder:</strong> ${data.root_folder_name}</p>` : ''}
                        ${data.files_synced !== undefined ? `<p>📄 <strong>Files synced:</strong> ${data.files_synced}</p>` : ''}
                        ${data.permissions_synced !== undefined ? `<p>🔐 <strong>Permissions synced:</strong> ${data.permissions_synced}</p>` : ''}
                        ${data.folders_processed !== undefined ? `<p>📂 <strong>Folders processed:</strong> ${data.folders_processed}</p>` : ''}
                      </div>
                    </div>
                  `,
                  allowOutsideClick: false,
                  showConfirmButton: false,
                  didOpen: () => {
                    Swal.showLoading();
                  }
                });
              } else {
                // Update existing dialog
                Swal.update({
                  html: `
                    <div class="text-left">
                      <div class="mb-4">
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                          <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-500" style="width: ${progress}%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mt-2 text-center">${progress}%</p>
                      </div>
                      <div class="space-y-2 text-sm">
                        ${data.root_folder_name ? `<p>📁 <strong>Root Folder:</strong> ${data.root_folder_name}</p>` : ''}
                        ${data.files_synced !== undefined ? `<p>📄 <strong>Files synced:</strong> ${data.files_synced}</p>` : ''}
                        ${data.permissions_synced !== undefined ? `<p>🔐 <strong>Permissions synced:</strong> ${data.permissions_synced}</p>` : ''}
                        ${data.folders_processed !== undefined ? `<p>📂 <strong>Folders processed:</strong> ${data.folders_processed}</p>` : ''}
                      </div>
                    </div>
                  `
                });
              }
            } else if (status.status === 'completed') {
              // Stop polling
              clearInterval(pollInterval);
              syncing.value = false;
              
              const data = status.data || {};
              const rootFolderName = data.root_folder_name || '';
              const rootFolderAction = data.root_folder_action || '';
              const rootFolderExisted = data.root_folder_existed || false;
      const filesSynced = data.files_synced || 0;
      const permissionsSynced = data.permissions_synced || 0;
      const foldersProcessed = data.folders_processed || 0;
      
              // Build root folder status message
              let rootFolderStatus = '';
              if (rootFolderName) {
                const statusIcon = rootFolderExisted ? '✓' : '🆕';
                rootFolderStatus = `
                  <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded">
                    <p class="font-semibold text-blue-900 mb-1">${statusIcon} Root Folder:</p>
                    <p class="text-blue-800">${rootFolderName}</p>
                    <p class="text-sm text-blue-600 mt-1">(${rootFolderAction})</p>
                  </div>
                `;
              }
              
              // Show success dialog
      await Swal.fire({
        icon: 'success',
        title: t('messages.sync_completed_successfully'),
        html: `
                  ${rootFolderStatus}
          <div class="text-left space-y-2">
            <p><strong>${t('google_drive.files_synced')}:</strong> ${filesSynced}</p>
            <p><strong>${t('google_drive.permissions_synced')}:</strong> ${permissionsSynced}</p>
            <p><strong>${t('google_drive.folders_processed')}:</strong> ${foldersProcessed}</p>
          </div>
        `,
        confirmButtonText: t('common.ok'),
      });
      
      await loadFiles(currentFolderId.value);
            } else if (status.status === 'failed') {
              // Stop polling
              clearInterval(pollInterval);
              syncing.value = false;
              
              const error = status.data?.error || 'Unknown error';
              showError('Sync failed: ' + error);
            }
          }
        } catch (error) {
          console.error('Error polling sync status:', error);
        }
      }, 2000); // Poll every 2 seconds
      
      // Stop polling after 10 minutes
      setTimeout(() => {
        clearInterval(pollInterval);
        syncing.value = false;
      }, 600000);
    } else {
      // Response didn't match expected format
      console.error('[GoogleDrive] ❌ Response success = false');
      console.error('[GoogleDrive] Response data:', response.data);
      syncing.value = false;
    }
  } catch (error) {
    console.error('[GoogleDrive] ❌ Error starting sync:', error);
    console.error('[GoogleDrive] Error details:', {
      message: error.message,
      response: error.response?.data,
      status: error.response?.status,
    });
    showError(error.response?.data?.message || t('common.error_occurred'));
    syncing.value = false;
  }
  
  console.log('[GoogleDrive] 🏁 showSyncDialog finished');
};

const showCreateFolderDialog = async () => {
  const { value: folderName } = await Swal.fire({
    title: t('google_drive.create_folder'),
    input: 'text',
    inputLabel: t('google_drive.enter_folder_name'),
    inputPlaceholder: t('google_drive.folder_name_input'),
    showCancelButton: true,
    confirmButtonText: t('common.create'),
    cancelButtonText: t('common.cancel'),
  });

  if (folderName) {
    try {
      const response = await axios.post('/api/google-drive/folders', {
        name: folderName,
        parent_id: currentFolderId.value,
      });
      if (response.data.success) {
        showSuccess(t('google_drive.folder_created'));
        await loadFiles(currentFolderId.value);
      }
    } catch (error) {
      console.error('Error creating folder:', error);
      showError(error.response?.data?.message || t('common.error_occurred'));
    }
  }
};

const showUploadDialog = async () => {
  const { value: file } = await Swal.fire({
    title: t('google_drive.upload_file'),
    html: `
      <input type="file" id="fileInput" class="block w-full text-sm text-gray-500
        file:mr-4 file:py-2 file:px-4
        file:rounded-full file:border-0
        file:text-sm file:font-semibold
        file:bg-blue-50 file:text-blue-700
        hover:file:bg-blue-100
      "/>
      <p class="mt-2 text-xs text-gray-500">${t('google_drive.max_file_size')}</p>
    `,
    showCancelButton: true,
    confirmButtonText: t('google_drive.upload'),
    cancelButtonText: t('common.cancel'),
    preConfirm: () => {
      const fileInput = document.getElementById('fileInput');
      if (!fileInput.files || fileInput.files.length === 0) {
        Swal.showValidationMessage(t('google_drive.select_file'));
        return false;
      }
      return fileInput.files[0];
    }
  });

  if (file) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('parent_id', currentFolderId.value || '');

    try {
      Swal.fire({
        title: t('google_drive.uploading'),
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      const response = await axios.post('/api/google-drive/upload', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });

      if (response.data.success) {
        Swal.close();
        showSuccess(t('google_drive.upload_success'));
        await loadFiles(currentFolderId.value);
      }
    } catch (error) {
      Swal.close();
      console.error('Error uploading file:', error);
      showError(error.response?.data?.message || t('common.error_occurred'));
    }
  }
};

const renameItem = async (item) => {
  const { value: newName } = await Swal.fire({
    title: t('google_drive.rename'),
    input: 'text',
    inputValue: item.name,
    inputLabel: t('google_drive.enter_new_name'),
    showCancelButton: true,
    confirmButtonText: t('common.save'),
    cancelButtonText: t('common.cancel'),
  });

  if (newName && newName !== item.name) {
    try {
      const response = await axios.patch(`/api/google-drive/files/${item.id}/rename`, {
        name: newName,
      });
      if (response.data.success) {
        showSuccess(t('google_drive.file_renamed'));
        await loadFiles(currentFolderId.value);
      }
    } catch (error) {
      console.error('Error renaming:', error);
      showError(error.response?.data?.message || t('common.error_occurred'));
    }
  }
};

const deleteItem = async (item) => {
  const result = await confirmDelete(
    t('google_drive.confirm_delete'),
    item.name
  );

  if (result.isConfirmed) {
    try {
      const response = await axios.delete(`/api/google-drive/files/${item.id}`);
      if (response.data.success) {
        showSuccess(t('google_drive.file_deleted'));
        await loadFiles(currentFolderId.value);
      }
    } catch (error) {
      console.error('Error deleting:', error);
      showError(error.response?.data?.message || t('common.error_occurred'));
    }
  }
};

const shareItem = async (item) => {
  try {
    // Get current permissions
    const permissionsResponse = await axios.get(`/api/google-drive/files/${item.id}/permissions`);
    const permissions = permissionsResponse.data.data || [];
    
    // Create permissions list HTML
    let permissionsHtml = `
      <div class="text-left">
        <div class="mb-4">
          <h3 class="text-sm font-medium text-gray-700 mb-2">${t('google_drive.current_permissions')}</h3>
          <div class="space-y-2 max-h-60 overflow-y-auto">
    `;
    
    if (permissions.length === 0) {
      permissionsHtml += `<p class="text-sm text-gray-500">${t('google_drive.no_permissions')}</p>`;
    } else {
      permissions.forEach((perm) => {
        permissionsHtml += `
          <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
            <div>
              <p class="text-sm font-medium">${perm.emailAddress || perm.displayName || 'Anyone'}</p>
              <p class="text-xs text-gray-500">${perm.role}</p>
            </div>
          </div>
        `;
      });
    }
    
    permissionsHtml += `
          </div>
        </div>
        <div class="border-t pt-4">
          <h3 class="text-sm font-medium text-gray-700 mb-2">${t('google_drive.share_link')}</h3>
          <input type="text" id="shareLinkInput" readonly 
            value="${item.web_view_link || ''}" 
            class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500">
          <button id="copyLinkBtn" class="mt-2 w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
            ${t('google_drive.copy_link')}
          </button>
        </div>
      </div>
    `;
    
    await Swal.fire({
      title: `${t('google_drive.share')}: ${item.name}`,
      html: permissionsHtml,
      width: '600px',
      showCloseButton: true,
      showConfirmButton: false,
      didOpen: () => {
        // Copy link functionality
        const copyBtn = document.getElementById('copyLinkBtn');
        const linkInput = document.getElementById('shareLinkInput');
        
        copyBtn.addEventListener('click', () => {
          linkInput.select();
          document.execCommand('copy');
          showSuccess(t('google_drive.link_copied'));
        });
      }
    });
    
  } catch (error) {
    console.error('Error sharing item:', error);
    showError(error.response?.data?.message || t('common.error_occurred'));
  }
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleString('vi-VN');
};

onMounted(() => {
  console.log('[GoogleDrive] 🎬 Component mounted');
  console.log('[GoogleDrive] 🔍 Initial syncing state:', syncing.value);
  console.log('[GoogleDrive] 🔍 canManage:', canManage.value);
  
  // Reset syncing state in case it was stuck from previous session
  syncing.value = false;
  console.log('[GoogleDrive] ✅ Reset syncing to false');
  
  loadFiles();
});
</script>

