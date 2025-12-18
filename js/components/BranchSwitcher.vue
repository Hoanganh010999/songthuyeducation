<template>
  <div class="relative" ref="branchRef">
    <button
      @click="showBranches = !showBranches"
      class="flex items-center space-x-2 px-3 py-1.5 rounded-md hover:bg-gray-100 transition"
    >
      <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
      </svg>
      <span class="hidden md:block text-sm font-medium text-gray-700">
        {{ currentBranchName }}
      </span>
      <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Dropdown -->
    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="showBranches"
        class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
      >
        <div class="px-4 py-2 border-b border-gray-200">
          <p class="text-xs font-semibold text-gray-500 uppercase">Chi nhánh</p>
        </div>
        
        <div v-if="loading" class="p-4 text-center text-gray-500">
          Đang tải...
        </div>
        
        <div v-else-if="userBranches.length === 0" class="p-4 text-center text-gray-500">
          Không có chi nhánh
        </div>
        
        <div v-else class="max-h-64 overflow-y-auto">
          <button
            v-for="branch in userBranches"
            :key="branch.id"
            @click="switchBranch(branch)"
            class="w-full text-left px-4 py-2 hover:bg-gray-100 transition flex items-center justify-between"
            :class="{ 'bg-blue-50 text-blue-600': branch.id === currentBranchId }"
          >
            <div>
              <p class="text-sm font-medium">{{ branch.name }}</p>
              <p class="text-xs text-gray-500">{{ branch.role_name }}</p>
            </div>
            <svg
              v-if="branch.id === currentBranchId"
              class="w-5 h-5 text-blue-600"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useAuthStore } from '../stores/auth';
import axios from 'axios';

const authStore = useAuthStore();
const branchRef = ref(null);
const showBranches = ref(false);
const userBranches = ref([]);
const loading = ref(false);
const currentBranchId = ref(null);

const currentBranchName = computed(() => {
  const branch = userBranches.value.find(b => b.id === currentBranchId.value);
  return branch?.name || 'Chi nhánh';
});

const loadUserBranches = async () => {
  loading.value = true;
  try {
    // Check if super-admin - get all branches
    const isSuperAdmin = authStore.currentUser?.roles?.some(r => r.name === 'super-admin');
    
    if (isSuperAdmin) {
      // Super-admin sees all branches
      const response = await axios.get('/api/branches/list');
      const data = response.data.data || response.data;
      userBranches.value = Array.isArray(data) ? data : [];
    } else {
      // Regular user - get their branches
      const response = await axios.get('/api/user');
      const user = response.data.data;
      userBranches.value = user.branches || [];
    }
    
    // Set current branch from localStorage, user, or first branch
    const savedBranchId = localStorage.getItem('current_branch_id');
    if (savedBranchId && userBranches.value.some(b => b.id == savedBranchId)) {
      currentBranchId.value = parseInt(savedBranchId);
    } else if (authStore.currentUser?.current_branch_id) {
      currentBranchId.value = authStore.currentUser.current_branch_id;
    } else if (userBranches.value.length > 0) {
      currentBranchId.value = userBranches.value[0].id;
      localStorage.setItem('current_branch_id', userBranches.value[0].id);
    }
    
    // Fetch branch-specific roles
    if (currentBranchId.value) {
      await authStore.fetchBranchRoles(currentBranchId.value);
    }
  } catch (error) {
    console.error('Error loading branches:', error);
    userBranches.value = [];
  } finally {
    loading.value = false;
  }
};

const switchBranch = async (branch) => {
  try {
    currentBranchId.value = branch.id;
    showBranches.value = false;
    
    // Save to localStorage
    localStorage.setItem('current_branch_id', branch.id);
    
    // Reload page to apply new branch context
    window.location.reload();
  } catch (error) {
    console.error('Error switching branch:', error);
  }
};

const handleClickOutside = (event) => {
  if (branchRef.value && !branchRef.value.contains(event.target)) {
    showBranches.value = false;
  }
};

// Watch for auth initialization
watch(() => authStore.currentUser, (newUser) => {
  if (newUser) {
    loadUserBranches();
  }
}, { immediate: true });

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

