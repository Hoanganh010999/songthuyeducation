<template>
  <div class="w-full max-w-xl">
    <!-- Main Card with RMIT-style -->
    <div class="bg-white rounded-xl shadow-2xl overflow-hidden">

      <!-- Navy Blue Gradient Header -->
      <div class="bg-gradient-to-br from-[#000054] to-[#000080] px-6 py-6 text-center">
        <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-3 shadow-lg">
          <span class="text-[#000054] font-bold text-2xl">S</span>
        </div>
        <h1 class="text-2xl font-semibold text-white">School Management Portal</h1>
      </div>

      <div class="px-8 py-6">
        <!-- Green Welcome Box -->
        <div class="bg-gradient-to-br from-[#5fd4a0] to-[#4ec88f] rounded-lg p-5 mb-5 shadow-md">
          <h2 class="text-lg font-semibold text-gray-900 mb-2">
            Welcome to your school management system!
          </h2>
          <p class="text-sm text-gray-800 leading-relaxed">
            Chào mừng trở lại! Vui lòng đăng nhập để truy cập hệ thống.
          </p>
        </div>

        <!-- Error Message (Red Box) -->
        <div v-if="errorMessage" class="bg-[#fff2f2] border-l-4 border-[#e30613] rounded-lg p-4 mb-6">
          <div class="flex items-center">
            <span class="text-[#e30613] font-bold mr-2">⚠️</span>
            <p class="text-sm text-[#e30613] font-medium">{{ errorMessage }}</p>
          </div>
        </div>

        <!-- Login Form -->
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Email Input -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
              Email
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#000054] focus:border-transparent transition-all text-sm"
              placeholder="Email"
            />
          </div>

          <!-- Password Input -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
              Mật khẩu
            </label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#000054] focus:border-transparent transition-all text-sm"
              placeholder="Password"
            />
          </div>

          <!-- Remember Me Checkbox -->
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <input
                id="remember"
                v-model="form.remember"
                type="checkbox"
                class="w-4 h-4 text-[#e30613] border-gray-300 rounded focus:ring-[#e30613] cursor-pointer"
              />
              <label for="remember" class="ml-2 text-sm text-gray-700 cursor-pointer">
                Ghi nhớ đăng nhập
              </label>
            </div>
            <router-link
              to="/auth/forgot-password"
              class="text-sm text-[#000054] hover:text-[#e30613] font-medium transition-colors"
            >
              Quên mật khẩu?
            </router-link>
          </div>

          <!-- Red Sign In Button (RMIT Style) -->
          <button
            type="submit"
            :disabled="loading"
            class="w-full bg-[#e30613] text-white py-3 rounded-full font-bold text-base hover:bg-[#c00510] focus:outline-none focus:ring-4 focus:ring-[#e30613]/30 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
          >
            <span v-if="!loading" class="flex items-center justify-center">
              <span class="mr-2">🔐</span>
              Sign in
            </span>
            <span v-else class="flex items-center justify-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Đang xử lý...
            </span>
          </button>

          <!-- Terms Text -->
          <p class="text-center text-xs text-gray-500 mt-3">
            Bằng việc đăng nhập, bạn chấp nhận điều khoản sử dụng của School Management System.
          </p>
        </form>
      </div>

      <!-- Support Footer -->
      <div class="bg-gray-50 px-8 py-4 border-t border-gray-200 text-center">
        <p class="text-xs text-gray-600">
          Cần hỗ trợ? Email: <a href="mailto:support@school.edu.vn" class="text-[#e30613] hover:underline font-medium">support@school.edu.vn</a>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const form = ref({
  email: '',
  password: '',
  remember: false
});

const loading = ref(false);
const errorMessage = ref('');

const handleSubmit = async () => {
  loading.value = true;
  errorMessage.value = '';

  const result = await authStore.login({
    email: form.value.email,
    password: form.value.password
  });

  loading.value = false;

  if (result.success) {
    router.push({ name: 'dashboard' });
  } else {
    errorMessage.value = result.message;
  }
};
</script>

<style scoped>
/* Additional smooth animations */
button:not(:disabled):active {
  transform: translateY(0) !important;
}

input:focus {
  transform: scale(1.01);
}

/* Smooth transition for all form elements */
input, button {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
