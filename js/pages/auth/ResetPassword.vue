<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
      <!-- Logo & Title -->
      <div class="text-center mb-8">
        <div class="mx-auto w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mb-4">
          <span class="text-white font-bold text-2xl">S</span>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-2">
          Đặt lại mật khẩu
        </h2>
        <p class="text-gray-600">
          Nhập mật khẩu mới cho tài khoản của bạn
        </p>
      </div>

      <!-- Card -->
      <div class="bg-white rounded-2xl shadow-xl p-8">
        <!-- Success State -->
        <div v-if="passwordReset" class="text-center">
          <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
          </div>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">Đặt lại mật khẩu thành công!</h3>
          <p class="text-gray-600 mb-6">Bạn có thể đăng nhập với mật khẩu mới.</p>
          <router-link
            to="/auth/login"
            class="inline-block w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition"
          >
            Đăng nhập ngay
          </router-link>
        </div>

        <!-- Form -->
        <form v-else @submit.prevent="handleSubmit">
          <!-- Password Input -->
          <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
              Mật khẩu mới
            </label>
            <input
              id="password"
              type="password"
              v-model="form.password"
              required
              minlength="6"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
              placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)"
              :disabled="loading"
            />
          </div>

          <!-- Password Confirmation Input -->
          <div class="mb-6">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
              Xác nhận mật khẩu
            </label>
            <input
              id="password_confirmation"
              type="password"
              v-model="form.password_confirmation"
              required
              minlength="6"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
              placeholder="Nhập lại mật khẩu mới"
              :disabled="loading"
            />
          </div>

          <!-- Error Message -->
          <div v-if="errorMessage" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
            {{ errorMessage }}
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="loading"
            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="loading" class="flex items-center justify-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Đang xử lý...
            </span>
            <span v-else>Đặt lại mật khẩu</span>
          </button>
        </form>
      </div>

      <!-- Back to Login -->
      <div class="mt-6 text-center">
        <router-link
          to="/auth/login"
          class="text-sm text-blue-600 hover:text-blue-700 font-medium inline-flex items-center"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          Quay lại đăng nhập
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../services/api';

const route = useRoute();

const form = ref({
  token: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const loading = ref(false);
const errorMessage = ref('');
const passwordReset = ref(false);

onMounted(() => {
  // Get token and email from URL query params
  form.value.token = route.query.token || '';
  form.value.email = route.query.email || '';
  
  if (!form.value.token) {
    errorMessage.value = 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.';
  }
});

const handleSubmit = async () => {
  // Validate passwords match
  if (form.value.password !== form.value.password_confirmation) {
    errorMessage.value = 'Mật khẩu xác nhận không khớp.';
    return;
  }

  loading.value = true;
  errorMessage.value = '';

  try {
    // TODO: Implement reset password API endpoint
    // await api.post('/api/reset-password', form.value);
    
    // Temporary: Show success
    console.log('Reset password request:', {
      token: form.value.token,
      email: form.value.email,
      password: '****'
    });
    
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1500));
    
    passwordReset.value = true;
  } catch (error) {
    console.error('Reset password error:', error);
    errorMessage.value = error.response?.data?.message || 'Có lỗi xảy ra. Vui lòng thử lại.';
  } finally {
    loading.value = false;
  }
};
</script>

