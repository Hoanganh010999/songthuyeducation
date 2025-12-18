<template>
  <div class="space-y-4">
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold text-gray-900">Sơ đồ tổ chức</h2>
      <div class="flex space-x-2">
        <button
          @click="refreshChart"
          class="px-4 py-2 text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-md transition"
        >
          Làm mới
        </button>
      </div>
    </div>

    <!-- Chart Container -->
    <div
      ref="chartContainer"
      id="orgChartContainer"
      class="w-full border border-gray-200 rounded-lg bg-white"
      style="height: 600px;"
    ></div>

    <!-- Sidebar Edit Panel -->
    <Transition name="slide-fade">
      <div
        v-if="showEditSidebar"
        class="fixed right-0 top-0 h-full w-96 bg-white shadow-2xl z-[9999] overflow-y-auto"
      >
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-semibold text-gray-900">
              {{ editForm.type === 'department' ? 'Chỉnh sửa phòng ban' : 'Chỉnh sửa nhân viên' }}
            </h3>
            <button @click="showEditSidebar = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Department Edit -->
          <div v-if="editForm.type === 'department'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tên phòng ban</label>
              <input
                v-model="editForm.name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Job Title mặc định</label>
              <select
                v-model="editForm.position_id"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">Không có</option>
                <option v-for="pos in positions" :key="pos.id" :value="pos.id">
                  {{ pos.name }}
                </option>
              </select>
              <p class="text-xs text-gray-500 mt-1">Nhân viên trong phòng ban này sẽ thừa kế quyền từ Job Title</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
              <textarea
                v-model="editForm.description"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              ></textarea>
            </div>

            <!-- Actions for Department -->
            <div class="border-t pt-4 mt-6 space-y-3">
              <button
                @click="openAddSubDepartment"
                class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition flex items-center justify-center gap-2"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tạo phòng ban con
              </button>
              
              <button
                @click="openAssignEmployee"
                class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition flex items-center justify-center gap-2"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Gán nhân viên
              </button>
            </div>
          </div>

          <!-- Employee Edit -->
          <div v-if="editForm.type === 'employee'" class="space-y-4">
            <div class="bg-blue-50 p-3 rounded-md mb-4">
              <p class="text-sm text-gray-700"><span class="font-medium">Nhân viên:</span> {{ editForm.employeeName }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Chức vụ</label>
              <select
                v-model="editForm.position_id"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option v-for="pos in positions" :key="pos.id" :value="pos.id">
                  {{ pos.name }}
                </option>
              </select>
            </div>
            
            <div class="space-y-2">
              <label class="flex items-center">
                <input
                  type="checkbox"
                  v-model="editForm.is_head"
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded"
                />
                <span class="ml-2 text-sm text-gray-700">Trưởng phòng</span>
              </label>
              <label class="flex items-center">
                <input
                  type="checkbox"
                  v-model="editForm.is_deputy"
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded"
                />
                <span class="ml-2 text-sm text-gray-700">Phó phòng</span>
              </label>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
              <textarea
                v-model="editForm.description"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              ></textarea>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex gap-2 mt-6 pt-4 border-t">
            <button
              @click="showEditSidebar = false"
              class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition"
            >
              Hủy
            </button>
            <button
              @click="handleSaveEdit"
              class="flex-1 px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700 transition"
            >
              Lưu
            </button>
            <button
              v-if="!editForm.type === 'root'"
              @click="handleDeleteNode"
              class="px-4 py-2 text-white bg-red-600 rounded-md hover:bg-red-700 transition"
            >
              Xóa
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Overlay -->
    <div
      v-if="showEditSidebar"
      class="fixed inset-0 bg-black bg-opacity-30 z-[9998]"
      @click="showEditSidebar = false"
    ></div>

    <!-- Assign Employee Modal - 2 Column Layout -->
    <div
      v-if="showAssignModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-[9999]"
      @click.self="showAssignModal = false"
    >
      <div class="relative top-10 mx-auto p-6 border w-[90%] max-w-6xl h-[85vh] shadow-2xl rounded-lg bg-white flex flex-col">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4 pb-4 border-b">
          <h3 class="text-xl font-semibold text-gray-900">Gán nhân viên vào phòng ban</h3>
          <button @click="showAssignModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- 2 Column Layout -->
        <div class="flex-1 flex gap-6 overflow-hidden">
          <!-- Left: Employee Details -->
          <div class="w-2/5 border-r pr-6 overflow-y-auto">
            <div v-if="selectedEmployeeDetail">
              <div class="text-center mb-6">
                <div class="w-24 h-24 mx-auto rounded-full bg-blue-600 flex items-center justify-center text-white text-3xl font-bold mb-3">
                  {{ getInitials(selectedEmployeeDetail.name) }}
                </div>
                <h4 class="text-xl font-semibold text-gray-900">{{ selectedEmployeeDetail.name }}</h4>
                <p class="text-sm text-gray-500">{{ selectedEmployeeDetail.email }}</p>
              </div>

              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Job Title</label>
                  <select
                    v-model="assignForm.position_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">Chọn Job Title</option>
                    <option v-for="pos in positions" :key="pos.id" :value="pos.id">
                      {{ pos.name }}
                    </option>
                  </select>
                  <p class="text-xs text-gray-500 mt-1">Nhân viên sẽ thừa kế quyền từ Job Title</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Vai trò đặc biệt</label>
                  <div class="space-y-2">
                    <label class="flex items-center p-2 border rounded hover:bg-gray-50 cursor-pointer">
                      <input
                        type="checkbox"
                        v-model="assignForm.is_head"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded"
                      />
                      <span class="ml-3">
                        <span class="text-sm font-medium text-gray-900">Trưởng phòng</span>
                        <span class="block text-xs text-gray-500">Quản lý toàn bộ phòng ban</span>
                      </span>
                    </label>
                    <label class="flex items-center p-2 border rounded hover:bg-gray-50 cursor-pointer">
                      <input
                        type="checkbox"
                        v-model="assignForm.is_deputy"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded"
                      />
                      <span class="ml-3">
                        <span class="text-sm font-medium text-gray-900">Phó phòng</span>
                        <span class="block text-xs text-gray-500">Hỗ trợ trưởng phòng</span>
                      </span>
                    </label>
                  </div>
                </div>

                <div class="pt-4 border-t">
                  <button
                    @click="handleAssignSingle"
                    :disabled="!assignForm.position_id"
                    class="w-full px-4 py-3 text-white bg-blue-600 rounded-md hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed font-medium"
                  >
                    Gán vào phòng ban
                  </button>
                </div>
              </div>
            </div>
            <div v-else class="flex items-center justify-center h-full text-gray-400">
              <div class="text-center">
                <svg class="w-16 h-16 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <p>Chọn nhân viên để xem chi tiết</p>
              </div>
            </div>
          </div>

          <!-- Right: Employee List -->
          <div class="flex-1 overflow-y-auto">
            <div class="mb-4">
              <input
                v-model="employeeSearchQuery"
                type="text"
                placeholder="Tìm kiếm nhân viên..."
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div class="space-y-2">
              <div
                v-for="employee in filteredEmployees"
                :key="employee.id"
                @click="selectEmployeeDetail(employee)"
                class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-blue-50 cursor-pointer transition"
                :class="{ 'bg-blue-100 border-blue-500': selectedEmployeeDetail?.id === employee.id }"
              >
                <div class="flex-shrink-0 h-12 w-12">
                  <div class="h-12 w-12 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                    {{ getInitials(employee.name) }}
                  </div>
                </div>
                <div class="flex-1">
                  <div class="text-sm font-medium text-gray-900">{{ employee.name }}</div>
                  <div class="text-xs text-gray-500">{{ employee.email }}</div>
                  <div class="text-xs text-gray-400">{{ employee.phone || 'Chưa có SĐT' }}</div>
                </div>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </div>

            <div v-if="filteredEmployees.length === 0" class="text-center py-12 text-gray-400">
              <svg class="w-16 h-16 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
              </svg>
              <p>Không tìm thấy nhân viên</p>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import axios from 'axios';
import OrgChart from '@balkangraph/orgchart.js';
import Swal from 'sweetalert2';

const authStore = useAuthStore();
const chartContainer = ref(null);
const showAssignModal = ref(false);
const showEditSidebar = ref(false);

let chart = null;

const departments = ref([]);
const flatDepartments = ref([]);
const availableEmployees = ref([]);
const departmentEmployees = ref([]);
const positions = ref([]);

const currentNode = ref(null);

const assignForm = ref({
  department_id: '',
  selected_employees: [],
  position_id: '',
  is_head: false,
  is_deputy: false
});

const selectedEmployeeDetail = ref(null);
const employeeSearchQuery = ref('');

const managerForm = ref({
  userId: null,
  departmentId: null,
  employeeName: '',
  departmentName: '',
  manager_id: ''
});

const editForm = ref({
  type: '',
  id: null,
  departmentId: null,
  name: '',
  employeeName: '',
  description: '',
  position_id: '',
  is_head: false,
  is_deputy: false
});

const filteredEmployees = computed(() => {
  if (!employeeSearchQuery.value) return availableEmployees.value;
  const query = employeeSearchQuery.value.toLowerCase();
  return availableEmployees.value.filter(emp => 
    emp.name?.toLowerCase().includes(query) ||
    emp.email?.toLowerCase().includes(query) ||
    emp.phone?.toLowerCase().includes(query)
  );
});

const currentDepartmentId = computed(() => {
  if (!contextMenu.value.node) return null;
  const nodeId = contextMenu.value.node.id;
  if (nodeId.startsWith('dept_')) {
    return nodeId.replace('dept_', '');
  }
  if (contextMenu.value.node.userData?.departmentId) {
    return contextMenu.value.node.userData.departmentId;
  }
  return null;
});

const loadDepartments = async () => {
  try {
    const response = await axios.get('/api/hr/departments/tree');
    departments.value = response.data;
    flatDepartments.value = flattenDepartments(response.data);
    return response.data;
  } catch (error) {
    console.error('Error loading departments:', error);
    return [];
  }
};

const flattenDepartments = (depts, result = []) => {
  depts.forEach(dept => {
    result.push({ id: dept.id, name: dept.name });
    if (dept.children && dept.children.length > 0) {
      flattenDepartments(dept.children, result);
    }
  });
  return result;
};

const loadEmployees = async () => {
  try {
    const response = await axios.get('/api/users');
    availableEmployees.value = response.data.data || [];
  } catch (error) {
    console.error('Error loading employees:', error);
  }
};

const loadPositions = async () => {
  try {
    const response = await axios.get('/api/hr/positions');
    positions.value = response.data.data || response.data || [];
  } catch (error) {
    console.error('Error loading positions:', error);
    positions.value = [];
  }
};

const transformToOrgChartData = (departments) => {
  const nodes = [];
  
  const processNode = (dept, parentId = null) => {
    const deptNode = {
      id: `dept_${dept.id}`,
      name: dept.name,
      title: dept.parent_id ? 'Phòng ban' : 'Ban quản trị',
      pid: parentId,
      tags: dept.parent_id ? ['department'] : ['root', 'department']
    };
    nodes.push(deptNode);
    
    if (dept.users && dept.users.length > 0) {
      dept.users.forEach(user => {
        const userNode = {
          id: `user_${user.id}_dept_${dept.id}`,
          name: user.name,
          title: user.pivot?.position_id ? getPositionName(user.positions) : 'Nhân viên',
          img: user.avatar || '',
          pid: `dept_${dept.id}`,
          tags: user.pivot?.is_head ? ['head'] : user.pivot?.is_deputy ? ['deputy'] : ['staff'],
          userData: {
            userId: user.id,
            departmentId: dept.id,
            positionId: user.pivot?.position_id,
            isHead: user.pivot?.is_head,
            isDeputy: user.pivot?.is_deputy
          }
        };
        nodes.push(userNode);
      });
    }
    
    if (dept.children && dept.children.length > 0) {
      dept.children.forEach(child => {
        processNode(child, `dept_${dept.id}`);
      });
    }
  };
  
  departments.forEach(dept => {
    if (!dept.parent_id) {
      processNode(dept);
    }
  });
  
  return nodes;
};

const getPositionName = (positions) => {
  if (!positions || positions.length === 0) return 'Nhân viên';
  return positions[0].name;
};

const initChart = async () => {
  const depts = await loadDepartments();
  
  // Nếu không có department nào, tạo root department
  if (depts.length === 0) {
    try {
      console.log('No departments found, creating root department...');
      const response = await axios.post('/api/hr/departments', {
        name: 'Ban quản trị',
        description: 'Phòng ban cấp cao nhất'
      });
      console.log('Root department created:', response.data);
      // Reload departments
      const newDepts = await loadDepartments();
      console.log('Reloaded departments:', newDepts);
      const nodes = transformToOrgChartData(newDepts);
      initChartWithNodes(nodes);
      return;
    } catch (error) {
      console.error('Error creating root department:', error);
      console.error('Error response:', error.response?.data);
    }
  }
  
  const nodes = transformToOrgChartData(depts);
  initChartWithNodes(nodes);
};

const initChartWithNodes = (nodes) => {
  console.log('=== INITIALIZING CHART ===');
  console.log('Nodes:', nodes);
  console.log('Container:', chartContainer.value);
  
  chart = new OrgChart(chartContainer.value, {
    nodes: nodes,
    nodeBinding: {
      field_0: 'name',
      field_1: 'title',
      img_0: 'img'
    },
    tags: {
      root: {
        template: 'diva'
      },
      department: {
        template: 'group'
      },
      head: {
        template: 'ula'
      },
      deputy: {
        template: 'rony'
      },
      staff: {
        template: 'ana'
      }
    },
    enableDragDrop: false,
    enableSearch: true,
    searchFields: ['name', 'title'],
    scaleInitial: 0.6,
    scaleMin: 0.3,
    scaleMax: 1.5,
    mouseScrool: OrgChart.action.zoom,
    menu: {
      pdf: { text: "Export PDF" },
      png: { text: "Export PNG" },
      svg: { text: "Export SVG" },
      csv: { text: "Export CSV" }
    },
    nodeMenu: {
      custom_edit: { text: 'Chỉnh sửa' },
      custom_add: { text: 'Thêm phòng ban con' },
      custom_assign: { text: 'Gán nhân viên' },
      custom_remove: { text: 'Xóa' }
    },
    editUI: null, // Disable built-in edit UI to prevent direct manipulation
    nodeMouseClick: OrgChart.action.none // Disable default click
  });
  
  console.log('Chart created:', chart);
  console.log('Attaching event listeners...');
  
  // Prevent default add/edit/remove actions
  chart.on('add', (sender, node) => {
    console.log('=== ADD EVENT BLOCKED ===');
    return false; // Block default add
  });
  console.log('✓ add event attached');
  
  chart.on('update', (sender, node) => {
    console.log('=== UPDATE EVENT BLOCKED ===');
    return false; // Block default update
  });
  console.log('✓ update event attached');
  
  chart.on('remove', (sender, nodeId) => {
    console.log('=== REMOVE EVENT BLOCKED ===');
    return false; // Block default remove
  });
  console.log('✓ remove event attached');
  
  // Custom node menu handler
  chart.on('node-menu-click', (sender, args) => {
    console.log('=== MENU CLICKED ===');
    console.log('Menu item:', args.menuItem);
    console.log('Text:', args.menuItem.text);
    console.log('Node:', args.node);
    
    currentNode.value = args.node;
    
    const menuText = args.menuItem.text;
    console.log('Checking menu text:', menuText);
    
    // Prevent default actions for built-in menu items
    if (menuText === 'Chỉnh sửa') {
      console.log('Opening edit sidebar...');
      openEditSidebar(args.node);
      return false;
    }
    
    if (menuText === 'Thêm phòng ban con') {
      console.log('Adding sub department...');
      if (args.node.userData?.userId) {
        Swal.fire('Lỗi!', 'Không thể tạo phòng ban con từ nhân viên', 'error');
      } else {
        openAddSubDepartment();
      }
      return false;
    }
    
    if (menuText === 'Gán nhân viên') {
      console.log('Assigning employee...');
      if (args.node.userData?.userId) {
        Swal.fire('Lỗi!', 'Không thể gán nhân viên vào nhân viên', 'error');
      } else {
        openAssignEmployee();
      }
      return false;
    }
    
    if (menuText === 'Xóa') {
      console.log('Deleting node...');
      handleDeleteNode();
      return false;
    }
    
    console.log('No matching menu item!');
    return false;
  });
  console.log('✓ node-menu-click event attached');
  
  // Debug: Log all clicks
  chart.on('click', (sender, args) => {
    console.log('=== CHART CLICKED ===', args);
  });
  console.log('✓ click event attached');
  
  chart.on('init', (sender) => {
    console.log('=== CHART INIT EVENT ===');
  });
  console.log('✓ init event attached');
  
  console.log('=== CHART INITIALIZATION COMPLETE ===');
  console.log('Chart config:', chart.config);
  console.log('NodeMenu config:', chart.config.nodeMenu);
};

const refreshChart = async () => {
  if (chart) {
    const depts = await loadDepartments();
    const nodes = transformToOrgChartData(depts);
    chart.load(nodes);
  }
};

const toggleEmployee = (employee) => {
  const index = assignForm.value.selected_employees.indexOf(employee.id);
  if (index > -1) {
    assignForm.value.selected_employees.splice(index, 1);
  } else {
    assignForm.value.selected_employees.push(employee.id);
  }
};

const handleAssign = async () => {
  try {
    let currentUserAssigned = false;
    
    for (const userId of assignForm.value.selected_employees) {
      await axios.post(`/api/hr/departments/${assignForm.value.department_id}/assign`, {
        user_id: userId,
        position_id: assignForm.value.position_id,
        is_head: assignForm.value.is_head,
        is_deputy: assignForm.value.is_deputy
      });
      
      // Check if current user was assigned
      if (authStore.user && authStore.user.id === userId) {
        currentUserAssigned = true;
      }
    }
    
    showAssignModal.value = false;
    assignForm.value = {
      department_id: '',
      selected_employees: [],
      position_id: '',
      is_head: false,
      is_deputy: false
    };
    
    await refreshChart();
    
    // Refresh user data if current user was assigned
    if (currentUserAssigned) {
      await authStore.fetchUser();
      Swal.fire({
        icon: 'success',
        title: 'Thành công!',
        text: 'Bạn đã được gán vào phòng ban. Quyền của bạn đã được cập nhật.',
        confirmButtonText: 'OK'
      });
    } else {
      Swal.fire('Thành công!', 'Đã phân công nhân viên thành công!', 'success');
    }
  } catch (error) {
    console.error('Error assigning employees:', error);
    Swal.fire('Lỗi!', 'Có lỗi xảy ra khi phân công nhân viên', 'error');
  }
};

const openEditSidebar = (node) => {
  console.log('openEditSidebar called with:', node);
  if (!node) {
    console.error('No node provided');
    return;
  }
  
  // Check if it's a user/employee node
  if (node.userData && node.userData.userId) {
    console.log('Opening employee sidebar');
    editForm.value = {
      type: 'employee',
      id: node.userData.userId,
      departmentId: node.userData.departmentId,
      employeeName: node.name,
      position_id: node.userData.positionId || '',
      is_head: node.userData.isHead || false,
      is_deputy: node.userData.isDeputy || false,
      description: ''
    };
  } else {
    // It's a department node
    console.log('Opening department sidebar');
    const deptId = node.id.replace('dept_', '');
    
    // Load department data from API to get default_position_id
    axios.get(`/api/hr/departments/${deptId}`).then(response => {
      editForm.value = {
        type: 'department',
        id: deptId,
        name: response.data.name,
        description: response.data.description || '',
        position_id: response.data.default_position_id || ''
      };
    }).catch(error => {
      console.error('Error loading department:', error);
      editForm.value = {
        type: 'department',
        id: deptId,
        name: node.name,
        description: '',
        position_id: ''
      };
    });
  }
  
  console.log('Setting showEditSidebar to true');
  showEditSidebar.value = true;
  console.log('showEditSidebar is now:', showEditSidebar.value);
};

const openAddSubDepartment = async () => {
  const node = currentNode.value;
  if (!node) return;
  
  let parentDeptId = null;
  if (node.id.startsWith('dept_')) {
    parentDeptId = node.id.replace('dept_', '');
  }
  
  const { value: deptName } = await Swal.fire({
    title: 'Tạo phòng ban mới',
    input: 'text',
    inputLabel: 'Tên phòng ban',
    inputPlaceholder: 'Nhập tên phòng ban...',
    showCancelButton: true,
    confirmButtonText: 'Tạo',
    cancelButtonText: 'Hủy',
    inputValidator: (value) => {
      if (!value) return 'Vui lòng nhập tên phòng ban!';
    }
  });
  
  if (deptName) {
    try {
      console.log('Creating department:', { name: deptName, parent_id: parentDeptId });
      const response = await axios.post('/api/hr/departments', {
        name: deptName,
        parent_id: parentDeptId
      });
      console.log('Department created:', response.data);
      
      await refreshChart();
      showEditSidebar.value = false;
      Swal.fire('Thành công!', 'Đã tạo phòng ban mới', 'success');
    } catch (error) {
      console.error('Error creating department:', error);
      console.error('Error response:', error.response?.data);
      Swal.fire('Lỗi!', error.response?.data?.message || 'Có lỗi xảy ra khi tạo phòng ban', 'error');
    }
  }
};

const selectEmployeeDetail = (employee) => {
  selectedEmployeeDetail.value = employee;
  // Reset form
  assignForm.value.position_id = '';
  assignForm.value.is_head = false;
  assignForm.value.is_deputy = false;
};

const openAssignEmployee = () => {
  const node = currentNode.value;
  if (!node) return;
  
  let deptId = null;
  if (node.id.startsWith('dept_')) {
    deptId = node.id.replace('dept_', '');
  }
  
  if (deptId) {
    assignForm.value.department_id = deptId;
    selectedEmployeeDetail.value = null;
    employeeSearchQuery.value = '';
    showAssignModal.value = true;
  } else {
    Swal.fire('Lỗi!', 'Không thể xác định phòng ban', 'error');
  }
};

const handleAssignSingle = async () => {
  if (!selectedEmployeeDetail.value || !assignForm.value.position_id) return;
  
  const assignedUserId = selectedEmployeeDetail.value.id;
  
  try {
    const response = await axios.post(`/api/hr/departments/${assignForm.value.department_id}/assign`, {
      user_id: assignedUserId,
      position_id: assignForm.value.position_id,
      is_head: assignForm.value.is_head,
      is_deputy: assignForm.value.is_deputy
    });
    
    showAssignModal.value = false;
    selectedEmployeeDetail.value = null;
    await refreshChart();
    
    // Check if assigned user is current logged in user
    if (authStore.user && authStore.user.id === assignedUserId) {
      // Refresh user data to get new permissions
      await authStore.fetchUser();
      
      Swal.fire({
        icon: 'success',
        title: 'Thành công!',
        text: 'Bạn đã được gán vào phòng ban. Quyền của bạn đã được cập nhật.',
        confirmButtonText: 'OK'
      });
    } else {
      // Show roles inherited info if available
      const rolesInherited = response.data?.data?.roles_inherited || [];
      let message = 'Đã gán nhân viên vào phòng ban';
      
      if (rolesInherited.length > 0) {
        message += `\n\nQuyền đã được thừa kế: ${rolesInherited.join(', ')}`;
      }
      
      Swal.fire('Thành công!', message, 'success');
    }
  } catch (error) {
    console.error('Error assigning employee:', error);
    Swal.fire('Lỗi!', error.response?.data?.message || 'Có lỗi xảy ra khi gán nhân viên', 'error');
  }
};


const handleSaveEdit = async () => {
  try {
    console.log('=== SAVING EDIT ===');
    console.log('Edit form:', editForm.value);
    
    let currentUserUpdated = false;
    
    if (editForm.value.type === 'department') {
      const payload = {
        name: editForm.value.name,
        description: editForm.value.description,
        default_position_id: editForm.value.position_id || null
      };
      console.log('Updating department:', editForm.value.id, payload);
      const response = await axios.put(`/api/hr/departments/${editForm.value.id}`, payload);
      console.log('Department updated:', response.data);
    } else {
      // Check if updating current user
      if (authStore.user && authStore.user.id === editForm.value.id) {
        currentUserUpdated = true;
      }
      
      const payload = {
        user_id: editForm.value.id,
        position_id: editForm.value.position_id,
        is_head: editForm.value.is_head,
        is_deputy: editForm.value.is_deputy
      };
      console.log('Updating user:', payload);
      await axios.post(`/api/hr/departments/${editForm.value.departmentId}/update-user`, payload);
    }
    
    showEditSidebar.value = false;
    console.log('Refreshing chart...');
    await refreshChart();
    console.log('Chart refreshed');
    
    // Refresh user data if current user was updated
    if (currentUserUpdated) {
      await authStore.fetchUser();
      Swal.fire({
        icon: 'success',
        title: 'Thành công!',
        text: 'Thông tin của bạn đã được cập nhật. Quyền của bạn có thể đã thay đổi.',
        confirmButtonText: 'OK'
      });
    } else {
      Swal.fire('Thành công!', 'Đã cập nhật thành công!', 'success');
    }
  } catch (error) {
    console.error('=== ERROR SAVING ===');
    console.error('Error:', error);
    console.error('Response:', error.response?.data);
    Swal.fire('Lỗi!', error.response?.data?.message || 'Có lỗi xảy ra khi cập nhật', 'error');
  }
};

const handleDeleteNode = async () => {
  const node = currentNode.value;
  if (!node) return;
  
  const result = await Swal.fire({
    title: 'Xác nhận xóa?',
    text: 'Bạn có chắc chắn muốn xóa?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy',
    confirmButtonColor: '#dc2626'
  });
  
  if (!result.isConfirmed) return;
  
  try {
    if (node.tags?.includes('department') || node.tags?.includes('root')) {
      await axios.delete(`/api/hr/departments/${node.id.replace('dept_', '')}`);
    } else {
      await axios.post(`/api/hr/departments/${node.userData.departmentId}/remove`, {
        user_id: node.userData.userId
      });
    }
    
    showEditSidebar.value = false;
    await refreshChart();
    Swal.fire('Thành công!', 'Đã xóa thành công!', 'success');
  } catch (error) {
    console.error('Error deleting:', error);
    Swal.fire('Lỗi!', error.response?.data?.message || 'Có lỗi xảy ra khi xóa', 'error');
  }
};

const getInitials = (name) => {
  if (!name) return '??';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

onMounted(() => {
  initChart();
  loadEmployees();
  loadPositions();
});

onUnmounted(() => {
  if (chart) {
    chart.destroy();
  }
});
</script>

<style scoped>
.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
  transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from {
  transform: translateX(20px);
  opacity: 0;
}

.slide-fade-leave-to {
  transform: translateX(20px);
  opacity: 0;
}
</style>
