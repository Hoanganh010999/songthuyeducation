<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">{{ t('permissions.title') }}</h2>
          <p class="text-sm text-gray-600 mt-1">{{ t('permissions.description') }}</p>
        </div>
        <div class="flex items-center space-x-2">
          <select
            v-model="selectedModule"
            @change="filterByModule"
            class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">{{ t('permissions.all_modules') }}</option>
            <option v-for="module in modules" :key="module" :value="module">
              {{ module }}
            </option>
          </select>
          <button
            @click="showCreateModal = true"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ t('permissions.create') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="p-8 text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="text-gray-600 mt-2">{{ t('common.loading') }}</p>
    </div>

    <!-- Permissions by Module -->
    <div v-else class="p-6">
      <div v-for="(perms, module) in groupedPermissions" :key="module" class="mb-6">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">{{ module }}</span>
            <span class="text-sm text-gray-500">({{ perms.length }} {{ t('permissions.items') }})</span>
          </h3>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-1/4">
                  {{ t('permissions.name') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-1/6">
                  {{ t('permissions.action') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ t('permissions.description') }}
                </th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">
                  {{ t('common.status') }}
                </th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">
                  {{ t('common.actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="permission in perms" :key="permission.id" class="hover:bg-gray-50">
                <td class="px-4 py-3">
                  <code class="text-sm font-mono text-gray-900 bg-gray-100 px-2 py-1 rounded">
                    {{ permission.name }}
                  </code>
                </td>
                <td class="px-4 py-3">
                  <span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded-full font-medium">
                    {{ permission.action }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="text-sm text-gray-900">
                    {{ permission.display_name || permission.description || '-' }}
                  </div>
                </td>
                <td class="px-4 py-3 text-center">
                  <span
                    :class="[
                      'px-2 py-1 text-xs rounded-full font-medium',
                      permission.is_active
                        ? 'bg-green-100 text-green-800'
                        : 'bg-red-100 text-red-800'
                    ]"
                  >
                    {{ permission.is_active ? t('common.active') : t('common.inactive') }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex justify-center gap-2">
                    <button
                      @click="editPermission(permission)"
                      class="p-1.5 text-green-600 hover:bg-green-50 rounded-lg transition"
                      :title="t('common.edit')"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button
                      @click="deletePermission(permission)"
                      class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition"
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

      <!-- Empty State -->
      <div v-if="Object.keys(groupedPermissions).length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
        <p class="mt-2 text-sm text-gray-600">{{ t('common.no_data') }}</p>
      </div>
    </div>

    <!-- Permission Modal -->
    <PermissionModal
      :show="showCreateModal || showEditModal"
      :permission="selectedPermission"
      :is-edit="showEditModal"
      :modules="modules"
      @close="closeModal"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';
import PermissionModal from './PermissionModal.vue';

const { t } = useI18n();
const swal = useSwal();

const permissions = ref([]);
const modules = ref([]);
const loading = ref(false);
const selectedModule = ref('');
const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedPermission = ref(null);

const groupedPermissions = computed(() => {
  const filtered = selectedModule.value
    ? permissions.value.filter(p => p.module === selectedModule.value)
    : permissions.value;

  const grouped = {};
  filtered.forEach(permission => {
    if (!grouped[permission.module]) {
      grouped[permission.module] = [];
    }
    grouped[permission.module].push(permission);
  });
  return grouped;
});

const loadPermissions = async () => {
  loading.value = true;
  try {
    const response = await api.get('/api/permissions');
    console.log('Permissions response:', response.data);
    if (response.data.success) {
      permissions.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load permissions:', error);
    swal.error('Có lỗi xảy ra khi tải danh sách quyền');
  } finally {
    loading.value = false;
  }
};

const loadModules = async () => {
  try {
    console.log('📡 Fetching modules...');
    const response = await api.get('/api/permissions/modules');
    console.log('📡 Modules response:', response.data);
    if (response.data.success) {
      modules.value = response.data.data;
      console.log('✅ Loaded modules:', modules.value);
    }
  } catch (error) {
    console.error('❌ Failed to load modules:', error);
    // Fallback: extract modules from permissions
    if (permissions.value.length > 0) {
      const uniqueModules = [...new Set(permissions.value.map(p => p.module))];
      modules.value = uniqueModules;
      console.log('⚠️ Using fallback modules from permissions:', modules.value);
    }
  }
};

const filterByModule = () => {
  // Filtering is handled by computed property
};

const editPermission = (permission) => {
  selectedPermission.value = permission;
  showEditModal.value = true;
};

const deletePermission = async (permission) => {
  const result = await swal.confirmDelete(
    `Bạn có chắc chắn muốn xóa quyền "${permission.name}"?`
  );
  
  if (!result.isConfirmed) return;

  try {
    const response = await api.delete(`/api/permissions/${permission.id}`);
    if (response.data.success) {
      swal.success(response.data.message);
      loadPermissions();
    }
  } catch (error) {
    console.error('Failed to delete permission:', error);
    swal.error(error.response?.data?.message || 'Có lỗi xảy ra khi xóa quyền');
  }
};

const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  selectedPermission.value = null;
};

const handleSaved = () => {
  closeModal();
  loadModules();
  loadPermissions();
};

onMounted(async () => {
  // Load permissions first, then extract modules
  await loadPermissions();
  await loadModules();
});
</script>

