<template>
  <div class="test-builder">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div class="flex items-center gap-4">
        <router-link :to="{ name: 'examination.tests' }" class="btn btn-ghost btn-circle">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
        </router-link>
        <div>
          <h1 class="text-2xl font-bold text-gray-800">
            {{ isEditing ? 'Chỉnh sửa bài test' : 'Tạo bài test mới' }}
          </h1>
          <p class="text-gray-500 mt-1">{{ test.title || 'Chưa có tiêu đề' }}</p>
        </div>
      </div>
      <div class="flex gap-3">
        <button @click="saveAsDraft" class="btn btn-outline" :disabled="saving">
          Lưu nháp
        </button>
        <button @click="saveAndPublish" class="btn btn-primary" :disabled="saving">
          <span v-if="saving" class="loading loading-spinner loading-sm mr-2"></span>
          {{ isEditing ? 'Cập nhật' : 'Xuất bản' }}
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Content -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Basic Info -->
        <div class="card bg-white shadow">
          <div class="card-body">
            <h3 class="card-title text-lg mb-4">Thông tin cơ bản</h3>

            <div class="grid grid-cols-2 gap-4">
              <!-- Test Type -->
              <div class="form-control">
                <label class="label">
                  <span class="label-text">Loại bài test <span class="text-error">*</span></span>
                </label>
                <select v-model="test.type" class="select select-bordered w-full" required>
                  <option value="">Chọn loại</option>
                  <option value="ielts">IELTS</option>
                  <option value="cambridge">Cambridge</option>
                  <option value="toeic">TOEIC</option>
                  <option value="custom">Tự tạo</option>
                  <option value="quiz">Quiz</option>
                  <option value="practice">Luyện tập</option>
                  <option value="placement">Placement Test</option>
                </select>
              </div>

              <!-- Subtype (for non-IELTS) -->
              <div class="form-control" v-if="availableSubtypes.length && test.type !== 'ielts'">
                <label class="label">
                  <span class="label-text">Loại con</span>
                </label>
                <select v-model="test.subtype" class="select select-bordered w-full">
                  <option value="">Chọn loại con</option>
                  <option v-for="subtype in availableSubtypes" :key="subtype" :value="subtype">
                    {{ getSubtypeLabel(subtype) }}
                  </option>
                </select>
              </div>

              <!-- Skill (for IELTS only) -->
              <div class="form-control" v-if="test.type === 'ielts'">
                <label class="label">
                  <span class="label-text">Kỹ năng chính <span class="text-error">*</span></span>
                </label>
                <select v-model="test.subtype" class="select select-bordered w-full" required>
                  <option value="">Chọn kỹ năng</option>
                  <option value="listening">Listening</option>
                  <option value="reading">Reading</option>
                  <option value="writing">Writing</option>
                  <option value="speaking">Speaking</option>
                </select>
              </div>
            </div>

            <!-- Title -->
            <div class="form-control mt-4">
              <label class="label">
                <span class="label-text">Tiêu đề <span class="text-error">*</span></span>
              </label>
              <input
                v-model="test.title"
                type="text"
                class="input input-bordered w-full"
                placeholder="Nhập tiêu đề bài test"
                required
              />
            </div>

            <!-- Description -->
            <div class="form-control mt-4">
              <label class="label">
                <span class="label-text">Mô tả</span>
              </label>
              <textarea
                v-model="test.description"
                class="textarea textarea-bordered h-24"
                placeholder="Mô tả ngắn về bài test..."
              ></textarea>
            </div>

            <!-- Instructions -->
            <div class="form-control mt-4">
              <label class="label">
                <span class="label-text">Hướng dẫn làm bài</span>
              </label>
              <textarea
                v-model="test.instructions"
                class="textarea textarea-bordered h-24"
                placeholder="Hướng dẫn cho học viên..."
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Sections (for IELTS/Cambridge) -->
        <div v-if="needsSections" class="card bg-white shadow">
          <div class="card-body">
            <div class="flex justify-between items-center mb-4">
              <h3 class="card-title text-lg">Các phần (Sections)</h3>
              <button @click="addSection" class="btn btn-sm btn-ghost">
                + Thêm phần
              </button>
            </div>

            <div class="space-y-4">
              <div
                v-for="(section, index) in test.sections"
                :key="index"
                class="border rounded-lg p-4"
              >
                <div class="flex justify-between items-start mb-3">
                  <div class="flex items-center gap-2">
                    <span class="badge badge-primary">Part {{ index + 1 }}</span>
                    <select
                      v-model="section.skill"
                      class="select select-bordered select-sm"
                    >
                      <option value="">Chọn kỹ năng</option>
                      <option value="listening">Listening</option>
                      <option value="reading">Reading</option>
                      <option value="writing">Writing</option>
                      <option value="speaking">Speaking</option>
                    </select>
                  </div>
                  <button
                    @click="removeSection(index)"
                    class="btn btn-ghost btn-sm btn-circle text-error"
                    :disabled="test.sections.length <= 1"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </button>
                </div>

                <input
                  v-model="section.title"
                  type="text"
                  class="input input-bordered input-sm w-full mb-2"
                  placeholder="Tiêu đề phần"
                />

                <textarea
                  v-model="section.instructions"
                  class="textarea textarea-bordered textarea-sm w-full"
                  placeholder="Hướng dẫn cho phần này..."
                  rows="2"
                ></textarea>

                <!-- Audio Track (for Listening) -->
                <div v-if="section.skill === 'listening'" class="mt-2">
                  <label class="label label-text-alt">Audio file:</label>
                  <input type="file" accept="audio/*" class="file-input file-input-sm file-input-bordered w-full" />
                </div>

                <!-- Reading Passage (for Reading) -->
                <div v-if="section.skill === 'reading'" class="mt-2">
                  <label class="label label-text-alt">Bài đọc:</label>
                  <textarea
                    v-model="section.passage_content"
                    class="textarea textarea-bordered w-full"
                    placeholder="Nội dung bài đọc..."
                    rows="4"
                  ></textarea>
                </div>

                <!-- Questions count -->
                <div class="mt-2 text-sm text-gray-500">
                  {{ section.questions?.length || 0 }} câu hỏi
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Questions -->
        <div class="card bg-white shadow">
          <div class="card-body">
            <div class="flex justify-between items-center mb-4">
              <h3 class="card-title text-lg">
                Câu hỏi
                <span class="badge badge-ghost ml-2">{{ test.questions?.length || 0 }}</span>
              </h3>
              <div class="flex gap-2">
                <button @click="showQuestionPicker = true" class="btn btn-sm btn-outline">
                  + Thêm từ ngân hàng
                </button>
                <button @click="createNewQuestion" class="btn btn-sm btn-primary">
                  + Tạo mới
                </button>
              </div>
            </div>

            <!-- Questions List -->
            <div v-if="!test.questions?.length" class="text-center py-8 text-gray-500">
              Chưa có câu hỏi nào. Thêm câu hỏi từ ngân hàng hoặc tạo mới.
            </div>

            <draggable
              v-else
              v-model="test.questions"
              item-key="id"
              handle=".drag-handle"
              class="space-y-2"
            >
              <template #item="{ element, index }">
                <div class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50">
                  <div class="drag-handle cursor-move text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                    </svg>
                  </div>
                  <span class="font-mono text-sm text-gray-500">{{ index + 1 }}</span>
                  <div class="flex-1">
                    <div class="font-medium">{{ element.question?.title || element.title }}</div>
                    <div class="text-sm text-gray-500">
                      {{ getQuestionTypeLabel(element.question?.type || element.type) }}
                      <span class="ml-2">{{ element.points || element.question?.points || 1 }} điểm</span>
                    </div>
                  </div>
                  <input
                    v-model.number="element.points"
                    type="number"
                    class="input input-bordered input-sm w-20"
                    min="0"
                    step="0.5"
                  />
                  <button @click="removeQuestion(index)" class="btn btn-ghost btn-sm btn-circle text-error">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </button>
                </div>
              </template>
            </draggable>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Settings -->
        <div class="card bg-white shadow">
          <div class="card-body">
            <h3 class="card-title text-lg mb-4">Cài đặt</h3>

            <!-- Time Limit -->
            <div class="form-control">
              <label class="label">
                <span class="label-text">Thời gian (phút)</span>
              </label>
              <input
                v-model.number="test.time_limit"
                type="number"
                class="input input-bordered w-full"
                min="0"
                placeholder="Không giới hạn"
              />
            </div>

            <!-- Passing Score -->
            <div class="form-control mt-4">
              <label class="label">
                <span class="label-text">Điểm đạt (%)</span>
              </label>
              <input
                v-model.number="test.passing_score"
                type="number"
                class="input input-bordered w-full"
                min="0"
                max="100"
                placeholder="60"
              />
            </div>

            <!-- Max Attempts -->
            <div class="form-control mt-4">
              <label class="label">
                <span class="label-text">Số lần làm tối đa</span>
              </label>
              <input
                v-model.number="test.max_attempts"
                type="number"
                class="input input-bordered w-full"
                min="0"
                placeholder="Không giới hạn"
              />
            </div>

            <div class="divider"></div>

            <!-- Options -->
            <div class="form-control">
              <label class="cursor-pointer label justify-start gap-3">
                <input v-model="test.shuffle_questions" type="checkbox" class="checkbox checkbox-sm" />
                <span class="label-text">Xáo trộn câu hỏi</span>
              </label>
            </div>

            <div class="form-control">
              <label class="cursor-pointer label justify-start gap-3">
                <input v-model="test.shuffle_options" type="checkbox" class="checkbox checkbox-sm" />
                <span class="label-text">Xáo trộn đáp án</span>
              </label>
            </div>

            <div class="form-control">
              <label class="cursor-pointer label justify-start gap-3">
                <input v-model="test.show_answers" type="checkbox" class="checkbox checkbox-sm" />
                <span class="label-text">Hiện đáp án sau khi nộp</span>
              </label>
            </div>

            <div class="form-control">
              <label class="cursor-pointer label justify-start gap-3">
                <input v-model="test.prevent_copy" type="checkbox" class="checkbox checkbox-sm" />
                <span class="label-text">Chặn copy/paste</span>
              </label>
            </div>

            <div class="form-control">
              <label class="cursor-pointer label justify-start gap-3">
                <input v-model="test.prevent_tab_switch" type="checkbox" class="checkbox checkbox-sm" />
                <span class="label-text">Theo dõi chuyển tab</span>
              </label>
            </div>
          </div>
        </div>

        <!-- IELTS Settings -->
        <div v-if="test.type === 'ielts'" class="card bg-white shadow">
          <div class="card-body">
            <h3 class="card-title text-lg mb-4">Cài đặt IELTS</h3>
            
            <div class="form-control">
              <label class="cursor-pointer label justify-start gap-3">
                <input v-model="ieltsSettings.calculateBandScore" type="checkbox" class="checkbox checkbox-sm" />
                <span class="label-text">Tính Band Score</span>
              </label>
            </div>

            <div class="form-control mt-2">
              <label class="cursor-pointer label justify-start gap-3">
                <input v-model="ieltsSettings.showDetailedFeedback" type="checkbox" class="checkbox checkbox-sm" />
                <span class="label-text">Hiển thị phản hồi chi tiết</span>
              </label>
            </div>

            <div class="form-control mt-2">
              <label class="cursor-pointer label justify-start gap-3">
                <input v-model="ieltsSettings.enablePrediction" type="checkbox" class="checkbox checkbox-sm" />
                <span class="label-text">Dự đoán điểm thi thật</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Summary -->
        <div class="card bg-white shadow">
          <div class="card-body">
            <h3 class="card-title text-lg mb-4">Tổng kết</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">Tổng câu hỏi:</span>
                <span class="font-medium">{{ test.questions?.length || 0 }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Tổng điểm:</span>
                <span class="font-medium">{{ totalPoints }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Thời gian:</span>
                <span class="font-medium">{{ test.time_limit ? test.time_limit + ' phút' : 'Không giới hạn' }}</span>
              </div>
              <div v-if="test.type === 'ielts' && test.subtype" class="flex justify-between">
                <span class="text-gray-500">Kỹ năng:</span>
                <span class="font-medium badge badge-primary">{{ test.subtype.toUpperCase() }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Question Picker Modal -->
    <QuestionPickerModal
      v-if="showQuestionPicker"
      :excluded-ids="selectedQuestionIds"
      @close="showQuestionPicker = false"
      @select="addQuestionsFromBank"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import draggable from 'vuedraggable'
import examinationApi from '@/services/examinationApi'
import QuestionPickerModal from './QuestionPickerModal.vue'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const saving = ref(false)
const showQuestionPicker = ref(false)

const test = reactive({
  type: '',
  subtype: '',
  title: '',
  description: '',
  instructions: '',
  time_limit: null,
  passing_score: 60,
  max_attempts: null,
  shuffle_questions: false,
  shuffle_options: false,
  show_answers: true,
  prevent_copy: false,
  prevent_tab_switch: false,
  sections: [],
  questions: [],
})

const ieltsSettings = reactive({
  calculateBandScore: false,
  showDetailedFeedback: false,
  enablePrediction: false,
})

const isEditing = computed(() => !!route.params.id)

const subtypesByType = {
  cambridge: ['starters', 'movers', 'flyers', 'ket', 'pet', 'fce', 'cae', 'cpe'],
  toeic: ['listening_reading', 'speaking_writing'],
}

const availableSubtypes = computed(() => {
  return test.type ? (subtypesByType[test.type] || []) : []
})

const needsSections = computed(() => {
  return ['cambridge', 'toeic'].includes(test.type)
})

function getSubtypeLabel(subtype) {
  const labels = {
    starters: 'Starters',
    movers: 'Movers',
    flyers: 'Flyers',
    ket: 'KET (A2)',
    pet: 'PET (B1)',
    fce: 'FCE (B2)',
    cae: 'CAE (C1)',
    cpe: 'CPE (C2)',
    listening_reading: 'Listening & Reading',
    speaking_writing: 'Speaking & Writing',
  }
  return labels[subtype] || subtype.toUpperCase()
}

const totalPoints = computed(() => {
  return test.questions?.reduce((sum, q) => sum + (q.points || q.question?.points || 1), 0) || 0
})

const selectedQuestionIds = computed(() => {
  return test.questions?.map(q => q.question_id || q.id) || []
})

onMounted(async () => {
  if (isEditing.value) {
    await loadTest()
  } else {
    // Pre-fill from query params when creating new test
    if (route.query.type) {
      test.type = route.query.type
    }
    if (route.query.skill) {
      test.subtype = route.query.skill
    }
    if (route.query.level) {
      test.subtype = route.query.level
    }
  }
})

watch(() => test.type, (newType, oldType) => {
  // Reset subtype when changing test type
  if (newType !== oldType) {
    test.subtype = ''
  }
  
  if (needsSections.value && !test.sections?.length) {
    // Add default sections based on type (for Cambridge, TOEIC)
    // IELTS doesn't use sections - it's single-skill tests
  }
})

async function loadTest() {
  loading.value = true
  try {
    const response = await examinationApi.tests.get(route.params.id)
    const testData = response.data.data || response.data
    Object.assign(test, testData)
    
    // Load IELTS settings if exists
    if (test.type === 'ielts' && testData.settings) {
      const settings = typeof testData.settings === 'string' ? JSON.parse(testData.settings) : testData.settings
      if (settings.ielts) {
        Object.assign(ieltsSettings, settings.ielts)
      }
    }
  } catch (error) {
    console.error('Failed to load test:', error)
    Swal.fire('Lỗi', 'Không thể tải bài test', 'error')
    router.push({ name: 'examination.tests' })
  } finally {
    loading.value = false
  }
}

function addSection() {
  test.sections.push({
    skill: '',
    title: '',
    instructions: '',
    questions: [],
  })
}

function removeSection(index) {
  if (test.sections.length > 1) {
    test.sections.splice(index, 1)
  }
}

function addQuestionsFromBank(questions) {
  const newQuestions = questions.map(q => ({
    question_id: q.id,
    question: q,
    points: q.points || 1,
  }))
  test.questions.push(...newQuestions)
  showQuestionPicker.value = false
}

function createNewQuestion() {
  // Open question form in a new window or modal
  router.push({
    name: 'examination.questions',
    query: { action: 'create', return_to: route.fullPath }
  })
}

function removeQuestion(index) {
  test.questions.splice(index, 1)
}

function getQuestionTypeLabel(type) {
  const labels = {
    multiple_choice: 'Trắc nghiệm',
    multiple_response: 'Nhiều đáp án',
    true_false: 'Đúng/Sai',
    fill_blanks: 'Điền chỗ trống',
    short_answer: 'Trả lời ngắn',
    matching: 'Nối cột',
    essay: 'Tự luận',
    audio_response: 'Ghi âm',
  }
  return labels[type] || type
}

async function saveAsDraft() {
  await saveTest('draft')
}

async function saveAndPublish() {
  await saveTest('published')
}

async function saveTest(status = 'draft') {
  if (!test.title || !test.type) {
    Swal.fire('Cảnh báo', 'Vui lòng điền tiêu đề và chọn loại bài test', 'warning')
    return
  }

  // Validate IELTS skill selection
  if (test.type === 'ielts' && !test.subtype) {
    Swal.fire('Cảnh báo', 'Vui lòng chọn kỹ năng chính cho bài test IELTS', 'warning')
    return
  }

  saving.value = true

  try {
    // Prepare settings
    const settings = test.settings ? (typeof test.settings === 'string' ? JSON.parse(test.settings) : test.settings) : {}
    
    // Add IELTS settings if applicable
    if (test.type === 'ielts') {
      settings.ielts = ieltsSettings
    }

    const payload = {
      ...test,
      status,
      settings: JSON.stringify(settings),
      questions: test.questions.map((q, index) => ({
        question_id: q.question_id || q.id,
        points: q.points,
        sort_order: index,
      })),
    }

    if (isEditing.value) {
      await examinationApi.tests.update(route.params.id, payload)
    } else {
      const response = await examinationApi.tests.create(payload)
      router.replace({
        name: 'examination.tests.edit',
        params: { id: response.data.id }
      })
    }

    Swal.fire('Thành công', 'Đã lưu bài test thành công!', 'success')
  } catch (error) {
    console.error('Failed to save test:', error)
    Swal.fire('Lỗi', 'Không thể lưu bài test: ' + (error.response?.data?.message || error.message), 'error')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.test-builder {
  @apply p-6;
}
</style>
