<template>
  <div class="max-w-7xl mx-auto p-6">
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Learning Journal</h1>
      <p class="text-gray-600">Viết nhật ký học tập hàng ngày của bạn</p>
    </div>

    <!-- Learning Journal Content -->
    <div class="bg-white rounded-lg shadow p-6">
      <LearningJournalTab :user-role="getUserRole()" />
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '../../stores/auth'
import LearningJournalTab from '../../components/classroom/LearningJournalTab.vue'

const authStore = useAuthStore()

const getUserRole = () => {
  if (authStore.hasRole('admin') || authStore.hasRole('super-admin')) {
    return 'admin'
  }
  if (authStore.hasRole('teacher')) {
    return 'teacher'
  }
  return 'student'
}
</script>
