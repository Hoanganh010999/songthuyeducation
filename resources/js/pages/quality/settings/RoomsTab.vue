<template>
  <div>
    <div class="flex justify-between mb-4">
      <h3 class="text-lg font-semibold">{{ t('rooms.title') }}</h3>
      <button v-if="authStore.hasPermission('classes.manage_settings')" @click="openModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Tạo phòng</button>
    </div>
    <div v-if="loading" class="text-center py-8"><div class="animate-spin h-8 w-8 border-4 border-blue-600 border-t-transparent rounded-full mx-auto"></div></div>
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="room in rooms" :key="room.id" class="border rounded-lg p-4 hover:shadow-md transition">
        <div class="flex justify-between items-start mb-2">
          <div>
            <h4 class="font-semibold text-lg">{{ room.name }}</h4>
            <p class="text-sm text-gray-600">{{ room.building }} - {{ room.floor }}</p>
          </div>
          <span :class="room.is_available ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 py-1 text-xs rounded-full">
            {{ room.is_available ? 'Sẵn sàng' : 'Đang sử dụng' }}
          </span>
        </div>
        <div class="text-sm text-gray-600 mb-3">
          <p>Sức chứa: {{ room.capacity }} người</p>
          <p>Loại: {{ roomTypes[room.room_type] || room.room_type }}</p>
        </div>
        <div class="flex justify-end space-x-2">
          <button @click="openModal(room)" class="text-sm text-blue-600">Sửa</button>
          <button @click="deleteRoom(room)" class="text-sm text-red-600">Xóa</button>
        </div>
      </div>
    </div>
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-bold mb-4">{{ editing ? 'Sửa phòng' : 'Tạo phòng' }}</h3>
        <div class="space-y-3">
          <div><label class="block text-sm mb-1">Tên phòng</label><input v-model="form.name" class="w-full px-3 py-2 border rounded-lg" /></div>
          <div><label class="block text-sm mb-1">Mã</label><input v-model="form.code" class="w-full px-3 py-2 border rounded-lg" /></div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="block text-sm mb-1">Tòa nhà</label><input v-model="form.building" class="w-full px-3 py-2 border rounded-lg" /></div>
            <div><label class="block text-sm mb-1">Tầng</label><input v-model="form.floor" class="w-full px-3 py-2 border rounded-lg" /></div>
          </div>
          <div><label class="block text-sm mb-1">Sức chứa</label><input v-model.number="form.capacity" type="number" class="w-full px-3 py-2 border rounded-lg" /></div>
          <div><label class="block text-sm mb-1">Loại phòng</label><select v-model="form.room_type" class="w-full px-3 py-2 border rounded-lg"><option v-for="(label, value) in roomTypes" :key="value" :value="value">{{ label }}</option></select></div>
        </div>
        <div class="flex justify-end space-x-3 mt-6">
          <button @click="showModal = false" class="px-4 py-2 border rounded-lg">Hủy</button>
          <button @click="save" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Lưu</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useI18n } from '../../../composables/useI18n';
import { useAuthStore } from '../../../stores/auth';

const { t } = useI18n();
const authStore = useAuthStore();
const loading = ref(false);
const rooms = ref([]);
const showModal = ref(false);
const editing = ref(null);
const form = ref({ name: '', code: '', building: '', floor: '', capacity: 40, room_type: 'classroom', is_available: true });
const roomTypes = { classroom: 'Phòng học', lab: 'Phòng thí nghiệm', computer_lab: 'Phòng máy', library: 'Thư viện', gym: 'Phòng gym', other: 'Khác' };

const load = async () => {
  loading.value = true;
  const branchId = localStorage.getItem('current_branch_id');
  const res = await axios.get('/api/class-settings/rooms', { params: { branch_id: branchId } });
  rooms.value = res.data.data;
  loading.value = false;
};

const openModal = (item = null) => {
  editing.value = item;
  form.value = item ? { ...item } : { name: '', code: '', building: '', floor: '', capacity: 40, room_type: 'classroom', is_available: true };
  showModal.value = true;
};

const save = async () => {
  try {
    const branchId = localStorage.getItem('current_branch_id');
    const data = { ...form.value, branch_id: branchId };
    if (editing.value) await axios.put(`/api/class-settings/rooms/${editing.value.id}`, data);
    else await axios.post('/api/class-settings/rooms', data);
    Swal.fire({ icon: 'success', timer: 1500 });
    showModal.value = false;
    load();
  } catch (error) {
    Swal.fire({ icon: 'error', text: error.response?.data?.message });
  }
};

const deleteRoom = async (item) => {
  if ((await Swal.fire({ title: 'Xóa?', showCancelButton: true })).isConfirmed) {
    await axios.delete(`/api/class-settings/rooms/${item.id}`);
    Swal.fire({ icon: 'success', timer: 1500 });
    load();
  }
};

onMounted(load);
</script>

