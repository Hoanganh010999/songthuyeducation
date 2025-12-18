<template>
  <div>
    <!-- Modal Header -->
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
      <div>
        <h3 class="text-xl font-bold text-gray-900">{{ t('teachers.settings_title') }}</h3>
        <p class="text-sm text-gray-600 mt-1">
          {{ t('teachers.settings_description') }}
        </p>
      </div>
      <button
        @click="$emit('close')"
        class="text-gray-400 hover:text-gray-600 transition"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Modal Content -->
    <div class="p-6">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-8">
        <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-600 mt-2">{{ t('teachers.loading') }}</p>
      </div>

      <!-- Position Selection -->
      <div v-else>
        <!-- Info Box -->
        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div class="flex items-start space-x-3">
            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm text-blue-800">
              <p class="font-medium mb-1">{{ t('teachers.guide') }}:</p>
              <ul class="list-disc list-inside space-y-1 text-blue-700">
                <li>{{ t('teachers.settings_hint_1') }}</li>
                <li>{{ t('teachers.settings_hint_2') }}</li>
                <li>{{ t('teachers.settings_hint_3') }}</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Positions Grid -->
        <div v-if="positions.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-96 overflow-y-auto">
          <div
            v-for="position in positions"
            :key="position.id"
            @click="togglePosition(position.code)"
            class="relative p-4 border rounded-lg cursor-pointer transition-all hover:shadow-md"
            :class="{
              'border-blue-500 bg-blue-50': selectedCodes.includes(position.code),
              'border-gray-300 bg-white hover:border-gray-400': !selectedCodes.includes(position.code)
            }"
          >
            <!-- Checkbox -->
            <div class="flex items-start space-x-3">
              <div class="flex items-center h-5 mt-0.5">
                <input
                  type="checkbox"
                  :checked="selectedCodes.includes(position.code)"
                  @click.stop="togglePosition(position.code)"
                  class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
              </div>
              <div class="flex-1 min-w-0">
                <div class="font-semibold text-gray-900">{{ position.name }}</div>
                <div class="flex items-center space-x-2 mt-1">
                  <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                    {{ position.code }}
                  </span>
                  <span class="text-xs text-gray-500">{{ t('teachers.level') }} {{ position.level }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-8 border border-gray-200 border-dashed rounded-lg">
          <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
          </svg>
          <p class="text-gray-600">{{ t('teachers.no_positions') }}</p>
          <p class="text-sm text-gray-500 mt-1">{{ t('teachers.add_position_code') }}</p>
        </div>

        <!-- Selected Summary -->
        <div v-if="selectedCodes.length > 0" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-green-900">
                {{ t('teachers.selected_codes') }} {{ selectedCodes.length }} {{ t('teachers.codes') }}
              </p>
              <p class="text-xs text-green-700 mt-1">
                {{ selectedCodes.join(', ') }}
              </p>
            </div>
            <button
              @click="selectedCodes = []"
              class="text-sm text-green-700 hover:text-green-900 underline"
            >
              {{ t('teachers.deselect_all') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Footer -->
    <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-end space-x-3">
      <button
        @click="$emit('close')"
        type="button"
        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
      >
        {{ t('teachers.cancel') }}
      </button>
      <button
        @click="saveSettings"
        :disabled="selectedCodes.length === 0 || saving"
        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
      >
        <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>{{ saving ? t('teachers.saving') : t('teachers.save_settings') }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useI18n } from '../../composables/useI18n';

const { t } = useI18n();
const emit = defineEmits(['close', 'saved']);

const loading = ref(false);
const saving = ref(false);
const positions = ref([]);
const selectedCodes = ref([]);

const loadPositions = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/quality/positions');
    positions.value = response.data.data || [];
  } catch (error) {
    console.error('Load positions error:', error);
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi!',
      text: error.response?.data?.message || 'Không thể tải danh sách vị trí',
      confirmButtonText: 'OK'
    });
  } finally {
    loading.value = false;
  }
};

const loadCurrentSettings = async () => {
  const branchId = localStorage.getItem('current_branch_id');
  if (!branchId) return;
  
  try {
    const response = await axios.get('/api/quality/teachers/settings', {
      params: { branch_id: branchId }
    });
    selectedCodes.value = response.data.data.position_codes || [];
  } catch (error) {
    console.error('Load settings error:', error);
  }
};

const togglePosition = (code) => {
  const index = selectedCodes.value.indexOf(code);
  if (index > -1) {
    selectedCodes.value.splice(index, 1);
  } else {
    selectedCodes.value.push(code);
  }
};

const saveSettings = async () => {
  if (selectedCodes.value.length === 0) {
    await Swal.fire({
      icon: 'warning',
      title: 'Chưa chọn mã vị trí',
      text: 'Vui lòng chọn ít nhất một mã vị trí',
      confirmButtonText: 'OK'
    });
    return;
  }

  const branchId = localStorage.getItem('current_branch_id');
  if (!branchId) {
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi!',
      text: 'Không xác định được chi nhánh hiện tại',
      confirmButtonText: 'OK'
    });
    return;
  }

  saving.value = true;
  try {
    await axios.post('/api/quality/teachers/settings', {
      branch_id: branchId,
      position_codes: selectedCodes.value
    });
    
    await Swal.fire({
      icon: 'success',
      title: 'Thành công!',
      text: 'Đã lưu thiết lập mã vị trí giáo viên cho chi nhánh này',
      confirmButtonText: 'OK'
    });
    
    emit('saved');
  } catch (error) {
    console.error('Save settings error:', error);
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi!',
      text: error.response?.data?.message || 'Không thể lưu thiết lập',
      confirmButtonText: 'OK'
    });
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  await loadPositions();
  await loadCurrentSettings();
});
</script>

