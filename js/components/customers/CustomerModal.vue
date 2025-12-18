<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 p-4"
    @click.self="close"
  >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto scale-85-modal">
      <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">
          {{ isEdit ? t('customers.edit') : t('customers.create') }}
        </h2>
        <button @click="close" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form @submit.prevent="saveCustomer" class="p-6 space-y-6">
        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ t('customers.name') }} <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.name"
            type="text"
            :placeholder="t('customers.name_placeholder')"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            required
          />
        </div>

        <!-- Phone & Email -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.phone') }}
            </label>
            <input
              v-model="form.phone"
              type="tel"
              :placeholder="t('customers.phone_placeholder')"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.email') }}
            </label>
            <input
              v-model="form.email"
              type="email"
              :placeholder="t('customers.email_placeholder')"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>

        <!-- Date of Birth & Gender -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.date_of_birth') }}
            </label>
            <input
              v-model="form.date_of_birth"
              type="date"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.gender') }}
            </label>
            <select
              v-model="form.gender"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="">{{ t('common.select') }}</option>
              <option value="male">{{ t('customers.gender_male') }}</option>
              <option value="female">{{ t('customers.gender_female') }}</option>
              <option value="other">{{ t('customers.gender_other') }}</option>
            </select>
          </div>
        </div>

        <!-- Address -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ t('customers.address') }}
          </label>
          <input
            v-model="form.address"
            type="text"
            :placeholder="t('customers.address_placeholder')"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- City, District, Ward -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.city') }}
            </label>
            <input
              v-model="form.city"
              type="text"
              :placeholder="t('customers.city_placeholder')"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.district') }}
            </label>
            <input
              v-model="form.district"
              type="text"
              :placeholder="t('customers.district_placeholder')"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.ward') }}
            </label>
            <input
              v-model="form.ward"
              type="text"
              :placeholder="t('customers.ward_placeholder')"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>

        <!-- Branch Selection - LOGIC QUAN TRỌNG -->
        <div v-if="authStore.isSuperAdmin">
          <!-- Super-admin: PHẢI chọn branch -->
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ t('customers.branch') }} <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.branch_id"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            required
          >
            <option value="">{{ t('customers.branch_placeholder') }}</option>
            <option v-for="branch in branches" :key="branch.id" :value="branch.id">
              {{ branch.name }}
            </option>
          </select>
        </div>
        <div v-else>
          <!-- User thường: Hiển thị branch (read-only) -->
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ t('customers.branch') }}
          </label>
          <input
            :value="primaryBranch?.name || t('customers.no_branch_error')"
            type="text"
            class="w-full px-4 py-2 border rounded-lg bg-gray-100 cursor-not-allowed"
            disabled
          />
          <p class="text-xs text-gray-500 mt-1">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ t('customers.branch_auto') }}
          </p>
        </div>

        <!-- Source & Assigned To -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.source') }}
            </label>
            <select
              v-model="form.source"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="">{{ t('common.select') }}</option>
              <option v-for="source in sources" :key="source.id" :value="source.name">
                {{ source.name }}
              </option>
            </select>
          </div>
          <!-- Assigned To - Tạm thời bỏ trống (chưa có module nhân sự) -->
          <!-- <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.assigned_to') }}
            </label>
            <select
              v-model="form.assigned_to"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="">{{ t('customers.assigned_to_placeholder') }}</option>
            </select>
          </div> -->
        </div>

        <!-- Estimated Value & Expected Close Date -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.estimated_value') }}
            </label>
            <input
              v-model="form.estimated_value"
              type="number"
              step="1000"
              min="0"
              :placeholder="t('customers.estimated_value_placeholder')"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.expected_close_date') }}
            </label>
            <input
              v-model="form.expected_close_date"
              type="date"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ t('customers.notes') }}
          </label>
          <textarea
            v-model="form.notes"
            rows="3"
            :placeholder="t('customers.notes_placeholder')"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          ></textarea>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3 pt-4 border-t">
          <button
            type="button"
            @click="close"
            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
          >
            {{ t('common.cancel') }}
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
          >
            <span v-if="loading">
              <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </span>
            <span>{{ loading ? t('common.saving') : t('common.save') }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useI18n } from '../../composables/useI18n';
