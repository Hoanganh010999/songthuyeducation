<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 p-4" @click.self="close">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" @click.stop>
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white z-10">
        <h3 class="text-xl font-semibold text-gray-800">
          {{ isEdit ? 'Chỉnh Sửa Chi Nhánh' : 'Tạo Chi Nhánh Mới' }}
        </h3>
        <button @click="close" class="p-2 rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="saveBranch" class="p-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Code -->
          <div>
            <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
              Mã Chi Nhánh <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.code"
              type="text"
              id="code"
              :disabled="isEdit"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'bg-gray-100': isEdit }"
              placeholder="VD: HN01, HCM01"
              required
            />
            <p v-if="!isEdit" class="text-xs text-gray-500 mt-1">Mã chi nhánh không thể thay đổi sau khi tạo</p>
          </div>

          <!-- Name -->
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
              Tên Chi Nhánh <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              id="name"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="VD: Chi Nhánh Hà Nội"
              required
            />
          </div>

          <!-- Phone -->
          <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Số Điện Thoại</label>
            <input
              v-model="form.phone"
              type="tel"
              id="phone"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="VD: 0241234567"
            />
          </div>

          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input
              v-model="form.email"
              type="email"
              id="email"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="VD: hanoi@school.com"
            />
          </div>

          <!-- City -->
          <div>
            <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Thành Phố</label>
            <input
              v-model="form.city"
              type="text"
              id="city"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="VD: Hà Nội"
            />
          </div>

          <!-- District -->
          <div>
            <label for="district" class="block text-sm font-medium text-gray-700 mb-1">Quận/Huyện</label>
            <input
              v-model="form.district"
              type="text"
              id="district"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="VD: Đống Đa"
            />
          </div>
        </div>

        <!-- Address -->
        <div>
          <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Địa Chỉ</label>
          <textarea
            v-model="form.address"
            id="address"
            rows="2"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="VD: 123 Đường Láng"
          ></textarea>
        </div>

        <!-- Description -->
        <div>
          <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Mô Tả</label>
          <textarea
            v-model="form.description"
            id="description"
            rows="3"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Mô tả về chi nhánh..."
          ></textarea>
        </div>

        <!-- Checkboxes -->
        <div class="flex items-center space-x-6">
          <div class="flex items-center">
            <input
              v-model="form.is_active"
              type="checkbox"
              id="is_active"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label for="is_active" class="ml-2 block text-sm text-gray-900">Hoạt động</label>
          </div>

          <div class="flex items-center">
            <input
              v-model="form.is_headquarters"
              type="checkbox"
              id="is_headquarters"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label for="is_headquarters" class="ml-2 block text-sm text-gray-900">Trụ sở chính</label>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
          <p class="text-sm text-red-600">{{ error }}</p>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
          <button
            type="button"
            @click="close"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
          >
            Hủy
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 flex items-center gap-2"
          >
            <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ loading ? 'Đang lưu...' : 'Lưu' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import api from '../../services/api';
import { useSwal } from '../../composables/useSwal';

const props = defineProps({
  show: { type: Boolean, default: false },
  branch: { type: Object, default: null },
  isEdit: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'saved']);

const swal = useSwal();

const form = ref({
  code: '',
  name: '',
  phone: '',
  email: '',
  address: '',
  city: '',
  district: '',
  ward: '',
  description: '',
  is_active: true,
  is_headquarters: false,
});

const loading = ref(false);
const error = ref('');

const resetForm = () => {
  form.value = {
    code: '',
    name: '',
    phone: '',
    email: '',
    address: '',
    city: '',
    district: '',
    ward: '',
    description: '',
    is_active: true,
    is_headquarters: false,
  };
  error.value = '';
};

const saveBranch = async () => {
  loading.value = true;
  error.value = '';

  try {
    let response;
    if (props.isEdit) {
      response = await api.put(`/api/branches/${props.branch.id}`, form.value);
    } else {
      response = await api.post('/api/branches', form.value);
    }

    if (response.data.success) {
      swal.success(response.data.message);
      emit('saved');
    } else {
      error.value = response.data.message || 'Có lỗi xảy ra';
    }
  } catch (err) {
    console.error('Branch save error:', err);
    error.value = err.response?.data?.message || 'Có lỗi xảy ra khi lưu chi nhánh';
    if (err.response?.data?.errors) {
      error.value += '\n' + Object.values(err.response.data.errors).flat().join('\n');
    }
  } finally {
    loading.value = false;
  }
};

const close = () => {
  emit('close');
};

watch(() => props.show, (newVal) => {
  if (newVal) {
    if (props.isEdit && props.branch) {
      form.value = {
        code: props.branch.code,
        name: props.branch.name,
        phone: props.branch.phone || '',
        email: props.branch.email || '',
        address: props.branch.address || '',
        city: props.branch.city || '',
        district: props.branch.district || '',
        ward: props.branch.ward || '',
        description: props.branch.description || '',
        is_active: props.branch.is_active ?? true,
        is_headquarters: props.branch.is_headquarters ?? false,
      };
    } else {
      resetForm();
    }
  }
}, { immediate: true });
</script>

