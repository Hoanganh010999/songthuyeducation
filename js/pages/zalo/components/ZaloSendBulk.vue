<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900">{{ t('zalo.send_bulk') }}</h1>
      <p class="mt-1 text-sm text-gray-600">{{ t('zalo.send_bulk_subtitle') }}</p>
    </div>

    <!-- Send Bulk Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <form @submit.prevent="sendBulk" class="space-y-6">
        <!-- Target Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-3">
            {{ t('zalo.select_recipients') }}
          </label>
          <div class="space-y-3">
            <!-- By Class -->
            <label class="flex items-center gap-3 p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer">
              <input
                v-model="form.targetType"
                type="radio"
                value="class"
                class="w-4 h-4 text-blue-600"
              />
              <div class="flex-1">
                <p class="font-medium text-gray-900">{{ t('zalo.by_class') }}</p>
                <p class="text-sm text-gray-600">{{ t('zalo.by_class_desc') }}</p>
              </div>
            </label>

            <!-- By Branch -->
            <label class="flex items-center gap-3 p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer">
              <input
                v-model="form.targetType"
                type="radio"
                value="branch"
                class="w-4 h-4 text-blue-600"
              />
              <div class="flex-1">
                <p class="font-medium text-gray-900">{{ t('zalo.by_branch') }}</p>
                <p class="text-sm text-gray-600">{{ t('zalo.by_branch_desc') }}</p>
              </div>
            </label>

            <!-- Manual Input -->
            <label class="flex items-center gap-3 p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer">
              <input
                v-model="form.targetType"
                type="radio"
                value="manual"
                class="w-4 h-4 text-blue-600"
              />
              <div class="flex-1">
                <p class="font-medium text-gray-900">{{ t('zalo.manual_input') }}</p>
                <p class="text-sm text-gray-600">{{ t('zalo.manual_input_desc') }}</p>
              </div>
            </label>
          </div>
        </div>

        <!-- Class Selector -->
        <div v-if="form.targetType === 'class'">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('zalo.select_class') }} <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.classId"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">{{ t('common.select') }}</option>
            <option v-for="cls in classes" :key="cls.id" :value="cls.id">
              {{ cls.name }}
            </option>
          </select>
        </div>

        <!-- Branch Selector -->
        <div v-if="form.targetType === 'branch'">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('zalo.select_branch') }} <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.branchId"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">{{ t('common.select') }}</option>
            <option v-for="branch in branches" :key="branch.id" :value="branch.id">
              {{ branch.name }}
            </option>
          </select>
        </div>

        <!-- Manual Phone Numbers -->
        <div v-if="form.targetType === 'manual'">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('zalo.phone_numbers') }} <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="form.phoneNumbers"
            rows="4"
            required
            :placeholder="t('zalo.phone_numbers_placeholder')"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          ></textarea>
          <p class="mt-1 text-xs text-gray-500">{{ t('zalo.phone_numbers_hint') }}</p>
        </div>

        <!-- Message -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ t('zalo.message') }} <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="form.message"
            rows="6"
            required
            :placeholder="t('zalo.bulk_message_placeholder')"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          ></textarea>
          <p class="mt-1 text-xs text-gray-500">
            {{ form.message.length }} / 1000 {{ t('zalo.characters') }}
          </p>
        </div>

        <!-- Preview Recipients -->
        <div v-if="estimatedRecipients > 0" class="p-4 bg-blue-50 rounded-lg">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-blue-800">
              <strong>{{ t('zalo.estimated_recipients') }}:</strong> {{ estimatedRecipients }} {{ t('zalo.people') }}
            </p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-3">
          <button
            type="submit"
            :disabled="sending || !canSend"
            class="inline-flex items-center gap-2 px-6 py-2.5 text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            <svg v-if="!sending" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
            <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ sending ? t('zalo.sending') : t('zalo.send_to_all') }}
          </button>

          <button
            type="button"
            @click="resetForm"
            class="px-6 py-2.5 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            {{ t('common.clear') }}
          </button>
        </div>
      </form>
    </div>

    <!-- Send Progress -->
    <div v-if="sendProgress.total > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ t('zalo.sending_progress') }}</h3>
      
      <div class="space-y-3">
        <!-- Progress Bar -->
        <div class="relative pt-1">
          <div class="flex mb-2 items-center justify-between">
            <div>
              <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-blue-600 bg-blue-200">
                {{ Math.round((sendProgress.sent / sendProgress.total) * 100) }}%
              </span>
            </div>
            <div class="text-right">
              <span class="text-xs font-semibold inline-block text-blue-600">
                {{ sendProgress.sent }} / {{ sendProgress.total }}
              </span>
            </div>
          </div>
          <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-blue-200">
            <div
              :style="{ width: (sendProgress.sent / sendProgress.total * 100) + '%' }"
              class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500 transition-all duration-500"
            ></div>
          </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4">
          <div class="text-center p-3 bg-green-50 rounded-lg">
            <p class="text-2xl font-bold text-green-600">{{ sendProgress.success }}</p>
            <p class="text-sm text-gray-600">{{ t('zalo.success') }}</p>
          </div>
          <div class="text-center p-3 bg-red-50 rounded-lg">
            <p class="text-2xl font-bold text-red-600">{{ sendProgress.failed }}</p>
            <p class="text-sm text-gray-600">{{ t('zalo.failed') }}</p>
          </div>
          <div class="text-center p-3 bg-gray-50 rounded-lg">
            <p class="text-2xl font-bold text-gray-600">{{ sendProgress.pending }}</p>
            <p class="text-sm text-gray-600">{{ t('zalo.pending') }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import { useSwal } from '../../../composables/useSwal';
import axios from 'axios';

const { t } = useI18n();
const { Swal } = useSwal();

const form = ref({
  targetType: 'class',
  classId: '',
  branchId: '',
  phoneNumbers: '',
  message: '',
});

const sending = ref(false);
const classes = ref([]);
const branches = ref([]);
const sendProgress = ref({
  total: 0,
  sent: 0,
  success: 0,
  failed: 0,
  pending: 0,
});

const estimatedRecipients = computed(() => {
  if (form.value.targetType === 'manual' && form.value.phoneNumbers) {
    return form.value.phoneNumbers.split(/[\n,;]/).filter(p => p.trim()).length;
  }
  return 0;
});

const canSend = computed(() => {
  if (!form.value.message) return false;
  
  if (form.value.targetType === 'class') return !!form.value.classId;
  if (form.value.targetType === 'branch') return !!form.value.branchId;
  if (form.value.targetType === 'manual') return !!form.value.phoneNumbers;
  
  return false;
});

const sendBulk = async () => {
  const confirmed = await Swal.fire({
    title: t('zalo.confirm_send'),
    text: t('zalo.confirm_send_bulk_message', { count: estimatedRecipients.value }),
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: t('common.confirm'),
    cancelButtonText: t('common.cancel'),
  });

  if (!confirmed.isConfirmed) return;

  sending.value = true;

  try {
    const response = await axios.post('/api/zalo/send-bulk', {
      targetType: form.value.targetType,
      classId: form.value.classId,
      branchId: form.value.branchId,
      phoneNumbers: form.value.phoneNumbers,
      message: form.value.message,
    });

    if (response.data.success) {
      sendProgress.value = {
        total: response.data.total || 0,
        sent: response.data.sent || 0,
        success: response.data.results?.length || 0,
        failed: response.data.errors?.length || 0,
        pending: 0,
      };

      Swal.fire({
        icon: 'success',
        title: t('common.success'),
        text: t('zalo.bulk_send_completed', { success: sendProgress.value.success, failed: sendProgress.value.failed }),
      });
    }
  } catch (error) {
    console.error('Bulk send error:', error);
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || error.message,
    });
  } finally {
    sending.value = false;
  }
};

const resetForm = () => {
  form.value = {
    targetType: 'class',
    classId: '',
    branchId: '',
    phoneNumbers: '',
    message: '',
  };
  sendProgress.value = {
    total: 0,
    sent: 0,
    success: 0,
    failed: 0,
    pending: 0,
  };
};

const loadClasses = async () => {
  try {
    const response = await axios.get('/api/classes');
    classes.value = response.data.data || [];
  } catch (error) {
    console.error('Failed to load classes:', error);
  }
};

const loadBranches = async () => {
  try {
    const response = await axios.get('/api/branches');
    branches.value = response.data.data || [];
  } catch (error) {
    console.error('Failed to load branches:', error);
  }
};

onMounted(() => {
  loadClasses();
  loadBranches();
});
</script>

