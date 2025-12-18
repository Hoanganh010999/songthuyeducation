<template>
  <Transition name="modal">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 p-4"
      @click.self="close"
    >
      <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl max-h-[90vh] flex flex-col">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between rounded-t-lg">
          <div>
            <h2 class="text-xl font-bold text-gray-900">{{ customer?.name }}</h2>
            <p class="text-sm text-gray-600 mt-1">{{ customer?.phone }} • {{ customer?.email }}</p>
          </div>
          <button @click="close" class="text-gray-400 hover:text-gray-600 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="border-b">
          <nav class="flex px-6 -mb-px">
            <button @click="activeTab = 'info'" :class="['px-6 py-3 text-sm font-medium border-b-2 transition', activeTab === 'info' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300']">
              {{ t('customers.info_and_children') }}
            </button>
            <button @click="activeTab = 'interactions'" :class="['px-6 py-3 text-sm font-medium border-b-2 transition', activeTab === 'interactions' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300']">
              {{ t('customers.interaction_history') }}
            </button>
          </nav>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
          <div v-if="activeTab === 'info'">
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
              <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-900">{{ t('customers.basic_info') }}</h3>
                <div class="flex gap-2">
                <button v-if="authStore.hasPermission('calendar.create')" @click="scheduleTestForCustomer" class="px-3 py-1.5 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition text-sm flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                  </svg>
                  {{ t('customers.schedule_test') }}
                </button>
                  <button v-if="authStore.hasPermission('calendar.create')" @click="scheduleTrialForCustomer" class="px-3 py-1.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    {{ t('customers.trial_class') }}
                  </button>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-600">{{ t('customers.code') }}:</span><span class="ml-2 font-medium">{{ customer?.code }}</span></div>
                <div><span class="text-gray-600">{{ t('customers.branch') }}:</span><span class="ml-2 font-medium">{{ customer?.branch?.name }}</span></div>
                <div><span class="text-gray-600">{{ t('customers.stage') }}:</span><span class="ml-2 font-medium">{{ t(`customers.stage_${customer?.stage}`) }}</span></div>
                <div><span class="text-gray-600">{{ t('customers.source') }}:</span><span class="ml-2 font-medium">{{ customer?.source || '-' }}</span></div>
              </div>
            </div>

            <div>
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">{{ t('customers.children_list') }}</h3>
                <button v-if="authStore.hasPermission('customers.create')" @click="openChildModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  {{ t('customers.add_child') }}
                </button>
              </div>

              <div v-if="loading" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
              </div>

              <div v-else-if="children.length === 0" class="text-center py-12 bg-gray-50 rounded-lg">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <p class="text-gray-600 mt-4">{{ t('customers.no_children') }}</p>
              </div>

              <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="child in children" :key="child.id" class="border rounded-lg p-4 hover:shadow-md transition">
                  <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                      <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                        <span class="text-2xl">{{ child.gender === 'male' ? '👦' : child.gender === 'female' ? '👧' : '🧒' }}</span>
                      </div>
                      <div>
                        <h4 class="font-semibold text-gray-900">{{ child.name }}</h4>
                        <p class="text-sm text-gray-500">{{ child.age ? `${child.age} ${t('customers.age_suffix')}` : t('common.unknown') }}</p>
                      </div>
                    </div>
                    <div class="flex gap-2">
                      <button v-if="authStore.hasPermission('calendar.create')" @click="scheduleTestForChild(child)" class="text-cyan-600 hover:text-cyan-800" :title="t('customers.schedule_test')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                      </button>
                      <button v-if="authStore.hasPermission('calendar.create')" @click="scheduleTrialForChild(child)" class="text-teal-600 hover:text-teal-800" :title="t('customers.trial_class')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                      </button>
                      <button v-if="authStore.hasPermission('customers.edit')" @click="openChildModal(child)" class="text-blue-600 hover:text-blue-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                      <button v-if="authStore.hasPermission('customers.delete')" @click="deleteChild(child)" class="text-red-600 hover:text-red-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </div>
                  <div class="space-y-2 text-sm">
                    <div v-if="child.school"><span class="text-gray-600">🏫 {{ t('customers.school') }}:</span><span class="ml-2">{{ child.school }}</span></div>
                    <div v-if="child.grade"><span class="text-gray-600">📚 {{ t('customers.grade') }}:</span><span class="ml-2">{{ child.grade }}</span></div>
                    <div v-if="child.interests"><span class="text-gray-600">⭐ {{ t('customers.interests') }}:</span><span class="ml-2">{{ child.interests }}</span></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="activeTab === 'interactions'">
            <CustomerInteractionHistory :customer="customer" :embedded="false" />
          </div>
        </div>

        <div class="border-t px-6 py-4 flex justify-end">
          <button @click="close" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">{{ t('common.close') }}</button>
        </div>
      </div>
    </div>
  </Transition>

  <CustomerChildModal :show="showChildModal" :customer="customer" :child="selectedChild" @close="closeChildModal" @saved="handleChildSaved" />
  <TrialClassModal
    :show="showTrialModal"
    :trialable-type="trialType"
    :trialable-id="trialId"
    :trialable-name="trialName"
    @close="closeTrialModal"
    @registered="handleTrialRegistered"
  />
