<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <button
              @click="goBack"
              class="text-gray-600 hover:text-gray-900"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
              </svg>
            </button>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Teaching Materials</h1>
              <p class="text-sm text-gray-500 mt-1">{{ lessonInfo.lesson_title }}</p>
            </div>
          </div>
          <button
            @click="createMaterial"
            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center space-x-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Add Material</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Lesson Info (Read-only) -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Lesson Focus</label>
            <div class="text-sm text-gray-900">{{ lessonInfo.lesson_focus || 'N/A' }}</div>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Level</label>
            <div class="text-sm text-gray-900">{{ lessonInfo.level || 'N/A' }}</div>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Duration (Mins)</label>
            <div class="text-sm text-gray-900">{{ lessonInfo.duration || 'N/A' }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Materials List -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
      </div>

      <div v-else-if="materials.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No materials</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by creating a new material.</p>
        <div class="mt-6">
          <button
            @click="createMaterial"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
          >
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Material
          </button>
        </div>
      </div>

      <div v-else class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Title
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Description
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Updated
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="material in materials" :key="material.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ material.title || 'Untitled' }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-500 line-clamp-2">
                  {{ stripHtml(material.description) || 'No description' }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(material.updated_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  @click="editMaterial(material.id)"
                  class="text-blue-600 hover:text-blue-900 mr-4"
                >
                  Edit
                </button>
                <button
                  @click="deleteMaterial(material.id)"
                  class="text-red-600 hover:text-red-900"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/api';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();

const sessionId = ref(route.params.sessionId);
const loading = ref(true);

const lessonInfo = ref({
  lesson_title: route.query.title || '',
  lesson_focus: route.query.focus || '',
  level: route.query.level || '',
  duration: route.query.duration || ''
});

const materials = ref([]);

// Load materials list
onMounted(async () => {
  try {
    loading.value = true;

    // Load session data if not passed via query
    if (!lessonInfo.value.lesson_title) {
      const sessionResponse = await api.get(`/quality/sessions/${sessionId.value}`);
      if (sessionResponse.data.success) {
        const session = sessionResponse.data.data;
        lessonInfo.value = {
          lesson_title: session.lesson_title,
          lesson_focus: session.lesson_focus,
          level: session.level,
          duration: session.duration_minutes
        };
      }
    }

    // Load materials list
    const materialsResponse = await api.get(`/quality/sessions/${sessionId.value}/materials`);
    if (materialsResponse.data.success) {
      materials.value = materialsResponse.data.data || [];
    }
  } catch (error) {
    console.error('Error loading data:', error);
    await Swal.fire('Error', 'Failed to load materials', 'error');
  } finally {
    loading.value = false;
  }
});

const createMaterial = () => {
  router.push({
    name: 'quality.materials-edit',
    params: { sessionId: sessionId.value, materialId: 'new' },
    query: {
      title: lessonInfo.value.lesson_title,
      focus: lessonInfo.value.lesson_focus,
      level: lessonInfo.value.level,
      duration: lessonInfo.value.duration
    }
  });
};

const editMaterial = (materialId) => {
  router.push({
    name: 'quality.materials-edit',
    params: { sessionId: sessionId.value, materialId: materialId }
  });
};

const deleteMaterial = async (materialId) => {
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: "You won't be able to revert this!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!'
  });

  if (result.isConfirmed) {
    try {
      await api.delete(`/quality/sessions/${sessionId.value}/materials/${materialId}`);

      // Remove from list
      materials.value = materials.value.filter(m => m.id !== materialId);

      await Swal.fire('Deleted!', 'Material has been deleted.', 'success');
    } catch (error) {
      console.error('Error deleting material:', error);
      await Swal.fire('Error', error.response?.data?.message || 'Failed to delete material', 'error');
    }
  }
};

const goBack = () => {
  router.back();
};

const stripHtml = (html) => {
  if (!html) return '';
  const tmp = document.createElement('div');
  tmp.innerHTML = html;
  return tmp.textContent || tmp.innerText || '';
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
