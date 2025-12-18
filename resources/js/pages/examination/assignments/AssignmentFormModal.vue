<template>
  <div class="modal modal-open">
    <div class="modal-box max-w-2xl">
      <h3 class="font-bold text-lg mb-4">
        {{ isEditing ? 'Chỉnh sửa bài giao' : 'Giao bài test mới' }}
      </h3>

      <form @submit.prevent="handleSubmit">
        <!-- Select Test -->
        <div class="form-control mb-4">
          <label class="label">
            <span class="label-text">Chọn bài test <span class="text-error">*</span></span>
          </label>
          <select v-model="form.test_id" class="select select-bordered w-full" required :disabled="!!initialTestId">
            <option value="">Chọn bài test</option>
            <option v-for="test in tests" :key="test.id" :value="test.id">
              {{ test.title }} ({{ test.type?.toUpperCase() }})
            </option>
          </select>
        </div>

        <!-- Title -->
        <div class="form-control mb-4">
          <label class="label">
            <span class="label-text">Tiêu đề bài giao</span>
          </label>
          <input
            v-model="form.title"
            type="text"
            class="input input-bordered w-full"
            placeholder="Để trống sẽ dùng tên bài test"
          />
        </div>

        <!-- Target Type -->
        <div class="form-control mb-4">
          <label class="label">
            <span class="label-text">Giao cho <span class="text-error">*</span></span>
          </label>
          <div class="flex gap-4">
            <label class="cursor-pointer label justify-start gap-2">
              <input type="radio" v-model="targetType" value="users" class="radio radio-primary" />
              <span class="label-text">Học viên cụ thể</span>
            </label>
            <label class="cursor-pointer label justify-start gap-2">
              <input type="radio" v-model="targetType" value="classes" class="radio radio-primary" />
              <span class="label-text">Lớp học</span>
            </label>
          </div>
        </div>

        <!-- Select Users -->
        <div v-if="targetType === 'users'" class="form-control mb-4">
          <label class="label">
            <span class="label-text">Chọn học viên</span>
          </label>
          <select v-model="form.user_ids" class="select select-bordered w-full" multiple size="5">
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }} ({{ user.email }})
            </option>
          </select>
          <label class="label">
            <span class="label-text-alt">Giữ Ctrl để chọn nhiều</span>
          </label>
        </div>

        <!-- Select Classes -->
        <div v-if="targetType === 'classes'" class="form-control mb-4">
          <label class="label">
            <span class="label-text">Chọn lớp học</span>
          </label>
          <select v-model="form.class_ids" class="select select-bordered w-full" multiple size="5">
            <option v-for="cls in classes" :key="cls.id" :value="cls.id">
              {{ cls.name }}
            </option>
          </select>
        </div>

        <!-- Date Range -->
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Ngày bắt đầu</span>
            </label>
            <input
              v-model="form.start_date"
              type="datetime-local"
              class="input input-bordered w-full"
            />
          </div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">Ngày kết thúc</span>
            </label>
            <input
              v-model="form.end_date"
              type="datetime-local"
              class="input input-bordered w-full"
            />
          </div>
        </div>

        <!-- Settings -->
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Thời gian làm bài (phút)</span>
            </label>
            <input
              v-model.number="form.time_limit"
              type="number"
              class="input input-bordered w-full"
              min="0"
              placeholder="Dùng của bài test"
            />
          </div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">Số lần làm tối đa</span>
            </label>
            <input
              v-model.number="form.max_attempts"
              type="number"
              class="input input-bordered w-full"
              min="1"
              placeholder="Không giới hạn"
            />
          </div>
        </div>

        <!-- Options -->
        <div class="space-y-2 mb-4">
          <div class="form-control">
            <label class="cursor-pointer label justify-start gap-3">
              <input v-model="form.shuffle_questions" type="checkbox" class="checkbox checkbox-sm" />
              <span class="label-text">Xáo trộn câu hỏi</span>
            </label>
          </div>
          <div class="form-control">
            <label class="cursor-pointer label justify-start gap-3">
              <input v-model="form.shuffle_options" type="checkbox" class="checkbox checkbox-sm" />
              <span class="label-text">Xáo trộn đáp án</span>
            </label>
          </div>
          <div class="form-control">
            <label class="cursor-pointer label justify-start gap-3">
              <input v-model="form.is_active" type="checkbox" class="checkbox checkbox-sm" />
              <span class="label-text">Kích hoạt ngay</span>
            </label>
          </div>
        </div>

        <!-- Instructions -->
        <div class="form-control mb-4">
          <label class="label">
            <span class="label-text">Ghi chú cho học viên</span>
          </label>
          <textarea
            v-model="form.instructions"
            class="textarea textarea-bordered h-20"
            placeholder="Hướng dẫn hoặc ghi chú thêm..."
          ></textarea>
        </div>

        <!-- Actions -->
        <div class="modal-action">
          <button type="button" @click="$emit('close')" class="btn">Hủy</button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            <span v-if="saving" class="loading loading-spinner loading-sm mr-2"></span>
            {{ isEditing ? 'Cập nhật' : 'Giao bài' }}
          </button>
        </div>
      </form>
    </div>
    <div class="modal-backdrop" @click="$emit('close')"></div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import examinationApi from '@/services/examinationApi'
