<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">{{ t('class_detail.comments') }}</h3>
          <p class="text-sm text-gray-500 mt-1">
            {{ session?.lesson_plan_session?.title || 'Session' }} - {{ formatDate(session?.scheduled_date) }}
          </p>
        </div>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="p-6 overflow-y-auto" style="max-height: calc(90vh - 140px);">
        <div class="space-y-6">
          <div
            v-for="(student, index) in commentData"
            :key="student.student_id"
            class="bg-gray-50 rounded-lg p-4"
          >
            <div class="flex items-center space-x-3 mb-4">
              <div class="flex-shrink-0 w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center text-white font-semibold">
                {{ index + 1 }}
              </div>
              <div>
                <h4 class="font-medium text-gray-900">{{ student.student_name }}</h4>
                <p class="text-sm text-gray-500">{{ student.student_code }}</p>
              </div>
            </div>

            <div class="space-y-4">
              <!-- Comment Text -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  {{ t('class_detail.comment_text') }}
                </label>
                <textarea
                  v-model="student.comment"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500"
                  :placeholder="t('class_detail.comment_text')"
                ></textarea>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Rating with Half-Star Support -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ t('class_detail.rating') }}
                  </label>
                  <div class="flex space-x-1">
                    <button
                      v-for="star in 5"
                      :key="star"
                      @click="handleStarClick($event, star, student)"
                      class="focus:outline-none relative"
                      style="width: 24px; height: 24px;"
                    >
                      <!-- Background (gray) star -->
                      <svg
                        class="w-6 h-6 absolute inset-0 text-gray-300"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                      >
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                      <!-- Filled (yellow) star with clip-path for half-star -->
                      <svg
                        class="w-6 h-6 absolute inset-0 text-yellow-400 transition-all"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        :style="getStarStyle(star, student.rating)"
                      >
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                    </button>
                  </div>
                  <div class="text-xs text-gray-500 mt-1">
                    {{ student.rating ? student.rating + '/5' : '-' }}
                  </div>
                </div>

                <!-- Behavior -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ t('class_detail.behavior') }}
                  </label>
                  <select
                    v-model="student.behavior"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500"
                  >
                    <option value="">-</option>
                    <option value="Tốt">Tốt</option>
                    <option value="TB">TB</option>
                    <option value="Cần nhắc nhở">Cần nhắc nhở</option>
                  </select>
                </div>

                <!-- Participation -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ t('class_detail.participation') }}
                  </label>
                  <select
                    v-model="student.participation"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500"
                  >
                    <option value="">-</option>
                    <option value="Tích cực">Tích cực</option>
                    <option value="Bình thường">Bình thường</option>
                    <option value="Ít tham gia">Ít tham gia</option>
                    <option value="Thụ động">Thụ động</option>
                    <option value="Không tham gia">Không tham gia</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
        >
          {{ t('common.cancel') }}
        </button>
        <button
          @click="saveComments"
          :disabled="saving"
          class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-md hover:bg-purple-700 disabled:opacity-50"
        >
          {{ saving ? t('common.saving') : t('common.save') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from '../../../composables/useI18n';
import axios from 'axios';
import dayjs from 'dayjs';
import Swal from 'sweetalert2';

const { t } = useI18n();

const props = defineProps({
  session: {
    type: Object,
    required: true
  },
  classData: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close', 'saved']);

const commentData = ref([]);
const saving = ref(false);

const formatDate = (date) => {
  return dayjs(date).format('DD/MM/YYYY');
};

const loadComments = async () => {
  try {
    // Use quick-comments route with permission check
    const response = await axios.get(`/api/quality/sessions/${props.session.id}/quick-comments`);
    
    if (response.data.success) {
      commentData.value = response.data.data.map(student => ({
        student_id: student.student_id,
        user_id: student.user_id,
        student_name: student.student_name,
        student_code: student.student_code,
        comment: student.comment || '',
        rating: student.rating || null,
        behavior: student.behavior || '',
        participation: student.participation || '',
      }));
    }
  } catch (error) {
    console.error('Error loading comments:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Failed to load comments'
    });
  }
};

// Handle star click for half-star rating
const handleStarClick = (event, starNumber, student) => {
  const rect = event.currentTarget.getBoundingClientRect();
  const clickX = event.clientX - rect.left;
  const starWidth = rect.width;
  
  // If clicked on left half, set to N-0.5, otherwise set to N
  if (clickX < starWidth / 2) {
    student.rating = starNumber - 0.5;
  } else {
    student.rating = starNumber;
  }
};

// Get style for star based on rating (for half-star display)
const getStarStyle = (starNumber, rating) => {
  if (!rating) {
    return { clipPath: 'inset(0 100% 0 0)' }; // Hide completely
  }
  
  if (rating >= starNumber) {
    return { clipPath: 'inset(0 0 0 0)' }; // Show full star
  } else if (rating >= starNumber - 0.5) {
    return { clipPath: 'inset(0 50% 0 0)' }; // Show half star (left half)
  } else {
    return { clipPath: 'inset(0 100% 0 0)' }; // Hide completely
  }
};

const saveComments = async () => {
  try {
    saving.value = true;
    
    // Prepare comments data (all students, even empty ones)
    const payload = {
      comments: commentData.value.map(student => ({
        user_id: student.user_id,
        comment: student.comment || null,
        rating: student.rating || null,
        behavior: student.behavior || null,
        participation: student.participation || null,
      }))
    };
    
    // Use quick-comments route
    const response = await axios.post(
      `/api/quality/sessions/${props.session.id}/quick-comments`,
      payload
    );
    
    if (response.data.success) {
      Swal.fire({
        icon: 'success',
        title: 'Success',
        text: 'Comments saved successfully',
        timer: 1500
      });
      emit('saved');
    }
  } catch (error) {
    console.error('Error saving comments:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Failed to save comments'
    });
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadComments();
});
</script>