import { useSwal } from '../../composables/useSwal';
import api from '../../services/api';

const props = defineProps({
  show: Boolean,
  customer: Object,
  isEdit: Boolean,
});

const emit = defineEmits(['close', 'saved']);
const authStore = useAuthStore();
const { t } = useI18n();
const swal = useSwal();

const form = ref({
  name: '',
  phone: '',
  email: '',
  date_of_birth: '',
  gender: '',
  address: '',
  city: '',
  district: '',
  ward: '',
  branch_id: '',
  source: '',
  assigned_to: '',
  notes: '',
  estimated_value: '',
  expected_close_date: '',
});

const branches = ref([]);
const sources = ref([]);
// const users = ref([]); // Tạm thời bỏ - chưa có module nhân sự
const loading = ref(false);

// Get primary branch của user
const primaryBranch = computed(() => {
  if (!authStore.currentUser) return null;
  const userBranches = authStore.currentUser.branches;
  if (!userBranches || userBranches.length === 0) return null;
  return userBranches.find(b => b.pivot?.is_primary) || userBranches[0];
});

const loadBranches = async () => {
  try {
    const response = await api.get('/api/branches/list');
    if (response.data.success) {
      branches.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load branches:', error);
  }
};

const loadSources = async () => {
  try {
    const response = await api.get('/api/customers/settings/sources');
    if (response.data.success) {
      sources.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load sources:', error);
  }
};

// const loadUsers = async () => {
//   try {
//     const response = await api.get('/api/users/list');
//     if (response.data.success) {
//       users.value = response.data.data;
//     }
//   } catch (error) {
//     console.error('Failed to load users:', error);
//   }
// };

const saveCustomer = async () => {
  // Validate primary branch for non-super-admin
  if (!authStore.isSuperAdmin && !primaryBranch.value) {
    swal.error(t('customers.no_branch_error'));
    return;
  }

  loading.value = true;
  try {
    // Nếu không phải super-admin, không gửi branch_id (backend tự động lấy)
    const payload = { ...form.value };
    
    // Convert empty strings to null
    Object.keys(payload).forEach(key => {
      if (payload[key] === '') {
        payload[key] = null;
      }
    });

    if (!authStore.isSuperAdmin) {
      delete payload.branch_id;
    }

    let response;
    if (props.isEdit) {
      response = await api.put(`/api/customers/${props.customer.id}`, payload);
    } else {
      response = await api.post('/api/customers', payload);
    }

    if (response.data.success) {
      swal.success(response.data.message);
      emit('saved');
    }
  } catch (error) {
    console.error('Save error:', error);
    const errorMessage = error.response?.data?.message || t('common.error_occurred');
    swal.error(errorMessage);
  } finally {
    loading.value = false;
  }
};

const close = () => {
  emit('close');
};

watch(() => props.show, (newVal) => {
  if (newVal) {
    if (props.isEdit && props.customer) {
      // Edit mode: Load customer data
      form.value = {
        name: props.customer.name || '',
        phone: props.customer.phone || '',
        email: props.customer.email || '',
        date_of_birth: props.customer.date_of_birth || '',
        gender: props.customer.gender || '',
        address: props.customer.address || '',
        city: props.customer.city || '',
        district: props.customer.district || '',
        ward: props.customer.ward || '',
        branch_id: props.customer.branch_id || '',
        source: props.customer.source || '',
        assigned_to: props.customer.assigned_to || '',
        notes: props.customer.notes || '',
        estimated_value: props.customer.estimated_value || '',
        expected_close_date: props.customer.expected_close_date || '',
      };
    } else {
      // Create mode: Reset form
      form.value = {
        name: '',
        phone: '',
        email: '',
        date_of_birth: '',
        gender: '',
        address: '',
        city: '',
        district: '',
        ward: '',
        branch_id: '',
        source: '',
        assigned_to: '',
        notes: '',
        estimated_value: '',
        expected_close_date: '',
      };
    }
  }
});

onMounted(() => {
  if (authStore.isSuperAdmin) {
    loadBranches();
  }
  loadSources();
  // loadUsers(); // Tạm thời bỏ - chưa có module nhân sự
});
</script>

<style scoped>
.scale-85-modal {
  transform: scale(0.85);
  transform-origin: center;
}
</style>