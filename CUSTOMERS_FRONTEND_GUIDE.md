# 🎨 CUSTOMERS MODULE - FRONTEND IMPLEMENTATION GUIDE

## ✅ Translations Đã Hoàn Tất

Đã seed 60+ translations cho customers module (EN + VI).

---

## 📝 Files Cần Tạo

### 1. Router Configuration

**File:** `resources/js/router/index.js`

```javascript
// Thêm import
import CustomersList from '../pages/customers/CustomersList.vue';
import CustomersKanban from '../pages/customers/CustomersKanban.vue';

// Thêm routes
{
    path: 'customers',
    name: 'customers.list',
    component: CustomersList,
    meta: { permission: 'customers.view' }
},
{
    path: 'customers/kanban',
    name: 'customers.kanban',
    component: CustomersKanban,
    meta: { permission: 'customers.view' }
},
```

---

### 2. Sidebar Navigation

**File:** `resources/js/layouts/DashboardLayout.vue`

```vue
<!-- Thêm sau Branches -->
<router-link
  v-if="authStore.hasPermission('customers.view')"
  to="/customers"
  class="flex items-center space-x-3 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition"
  :class="{ 'bg-gray-100 text-blue-600': $route.path.startsWith('/customers') }"
>
  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
  </svg>
  <span class="font-medium">{{ t('customers.title') }}</span>
</router-link>
```

---

### 3. CustomersList.vue (Simplified)

**File:** `resources/js/pages/customers/CustomersList.vue`

```vue
<template>
  <div>
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ t('customers.list') }}</h1>
        <div class="flex items-center space-x-2 mt-2">
          <router-link to="/customers" class="text-blue-600 font-medium">
            {{ t('customers.list_view') }}
          </router-link>
          <span class="text-gray-400">|</span>
          <router-link to="/customers/kanban" class="text-gray-600 hover:text-blue-600">
            {{ t('customers.kanban_view') }}
          </router-link>
        </div>
      </div>
      <button
        v-if="authStore.hasPermission('customers.create')"
        @click="showCreateModal = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
      >
        {{ t('customers.create') }}
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input
          v-model="filters.search"
          @input="debouncedSearch"
          type="text"
          :placeholder="t('common.search')"
          class="px-4 py-2 border rounded-lg"
        />
        <select v-model="filters.stage" @change="loadCustomers(1)" class="px-4 py-2 border rounded-lg">
          <option value="">{{ t('customers.all_stages') }}</option>
          <option value="lead">{{ t('customers.stage_lead') }}</option>
          <option value="contacted">{{ t('customers.stage_contacted') }}</option>
          <!-- ... other stages ... -->
        </select>
        <select v-model="filters.branch_id" @change="loadCustomers(1)" class="px-4 py-2 border rounded-lg">
          <option value="">{{ t('customers.all_branches') }}</option>
          <option v-for="branch in branches" :key="branch.id" :value="branch.id">
            {{ branch.name }}
          </option>
        </select>
        <button @click="resetFilters" class="px-4 py-2 border rounded-lg hover:bg-gray-50">
          {{ t('common.reset') }}
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ t('customers.code') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ t('customers.name') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ t('customers.phone') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ t('customers.stage') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ t('customers.branch') }}
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
              {{ t('common.actions') }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="customer in customers" :key="customer.id" class="hover:bg-gray-50">
            <td class="px-6 py-4">
              <code class="text-sm font-mono bg-gray-100 px-2 py-1 rounded">
                {{ customer.code }}
              </code>
            </td>
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">{{ customer.name }}</div>
              <div class="text-sm text-gray-500">{{ customer.email }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ customer.phone }}</td>
            <td class="px-6 py-4">
              <span :class="getStageClass(customer.stage)" class="px-3 py-1 text-xs font-semibold rounded-full">
                {{ t(`customers.stage_${customer.stage}`) }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ customer.branch?.name }}</td>
            <td class="px-6 py-4 text-right">
              <button @click="editCustomer(customer)" class="text-blue-600 hover:text-blue-900">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Customer Modal -->
    <CustomerModal
      :show="showCreateModal || showEditModal"
      :customer="selectedCustomer"
      :is-edit="showEditModal"
      @close="closeModal"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useI18n } from '../../composables/useI18n';
import api from '../../services/api';
import CustomerModal from '../../components/customers/CustomerModal.vue';

const authStore = useAuthStore();
const { t } = useI18n();

const customers = ref([]);
const branches = ref([]);
const loading = ref(false);
const filters = ref({ search: '', stage: '', branch_id: '' });
const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedCustomer = ref(null);

const getStageClass = (stage) => {
  const classes = {
    lead: 'bg-gray-100 text-gray-800',
    contacted: 'bg-blue-100 text-blue-800',
    qualified: 'bg-green-100 text-green-800',
    proposal: 'bg-yellow-100 text-yellow-800',
    negotiation: 'bg-orange-100 text-orange-800',
    closed_won: 'bg-emerald-100 text-emerald-800',
    closed_lost: 'bg-red-100 text-red-800',
  };
  return classes[stage] || 'bg-gray-100 text-gray-800';
};

const loadCustomers = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      per_page: 15,
      search: filters.value.search || undefined,
      stage: filters.value.stage || undefined,
      branch_id: filters.value.branch_id || undefined,
    };
    const response = await api.get('/api/customers', { params });
    if (response.data.success) {
      customers.value = response.data.data.data;
    }
  } catch (error) {
    console.error('Failed to load customers:', error);
  } finally {
    loading.value = false;
  }
};

const loadBranches = async () => {
  try {
    const response = await api.get('/api/branches/list');
    if (response.data.success) {
      branches.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load branches:', error);
  }
};

let searchTimeout;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => loadCustomers(1), 500);
};

const resetFilters = () => {
  filters.value = { search: '', stage: '', branch_id: '' };
  loadCustomers(1);
};

const editCustomer = (customer) => {
  selectedCustomer.value = customer;
  showEditModal.value = true;
};

const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  selectedCustomer.value = null;
};

const handleSaved = () => {
  closeModal();
  loadCustomers();
};

onMounted(() => {
  loadCustomers();
  loadBranches();
});
</script>
```

