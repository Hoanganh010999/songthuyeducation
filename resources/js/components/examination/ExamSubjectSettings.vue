<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
      <!-- Overlay -->
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="$emit('close')"></div>

      <!-- Modal -->
      <div class="relative bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
        <!-- Header -->
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between z-10">
          <h2 class="text-lg font-semibold text-gray-800">Cài đặt môn học</h2>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Content -->
        <div class="p-6 overflow-y-auto" style="max-height: calc(90vh - 130px);">
          <div class="grid grid-cols-2 gap-6">
            <!-- Left: Subjects List -->
            <div>
              <div class="flex items-center justify-between mb-4">
                <h3 class="font-medium text-gray-800">Danh sách môn học</h3>
                <button @click="showAddSubject = true" class="text-sm text-blue-600 hover:text-blue-800">
                  + Thêm môn học
                </button>
              </div>

              <!-- Add Subject Form -->
              <div v-if="showAddSubject" class="mb-4 p-3 bg-gray-50 rounded-lg">
                <input
                  v-model="newSubject.name"
                  type="text"
                  placeholder="Tên môn học"
                  class="w-full px-3 py-2 border rounded-lg mb-2"
                />
                <input
                  v-model="newSubject.code"
                  type="text"
                  placeholder="Mã môn (VD: english, math)"
                  class="w-full px-3 py-2 border rounded-lg mb-2"
                />
                <div class="flex justify-end space-x-2">
                  <button @click="showAddSubject = false; resetNewSubject()" class="px-3 py-1 text-gray-600 hover:bg-gray-200 rounded">
                    Hủy
                  </button>
                  <button @click="addSubject" :disabled="!newSubject.name || !newSubject.code" class="px-3 py-1 bg-blue-600 text-white rounded disabled:opacity-50">
                    Thêm
                  </button>
                </div>
              </div>

              <!-- Subjects List -->
              <div v-if="loading" class="text-center py-4 text-gray-500">Đang tải...</div>
              <div v-else-if="subjects.length === 0" class="text-center py-4 text-gray-500">Chưa có môn học nào</div>
              <div v-else class="space-y-2">
                <div
                  v-for="subject in subjects"
                  :key="subject.id"
                  @click="selectSubject(subject)"
                  class="p-3 border rounded-lg cursor-pointer transition-colors"
                  :class="selectedSubject?.id === subject.id ? 'border-blue-500 bg-blue-50' : 'hover:bg-gray-50'"
                >
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="font-medium text-gray-800">{{ subject.name }}</p>
                      <p class="text-sm text-gray-500">{{ subject.code }}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                      <span class="text-xs text-gray-500">{{ subject.categories_count || 0 }} phân loại</span>
                      <button @click.stop="editSubject(subject)" class="p-1 text-gray-400 hover:text-blue-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                      </button>
                      <button @click.stop="deleteSubject(subject)" class="p-1 text-gray-400 hover:text-red-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right: Categories of Selected Subject -->
            <div>
              <div class="flex items-center justify-between mb-4">
                <h3 class="font-medium text-gray-800">
                  {{ selectedSubject ? `Phân loại: ${selectedSubject.name}` : 'Chọn môn học để xem phân loại' }}
                </h3>
                <button
                  v-if="selectedSubject"
                  @click="showAddCategory = true"
                  class="text-sm text-blue-600 hover:text-blue-800"
                >
                  + Thêm phân loại
                </button>
              </div>

              <!-- Add Category Form -->
              <div v-if="showAddCategory && selectedSubject" class="mb-4 p-3 bg-gray-50 rounded-lg">
                <input
                  v-model="newCategory.name"
                  type="text"
                  placeholder="Tên phân loại"
                  class="w-full px-3 py-2 border rounded-lg mb-2"
                />
                <input
                  v-model="newCategory.description"
                  type="text"
                  placeholder="Mô tả (tùy chọn)"
                  class="w-full px-3 py-2 border rounded-lg mb-2"
                />
                <div class="flex justify-end space-x-2">
                  <button @click="showAddCategory = false; resetNewCategory()" class="px-3 py-1 text-gray-600 hover:bg-gray-200 rounded">
                    Hủy
                  </button>
                  <button @click="addCategory" :disabled="!newCategory.name" class="px-3 py-1 bg-blue-600 text-white rounded disabled:opacity-50">
                    Thêm
                  </button>
                </div>
              </div>

              <!-- Categories List -->
              <div v-if="!selectedSubject" class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg">
                <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                <p>Chọn một môn học để xem và quản lý phân loại</p>
              </div>
              <div v-else-if="loadingCategories" class="text-center py-4 text-gray-500">Đang tải...</div>
              <div v-else-if="categories.length === 0" class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg">
                <p>Chưa có phân loại nào</p>
                <button @click="showAddCategory = true" class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                  + Thêm phân loại đầu tiên
                </button>
              </div>
              <div v-else class="space-y-2">
                <div
                  v-for="category in categories"
                  :key="category.id"
                  class="p-3 border rounded-lg hover:bg-gray-50"
                >
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="font-medium text-gray-800">{{ category.name }}</p>
                      <p v-if="category.description" class="text-sm text-gray-500">{{ category.description }}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                      <button @click="editCategory(category)" class="p-1 text-gray-400 hover:text-blue-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                      </button>
                      <button @click="deleteCategory(category)" class="p-1 text-gray-400 hover:text-red-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="sticky bottom-0 bg-gray-50 border-t px-6 py-3 flex justify-end">
          <button @click="$emit('close')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
            Đóng
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Subject Modal -->
    <div v-if="editingSubject" class="fixed inset-0 z-60 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-30" @click="editingSubject = null"></div>
      <div class="relative bg-white rounded-lg shadow-xl p-6 w-96">
        <h3 class="font-semibold mb-4">Chỉnh sửa môn học</h3>
        <input v-model="editingSubject.name" type="text" placeholder="Tên môn học" class="w-full px-3 py-2 border rounded-lg mb-3" />
        <input v-model="editingSubject.code" type="text" placeholder="Mã môn" class="w-full px-3 py-2 border rounded-lg mb-3" />
        <div class="flex justify-end space-x-2">
          <button @click="editingSubject = null" class="px-3 py-2 text-gray-600 hover:bg-gray-100 rounded">Hủy</button>
          <button @click="saveSubject" class="px-3 py-2 bg-blue-600 text-white rounded">Lưu</button>
        </div>
      </div>
    </div>

    <!-- Edit Category Modal -->
    <div v-if="editingCategory" class="fixed inset-0 z-60 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-30" @click="editingCategory = null"></div>
      <div class="relative bg-white rounded-lg shadow-xl p-6 w-96">
        <h3 class="font-semibold mb-4">Chỉnh sửa phân loại</h3>
        <input v-model="editingCategory.name" type="text" placeholder="Tên phân loại" class="w-full px-3 py-2 border rounded-lg mb-3" />
        <input v-model="editingCategory.description" type="text" placeholder="Mô tả" class="w-full px-3 py-2 border rounded-lg mb-3" />
        <div class="flex justify-end space-x-2">
          <button @click="editingCategory = null" class="px-3 py-2 text-gray-600 hover:bg-gray-100 rounded">Hủy</button>
          <button @click="saveCategory" class="px-3 py-2 bg-blue-600 text-white rounded">Lưu</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'