import api from '@/api'

const props = defineProps({
  assignment: Object,
  initialTestId: [String, Number],
})

const emit = defineEmits(['close', 'saved'])

const saving = ref(false)
const tests = ref([])
const users = ref([])
const classes = ref([])
const targetType = ref('users')

const form = reactive({
  test_id: '',
  title: '',
  user_ids: [],
  class_ids: [],
  start_date: '',
  end_date: '',
  time_limit: null,
  max_attempts: null,
  shuffle_questions: false,
  shuffle_options: false,
  is_active: true,
  instructions: '',
})

const isEditing = computed(() => !!props.assignment)

onMounted(async () => {
  await Promise.all([
    loadTests(),
    loadUsers(),
    loadClasses(),
  ])

  if (props.initialTestId) {
    form.test_id = props.initialTestId
  }

  if (props.assignment) {
    populateForm()
  }
})

async function loadTests() {
  try {
    const response = await examinationApi.tests.list({ status: 'published', per_page: 100 })
    tests.value = response.data.data
  } catch (error) {
    console.error('Failed to load tests:', error)
  }
}

async function loadUsers() {
  try {
    const response = await api.get('/users/list')
    users.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to load users:', error)
  }
}

async function loadClasses() {
  try {
    const response = await api.get('/classes', { params: { status: 'active' } })
    classes.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to load classes:', error)
  }
}

function populateForm() {
  const a = props.assignment
  Object.assign(form, {
    test_id: a.test_id,
    title: a.title,
    start_date: a.start_date ? formatDatetimeLocal(a.start_date) : '',
    end_date: a.end_date ? formatDatetimeLocal(a.end_date) : '',
    time_limit: a.time_limit,
    max_attempts: a.max_attempts,
    shuffle_questions: a.shuffle_questions,
    shuffle_options: a.shuffle_options,
    is_active: a.is_active,
    instructions: a.instructions,
  })

  // Determine target type and populate IDs
  if (a.targets?.length) {
    const firstTarget = a.targets[0]
    if (firstTarget.targetable_type?.includes('User')) {
      targetType.value = 'users'
      form.user_ids = a.targets.map(t => t.targetable_id)
    } else if (firstTarget.targetable_type?.includes('Class')) {
      targetType.value = 'classes'
      form.class_ids = a.targets.map(t => t.targetable_id)
    }
  }
}

function formatDatetimeLocal(date) {
  const d = new Date(date)
  const offset = d.getTimezoneOffset()
  const localDate = new Date(d.getTime() - offset * 60 * 1000)
  return localDate.toISOString().slice(0, 16)
}

async function handleSubmit() {
  if (!form.test_id) {
    alert('Vui lòng chọn bài test')
    return
  }

  const targets = targetType.value === 'users' ? form.user_ids : form.class_ids
  if (!targets.length) {
    alert('Vui lòng chọn đối tượng giao bài')
    return
  }

  saving.value = true

  try {
    const payload = {
      test_id: form.test_id,
      title: form.title,
      start_date: form.start_date || null,
      end_date: form.end_date || null,
      time_limit: form.time_limit,
      max_attempts: form.max_attempts,
      shuffle_questions: form.shuffle_questions,
      shuffle_options: form.shuffle_options,
      is_active: form.is_active,
      instructions: form.instructions,
      targets: targets.map(id => ({
        targetable_type: targetType.value === 'users' ? 'App\\Models\\User' : 'App\\Models\\ClassModel',
        targetable_id: id,
      })),
    }

    if (isEditing.value) {
      await examinationApi.assignments.update(props.assignment.id, payload)
    } else {
      await examinationApi.assignments.create(payload)
    }

    emit('saved')
  } catch (error) {
    console.error('Failed to save assignment:', error)
    alert('Không thể lưu: ' + (error.response?.data?.message || error.message))
  } finally {
    saving.value = false
  }
}
</script>
