<template>
  <div class="flex flex-col h-full">
    <!-- Module Header (Industry Switcher) -->
    <div class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ t('quality.title') }}</h1>
          <p class="text-sm text-gray-600 mt-1">{{ t('quality.description') }}</p>
        </div>
        
        <!-- Industry Switcher -->
        <div class="flex items-center space-x-2">
          <span class="text-sm text-gray-600">{{ t('quality.industry') }}:</span>
          <select
            v-model="selectedIndustry"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="education">{{ t('quality.industry_education') }}</option>
            <option value="healthcare" disabled>{{ t('quality.industry_healthcare') }} ({{ t('quality.coming_soon') }})</option>
            <option value="retail" disabled>{{ t('quality.industry_retail') }} ({{ t('quality.coming_soon') }})</option>
            <option value="manufacturing" disabled>{{ t('quality.industry_manufacturing') }} ({{ t('quality.coming_soon') }})</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Main Content with Sidebar -->
    <div class="flex flex-1 overflow-hidden">
      <!-- Education Sidebar -->
      <div v-if="selectedIndustry === 'education'" class="w-64 bg-white border-r border-gray-200 overflow-y-auto">
        <div class="p-4 space-y-2">
          <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">
            {{ t('quality.industry_education') }}
          </div>
          
          <!-- Teachers Menu Item -->
          <button
            v-if="authStore.hasPermission('teachers.view')"
            @click="selectedModule = 'teachers'"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50 text-blue-600': selectedModule === 'teachers' }"
          >
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">{{ t('teachers.title') }}</div>
              <div class="text-xs text-gray-500">{{ t('teachers.description') }}</div>
            </div>
          </button>

          <!-- Students Menu Item -->
          <button
            @click="selectedModule = 'students'"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50 text-blue-600': selectedModule === 'students' }"
          >
            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">{{ t('quality.students') }}</div>
              <div class="text-xs text-gray-500">{{ t('quality.students_description') }}</div>
            </div>
          </button>

          <!-- Parents Menu Item -->
          <button
            @click="selectedModule = 'parents'"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50 text-blue-600': selectedModule === 'parents' }"
          >
            <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">{{ t('quality.parents') }}</div>
              <div class="text-xs text-gray-500">{{ t('quality.parents_description') }}</div>
            </div>
          </button>

          <!-- Subjects Menu Item -->
          <button
            v-if="authStore.hasPermission('subjects.view')"
            @click="selectedModule = 'subjects'"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50 text-blue-600': selectedModule === 'subjects' }"
          >
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">{{ t('subjects.title') }}</div>
              <div class="text-xs text-gray-500">{{ t('subjects.description') }}</div>
            </div>
          </button>

          <!-- Classes Management Menu Item -->
          <button
            v-if="authStore.hasPermission('classes.view')"
            @click="selectedModule = 'classes'"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50 text-blue-600': selectedModule === 'classes' }"
          >
            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">{{ t('classes.management_title') }}</div>
              <div class="text-xs text-gray-500">{{ t('classes.management_description') }}</div>
            </div>
          </button>

          <!-- Syllabus Menu Item -->
          <button
            v-if="authStore.hasPermission('lesson_plans.view') || authStore.hasPermission('syllabus.view')"
            @click="selectedModule = 'syllabus'"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50 text-blue-600': selectedModule === 'syllabus' }"
          >
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">{{ t('syllabus.module_title') }}</div>
              <div class="text-xs text-gray-500">{{ t('syllabus.module_description') }}</div>
            </div>
          </button>

          <!-- Divider -->
          <div class="my-4 border-t border-gray-200" v-if="authStore.hasPermission('quality.manage_settings')"></div>

          <!-- Settings Menu Item -->
          <router-link
            v-if="authStore.hasPermission('quality.manage_settings')"
            to="/quality/settings"
            class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-50 transition text-left"
            :class="{ 'bg-blue-50 text-blue-600': $route.path === '/quality/settings' }"
          >
            <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-gray-900">{{ t('quality.settings') }}</div>
              <div class="text-xs text-gray-500">{{ t('quality.settings_description') }}</div>
            </div>
          </router-link>
        </div>
      </div>

      <!-- Content Area -->
      <div class="flex-1 bg-gray-50 overflow-y-auto p-6">
        <!-- Education Content -->
        <div v-if="selectedIndustry === 'education'">
          <TeachersList v-if="selectedModule === 'teachers'" />
          <StudentsList v-else-if="selectedModule === 'students'" />
          <ParentsList v-else-if="selectedModule === 'parents'" />
          <SubjectsList v-else-if="selectedModule === 'subjects'" />
          <ClassesManagement v-else-if="selectedModule === 'classes'" />
          <SyllabusList v-else-if="selectedModule === 'syllabus'" />
          <div v-else class="text-center py-12">
            <p class="text-gray-500">{{ t('quality.select_module') }}</p>
          </div>
        </div>

        <!-- Other Industries Placeholder -->
        <div v-else class="flex items-center justify-center h-full">
          <div class="text-center">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
            <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ t('quality.industry_coming_soon') }}</h2>
            <p class="text-gray-600">{{ t('quality.feature_in_development') }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from '../../composables/useI18n';
import { useAuthStore } from '../../stores/auth';
import TeachersList from './TeachersList.vue';
import StudentsList from './StudentsList.vue';
import ParentsList from './ParentsList.vue';
import SubjectsList from './SubjectsList.vue';
import ClassesManagement from './ClassesManagement.vue';
import SyllabusList from './SyllabusList.vue';

const { t } = useI18n();
const authStore = useAuthStore();
const route = useRoute();

const selectedIndustry = ref('education');
const selectedModule = ref('teachers'); // Default to teachers

// Check if there's a module query parameter on mount
onMounted(() => {
  if (route.query.module) {
    selectedModule.value = route.query.module;
  }
});
</script>


