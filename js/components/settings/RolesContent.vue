<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">{{ t('roles.title') }}</h2>
          <p class="text-sm text-gray-600 mt-1">{{ t('roles.description') }}</p>
        </div>
        <button
          @click="showCreateModal = true"
          class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          {{ t('roles.create') }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="p-8 text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
      <p class="text-gray-600 mt-2">{{ t('common.loading') }}</p>
    </div>

    <!-- Roles Grid -->
    <div v-else class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="role in roles"
          :key="role.id"
          class="border border-gray-200 rounded-lg p-5 hover:shadow-md transition-shadow"
        >
          <!-- Role Header -->
          <div class="flex items-start justify-between mb-3">
            <div>
              <h3 class="font-semibold text-gray-900 text-lg">{{ role.display_name || role.name }}</h3>
              <span class="text-xs font-mono bg-gray-100 text-gray-600 px-2 py-1 rounded mt-1 inline-block">
                {{ role.name }}
              </span>
            </div>
            <span
              :class="[
                'px-2 py-1 text-xs rounded-full font-medium',
                role.is_active
                  ? 'bg-green-100 text-green-800'
                  : 'bg-red-100 text-red-800'
              ]"
            >
              {{ role.is_active ? t('common.active') : t('common.inactive') }}
            </span>
          </div>

          <!-- Role Description -->
          <p v-if="role.description" class="text-sm text-gray-600 mb-3 line-clamp-2">
            {{ role.description }}
          </p>

          <!-- Role Stats -->
          <div class="mb-3 p-3 bg-gray-50 rounded-lg">
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-600">{{ t('roles.permissions_count') }}:</span>
              <span class="font-semibold text-purple-600">{{ role.permissions_count || 0 }}</span>
            </div>
            <div class="flex justify-between items-center text-sm mt-1">
              <span class="text-gray-600">{{ t('roles.users_count') }}:</span>
              <span class="font-semibold text-purple-600">{{ role.users_count || 0 }}</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-between pt-3 border-t border-gray-200">
            <button
              @click="viewPermissions(role)"
              class="px-3 py-1.5 text-sm bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center gap-1"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              <span>{{ t('roles.permissions') }}</span>
            </button>
            <div class="flex space-x-2">
              <button
                @click="editRole(role)"
                class="p-1.5 text-green-600 hover:bg-green-50 rounded-lg transition"
                :title="t('common.edit')"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button
                v-if="!['super-admin', 'admin'].includes(role.name)"
                @click="deleteRole(role)"
                class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition"
                :title="t('common.delete')"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="roles.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <p class="mt-2 text-sm text-gray-600">{{ t('common.no_data') }}</p>
      </div>
    </div>

    <!-- Role Modal -->
    <RoleModal
      :show="showCreateModal || showEditModal"
      :role="selectedRole"
      :is-edit="showEditModal"
      @close="closeModal"
      @saved="handleSaved"
    />

    <!-- Role Permissions Modal -->
    <RolePermissionsModal
      v-if="showPermissionsModal && selectedRole"
      :show="showPermissionsModal"
      :role="selectedRole"
      @close="closePermissionsModal"
      @saved="handlePermissionsSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../services/api';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';
import RoleModal from './RoleModal.vue';
import RolePermissionsModal from './RolePermissionsModal.vue';

const { t } = useI18n();
const swal = useSwal();

const roles = ref([]);
const loading = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showPermissionsModal = ref(false);
const selectedRole = ref(null);

const loadRoles = async () => {
  loading.value = true;
  try {
    const response = await api.get('/api/roles');
    console.log('Roles response:', response.data);
    if (response.data.success) {
      roles.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load roles:', error);
    swal.error('Có lỗi xảy ra khi tải danh sách vai trò');
  } finally {
    loading.value = false;
  }
};

const editRole = (role) => {
  selectedRole.value = role;
  showEditModal.value = true;
};

const deleteRole = async (role) => {
  const result = await swal.confirmDelete(
    `Bạn có chắc chắn muốn xóa vai trò "${role.display_name || role.name}"?`
  );
  
  if (!result.isConfirmed) return;

  try {
    const response = await api.delete(`/api/roles/${role.id}`);
    if (response.data.success) {
      swal.success(response.data.message);
      loadRoles();
    }
  } catch (error) {
    console.error('Failed to delete role:', error);
    swal.error(error.response?.data?.message || 'Có lỗi xảy ra khi xóa vai trò');
  }
};

const viewPermissions = (role) => {
  selectedRole.value = role;
  showPermissionsModal.value = true;
};

const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  selectedRole.value = null;
};

const closePermissionsModal = () => {
  showPermissionsModal.value = false;
  selectedRole.value = null;
};

const handleSaved = () => {
  closeModal();
  loadRoles();
};

const handlePermissionsSaved = () => {
  closePermissionsModal();
  loadRoles();
};

onMounted(() => {
  loadRoles();
});
</script>

