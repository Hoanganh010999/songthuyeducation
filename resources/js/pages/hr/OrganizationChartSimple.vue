<template>
  <div class="p-6">
    <div class="mb-4 flex justify-between items-center">
      <h2 class="text-2xl font-bold">Sơ đồ tổ chức</h2>
    </div>

    <!-- Tree View -->
    <div class="bg-white rounded-lg shadow p-8 overflow-x-auto">
      <div v-if="loading" class="text-center py-8 text-gray-500">
        Đang tải...
      </div>
      <div v-else-if="departments.length === 0" class="text-center py-8 text-gray-500">
        Đang khởi tạo sơ đồ tổ chức...
      </div>
      <div v-else class="flex justify-center">
        <DepartmentNode
          v-for="dept in rootDepartments"
          :key="dept.id"
          :department="dept"
          :positions="positions"
          @edit="handleEdit"
          @add-child="handleAddChild"
          @assign="handleAssign"
          @delete="handleDelete"
        />
      </div>
    </div>

    <!-- Modals -->
    <DepartmentModal
      v-if="showDepartmentModal"
      :department="selectedDepartment"
      :positions="positions"
      :parent-id="parentDepartmentId"
      @close="closeModals"
      @saved="handleSaved"
    />

    <AssignEmployeeModal
      v-if="showAssignModal"
      :department-id="selectedDepartmentId"
      :positions="positions"
      @close="showAssignModal = false"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import DepartmentNode from './DepartmentNode.vue';
import DepartmentModal from './DepartmentModal.vue';
import AssignEmployeeModal from './AssignEmployeeModal.vue';

const departments = ref([]);
const positions = ref([]);
const loading = ref(false);
const showDepartmentModal = ref(false);
const showAssignModal = ref(false);
const selectedDepartment = ref(null);
const selectedDepartmentId = ref(null);
const parentDepartmentId = ref(null);

const rootDepartments = computed(() => {
  return departments.value.filter(d => !d.parent_id);
});

const loadDepartments = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/hr/departments/tree');
    departments.value = flattenDepartments(response.data);
    
    // Auto-create root department if none exists
    if (departments.value.length === 0) {
      await createRootDepartment();
    }
  } catch (error) {
    console.error('Error loading departments:', error);
  } finally {
    loading.value = false;
  }
};

const createRootDepartment = async () => {
  try {
    const branchId = localStorage.getItem('current_branch_id');
    if (!branchId) {
      console.error('No branch selected');
      return;
    }
    
    await axios.post('/api/hr/departments', {
      name: 'Ban quản trị',
      description: 'Phòng ban gốc',
      branch_id: parseInt(branchId),
      parent_id: null
    });
    
    // Reload departments after creating root
    const response = await axios.get('/api/hr/departments/tree');
    departments.value = flattenDepartments(response.data);
  } catch (error) {
    console.error('Error creating root department:', error);
  }
};

const flattenDepartments = (depts, result = []) => {
  depts.forEach(dept => {
    result.push(dept);
    if (dept.children && dept.children.length > 0) {
      flattenDepartments(dept.children, result);
    }
  });
  return result;
};

const loadPositions = async () => {
  try {
    const response = await axios.get('/api/hr/positions');
    positions.value = response.data.data || response.data || [];
  } catch (error) {
    console.error('Error loading positions:', error);
  }
};

const handleEdit = (dept) => {
  selectedDepartment.value = dept;
  parentDepartmentId.value = null;
  showDepartmentModal.value = true;
};

const handleAddChild = (parentDept) => {
  selectedDepartment.value = null;
  parentDepartmentId.value = parentDept.id;
  showDepartmentModal.value = true;
};

const handleAssign = (dept) => {
  selectedDepartmentId.value = dept.id;
  showAssignModal.value = true;
};

const handleDelete = async (dept) => {
  if (!confirm(`Xóa phòng ban "${dept.name}"?`)) return;
  
  try {
    await axios.delete(`/api/hr/departments/${dept.id}`);
    await loadDepartments();
  } catch (error) {
    alert(error.response?.data?.message || 'Có lỗi xảy ra');
  }
};

const closeModals = () => {
  showDepartmentModal.value = false;
  selectedDepartment.value = null;
  parentDepartmentId.value = null;
};

const handleSaved = async () => {
  showDepartmentModal.value = false;
  showAssignModal.value = false;
  await loadDepartments();
};

onMounted(() => {
  loadDepartments();
  loadPositions();
});
</script>

