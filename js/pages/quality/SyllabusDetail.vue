<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center h-screen">
      <div class="animate-spin h-12 w-12 border-4 border-blue-600 border-t-transparent rounded-full"></div>
    </div>

    <!-- Main Content -->
    <div v-else class="p-6 space-y-6">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <button @click="goBack" class="text-gray-600 hover:text-gray-900">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
              </svg>
            </button>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">{{ syllabus.name }}</h1>
              <p class="text-sm text-gray-600">{{ syllabus.subject?.name }} - {{ syllabus.code }}</p>
            </div>
          </div>
          <span :class="statusClass(syllabus.status)" class="px-3 py-1 text-sm rounded-full">
            {{ statusText(syllabus.status) }}
          </span>
        </div>

        <div v-if="syllabus.description" class="mt-4 text-gray-600">
          {{ syllabus.description }}
        </div>
      </div>

      <!-- Units List -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b flex items-center justify-between">
          <div>
            <h2 class="text-xl font-semibold text-gray-900">{{ t('syllabus.units_tab') }}</h2>
            <p class="text-sm text-gray-600 mt-1">{{ t('syllabus.units_management') }}</p>
          </div>
          <button 
            @click="addUnit" 
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center space-x-2 transition"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            <span>{{ t('syllabus.add_session') }}</span>
          </button>
        </div>

        <!-- Empty State -->
        <div v-if="units.length === 0" class="p-12 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <p class="mt-4 text-gray-500">{{ t('syllabus.no_units') }}</p>
          <p class="text-sm text-gray-400 mt-2">{{ t('syllabus.click_to_start') }}</p>
        </div>

        <!-- Units Cards -->
        <div v-else class="p-6 space-y-4">
          <div v-for="(unit, index) in units" :key="unit.id || index" 
               class="border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
            <div class="p-6">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center space-x-3 mb-3">
                    <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                      <span class="text-blue-600 font-semibold text-sm">{{ unit.session_number || index + 1 }}</span>
                    </div>
                    <div class="flex-1">
                      <div class="flex items-center justify-between">
                        <div>
                          <h3 class="text-lg font-semibold text-gray-900">
                            Unit {{ unit.session_number || index + 1 }}
                          </h3>
                          <p class="text-sm text-gray-600 mt-0.5">{{ unit.lesson_title || t('syllabus.no_title') }}</p>
                          <div v-if="unit.duration_minutes" class="text-sm text-gray-500 flex items-center space-x-1 mt-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>{{ unit.duration_minutes }} {{ t('syllabus.minutes') }}</span>
                          </div>
                        </div>
                        <!-- Folder Badges -->
                        <div class="flex flex-wrap gap-2">
                          <!-- Lesson Plans Folder -->
                          <a 
                            v-if="unit.lesson_plans_folder_id" 
                            :href="`https://drive.google.com/drive/folders/${unit.lesson_plans_folder_id}`" 
                            target="_blank"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition cursor-pointer"
                            :title="t('syllabus.open_lesson_plans')"
                          >
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                            {{ t('syllabus.lesson_plans_badge') }}
                          </a>
                          
                          <!-- Materials Folder -->
                          <a 
                            v-if="unit.materials_folder_id" 
                            :href="`https://drive.google.com/drive/folders/${unit.materials_folder_id}`" 
                            target="_blank"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200 transition cursor-pointer"
                            :title="t('syllabus.open_materials')"
                          >
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            {{ t('syllabus.materials_badge') }}
                          </a>
                          
                          <!-- Homework Folder -->
                          <a 
                            v-if="unit.homework_folder_id" 
                            :href="`https://drive.google.com/drive/folders/${unit.homework_folder_id}`" 
                            target="_blank"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 hover:bg-purple-200 transition cursor-pointer"
                            :title="t('syllabus.open_homework')"
                          >
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            {{ t('syllabus.homework_badge') }}
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Content preview - 3 columns -->
                  <div v-if="unit.lesson_objectives || unit.lesson_content || unit.notes" class="mt-3 grid grid-cols-3 gap-4">
                    <div v-if="unit.lesson_objectives" class="text-sm border-l-2 border-blue-400 pl-3">
                      <span class="font-semibold text-gray-700 block mb-1">{{ t('syllabus.objectives_label') }}</span>
                      <div class="prose-sm text-gray-600 line-clamp-3" v-html="unit.lesson_objectives"></div>
                    </div>
                    <div v-if="unit.lesson_content" class="text-sm border-l-2 border-green-400 pl-3">
                      <span class="font-semibold text-gray-700 block mb-1">{{ t('syllabus.content_label') }}</span>
                      <div class="prose-sm text-gray-600 line-clamp-3" v-html="unit.lesson_content"></div>
                    </div>
                    <div v-if="unit.notes" class="text-sm border-l-2 border-yellow-400 pl-3">
                      <span class="font-semibold text-gray-700 block mb-1">{{ t('syllabus.notes_label') }}</span>
                      <p class="text-gray-600 line-clamp-3">{{ unit.notes }}</p>
                    </div>
                  </div>

                  <!-- Actions -->
                  <div class="mt-4 flex items-center justify-between">
                    <div class="flex flex-wrap gap-2">
                      <!-- Evaluation Form Button -->
                      <button
                        v-if="unit.id"
                        @click="unit.valuation_form_id ? editEvaluationForm(unit) : createEvaluationForm(unit)"
                        :class="[
                          'px-3 py-1 rounded-lg text-xs font-medium flex items-center space-x-1 transition',
                          unit.valuation_form_id 
                            ? 'bg-green-100 text-green-700 hover:bg-green-200' 
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        <span>{{ unit.valuation_form_id ? t('syllabus.edit_evaluation_btn') : t('syllabus.create_evaluation_btn') }}</span>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex-shrink-0 ml-4 flex space-x-2">
                  <button
                    @click="editUnit(unit)"
                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                    :title="t('syllabus.btn_edit')"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                  </button>
                  <button
                    @click="deleteUnit(unit)"
                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition"
                    :title="t('syllabus.btn_delete')"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Lesson Session Editor Modal -->
    <div v-if="showSessionEditor" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="w-full max-w-5xl max-h-[95vh] overflow-y-auto">
        <LessonSessionEditor 
          :session="editingSession"
          :syllabusId="syllabus.id"
          :syllabusName="syllabus.name"
          :syllabusFolderId="syllabus.google_drive_folder_id"
          @save="onSessionSaved"
          @close="closeSessionEditor"
        />
      </div>
    </div>

    <!-- Evaluation Form Builder Modal -->
    <div v-if="showEvaluationBuilder" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg w-full max-w-5xl max-h-[95vh] overflow-hidden flex flex-col">
        <EvaluationFormBuilder 
          :unit="currentUnit"
          :valuation-form="currentEvaluationForm"
          @saved="onEvaluationFormSaved" 
          @cancel="closeEvaluationBuilder" 
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';
import Swal from 'sweetalert2';
import LessonSessionEditor from './LessonSessionEditor.vue';
import EvaluationFormBuilder from './EvaluationFormBuilder.vue';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const loading = ref(true);
const syllabus = ref({});
const units = ref([]);
const showSessionEditor = ref(false);
const editingSession = ref({});
const showEvaluationBuilder = ref(false);
const currentUnit = ref(null);
const currentEvaluationForm = ref(null);

