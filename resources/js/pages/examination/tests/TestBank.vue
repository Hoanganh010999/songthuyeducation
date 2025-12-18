<template>
  <div class="test-bank">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Ngân hàng bài test</h1>
        <p class="text-gray-500 mt-1">Quản lý các bài test IELTS, Cambridge và tự tạo</p>
      </div>
      <router-link :to="{ name: 'examination.tests.create' }" class="btn btn-primary">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Tạo bài test
      </router-link>
    </div>

    <!-- Test Type Tabs -->
    <div class="tabs tabs-boxed mb-6 bg-white p-1">
      <a
        v-for="tab in testTypeTabs"
        :key="tab.value"
        class="tab"
        :class="{ 'tab-active': filters.type === tab.value }"
        @click="filterByType(tab.value)"
      >
        {{ tab.label }}
        <span class="badge badge-sm ml-2">{{ getTypeCount(tab.value) }}</span>
      </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-2">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Tìm kiếm bài test..."
            class="input input-bordered w-full"
            @input="debouncedSearch"
          />
        </div>
        <div>
          <select v-model="filters.subtype" class="select select-bordered w-full" @change="loadTests">
            <option value="">Tất cả loại con</option>
            <option v-for="subtype in availableSubtypes" :key="subtype" :value="subtype">
              {{ subtype.toUpperCase() }}
            </option>
          </select>
        </div>
        <div>
          <select v-model="filters.status" class="select select-bordered w-full" @change="loadTests">
            <option value="">Tất cả trạng thái</option>
            <option value="draft">Nháp</option>
            <option value="published">Đã xuất bản</option>
            <option value="archived">Đã lưu trữ</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Tests Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-if="loading" class="col-span-full text-center py-12">
        <span class="loading loading-spinner loading-lg"></span>
      </div>

      <div v-else-if="tests.length === 0" class="col-span-full text-center py-12 text-gray-500">
        Không có bài test nào
      </div>

      <div
        v-for="test in tests"
        :key="test.id"
        class="card bg-white shadow-lg hover:shadow-xl transition-shadow"
      >
        <div class="card-body">
          <!-- Test Type Badge -->
          <div class="flex items-center gap-2 mb-2">
            <span class="badge" :class="getTypeBadgeClass(test.type)">
              {{ test.type.toUpperCase() }}
            </span>
            <span v-if="test.subtype" class="badge badge-outline">
              {{ test.subtype.toUpperCase() }}
            </span>
            <span class="badge" :class="getStatusBadgeClass(test.status)">
              {{ getStatusLabel(test.status) }}
            </span>
          </div>

          <!-- Title -->
          <h2 class="card-title text-lg">{{ test.title }}</h2>

          <!-- Description -->
          <p class="text-sm text-gray-500 line-clamp-2">{{ test.description }}</p>

          <!-- Stats -->
          <div class="flex items-center gap-4 text-sm text-gray-600 mt-2">
            <div class="flex items-center">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              {{ test.questions_count || 0 }} câu hỏi
            </div>
            <div class="flex items-center">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              {{ test.time_limit ? test.time_limit + ' phút' : 'Không giới hạn' }}
            </div>
          </div>

          <!-- Sections (for IELTS/Cambridge) -->
          <div v-if="test.sections?.length" class="flex flex-wrap gap-1 mt-2">
            <span v-for="section in test.sections" :key="section.id" class="badge badge-sm badge-ghost">
              {{ section.skill || section.title }}
            </span>
          </div>

          <!-- Actions -->
          <div class="card-actions justify-end mt-4">
            <router-link
              :to="{ name: 'examination.tests.edit', params: { id: test.id } }"
              class="btn btn-sm btn-ghost"
            >
              Sửa
            </router-link>
            <button @click="previewTest(test)" class="btn btn-sm btn-ghost">
              Xem trước
            </button>
            <div class="dropdown dropdown-end">
              <label tabindex="0" class="btn btn-sm btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01"/>
                </svg>
              </label>
              <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-40">
                <li><a @click="duplicateTest(test)">Nhân bản</a></li>
                <li><a @click="createAssignment(test)">Giao bài</a></li>
                <li v-if="test.status === 'draft'">
                  <a @click="publishTest(test)">Xuất bản</a>
                </li>
                <li><a @click="deleteTest(test)" class="text-error">Xóa</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total > pagination.perPage" class="flex justify-center mt-8">
      <div class="btn-group">
        <button
          class="btn"
          :disabled="pagination.page === 1"
          @click="changePage(pagination.page - 1)"
        >«</button>
        <button class="btn">Trang {{ pagination.page }} / {{ pagination.lastPage }}</button>
        <button
          class="btn"
          :disabled="pagination.page >= pagination.lastPage"
          @click="changePage(pagination.page + 1)"
        >»</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { debounce } from 'lodash-es'
