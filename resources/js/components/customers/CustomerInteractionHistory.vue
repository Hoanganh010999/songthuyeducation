<template>
  <div>
    <!-- Add Interaction Button -->
    <button
      v-if="!embedded"
      @click="openAddModal"
      class="w-full mb-6 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center justify-center gap-2"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
      </svg>
      {{ t('customers.add_interaction') }}
    </button>

    <!-- Interactions Timeline -->
    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="text-gray-600 mt-2">{{ t('common.loading') }}</p>
    </div>

    <div v-else-if="interactions.length === 0" class="text-center py-12">
      <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="text-gray-600 mt-4">{{ t('customers.no_interactions') }}</p>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="interaction in interactions"
        :key="interaction.id"
        class="border rounded-lg p-4 hover:shadow-md transition"
      >
        <!-- Header -->
        <div class="flex items-start justify-between mb-3">
          <div class="flex items-center gap-3">
            <div
              :style="{ backgroundColor: interaction.interaction_type?.color || '#3B82F6' }"
              class="w-10 h-10 rounded-lg flex items-center justify-center text-white text-lg"
            >
              {{ getIconEmoji(interaction.interaction_type?.icon) }}
            </div>
            <div>
              <h4 class="font-semibold text-gray-900">{{ interaction.interaction_type?.name }}</h4>
              <p class="text-sm text-gray-500">
                {{ formatDate(interaction.interaction_date) }} · {{ interaction.user?.name }}
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span
              :style="{ 
                backgroundColor: interaction.interaction_result?.color + '20',
                color: interaction.interaction_result?.color 
              }"
              class="px-3 py-1 rounded-full text-sm font-medium"
            >
              {{ interaction.interaction_result?.name }}
            </span>
            <button
              v-if="authStore.hasPermission('customers.delete')"
              @click="deleteInteraction(interaction)"
              class="text-red-600 hover:text-red-800"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Notes -->
        <div class="bg-gray-50 rounded-lg p-3 mb-3">
          <p class="text-gray-700 whitespace-pre-wrap">{{ interaction.notes }}</p>
        </div>

        <!-- Next Follow Up -->
        <div v-if="interaction.next_follow_up" class="flex items-center gap-2 text-sm text-blue-600">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          {{ t('customers.next_follow_up') }}: {{ formatDate(interaction.next_follow_up) }}
        </div>
      </div>
    </div>

    <!-- Add Interaction Modal -->
    <CustomerInteractionFormModal
      v-if="!embedded"
      :show="showFormModal"
      :customer="customer"
      @close="closeFormModal"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import { useAuthStore } from '../../stores/auth';
import { useSwal } from '../../composables/useSwal';
import api from '../../services/api';
import CustomerInteractionFormModal from './CustomerInteractionFormModal.vue';

const props = defineProps({
  customer: {
    type: Object,
    required: true,
  },
  embedded: {
    type: Boolean,
    default: false,
  },
});

const { t } = useI18n();
const authStore = useAuthStore();
const swal = useSwal();

const interactions = ref([]);
const loading = ref(false);
const showFormModal = ref(false);

const iconEmojiMap = {
  phone: '📞',
  envelope: '✉️',
  message: '💬',
  users: '👥',
  comment: '💬',
  facebook: '📘',
  store: '🏪',
  'check-circle': '✅',
  'phone-slash': '📵',
  calendar: '📅',
  'times-circle': '❌',
  clock: '⏰',
  'info-circle': 'ℹ️',
  ban: '🚫',
  google: '🔍',
  'user-friends': '👫',
  walking: '🚶',
  globe: '🌐',
  'calendar-star': '🎉',
  'ellipsis-h': '⋯',
};

const getIconEmoji = (icon) => {
  return iconEmojiMap[icon] || '📌';
};

const formatDate = (date) => {
  if (!date) return '';
  // Parse ISO string và hiển thị theo local timezone
  const d = new Date(date);
  return d.toLocaleString('vi-VN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  });
};

const loadInteractions = async () => {
  if (!props.customer?.id) return;
  
  loading.value = true;
  try {
    const response = await api.get(`/api/customers/${props.customer.id}/interactions`);
    if (response.data.success) {
      interactions.value = response.data.data.data || response.data.data;
    }
  } catch (error) {
    console.error('Failed to load interactions:', error);
    swal.error('Có lỗi xảy ra khi tải lịch sử tương tác');
  } finally {
    loading.value = false;
  }
};

const openAddModal = () => {
  showFormModal.value = true;
};

const closeFormModal = () => {
  showFormModal.value = false;
};

const handleSaved = () => {
  closeFormModal();
  loadInteractions();
};

const deleteInteraction = async (interaction) => {
  const result = await swal.confirmDelete(
    `Bạn có chắc chắn muốn xóa lịch sử tương tác này?`
  );
  
  if (!result.isConfirmed) return;

  try {
    const response = await api.delete(
      `/api/customers/${props.customer.id}/interactions/${interaction.id}`
    );
    if (response.data.success) {
      swal.success(response.data.message);
      loadInteractions();
    }
  } catch (error) {
    console.error('Failed to delete interaction:', error);
    swal.error(error.response?.data?.message || 'Có lỗi xảy ra khi xóa');
  }
};

// Load on mount
onMounted(() => {
  if (props.customer) {
    loadInteractions();
  }
});

// Watch customer changes
watch(() => props.customer, (newVal) => {
  if (newVal) {
    loadInteractions();
  }
}, { immediate: true });
</script>