</template>

<script setup>
import { ref, watch } from 'vue';
import { useI18n } from '../../composables/useI18n';
import { useAuthStore } from '../../stores/auth';
import { useSwal } from '../../composables/useSwal';
import api from '../../services/api';
import CustomerInteractionHistory from './CustomerInteractionHistory.vue';
import CustomerChildModal from './CustomerChildModal.vue';
import TrialClassModal from './TrialClassModal.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  customer: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const { t } = useI18n();
const authStore = useAuthStore();
const swal = useSwal();

const activeTab = ref('info');
const loading = ref(false);
const children = ref([]);
const showChildModal = ref(false);
const selectedChild = ref(null);
const showTrialModal = ref(false);
const trialType = ref('customer');
const trialId = ref(null);
const trialName = ref('');

const loadChildren = async () => {
  if (!props.customer?.id) return;
  loading.value = true;
  try {
    const response = await api.get(`/api/customers/${props.customer.id}/children`);
    if (response.data.success) children.value = response.data.data;
  } catch (error) {
    console.error('Failed to load children:', error);
    swal.error(t('customers.error_load_children'));
  } finally {
    loading.value = false;
  }
};

const openChildModal = (child = null) => {
  selectedChild.value = child;
  showChildModal.value = true;
};

const closeChildModal = () => {
  showChildModal.value = false;
  selectedChild.value = null;
};

const handleChildSaved = () => {
  closeChildModal();
  loadChildren();
};

const deleteChild = async (child) => {
  const result = await swal.confirmDelete(t('customers.confirm_delete_child', { name: child.name }));
  if (!result.isConfirmed) return;
  try {
    const response = await api.delete(`/api/customers/${props.customer.id}/children/${child.id}`);
    if (response.data.success) {
      swal.success(response.data.message);
      loadChildren();
    }
  } catch (error) {
    console.error('Failed to delete child:', error);
    swal.error(error.response?.data?.message || t('customers.error_delete_child'));
  }
};

