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
        <draggable 
          v-else
          v-model="units"
          item-key="id"
          @end="onDragEnd"
          class="p-6 space-y-4"
          handle=".drag-handle"
          :animation="200"
        >
          <template #item="{element: unit, index}">
            <div 
               class="border border-gray-200 rounded-lg hover:shadow-md transition-shadow"
               :class="{'bg-blue-50': isDragging}"
            >
            <div class="p-6">
              <div class="flex items-start justify-between">
                <!-- Drag Handle -->
                <div class="drag-handle cursor-move mr-3 text-gray-400 hover:text-gray-600 flex-shrink-0" title="Drag to reorder">
                  <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z"/>
                  </svg>
                </div>
                
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

                  <!-- Content preview - 3 columns: AIMS | LANGUAGE | NOTES -->
                  <div v-if="unit.communicative_outcome || unit.linguistic_aim || unit.language_area || unit.context || unit.notes || unit.framework_shape" class="mt-3 grid grid-cols-3 gap-4">
                    <!-- Column 1: AIMS -->
                    <div v-if="unit.communicative_outcome || unit.linguistic_aim || unit.productive_subskills_focus || unit.receptive_subskills_focus" class="text-sm border-l-2 border-blue-400 pl-3">
                      <span class="font-semibold text-gray-700 block mb-1">AIMS</span>
                      <div v-if="unit.communicative_outcome" class="text-xs text-gray-600 mb-1">
                        <strong>Communicative:</strong> <span class="line-clamp-2">{{ unit.communicative_outcome }}</span>
                      </div>
                      <div v-if="unit.linguistic_aim" class="text-xs text-gray-600 mb-1">
                        <strong>Linguistic:</strong> <span class="line-clamp-2">{{ unit.linguistic_aim }}</span>
                      </div>
                      <div v-if="unit.productive_subskills_focus" class="text-xs text-gray-500 mb-1">
                        <strong>Productive:</strong> <span class="line-clamp-1">{{ unit.productive_subskills_focus }}</span>
                      </div>
                      <div v-if="unit.receptive_subskills_focus" class="text-xs text-gray-500">
                        <strong>Receptive:</strong> <span class="line-clamp-1">{{ unit.receptive_subskills_focus }}</span>
                      </div>
                    </div>

                    <!-- Column 2: LANGUAGE -->
                    <div v-if="unit.language_area || unit.examples_of_language || unit.context" class="text-sm border-l-2 border-green-400 pl-3">
                      <span class="font-semibold text-gray-700 block mb-1">LANGUAGE</span>
                      <div v-if="unit.language_area" class="text-xs text-gray-600 mb-1">
                        <strong>Area:</strong> <span class="line-clamp-1">{{ unit.language_area }}</span>
                      </div>
                      <div v-if="unit.examples_of_language" class="text-xs text-gray-600 mb-1">
                        <strong>Examples:</strong> <span class="line-clamp-2">{{ unit.examples_of_language }}</span>
                      </div>
                      <div v-if="unit.context" class="text-xs text-gray-500">
                        <strong>Context:</strong> <span class="line-clamp-1">{{ unit.context }}</span>
                      </div>
                    </div>

                    <!-- Column 3: NOTES -->
                    <div v-if="unit.notes || unit.framework_shape || unit.teaching_aspects_to_improve" class="text-sm border-l-2 border-yellow-400 pl-3">
                      <span class="font-semibold text-gray-700 block mb-1">NOTES</span>
                      <div v-if="unit.framework_shape" class="text-xs text-gray-600 mb-1">
                        <strong>Framework:</strong> <span class="line-clamp-1">{{ unit.framework_shape }}</span>
                      </div>
                      <p v-if="unit.notes" class="text-xs text-gray-600 line-clamp-2 mb-1">{{ unit.notes }}</p>
                      <div v-if="unit.teaching_aspects_to_improve" class="text-xs text-gray-500">
                        <strong>Personal:</strong> <span class="line-clamp-1">{{ unit.teaching_aspects_to_improve }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Action Buttons -->
                  <div class="mt-4 flex flex-wrap gap-2">
                    <!-- Create Homework Button -->
                    <button
                      v-if="unit.id"
                      @click="createHomework(unit)"
                      class="px-3 py-1.5 rounded-lg text-xs font-medium flex items-center space-x-1.5 transition bg-purple-100 text-purple-700 hover:bg-purple-200"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                      </svg>
                      <span>{{ t('syllabus.create_homework_btn') || 'Create Homework' }}</span>
                    </button>

                    <!-- Create Lesson Plan Button -->
                    <button
                      v-if="unit.id"
                      @click="createLessonPlan(unit)"
                      class="px-3 py-1.5 rounded-lg text-xs font-medium flex items-center space-x-1.5 transition bg-blue-100 text-blue-700 hover:bg-blue-200"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                      </svg>
                      <span>{{ t('syllabus.create_lesson_plan_btn') || 'Create Lesson Plan' }}</span>
                    </button>

                    <!-- Manage Materials Button -->
                    <button
                      v-if="unit.id"
                      @click="manageMaterials(unit)"
                      class="px-3 py-1.5 rounded-lg text-xs font-medium flex items-center space-x-1.5 transition bg-teal-100 text-teal-700 hover:bg-teal-200"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                      </svg>
                      <span>{{ t('syllabus.manage_materials_btn') || 'Manage Materials' }}</span>
                    </button>

                    <!-- Create/Edit Evaluation Form Button -->
                    <button
                      v-if="unit.id"
                      @click="unit.valuation_form_id ? editEvaluationForm(unit) : createEvaluationForm(unit)"
                      :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-medium flex items-center space-x-1.5 transition',
                        unit.valuation_form_id
                          ? 'bg-green-100 text-green-700 hover:bg-green-200'
                          : 'bg-orange-100 text-orange-700 hover:bg-orange-200'
                      ]"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                      </svg>
                      <span>{{ unit.valuation_form_id ? t('syllabus.edit_evaluation_btn') : t('syllabus.create_evaluation_btn') }}</span>
                    </button>
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
          </template>
        </draggable>
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
          :subjectId="syllabus.subject_id"
          :subject="syllabus.subject"
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

    <!-- Homework Management Modal -->
    <div v-if="showHomeworkManagement" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="bg-purple-600 text-white px-6 py-4 flex items-center justify-between">
          <div>
            <h2 class="text-xl font-semibold">Quản lý Homework</h2>
            <p class="text-sm text-purple-100 mt-1">Unit {{ currentUnit?.session_number }}: {{ currentUnit?.lesson_title }}</p>
          </div>
          <button @click="closeHomeworkManagement" class="text-white hover:bg-purple-700 rounded-lg p-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6">
          <!-- Create New Homework Button -->
          <div class="mb-6">
            <button
              @click="openHomeworkBuilder"
              class="w-full px-4 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 flex items-center justify-center space-x-2 shadow-md"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
              </svg>
              <span>Tạo Homework Mới</span>
            </button>
          </div>

          <!-- Homework List -->
          <div v-if="currentUnit?.homework && currentUnit.homework.length > 0">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Homework Templates ({{ currentUnit.homework.length }})</h3>
            <div class="space-y-3">
              <div
                v-for="homework in currentUnit.homework"
                :key="homework.id"
                class="bg-purple-50 border-2 border-purple-200 rounded-lg p-4 hover:border-purple-400 transition"
              >
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <div class="flex items-center space-x-2 mb-2">
                      <h4 class="font-semibold text-gray-900">{{ homework.title }}</h4>
                      <span class="px-2 py-0.5 text-xs bg-purple-100 text-purple-700 rounded-full">{{ homework.status }}</span>
                    </div>
                    <p v-if="homework.description" class="text-sm text-gray-600 mb-2">{{ homework.description }}</p>
                    <div class="flex items-center space-x-4 text-xs text-gray-500">
                      <span v-if="homework.exercises && homework.exercises.length" class="flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>{{ homework.exercises.length }} câu hỏi</span>
                      </span>
                      <span v-if="homework.deadline" class="flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>{{ new Date(homework.deadline).toLocaleDateString('vi-VN') }}</span>
                      </span>
                    </div>
                  </div>
                  <div class="flex items-center space-x-2 ml-4">
                    <button
                      @click="viewHomeworkDetail(homework)"
                      class="px-3 py-1.5 text-sm bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center space-x-1"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                      </svg>
                      <span>Xem</span>
                    </button>
                    <button
                      @click="editHomework(homework)"
                      class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center space-x-1"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                      </svg>
                      <span>Sửa</span>
                    </button>
                    <button
                      @click="deleteHomework(homework, currentUnit)"
                      class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg"
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
          <div v-else class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="mt-2 text-sm text-gray-600">Chưa có homework template nào</p>
            <p class="text-xs text-gray-500">Nhấn "Tạo Homework Mới" để bắt đầu</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="border-t px-6 py-4 bg-gray-50 flex justify-end">
          <button
            @click="closeHomeworkManagement"
            class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100"
          >
            Đóng
          </button>
        </div>
      </div>
    </div>

    <!-- Homework Exercise Builder Modal -->
    <HomeworkExerciseBuilder
      v-if="showHomeworkBuilder"
      :session="currentUnit"
      :classId="null"
      :subjectId="syllabus?.subject_id"
      :subject="syllabus?.subject"
      :editingHomework="editingHomework"
      @close="closeHomeworkBuilder"
      @saved="onHomeworkSaved"
    />

    <!-- Homework Detail View Modal -->
    <div v-if="viewingHomework" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="bg-purple-600 text-white px-6 py-4 flex items-center justify-between">
          <div class="flex-1">
            <h2 class="text-xl font-semibold">{{ viewingHomework.title }}</h2>
            <div class="flex items-center space-x-4 mt-2 text-sm text-purple-100">
              <span v-if="viewingHomework.deadline">
                📅 Deadline: {{ new Date(viewingHomework.deadline).toLocaleString('vi-VN') }}
              </span>
              <span v-if="viewingHomework.exercises">
                📝 {{ viewingHomework.exercises.length }} câu hỏi
              </span>
              <span class="px-2 py-0.5 bg-purple-500 rounded-full text-xs">
                {{ viewingHomework.status }}
              </span>
            </div>
          </div>
          <button @click="viewingHomework = null" class="text-white hover:bg-purple-700 rounded-lg p-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6">
          <!-- Description -->
          <div v-if="viewingHomework.description" class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <p class="text-sm font-medium text-gray-700">{{ viewingHomework.description }}</p>
          </div>

          <!-- Exercises List -->
          <div v-if="viewingHomework.exercises && viewingHomework.exercises.length > 0">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Danh sách câu hỏi ({{ viewingHomework.exercises.length }})</h3>
            <div class="space-y-3">
              <div
                v-for="(exercise, idx) in viewingHomework.exercises"
                :key="exercise.id"
                class="bg-white border-2 border-gray-200 rounded-lg p-4 hover:border-purple-300 transition cursor-pointer"
                @click="previewExercise(exercise)"
              >
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <div class="flex items-center space-x-2 mb-2">
                      <span class="font-semibold text-gray-700">Câu {{ idx + 1 }}</span>
                      <span class="px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded">{{ exercise.type }}</span>
                      <span class="px-2 py-0.5 text-xs bg-purple-100 text-purple-700 rounded">
                        {{ exercise.pivot?.points || exercise.points || 0 }} điểm
                      </span>
                      <span v-if="exercise.skill" class="px-2 py-0.5 text-xs bg-green-100 text-green-700 rounded">
                        {{ exercise.skill }}
                      </span>
                    </div>
                    <p class="text-sm text-gray-800 font-medium line-clamp-2">
                      {{ exercise.title }}
                    </p>
                  </div>
                  <svg class="w-5 h-5 text-gray-400 ml-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-12 text-gray-500">
            <p>Không có câu hỏi nào</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="border-t px-6 py-4 bg-gray-50 flex justify-end">
          <button
            @click="viewingHomework = null"
            class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
          >
            Đóng
          </button>
        </div>
      </div>
    </div>

    <!-- Exercise Preview Modal -->
    <ExercisePreviewModal
      v-if="previewingExercise"
      :exercise="previewingExercise"
      @close="previewingExercise = null"
    />

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from '../../composables/useI18n';
import axios from 'axios';
import Swal from 'sweetalert2';
import draggable from 'vuedraggable';
import LessonSessionEditor from './LessonSessionEditor.vue';
import EvaluationFormBuilder from './EvaluationFormBuilder.vue';
import HomeworkExerciseBuilder from '../../components/homework/HomeworkExerciseBuilder.vue';
import ExercisePreviewModal from '../../components/homework/ExercisePreviewModal.vue';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const loading = ref(true);
const syllabus = ref({});
const units = ref([]);
const showSessionEditor = ref(false);
const editingSession = ref({});
const showEvaluationBuilder = ref(false);
const showHomeworkManagement = ref(false);
const showHomeworkBuilder = ref(false);
const viewingHomework = ref(null);
const editingHomework = ref(null);
const previewingExercise = ref(null);
const currentUnit = ref(null);
const currentEvaluationForm = ref(null);
const isDragging = ref(false);