const emit = defineEmits(['close'])

const loading = ref(false)
const loadingCategories = ref(false)
const subjects = ref([])
const categories = ref([])
const selectedSubject = ref(null)

const showAddSubject = ref(false)
const showAddCategory = ref(false)

const newSubject = ref({ name: '', code: '' })
const newCategory = ref({ name: '', description: '' })

const editingSubject = ref(null)
const editingCategory = ref(null)

onMounted(() => {
  fetchSubjects()
})

async function fetchSubjects() {
  loading.value = true
  try {
    const response = await api.get('/examination/subjects')
    subjects.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching subjects:', error)
  } finally {
    loading.value = false
  }
}

async function fetchCategories(subjectId) {
  loadingCategories.value = true
  try {
    const response = await api.get(`/examination/subjects/${subjectId}/categories`)
    categories.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching categories:', error)
    categories.value = []
  } finally {
    loadingCategories.value = false
  }
}

function selectSubject(subject) {
  selectedSubject.value = subject
  fetchCategories(subject.id)
}

function resetNewSubject() {
  newSubject.value = { name: '', code: '' }
}

function resetNewCategory() {
  newCategory.value = { name: '', description: '' }
}

async function addSubject() {
  try {
    await api.post('/examination/subjects', newSubject.value)
    await fetchSubjects()
    showAddSubject.value = false
    resetNewSubject()
  } catch (error) {
    console.error('Error adding subject:', error)
    alert('Có lỗi xảy ra khi thêm môn học')
  }
}

async function addCategory() {
  if (!selectedSubject.value) return
  try {
    await api.post(`/examination/subjects/${selectedSubject.value.id}/categories`, newCategory.value)
    await fetchCategories(selectedSubject.value.id)
    await fetchSubjects() // Refresh count
    showAddCategory.value = false
    resetNewCategory()
  } catch (error) {
    console.error('Error adding category:', error)
    alert('Có lỗi xảy ra khi thêm phân loại')
  }
}

function editSubject(subject) {
  editingSubject.value = { ...subject }
}

function editCategory(category) {
  editingCategory.value = { ...category }
}

async function saveSubject() {
  try {
    await api.put(`/examination/subjects/${editingSubject.value.id}`, editingSubject.value)
    await fetchSubjects()
    if (selectedSubject.value?.id === editingSubject.value.id) {
      selectedSubject.value = { ...editingSubject.value }
    }
    editingSubject.value = null
  } catch (error) {
    console.error('Error updating subject:', error)
    alert('Có lỗi xảy ra khi cập nhật môn học')
  }
}

async function saveCategory() {
  if (!selectedSubject.value) return
  try {
    await api.put(`/examination/subjects/${selectedSubject.value.id}/categories/${editingCategory.value.id}`, editingCategory.value)
    await fetchCategories(selectedSubject.value.id)
    editingCategory.value = null
  } catch (error) {
    console.error('Error updating category:', error)
    alert('Có lỗi xảy ra khi cập nhật phân loại')
  }
}

async function deleteSubject(subject) {
  if (!confirm(`Bạn có chắc muốn xóa môn học "${subject.name}"? Tất cả phân loại sẽ bị xóa theo.`)) return
  try {
    await api.delete(`/examination/subjects/${subject.id}`)
    await fetchSubjects()
    if (selectedSubject.value?.id === subject.id) {
      selectedSubject.value = null
      categories.value = []
    }
  } catch (error) {
    console.error('Error deleting subject:', error)
    alert('Có lỗi xảy ra khi xóa môn học')
  }
}

async function deleteCategory(category) {
  if (!confirm(`Bạn có chắc muốn xóa phân loại "${category.name}"?`)) return
  if (!selectedSubject.value) return
  try {
    await api.delete(`/examination/subjects/${selectedSubject.value.id}/categories/${category.id}`)
    await fetchCategories(selectedSubject.value.id)
    await fetchSubjects() // Refresh count
  } catch (error) {
    console.error('Error deleting category:', error)
    alert('Có lỗi xảy ra khi xóa phân loại')
  }
}
</script>