const unitFolders = ref([]);

const loadSyllabus = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`/api/lesson-plans/${route.params.id}`);
    syllabus.value = response.data.data;
    unitFolders.value = response.data.unit_folders || [];
    
    // Sort sessions by session_number in ascending order
    units.value = (response.data.data.sessions || []).sort((a, b) => 
      (a.session_number || 0) - (b.session_number || 0)
    );
    
    // Map folder IDs to units based on session_number
    units.value = units.value.map(unit => {
      const unitFolder = unitFolders.value.find(f => f.unit_number === unit.session_number);
      return {
        ...unit,
        materials_folder_id: unitFolder?.materials_folder_id,
        homework_folder_id: unitFolder?.homework_folder_id,
        lesson_plans_folder_id: unitFolder?.lesson_plans_folder_id,
      };
    });
  } catch (error) {
    console.error('Load syllabus error:', error);
    Swal.fire(t('common.error'), t('syllabus.error_load'), 'error');
  } finally {
    loading.value = false;
  }
};

const addUnit = () => {
  // Calculate next session number based on the maximum existing session number
  const maxSessionNumber = units.value.length > 0 
    ? Math.max(...units.value.map(u => u.session_number || 0))
    : 0;
  
  editingSession.value = {
    lesson_plan_id: syllabus.value.id,
    session_number: maxSessionNumber + 1,
    lesson_title: '',
    lesson_plan_url: '',
    materials_url: '',
    homework_url: '',
    lesson_objectives: '',
    lesson_content: '',
    duration_minutes: 45,
    notes: '',
    valuation_form_id: null
  };
  showSessionEditor.value = true;
};

const editUnit = (unit) => {
  editingSession.value = { ...unit };
  showSessionEditor.value = true;
};

const closeSessionEditor = () => {
  showSessionEditor.value = false;
  editingSession.value = {};
};

