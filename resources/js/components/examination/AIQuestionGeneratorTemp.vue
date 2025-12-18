<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="$emit('close')"></div>
      <div class="relative bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between z-10">
          <div>
            <h2 class="text-lg font-semibold text-gray-800">Tạo câu hỏi bằng AI</h2>
            <p class="text-sm text-gray-500 mt-1">Sử dụng AI để tạo câu hỏi nhanh chóng</p>
          </div>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <form @submit.prevent="generateQuestions" class="p-6 space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Chủ đề / Nội dung *</label>
            <textarea v-model="form.topic" rows="3" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Nhập chủ đề hoặc nội dung cần tạo câu hỏi..."></textarea>
            <p class="text-xs text-gray-500 mt-1">Mô tả chi tiết về chủ đề cần tạo câu hỏi</p>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Môn học</label>
              <select v-model="form.subject_id" @change="onSubjectChange" :disabled="loadingSubjects" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100">
                <option value="">{{ loadingSubjects ? 'Đang tải...' : 'Chọn môn học' }}</option>
                <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Phân loại</label>
              <select v-model="form.subject_category_id" :disabled="!form.subject_id || loadingCategories" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                <option value="">{{ form.subject_id ? 'Không chọn' : 'Chọn môn học trước' }}</option>
                <option v-for="category in subjectCategories" :key="category.id" :value="category.id">{{ category.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Độ khó *</label>
              <select v-model="form.difficulty" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="easy">Dễ</option>
                <option value="medium">Trung bình</option>
                <option value="hard">Khó</option>
                <option value="expert">Chuyên gia</option>
              </select>
            </div>
          </div>