---

### 4. CustomerModal.vue (Key Logic)

**File:** `resources/js/components/customers/CustomerModal.vue`

**Key Feature: Branch Logic**
```vue
<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
      <form @submit.prevent="saveCustomer" class="p-6 space-y-6">
        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ t('customers.name') }} <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.name"
            type="text"
            :placeholder="t('customers.name_placeholder')"
            class="w-full px-4 py-2 border rounded-lg"
            required
          />
        </div>

        <!-- Phone & Email -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.phone') }}
            </label>
            <input v-model="form.phone" type="tel" class="w-full px-4 py-2 border rounded-lg" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t('customers.email') }}
            </label>
            <input v-model="form.email" type="email" class="w-full px-4 py-2 border rounded-lg" />
          </div>
        </div>

        <!-- Branch Selection - LOGIC QUAN TRỌNG -->
        <div v-if="authStore.isSuperAdmin">
          <!-- Super-admin: PHẢI chọn branch -->
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ t('customers.branch') }} <span class="text-red-500">*</span>
          </label>
          <select v-model="form.branch_id" class="w-full px-4 py-2 border rounded-lg" required>
            <option value="">{{ t('customers.branch_placeholder') }}</option>
            <option v-for="branch in branches" :key="branch.id" :value="branch.id">
              {{ branch.name }}
            </option>
          </select>
        </div>
        <div v-else>
          <!-- User thường: Hiển thị branch (read-only) -->
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ t('customers.branch') }}
          </label>
          <input
            :value="primaryBranch?.name || t('customers.no_branch_error')"
            type="text"
            class="w-full px-4 py-2 border rounded-lg bg-gray-100"
            disabled
          />
          <p class="text-xs text-gray-500 mt-1">{{ t('customers.branch_auto') }}</p>
        </div>

        <!-- Assigned To -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ t('customers.assigned_to') }}
          </label>
          <select v-model="form.assigned_to" class="w-full px-4 py-2 border rounded-lg">
            <option value="">{{ t('customers.assigned_to_placeholder') }}</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }}
            </option>
          </select>
        </div>

        <!-- Estimated Value -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ t('customers.estimated_value') }}
          </label>
          <input
            v-model="form.estimated_value"
            type="number"
            :placeholder="t('customers.estimated_value_placeholder')"
            class="w-full px-4 py-2 border rounded-lg"
          />
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ t('customers.notes') }}
          </label>
          <textarea
            v-model="form.notes"
            rows="3"
            :placeholder="t('customers.notes_placeholder')"
            class="w-full px-4 py-2 border rounded-lg"
          ></textarea>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3 pt-4 border-t">
          <button type="button" @click="close" class="px-4 py-2 border rounded-lg">
            {{ t('common.cancel') }}
          </button>
          <button type="submit" :disabled="loading" class="px-4 py-2 bg-blue-600 text-white rounded-lg">
            {{ loading ? t('common.saving') : t('common.save') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useI18n } from '../../composables/useI18n';
import api from '../../services/api';

const props = defineProps({
  show: Boolean,
  customer: Object,
  isEdit: Boolean,
});

const emit = defineEmits(['close', 'saved']);
const authStore = useAuthStore();
const { t } = useI18n();

const form = ref({
  name: '',
  phone: '',
  email: '',
  branch_id: '',
  assigned_to: '',
  estimated_value: '',
  notes: '',
});

const branches = ref([]);
const users = ref([]);
const loading = ref(false);

// Get primary branch của user
const primaryBranch = computed(() => {
  if (!authStore.currentUser) return null;
  return authStore.currentUser.branches?.find(b => b.pivot?.is_primary);
});

const loadBranches = async () => {
  try {
    const response = await api.get('/api/branches/list');
    if (response.data.success) {
      branches.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load branches:', error);
  }
};

const saveCustomer = async () => {
  loading.value = true;
  try {
    // Nếu không phải super-admin, không gửi branch_id (backend tự động lấy)
    const payload = { ...form.value };
    if (!authStore.isSuperAdmin) {
      delete payload.branch_id;
    }

    let response;
    if (props.isEdit) {
      response = await api.put(`/api/customers/${props.customer.id}`, payload);
    } else {
      response = await api.post('/api/customers', payload);
    }

    if (response.data.success) {
      alert(response.data.message);
      emit('saved');
    }
  } catch (error) {
    console.error('Save error:', error);
    alert(error.response?.data?.message || 'Có lỗi xảy ra');
  } finally {
    loading.value = false;
  }
};

const close = () => emit('close');

watch(() => props.show, (newVal) => {
  if (newVal) {
    if (props.isEdit && props.customer) {
      form.value = { ...props.customer };
    } else {
      form.value = {
        name: '',
        phone: '',
        email: '',
        branch_id: '',
        assigned_to: '',
        estimated_value: '',
        notes: '',
      };
    }
  }
});

onMounted(() => {
  loadBranches();
});
</script>
```