const onSessionSaved = async (sessionData) => {
  try {
    // Check if we have the original session ID from editingSession
    const sessionId = editingSession.value.id || sessionData.id;
    
    if (sessionId) {
      // Update existing
      await axios.put(`/api/lesson-plans/${syllabus.value.id}/sessions/${sessionId}`, sessionData);
      await Swal.fire(t('common.success'), t('syllabus.session_updated_success'), 'success');
    } else {
      // Create new
      const response = await axios.post(`/api/lesson-plans/${syllabus.value.id}/sessions`, sessionData);
      await Swal.fire(t('common.success'), t('syllabus.session_created_success'), 'success');
    }
    closeSessionEditor();
    await loadSyllabus();
  } catch (error) {
    console.error('Save session error:', error);
    Swal.fire(t('common.error'), error.response?.data?.message || t('syllabus.session_save_error'), 'error');
  }
};

const deleteUnit = async (unit) => {
  const result = await Swal.fire({
    title: t('syllabus.confirm_delete_unit'),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: t('common.delete'),
    cancelButtonText: t('common.cancel')
  });

  if (!result.isConfirmed) return;

  try {
    if (unit.id) {
      await axios.delete(`/api/lesson-plans/${syllabus.value.id}/sessions/${unit.id}`);
    }
    units.value = units.value.filter(u => u !== unit);
    await Swal.fire(t('common.success'), t('syllabus.unit_deleted'), 'success');
  } catch (error) {
    console.error('Delete unit error:', error);
    Swal.fire(t('common.error'), t('syllabus.error_delete'), 'error');
  }
};

const createEvaluationForm = async (unit) => {
  if (!unit.id) {
    await saveUnit(unit);
  }
  currentUnit.value = unit;
  currentEvaluationForm.value = null;
  showEvaluationBuilder.value = true;
};

const editEvaluationForm = async (unit) => {
  try {
    const response = await axios.get(`/api/valuation-forms/${unit.valuation_form_id}`);
    currentUnit.value = unit;
    currentEvaluationForm.value = response.data.data;
    showEvaluationBuilder.value = true;
  } catch (error) {
    console.error('Load evaluation form error:', error);
    Swal.fire(t('common.error'), 'Cannot load evaluation form', 'error');
  }
};

const closeEvaluationBuilder = () => {
  showEvaluationBuilder.value = false;
  currentUnit.value = null;
  currentEvaluationForm.value = null;
};

const onEvaluationFormSaved = async (formId) => {
  if (currentUnit.value) {
    try {
      // Update the session with the valuation form ID
      await axios.put(`/api/lesson-plans/${syllabus.value.id}/sessions/${currentUnit.value.id}`, {
        ...currentUnit.value,
        valuation_form_id: formId
      });
      
      Swal.fire(t('common.success'), t('syllabus.evaluation_linked_success'), 'success');
    } catch (error) {
      console.error('Update session error:', error);
      Swal.fire(t('common.error'), error.response?.data?.message || t('syllabus.session_update_error'), 'error');
    }
  }
  closeEvaluationBuilder();
  await loadSyllabus();
};

const goBack = () => {
  router.push({ path: '/quality', query: { module: 'syllabus' } });
};

const statusClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    approved: 'bg-blue-100 text-blue-800',
    active: 'bg-green-100 text-green-800',
    in_use: 'bg-green-100 text-green-800',
    archived: 'bg-gray-100 text-gray-800'
  };
  return classes[status] || classes.draft;
};

const statusText = (status) => {
  const texts = {
    draft: t('syllabus.status_draft'),
    approved: t('syllabus.status_approved'),
    active: t('syllabus.status_approved'),
    in_use: t('syllabus.status_in_use'),
    archived: t('syllabus.status_archived')
  };
  return texts[status] || status;
};

onMounted(() => {
  loadSyllabus();
});
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Prose styling for HTML content */
.prose-sm :deep(p) {
  margin-bottom: 0.5em;
}

.prose-sm :deep(ul), .prose-sm :deep(ol) {
  padding-left: 1.5em;
  margin-bottom: 0.5em;
}

.prose-sm :deep(ul) {
  list-style-type: disc;
}

.prose-sm :deep(ol) {
  list-style-type: decimal;
}

.prose-sm :deep(li) {
  margin-bottom: 0.25em;
}

.prose-sm :deep(strong) {
  font-weight: 600;
}

.prose-sm :deep(em) {
  font-style: italic;
}

.prose-sm :deep(h1), .prose-sm :deep(h2), .prose-sm :deep(h3), .prose-sm :deep(h4) {
  font-weight: 600;
  margin-top: 0.5em;
  margin-bottom: 0.25em;
}

.prose-sm :deep(blockquote) {
  border-left: 3px solid #e5e7eb;
  padding-left: 1em;
  font-style: italic;
  color: #6b7280;
}
</style>

