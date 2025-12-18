<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="$emit('close')"></div>

      <!-- Modal panel -->
      <div class="relative inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl z-10">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">
            Gán phụ huynh cho học viên: {{ student?.user?.name }}
          </h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-500">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Search Parent -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Tìm kiếm phụ huynh (theo tên hoặc số điện thoại)
          </label>
          <div class="flex gap-2">
            <input
              v-model="searchQuery"
              @input="searchParents"
              type="text"
              placeholder="Nhập tên hoặc số điện thoại phụ huynh..."
              class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
            />
            <button
              @click="searchParents"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              Tìm kiếm
            </button>
          </div>
        </div>

        <!-- Search Results -->
        <div v-if="searching" class="text-center py-4">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
          <p class="mt-2 text-sm text-gray-600">Đang tìm kiếm...</p>
        </div>

        <div v-else-if="searchResults.length > 0" class="mb-4">
          <p class="text-sm text-gray-600 mb-2">Kết quả tìm kiếm ({{ searchResults.length }})</p>
          <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg">
            <div
              v-for="parent in searchResults"
              :key="parent.id"
              class="p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition"
              @click="selectParent(parent)"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <div class="font-medium text-gray-900">{{ parent.user?.name || 'N/A' }}</div>
                  <div class="text-sm text-gray-600">
                    <span v-if="parent.user?.email">📧 {{ parent.user.email }}</span>
                    <span v-if="parent.user?.phone" class="ml-2">📞 {{ parent.user.phone }}</span>
                  </div>
                  <div v-if="parent.students && parent.students.length > 0" class="text-xs text-gray-500 mt-1">
                    Con: {{ parent.students.map(s => s.user?.name).join(', ') }}
                  </div>
                </div>
                <div>
                  <button
                    @click.stop="assignParent(parent)"
                    class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700"
                  >
                    Chọn
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="searched && searchResults.length === 0" class="text-center py-8">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <p class="mt-2 text-sm text-gray-600">Không tìm thấy phụ huynh nào</p>
          <button
            @click="createNewParent"
            class="mt-4 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
          >
            + Tạo phụ huynh mới
          </button>
        </div>

        <div v-else class="text-center py-8 text-gray-500">
          <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <p class="mt-2">Nhập tên hoặc số điện thoại để tìm kiếm phụ huynh</p>
        </div>

        <!-- Footer -->
        <div class="mt-6 flex justify-end">
          <button
            @click="$emit('close')"
            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
          >
            Đóng
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import api from '../../services/api';
import Swal from 'sweetalert2';

const props = defineProps({
  show: Boolean,
  student: Object
});

const emit = defineEmits(['close', 'assigned']);

const searchQuery = ref('');
const searchResults = ref([]);
const searching = ref(false);
const searched = ref(false);

// Reset when modal opens/closes
watch(() => props.show, (newVal) => {
  if (newVal) {
    searchQuery.value = '';
    searchResults.value = [];
    searched.value = false;
  }
});

const searchParents = async () => {
  if (!searchQuery.value.trim()) {
    return;
  }

  searching.value = true;
  searched.value = false;

  try {
    const response = await api.get('/api/quality/parents/search', {
      params: {
        query: searchQuery.value.trim()
      }
    });

    searchResults.value = response.data.data || [];
    searched.value = true;
  } catch (error) {
    console.error('Error searching parents:', error);
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: 'Không thể tìm kiếm phụ huynh'
    });
  } finally {
    searching.value = false;
  }
};

const selectParent = (parent) => {
  console.log('Selected parent:', parent);
};

const assignParent = async (parent) => {
  try {
    const result = await Swal.fire({
      title: 'Xác nhận gán phụ huynh',
      html: `
        <div class="text-left">
          <p class="text-sm text-gray-600 mb-2">Phụ huynh: <strong>${parent.user?.name}</strong></p>
          <p class="text-sm text-gray-600">Học viên: <strong>${props.student?.user?.name}</strong></p>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Xác nhận',
      cancelButtonText: 'Hủy'
    });

    if (!result.isConfirmed) {
      return;
    }

    await api.post(`/api/quality/students/${props.student.id}/assign-parent`, {
      parent_id: parent.id
    });

    await Swal.fire({
      icon: 'success',
      title: 'Thành công',
      text: 'Đã gán phụ huynh cho học viên',
      timer: 1500,
      showConfirmButton: false
    });

    emit('assigned');
  } catch (error) {
    console.error('Error assigning parent:', error);
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: error.response?.data?.message || 'Không thể gán phụ huynh'
    });
  }
};

const createNewParent = async () => {
  const { value: formValues } = await Swal.fire({
    title: 'Tạo phụ huynh mới',
    html: `
      <div class="text-left space-y-3">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tên phụ huynh *</label>
          <input id="parent-name" class="swal2-input w-full" placeholder="Nhập tên phụ huynh">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
          <input id="parent-email" type="email" class="swal2-input w-full" placeholder="Nhập email">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
          <input id="parent-phone" type="tel" class="swal2-input w-full" placeholder="Nhập số điện thoại">
        </div>
      </div>
    `,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Tạo mới',
    cancelButtonText: 'Hủy',
    preConfirm: () => {
      const name = document.getElementById('parent-name').value;
      const email = document.getElementById('parent-email').value;
      const phone = document.getElementById('parent-phone').value;
      
      if (!name || !email) {
        Swal.showValidationMessage('Vui lòng nhập đầy đủ tên và email');
        return false;
      }
      
      return { name, email, phone };
    }
  });
  
  if (formValues) {
    try {
      const response = await api.post('/api/quality/parents/create', {
        ...formValues,
        student_id: props.student.id
      });
      
      await Swal.fire({
        icon: 'success',
        title: 'Thành công',
        text: 'Đã tạo phụ huynh và gán cho học viên',
        timer: 1500,
        showConfirmButton: false
      });
      
      emit('assigned');
    } catch (error) {
      console.error('Error creating parent:', error);
      await Swal.fire({
        icon: 'error',
        title: 'Lỗi',
        text: error.response?.data?.message || 'Không thể tạo phụ huynh'
      });
    }
  }
};
</script>

