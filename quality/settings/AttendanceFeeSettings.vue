<template>
  <div>
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">{{ t('attendance_fee.policies') }}</h2>
      </div>
      <button
        @click="showModal = true; editingPolicy = null"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ t('attendance_fee.create_policy') }}
      </button>
    </div>

    <!-- Policies List -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="policies.length === 0" class="text-center py-12 bg-gray-50 rounded-lg">
      <p class="text-gray-500">{{ t('attendance_fee.no_policies') }}</p>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="policy in policies"
        :key="policy.id"
        class="bg-white border rounded-lg p-6 hover:shadow-md transition-shadow"
        :class="{ 'border-blue-500 border-2': policy.is_active }"
      >
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
              <h3 class="text-lg font-semibold text-gray-900">{{ policy.name }}</h3>
              <span
                v-if="policy.is_active"
                class="px-3 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full"
              >
                {{ t('attendance_fee.is_active') }}
              </span>
            </div>
            <p v-if="policy.description" class="text-gray-600 text-sm mb-4">{{ policy.description }}</p>

            <div class="grid grid-cols-3 gap-6 mt-4">
              <!-- Unexcused Absence -->
              <div class="bg-red-50 p-4 rounded-lg">
                <h4 class="text-sm font-semibold text-red-900 mb-2">{{ t('attendance_fee.unexcused_absence') }}</h4>
                <div class="text-2xl font-bold text-red-600">{{ policy.absence_unexcused_percent }}%</div>
                <p class="text-xs text-red-700 mt-1">
                  {{ t('attendance_fee.absence_consecutive_threshold') }}: {{ policy.absence_consecutive_threshold }}
                </p>
              </div>

              <!-- Excused Absence -->
              <div class="bg-orange-50 p-4 rounded-lg">
                <h4 class="text-sm font-semibold text-orange-900 mb-2">{{ t('attendance_fee.excused_absence') }}</h4>
                <div class="text-2xl font-bold text-orange-600">{{ policy.absence_excused_percent }}%</div>
                <p class="text-xs text-orange-700 mt-1">
                  {{ t('attendance_fee.absence_excused_free_limit') }}: {{ policy.absence_excused_free_limit }}/{{ t('common.month') }}
                </p>
              </div>

              <!-- Late -->
              <div class="bg-yellow-50 p-4 rounded-lg">
                <h4 class="text-sm font-semibold text-yellow-900 mb-2">{{ t('attendance_fee.late') }}</h4>
                <div class="text-2xl font-bold text-yellow-600">{{ policy.late_deduct_percent }}%</div>
                <p class="text-xs text-yellow-700 mt-1">
                  {{ t('attendance_fee.late_grace_minutes') }}: {{ policy.late_grace_minutes }} {{ t('common.minutes') }}
                </p>
              </div>
            </div>
          </div>

          <div class="flex gap-2 ml-4">
            <button
              v-if="!policy.is_active"
              @click="activatePolicy(policy)"
              class="px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 text-sm"
            >
              {{ t('attendance_fee.activate') }}
            </button>
            <button
              @click="editPolicy(policy)"
              class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 text-sm"
            >
              {{ t('common.edit') }}
            </button>
            <button
              v-if="!policy.is_active"
              @click="deletePolicy(policy)"
              class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 text-sm"
            >
              {{ t('common.delete') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <PolicyModal
      v-if="showModal"
      :policy="editingPolicy"
      @close="showModal = false"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import axios from 'axios';
import Swal from 'sweetalert2';
import PolicyModal from './PolicyModal.vue';

const { t } = useI18n();
const loading = ref(false);
const policies = ref([]);
const showModal = ref(false);
const editingPolicy = ref(null);

const loadPolicies = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/quality/attendance-fee-policies');
    console.log('📦 Policies response:', response.data);
    policies.value = response.data.data || [];
  } catch (error) {
    console.error('❌ Error loading policies:', error);
    policies.value = [];
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || t('common.error_occurred')
    });
  } finally {
    loading.value = false;
  }
};

const editPolicy = (policy) => {
  editingPolicy.value = policy;
  showModal.value = true;
};

const activatePolicy = async (policy) => {
  try {
    await axios.post(`/api/quality/attendance-fee-policies/${policy.id}/activate`);
    await Swal.fire({
      icon: 'success',
      title: t('common.success'),
      text: t('attendance_fee.policy_activated'),
      timer: 1500,
      showConfirmButton: false
    });
    await loadPolicies();
  } catch (error) {
    console.error('Error activating policy:', error);
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || t('common.error_occurred')
    });
  }
};

const deletePolicy = async (policy) => {
  const result = await Swal.fire({
    icon: 'warning',
    title: t('attendance_fee.confirm_delete'),
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#6c757d',
    confirmButtonText: t('common.delete'),
    cancelButtonText: t('common.cancel')
  });

  if (result.isConfirmed) {
    try {
      await axios.delete(`/api/quality/attendance-fee-policies/${policy.id}`);
      await Swal.fire({
        icon: 'success',
        title: t('common.success'),
        text: t('attendance_fee.policy_deleted'),
        timer: 1500,
        showConfirmButton: false
      });
      await loadPolicies();
    } catch (error) {
      console.error('Error deleting policy:', error);
      Swal.fire({
        icon: 'error',
        title: t('common.error'),
        text: error.response?.data?.message || t('attendance_fee.cannot_delete_active')
      });
    }
  }
};

const handleSaved = () => {
  showModal.value = false;
  editingPolicy.value = null;
  loadPolicies();
};

onMounted(() => {
  loadPolicies();
});
</script>

