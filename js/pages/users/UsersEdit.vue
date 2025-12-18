<template>
  <div class="max-w-2xl">
    <div class="mb-6">
      <router-link to="/users" class="text-blue-600 hover:text-blue-800 flex items-center space-x-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        <span>Quay lại</span>
      </router-link>
      <h1 class="text-2xl font-bold text-gray-900 mt-4">Chỉnh sửa User</h1>
    </div>

    <div v-if="loadingUser" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-600">Đang tải...</p>
    </div>

    <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <form @submit.prevent="handleSubmit" class="space-y-5">
        <div v-if="errorMessage" class="bg-red-50 border border-red-200 rounded-lg p-3">
          <p class="text-sm text-red-600">{{ errorMessage }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tên</label>
          <input v-model="form.name" type="text" required 
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input v-model="form.email" type="email" required 
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại <span class="text-red-500">*</span></label>
          <input v-model="form.phone" type="tel" required 
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu mới (để trống nếu không đổi)</label>
          <input v-model="form.password" type="password" 
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
        </div>

        <div v-if="form.password">
          <label class="block text-sm font-medium text-gray-700 mb-1">Xác nhận mật khẩu</label>
          <input v-model="form.password_confirmation" type="password" 
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Roles</label>
          <div class="space-y-2">
            <label v-for="role in roles" :key="role.id" class="flex items-center">
              <input type="checkbox" :value="role.id" v-model="form.role_ids" 
                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
              <span class="ml-2 text-sm text-gray-700">{{ role.display_name }}</span>
            </label>
          </div>
        </div>

        <div class="flex justify-end space-x-3 pt-4">
          <router-link to="/users" 
            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
            Hủy
          </router-link>
          <button type="submit" :disabled="loading"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
            {{ loading ? 'Đang lưu...' : 'Lưu thay đổi' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { usersApi, rolesApi } from '../../services/api';

const route = useRoute();
const router = useRouter();

const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  role_ids: []
});

const roles = ref([]);
const loading = ref(false);
const loadingUser = ref(true);
const errorMessage = ref('');

const loadUser = async () => {
  try {
    const response = await usersApi.getById(route.params.id);
    if (response.data.success) {
      const user = response.data.data;
      form.value.name = user.name;
      form.value.email = user.email;
      form.value.phone = user.phone || '';
      form.value.role_ids = user.roles.map(r => r.id);
    }
  } catch (error) {
    console.error('Load user error:', error);
    router.push('/users');
  } finally {
    loadingUser.value = false;
  }
};

const loadRoles = async () => {
  try {
    const response = await rolesApi.getAll({ per_page: 100 });
    if (response.data.success) {
      roles.value = response.data.data.data;
    }
  } catch (error) {
    console.error('Load roles error:', error);
  }
};

const handleSubmit = async () => {
  loading.value = true;
  errorMessage.value = '';
  
  try {
    const response = await usersApi.update(route.params.id, form.value);
    if (response.data.success) {
      router.push('/users');
    }
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Có lỗi xảy ra';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  Promise.all([loadUser(), loadRoles()]);
});
</script>