const unitFolders = ref([]);

// Computed property to group exercises by group_id (for reading passages)
const groupedHomeworkExercises = computed(() => {
  if (!viewingHomework.value || !viewingHomework.value.exercises) {
    return [];
  }

  const groups = {};
  const ungrouped = [];

  viewingHomework.value.exercises.forEach((exercise) => {
    const groupId = exercise.settings?.group_id;

    if (groupId) {
      if (!groups[groupId]) {
        const content = typeof exercise.content === 'string'
          ? JSON.parse(exercise.content)
          : exercise.content;

        groups[groupId] = {
          id: groupId,
          passage: content?.passage || '',
          title: exercise.title?.split(' - Q')[0] || 'Reading Passage',
          exercises: []
        };
      }
      groups[groupId].exercises.push(exercise);
    } else {
      // For ungrouped exercises, treat each as its own group for consistent display
      const content = typeof exercise.content === 'string'
        ? JSON.parse(exercise.content)
        : exercise.content;

      ungrouped.push({
        id: `single_${exercise.id}`,
        passage: content?.passage || null,
        title: exercise.title || 'Question',
        exercises: [exercise]
      });
    }
  });

  return [...Object.values(groups), ...ungrouped];
});

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
        homework: [] // Initialize homework array
      };
    });

    // Load homework for each session
    await loadHomeworkForSessions();
  } catch (error) {
    console.error('Load syllabus error:', error);
    Swal.fire(t('common.error'), t('syllabus.error_load'), 'error');
  } finally {
    loading.value = false;
  }
};