import examinationApi from '@/services/examinationApi'

const router = useRouter()
const loading = ref(false)
const tests = ref([])
const typeCounts = ref({})

const filters = reactive({
  search: '',
  type: '',
  subtype: '',
  status: '',
})

const pagination = reactive({
  page: 1,
  perPage: 12,
  total: 0,
  lastPage: 1,
})

const testTypeTabs = [
  { value: '', label: 'Tất cả' },
  { value: 'ielts', label: 'IELTS' },
  { value: 'cambridge', label: 'Cambridge' },
  { value: 'toeic', label: 'TOEIC' },
  { value: 'custom', label: 'Tự tạo' },
  { value: 'quiz', label: 'Quiz' },
  { value: 'practice', label: 'Luyện tập' },
]

const subtypesByType = {
  ielts: ['academic', 'general'],
  cambridge: ['ket', 'pet', 'fce', 'cae', 'cpe'],
  toeic: ['listening_reading', 'speaking_writing'],
}

const availableSubtypes = computed(() => {
  return filters.type ? (subtypesByType[filters.type] || []) : []
})

onMounted(() => {
  loadTests()
})

async function loadTests() {
  loading.value = true
  try {
    const response = await examinationApi.tests.list({
      ...filters,
      page: pagination.page,
      per_page: pagination.perPage,
    })
    tests.value = response.data.data
    pagination.total = response.data.meta?.total || response.data.total || 0
    pagination.lastPage = response.data.meta?.last_page || Math.ceil(pagination.total / pagination.perPage)

    // Update type counts
    if (response.data.counts) {
      typeCounts.value = response.data.counts
    }
  } catch (error) {
    console.error('Failed to load tests:', error)
  } finally {
    loading.value = false
  }
}

const debouncedSearch = debounce(() => {
  pagination.page = 1
  loadTests()
}, 300)

function filterByType(type) {
  filters.type = type
  filters.subtype = ''
  pagination.page = 1
  loadTests()
}

function changePage(page) {
  pagination.page = page
  loadTests()
}

function getTypeCount(type) {
  if (!type) return pagination.total
  return typeCounts.value[type] || 0
}

function getTypeBadgeClass(type) {
  const map = {
    ielts: 'badge-error',
    cambridge: 'badge-primary',
    toeic: 'badge-warning',
    custom: 'badge-info',
    quiz: 'badge-success',
    practice: 'badge-secondary',
  }
  return map[type] || 'badge-ghost'
}

function getStatusBadgeClass(status) {
  const map = {
    draft: 'badge-warning',
    published: 'badge-success',
    archived: 'badge-ghost',
  }
  return map[status] || 'badge-ghost'
}

function getStatusLabel(status) {
  const map = {
    draft: 'Nháp',
    published: 'Đã xuất bản',
    archived: 'Lưu trữ',
  }
  return map[status] || status
}

function previewTest(test) {
  router.push({ name: 'examination.tests.preview', params: { id: test.id } })
}

async function duplicateTest(test) {
  if (!confirm('Bạn có chắc muốn nhân bản bài test này?')) return

  try {
    const response = await examinationApi.tests.duplicate(test.id)
    router.push({ name: 'examination.tests.edit', params: { id: response.data.id } })
  } catch (error) {
    console.error('Failed to duplicate test:', error)
    alert('Không thể nhân bản bài test')
  }
}

function createAssignment(test) {
  router.push({
    name: 'examination.assignments.create',
    query: { test_id: test.id }
  })
}

async function publishTest(test) {
  if (!confirm('Xuất bản bài test này?')) return

  try {
    await examinationApi.tests.update(test.id, { status: 'published' })
    loadTests()
  } catch (error) {
    console.error('Failed to publish test:', error)
    alert('Không thể xuất bản bài test')
  }
}

async function deleteTest(test) {
  if (!confirm('Bạn có chắc muốn xóa bài test này?')) return

  try {
    await examinationApi.tests.delete(test.id)
    loadTests()
  } catch (error) {
    console.error('Failed to delete test:', error)
    alert('Không thể xóa bài test')
  }
}
</script>

<style scoped>
.test-bank {
  @apply p-6;
}
</style>