---

### 5. CustomersKanban.vue (Simplified)

**File:** `resources/js/pages/customers/CustomersKanban.vue`

```vue
<template>
  <div>
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ t('customers.kanban') }}</h1>
        <div class="flex items-center space-x-2 mt-2">
          <router-link to="/customers" class="text-gray-600 hover:text-blue-600">
            {{ t('customers.list_view') }}
          </router-link>
          <span class="text-gray-400">|</span>
          <router-link to="/customers/kanban" class="text-blue-600 font-medium">
            {{ t('customers.kanban_view') }}
          </router-link>
        </div>
      </div>
      <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white rounded-lg">
        {{ t('customers.create') }}
      </button>
    </div>

    <!-- Kanban Board -->
    <div class="flex space-x-4 overflow-x-auto pb-4">
      <div
        v-for="(stageData, stageKey) in kanbanData"
        :key="stageKey"
        class="flex-shrink-0 w-80 bg-gray-50 rounded-lg p-4"
      >
        <!-- Stage Header -->
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-gray-900">
            {{ t(`customers.stage_${stageKey}`) }}
          </h3>
          <span class="px-2 py-1 text-xs font-semibold bg-gray-200 text-gray-700 rounded-full">
            {{ stageData.count }}
          </span>
        </div>

        <!-- Customer Cards -->
        <div class="space-y-3">
          <div
            v-for="customer in stageData.customers"
            :key="customer.id"
            class="bg-white rounded-lg p-4 shadow-sm border border-gray-200 cursor-pointer hover:shadow-md transition"
            @click="viewCustomer(customer)"
          >
            <h4 class="font-medium text-gray-900">{{ customer.name }}</h4>
            <p class="text-sm text-gray-600 mt-1">{{ customer.phone }}</p>
            <div class="flex items-center justify-between mt-3">
              <span class="text-xs text-gray-500">{{ customer.branch?.name }}</span>
              <span v-if="customer.estimated_value" class="text-sm font-semibold text-green-600">
                {{ formatCurrency(customer.estimated_value) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Customer Modal -->
    <CustomerModal
      :show="showCreateModal || showEditModal"
      :customer="selectedCustomer"
      :is-edit="showEditModal"
      @close="closeModal"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from '../../composables/useI18n';
import api from '../../services/api';
import CustomerModal from '../../components/customers/CustomerModal.vue';

const { t } = useI18n();

const kanbanData = ref({});
const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedCustomer = ref(null);

const loadKanban = async () => {
  try {
    const response = await api.get('/api/customers/kanban');
    if (response.data.success) {
      kanbanData.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load kanban:', error);
  }
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND'
  }).format(value);
};

const viewCustomer = (customer) => {
  selectedCustomer.value = customer;
  showEditModal.value = true;
};

const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  selectedCustomer.value = null;
};

const handleSaved = () => {
  closeModal();
  loadKanban();
};

onMounted(() => {
  loadKanban();
});
</script>
```

---

## 🚀 Quick Implementation Steps

### Step 1: Tạo Files (Copy code từ guide trên)
```
resources/js/pages/customers/CustomersList.vue
resources/js/pages/customers/CustomersKanban.vue
resources/js/components/customers/CustomerModal.vue
```

### Step 2: Update Router
```javascript
// resources/js/router/index.js
import CustomersList from '../pages/customers/CustomersList.vue';
import CustomersKanban from '../pages/customers/CustomersKanban.vue';

// Add routes...
```

### Step 3: Update Sidebar
```vue
<!-- resources/js/layouts/DashboardLayout.vue -->
<!-- Add Customers link after Branches -->
```

### Step 4: Build
```bash
npm run build
```

### Step 5: Test
```
1. Reload: Ctrl + Shift + R
2. Login: admin@example.com
3. Click "Khách Hàng" in sidebar
4. Test create customer:
   - Super-admin: Chọn branch
   - User thường: Auto branch
```

---

## ✅ Checklist

- [x] Translations seeded (60+ keys)
- [ ] CustomersList.vue created
- [ ] CustomersKanban.vue created
- [ ] CustomerModal.vue created (with branch logic)
- [ ] Router updated
- [ ] Sidebar updated
- [ ] Build successful
- [ ] Test create/edit/view

---

**Follow guide này để hoàn thiện frontend!** 🎨