const scheduleTestForCustomer = async () => {
  const result = await swal.fire({
    title: t('calendar.schedule_placement_test_entrance'),
    html: `
      <div class="text-left space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">${t('calendar.test_date_required')}</label>
          <input type="datetime-local" id="test_date" class="swal2-input w-full" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">${t('calendar.duration_minutes')}</label>
          <input type="number" id="duration_minutes" class="swal2-input w-full" value="60" min="30" max="240">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">${t('calendar.location')}</label>
          <input type="text" id="location" class="swal2-input w-full" placeholder="${t('calendar.location_placeholder')}">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">${t('calendar.notes')}</label>
          <textarea id="notes" class="swal2-textarea w-full" placeholder="${t('calendar.notes_placeholder')}"></textarea>
        </div>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: t('calendar.schedule_placement_test'),
    cancelButtonText: t('common.cancel'),
    preConfirm: () => {
      const test_date = document.getElementById('test_date').value;
      const duration_minutes = document.getElementById('duration_minutes').value;
      const location = document.getElementById('location').value;
      const notes = document.getElementById('notes').value;

      if (!test_date) {
        swal.showValidationMessage(t('calendar.please_select_test_date'));
        return false;
      }

      return { test_date, duration_minutes, location, notes };
    }
  });

  if (result.isConfirmed) {
    try {
      // Convert datetime-local to ISO string
      const payload = {
        ...result.value,
        test_date: new Date(result.value.test_date).toISOString(),
      };

      const response = await api.post(`/api/calendar/placement-test/customer/${props.customer.id}`, payload);
      if (response.data.success) {
        swal.success(response.data.message);
      }
    } catch (error) {
      console.error('Failed to schedule test:', error);
      swal.error(error.response?.data?.message || t('common.error'));
    }
  }
};

const scheduleTrialForCustomer = () => {
  trialType.value = 'customer';
  trialId.value = props.customer.id;
  trialName.value = props.customer.name;
  showTrialModal.value = true;
};

const scheduleTrialForChild = (child) => {
  trialType.value = 'child';
  trialId.value = child.id;
  trialName.value = child.name;
  showTrialModal.value = true;
};

const closeTrialModal = () => {
  showTrialModal.value = false;
};

const handleTrialRegistered = () => {
  swal.success(t('customers.trial_registered_success'));
};

const scheduleTestForChild = async (child) => {
  const result = await swal.fire({
    title: t('calendar.schedule_test_for', { name: child.name }),
    html: `
      <div class="text-left space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">${t('calendar.test_date_required')}</label>
          <input type="datetime-local" id="test_date" class="swal2-input w-full" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">${t('calendar.duration_minutes')}</label>
          <input type="number" id="duration_minutes" class="swal2-input w-full" value="60" min="30" max="240">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">${t('calendar.location')}</label>
          <input type="text" id="location" class="swal2-input w-full" placeholder="${t('calendar.location_placeholder')}">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">${t('calendar.notes')}</label>
          <textarea id="notes" class="swal2-textarea w-full" placeholder="${t('calendar.notes_placeholder')}"></textarea>
        </div>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: t('calendar.schedule_placement_test'),
    cancelButtonText: t('common.cancel'),
    preConfirm: () => {
      const test_date = document.getElementById('test_date').value;
      const duration_minutes = document.getElementById('duration_minutes').value;
      const location = document.getElementById('location').value;
      const notes = document.getElementById('notes').value;

      if (!test_date) {
        swal.showValidationMessage(t('calendar.please_select_test_date'));
        return false;
      }

      return { test_date, duration_minutes, location, notes };
    }
  });

  if (result.isConfirmed) {
    try {
      // Convert datetime-local to ISO string
      const payload = {
        ...result.value,
        test_date: new Date(result.value.test_date).toISOString(),
      };

      const response = await api.post(`/api/calendar/placement-test/child/${child.id}`, payload);
      if (response.data.success) {
        swal.success(response.data.message);
        loadChildren();
      }
    } catch (error) {
      console.error('Failed to schedule test:', error);
      swal.error(error.response?.data?.message || t('common.error'));
    }
  }
};


const close = () => emit('close');

watch(() => props.show, (newVal) => {
  if (newVal && props.customer) {
    activeTab.value = 'info';
    loadChildren();
  }
});

watch(() => props.customer, (newVal) => {
  if (newVal && props.show) {
    loadChildren();
  }
});
</script>