const loadHomeworkForSessions = async () => {
  // Load homework for each session using session_id
  const homeworkPromises = units.value.map(async (unit) => {
    if (!unit.id) return;

    try {
      const response = await axios.get('/api/course/homework/assignments', {
        params: { session_id: unit.id },
        headers: {
          'X-Branch-Id': localStorage.getItem('current_branch_id')
        }
      });

      if (response.data.success) {
        unit.homework = response.data.data || [];
      }
    } catch (error) {
      console.error(`Error loading homework for session ${unit.id}:`, error);
      unit.homework = [];
    }
  });

  await Promise.all(homeworkPromises);
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

const onDragEnd = async () => {
  isDragging.value = false;
  
  console.log('[SyllabusDetail] 📦 Drag ended, updating order...');
  
  // Reassign session_number based on new position
  units.value.forEach((unit, index) => {
    unit.session_number = index + 1;
  });
  
  // Save new order to backend
  try {
    const orderData = units.value.map((unit, index) => ({
      id: unit.id,
      session_number: index + 1
    }));
    
    console.log('[SyllabusDetail] 📤 Sending new order:', orderData);
    
    await axios.put(`/api/lesson-plans/${syllabus.value.id}/sessions/reorder`, {
      order: orderData
    });
    
    await Swal.fire({
      icon: 'success',
      title: t('common.success'),
      text: 'Thứ tự buổi học đã được cập nhật',
      timer: 2000,
      showConfirmButton: false
    });
    
    // Reload to ensure consistency
    await loadSyllabus();
  } catch (error) {
    console.error('[SyllabusDetail] ❌ Error updating order:', error);
    Swal.fire({
      icon: 'error',
      title: t('common.error'),
      text: error.response?.data?.message || 'Không thể cập nhật thứ tự'
    });
    // Reload to restore original order
    await loadSyllabus();
  }
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

// Homework functions
const createHomework = async (unit) => {
  currentUnit.value = unit;

  // Load homework for this session if not already loaded
  if (!unit.homework) {
    try {
      const response = await axios.get('/api/course/homework/assignments', {
        params: { session_id: unit.id },
        headers: {
          'X-Branch-Id': localStorage.getItem('current_branch_id')
        }
      });

      if (response.data.success) {
        unit.homework = response.data.data || [];
      }
    } catch (error) {
      console.error('Error loading homework:', error);
      unit.homework = [];
    }
  }

  showHomeworkManagement.value = true;
};

const closeHomeworkManagement = () => {
  showHomeworkManagement.value = false;
};

const openHomeworkBuilder = () => {
  showHomeworkBuilder.value = true;
};

const closeHomeworkBuilder = () => {
  showHomeworkBuilder.value = false;
  editingHomework.value = null;
};

const onHomeworkSaved = async (homeworkData) => {
  await Swal.fire({
    icon: 'success',
    title: t('common.success'),
    text: 'Homework created successfully',
    timer: 2000,
    showConfirmButton: false
  });
  closeHomeworkBuilder();

  // Reload homework for current unit
  if (currentUnit.value && currentUnit.value.id) {
    try {
      const response = await axios.get('/api/course/homework/assignments', {
        params: { session_id: currentUnit.value.id },
        headers: {
          'X-Branch-Id': localStorage.getItem('current_branch_id')
        }
      });

      if (response.data.success) {
        currentUnit.value.homework = response.data.data || [];
      }
    } catch (error) {
      console.error('Error reloading homework:', error);
    }
  }
};

// Lesson Plan functions
const createLessonPlan = (unit) => {
  if (unit.id) {
    router.push({
      name: 'lesson-plan.editor',
      params: { sessionId: unit.id }
    });
  }
};

// Materials functions
const manageMaterials = (unit) => {
  if (unit.id) {
    router.push({
      name: 'quality.materials-list',
      params: { sessionId: unit.id }
    });
  }
};

// Helper functions for homework detail view
const getExerciseQuestion = (exercise) => {
  const content = typeof exercise.content === 'string'
    ? JSON.parse(exercise.content)
    : exercise.content;

  return content?.question || exercise.title || 'Question';
};

const formatAnswer = (answer) => {
  if (Array.isArray(answer)) {
    return answer.join(', ');
  }
  if (typeof answer === 'object' && answer !== null) {
    return JSON.stringify(answer, null, 2);
  }
  return answer || 'N/A';
};

// Homework detail and delete functions
const viewHomeworkDetail = (homework) => {
  viewingHomework.value = homework;
};

const editHomework = (homework) => {
  editingHomework.value = homework;
  showHomeworkBuilder.value = true;
  showHomeworkManagement.value = false;
};

const previewExercise = (exercise) => {
  previewingExercise.value = exercise;
};

const deleteHomework = async (homework, unit) => {
  const result = await Swal.fire({
    title: 'Xóa Homework Template?',
    text: `Bạn có chắc muốn xóa "${homework.title}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Xóa',
    cancelButtonText: 'Hủy'
  });

  if (!result.isConfirmed) return;

  try {
    await axios.delete(`/api/course/homework/assignments/${homework.id}`, {
      headers: {
        'X-Branch-Id': localStorage.getItem('current_branch_id')
      }
    });

    // Remove from UI
    const index = unit.homework.findIndex(h => h.id === homework.id);
    if (index !== -1) {
      unit.homework.splice(index, 1);
    }

    await Swal.fire({
      icon: 'success',
      title: 'Đã xóa!',
      text: 'Homework template đã được xóa',
      timer: 2000,
      showConfirmButton: false
    });
  } catch (error) {
    console.error('Error deleting homework:', error);
    await Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: error.response?.data?.message || 'Không thể xóa homework'
    });
  }
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

