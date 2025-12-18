<template>
  <Transition name="slide-fade">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex justify-end bg-gray-900 bg-opacity-50"
      @click.self="close"
    >
      <div
        class="relative w-full max-w-4xl bg-white shadow-xl flex flex-col overflow-hidden"
        @click.stop
      >
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <div>
            <h2 class="text-xl font-semibold text-gray-800">
              {{ t('roles.manage_permissions') }}: {{ role.display_name || role.name }}
            </h2>
            <p class="text-sm text-gray-600 mt-1">
              {{ t('roles.manage_permissions_desc') }}
            </p>
          </div>
          <button
            @click="close"
            class="p-2 rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Stats -->
        <div class="px-6 py-3 bg-purple-50 border-b border-purple-100">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
              <span class="font-semibold">{{ selectedPermissions.length }}</span> / {{ allPermissions.length }} {{ t('roles.permissions_selected') }}
            </div>
            <div class="flex items-center space-x-2">
              <button
                @click="selectAll"
                class="px-3 py-1 text-xs font-medium text-purple-700 bg-purple-100 rounded-lg hover:bg-purple-200 transition"
              >
                {{ t('common.select_all') }}
              </button>
              <button
                @click="deselectAll"
                class="px-3 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition"
              >
                {{ t('common.deselect_all') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex-1 flex items-center justify-center">
          <div class="text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
            <p class="text-gray-600 mt-2">{{ t('common.loading') }}</p>
          </div>
        </div>

        <!-- Permissions by Module -->
        <div v-else class="flex-1 overflow-y-auto p-6">
          <div v-for="(perms, module) in groupedPermissions" :key="module" class="mb-6">
            <!-- Module Header -->
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-3">
                <h3 class="text-lg font-semibold text-gray-900">
                  <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">{{ module }}</span>
                </h3>
                <span class="text-sm text-gray-500">
                  ({{ perms.filter(p => selectedPermissions.includes(p.id)).length }}/{{ perms.length }})
                </span>
              </div>
              <div class="flex items-center space-x-2">
                <button
                  @click="selectModule(module)"
                  class="px-2 py-1 text-xs text-blue-700 hover:bg-blue-50 rounded transition"
                >
                  {{ t('common.select_all') }}
                </button>
                <button
                  @click="deselectModule(module)"
                  class="px-2 py-1 text-xs text-gray-700 hover:bg-gray-50 rounded transition"
                >
                  {{ t('common.deselect_all') }}
                </button>
              </div>
            </div>

            <!-- Permissions Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div
                v-for="permission in perms"
                :key="permission.id"
                @click="togglePermission(permission.id)"
                class="flex items-center p-3 border rounded-lg cursor-pointer transition"
                :class="[
                  selectedPermissions.includes(permission.id)
                    ? 'border-purple-300 bg-purple-50'
                    : 'border-gray-200 hover:border-purple-200 hover:bg-gray-50'
                ]"
              >
                <input
                  type="checkbox"
                  :checked="selectedPermissions.includes(permission.id)"
                  @click.stop
                  @change="togglePermission(permission.id)"
                  class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                />
                <div class="ml-3 flex-1">
                  <div class="flex items-center gap-2">
                    <code class="text-sm font-mono text-gray-900">{{ permission.name }}</code>
                    <span
                      class="px-2 py-0.5 text-xs bg-purple-100 text-purple-800 rounded-full font-medium"
                    >
                      {{ permission.action }}
                    </span>
                  </div>
                  <p v-if="permission.display_name" class="text-xs text-gray-600 mt-1">
                    {{ permission.display_name }}
                  </p>
                </div>
              </div>
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

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-between items-center">
          <div class="text-sm text-gray-600">
            {{ t('roles.changes_not_saved') }}
          </div>
          <div class="flex space-x-3">
            <button
              @click="close"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
            >
              {{ t('common.cancel') }}
            </button>
            <button
              @click="savePermissions"
              :disabled="saving"
              class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 transition disabled:opacity-50 flex items-center gap-2"
            >
              <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ saving ? t('common.saving') : t('common.save_changes') }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import api from '../../services/api';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  role: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['close', 'saved']);

const { t } = useI18n();
const swal = useSwal();

const allPermissions = ref([]);
const selectedPermissions = ref([]);
const loading = ref(false);
const saving = ref(false);

const groupedPermissions = computed(() => {
  const grouped = {};
  allPermissions.value.forEach(permission => {
    if (!grouped[permission.module]) {
      grouped[permission.module] = [];
    }
    grouped[permission.module].push(permission);
  });
  return grouped;
});

const loadPermissions = async () => {
  console.log('🔄 Loading permissions for role:', props.role);
  loading.value = true;
  try {
    // Load all permissions
    console.log('📡 Fetching all permissions...');
    const permissionsResponse = await api.get('/api/permissions');
    console.log('📡 All permissions response:', permissionsResponse.data);
    if (permissionsResponse.data.success) {
      allPermissions.value = permissionsResponse.data.data;
      console.log('✅ Loaded', allPermissions.value.length, 'permissions');
    }

    // Load role's current permissions
    console.log('📡 Fetching role permissions for role ID:', props.role.id);
    const rolePermissionsResponse = await api.get(`/api/roles/${props.role.id}/permissions`);
    console.log('📡 Role permissions response:', rolePermissionsResponse.data);
    if (rolePermissionsResponse.data.success) {
      selectedPermissions.value = rolePermissionsResponse.data.data.map(p => p.id);
      console.log('✅ Selected permissions:', selectedPermissions.value);
    }
  } catch (error) {
    console.error('❌ Failed to load permissions:', error);
    swal.error('Có lỗi xảy ra khi tải danh sách quyền');
  } finally {
    loading.value = false;
  }
};

const togglePermission = (permissionId) => {
  const index = selectedPermissions.value.indexOf(permissionId);
  if (index > -1) {
    selectedPermissions.value.splice(index, 1);
  } else {
    selectedPermissions.value.push(permissionId);
  }
};

const selectAll = () => {
  selectedPermissions.value = allPermissions.value.map(p => p.id);
};

const deselectAll = () => {
  selectedPermissions.value = [];
};

const selectModule = (module) => {
  const modulePermissions = groupedPermissions.value[module];
  modulePermissions.forEach(permission => {
    if (!selectedPermissions.value.includes(permission.id)) {
      selectedPermissions.value.push(permission.id);
    }
  });
};

const deselectModule = (module) => {
  const modulePermissions = groupedPermissions.value[module];
  const modulePermissionIds = modulePermissions.map(p => p.id);
  selectedPermissions.value = selectedPermissions.value.filter(
    id => !modulePermissionIds.includes(id)
  );
};

const savePermissions = async () => {
  saving.value = true;
  try {
    const response = await api.post(`/api/roles/${props.role.id}/permissions`, {
      permission_ids: selectedPermissions.value,
    });

    if (response.data.success) {
      swal.success(response.data.message);
      emit('saved');
    }
  } catch (error) {
    console.error('Failed to save permissions:', error);
    swal.error(error.response?.data?.message || 'Có lỗi xảy ra khi lưu quyền');
  } finally {
    saving.value = false;
  }
};

const close = () => {
  emit('close');
};

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      loadPermissions();
    }
  },
  { immediate: true }
);
</script>