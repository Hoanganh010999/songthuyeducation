<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 p-4" @click.self="close">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto" @click.stop>
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white z-10">
        <h3 class="text-xl font-semibold text-gray-800">Chi Tiết Chi Nhánh</h3>
        <button @click="close" class="p-2 rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div v-if="branch" class="p-6 space-y-6">
        <!-- Branch Info -->
        <div class="bg-gray-50 rounded-lg p-4">
          <div class="flex items-start justify-between mb-4">
            <div>
              <div class="flex items-center space-x-3 mb-2">
                <code class="text-lg font-mono font-bold text-gray-900 bg-white px-3 py-1 rounded border border-gray-300">
                  {{ branch.code }}
                </code>
                <span v-if="branch.is_headquarters" class="px-3 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full">
                  TRỤ SỞ CHÍNH
                </span>
              </div>
              <h4 class="text-2xl font-bold text-gray-900">{{ branch.name }}</h4>
            </div>
            <span
              :class="[
                'px-3 py-1 text-sm font-semibold rounded-full',
                branch.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
              ]"
            >
              {{ branch.is_active ? 'Hoạt động' : 'Ngừng hoạt động' }}
            </span>
          </div>

          <p v-if="branch.description" class="text-gray-600">{{ branch.description }}</p>
        </div>

        <!-- Contact Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h5 class="text-sm font-semibold text-gray-500 uppercase mb-3">Thông Tin Liên Hệ</h5>
            <div class="space-y-2">
              <div v-if="branch.phone" class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <span class="text-gray-700">{{ branch.phone }}</span>
              </div>
              <div v-if="branch.email" class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span class="text-gray-700">{{ branch.email }}</span>
              </div>
              <div v-if="!branch.phone && !branch.email" class="text-gray-400 text-sm">Chưa có thông tin</div>
            </div>
          </div>

          <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h5 class="text-sm font-semibold text-gray-500 uppercase mb-3">Địa Chỉ</h5>
            <div class="text-gray-700">
              <p v-if="branch.address">{{ branch.address }}</p>
              <p v-if="branch.ward || branch.district || branch.city">
                {{ [branch.ward, branch.district, branch.city].filter(Boolean).join(', ') }}
              </p>
              <p v-if="!branch.address && !branch.city" class="text-gray-400 text-sm">Chưa có địa chỉ</p>
            </div>
          </div>
        </div>

        <!-- Manager Info -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <h5 class="text-sm font-semibold text-gray-500 uppercase mb-3">Quản Lý Chi Nhánh</h5>
          <div v-if="branch.manager" class="flex items-center space-x-3">
            <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center">
              <span class="text-white text-lg font-semibold">
                {{ branch.manager.name.charAt(0).toUpperCase() }}
              </span>
            </div>
            <div>
              <p class="font-medium text-gray-900">{{ branch.manager.name }}</p>
              <p class="text-sm text-gray-500">{{ branch.manager.email }}</p>
            </div>
          </div>
          <p v-else class="text-gray-400 text-sm">Chưa có quản lý</p>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <p class="text-3xl font-bold text-blue-600">{{ branch.users_count || 0 }}</p>
            <p class="text-sm text-blue-800 mt-1">Nhân Sự</p>
          </div>
          <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <p class="text-3xl font-bold text-green-600">0</p>
            <p class="text-sm text-green-800 mt-1">Học Viên</p>
          </div>
          <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
            <p class="text-3xl font-bold text-purple-600">0</p>
            <p class="text-sm text-purple-800 mt-1">Khách Hàng</p>
          </div>
        </div>

        <!-- Metadata -->
        <div v-if="branch.created_at" class="text-xs text-gray-500 pt-4 border-t border-gray-200">
          <p>Ngày tạo: {{ new Date(branch.created_at).toLocaleString('vi-VN') }}</p>
          <p v-if="branch.updated_at">Cập nhật: {{ new Date(branch.updated_at).toLocaleString('vi-VN') }}</p>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end">
        <button
          @click="close"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
        >
          Đóng
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  show: { type: Boolean, default: false },
  branch: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const close = () => {
  emit('close');
};
</script>

