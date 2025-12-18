<template>
  <div class="flex h-screen bg-gray-50">
    <!-- Secondary Sidebar -->
    <div class="w-72 bg-white border-r border-gray-200 overflow-y-auto">
      <div class="p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ t('course.title') }}</h1>
        <p class="text-sm text-gray-600 mb-6">{{ t('course.description') }}</p>

        <div class="space-y-2">
          <!-- Classroom Board -->
          <button
            @click="selectedModule = 'classroom'"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50 text-blue-600': selectedModule === 'classroom' }"
          >
            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">{{ t('course.classroom_board') }}</div>
              <div class="text-xs text-gray-500">{{ t('course.classroom_description') }}</div>
            </div>
          </button>

          <!-- Learning Journal -->
          <button
            v-if="canViewLearningJournal"
            @click="selectedModule = 'journal'"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50 text-blue-600': selectedModule === 'journal' }"
          >
            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">Learning Journal</div>
              <div class="text-xs text-gray-500">Viết nhật ký học tập hàng ngày</div>
            </div>
          </button>

          <!-- Vocabulary Book -->
          <button
            @click="selectedModule = 'vocabulary'"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50 text-blue-600': selectedModule === 'vocabulary' }"
          >
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">📚 Vocabulary Book</div>
              <div class="text-xs text-gray-500">Sổ từ vựng cá nhân & luyện tập flashcard</div>
            </div>
          </button>

          <!-- Learning History -->
          <button
            @click="selectedModule = 'history'"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50 text-blue-600': selectedModule === 'history' }"
          >
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">{{ t('course.learning_history') }}</div>
              <div class="text-xs text-gray-500">{{ t('course.history_description') }}</div>
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto">
      <ClassroomBoard v-if="selectedModule === 'classroom'" />
      <LearningJournalPage v-else-if="selectedModule === 'journal'" />
      <VocabularyBook v-else-if="selectedModule === 'vocabulary'" />
      <LearningHistory v-else-if="selectedModule === 'history'" />
      <div v-else class="flex items-center justify-center h-full">
        <div class="text-center">
          <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
          </div>
          <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ t('course.title') }}</h2>
          <p class="text-gray-600">{{ t('common.select_module') }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from '../../composables/useI18n';
import { useAuthStore } from '../../stores/auth';
import ClassroomBoard from './ClassroomBoard.vue';
import LearningJournalPage from './LearningJournalPage.vue';
import VocabularyBook from './VocabularyBook.vue';
import LearningHistory from './LearningHistory.vue';

const { t } = useI18n();
const authStore = useAuthStore();
const selectedModule = ref('classroom');

// Check if user can view Learning Journal
const canViewLearningJournal = computed(() => {
  // Check if user has explicit permission to view learning journal
  if (authStore.hasPermission('course.view_learning_journal')) {
    return true;
  }

  // Students with course.view permission can also access
  if (authStore.hasRole('student') && authStore.hasPermission('course.view')) {
    return true;
  }

  return false;
});
</script>

