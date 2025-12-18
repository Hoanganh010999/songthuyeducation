<template>
  <div class="relative" ref="notificationRef">
    <button
      @click="toggleNotifications"
      class="relative p-2 rounded-md hover:bg-gray-100 transition"
    >
      <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
      </svg>
      
      <!-- Badge -->
      <span
        v-if="unreadCount > 0"
        class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="showNotifications"
        class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
      >
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
          <h3 class="text-lg font-semibold text-gray-900">Thông báo</h3>
          <button
            v-if="notifications.length > 0"
            @click="markAllAsRead"
            class="text-xs text-blue-600 hover:text-blue-700"
          >
            Đánh dấu tất cả đã đọc
          </button>
        </div>

        <div class="max-h-96 overflow-y-auto">
          <div v-if="loading" class="p-4 text-center text-gray-500">
            Đang tải...
          </div>
          
          <div v-else-if="notifications.length === 0" class="p-8 text-center text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <p>Không có thông báo mới</p>
          </div>
          
          <div v-else>
            <div
              v-for="notification in notifications"
              :key="notification.id"
              class="p-4 border-b border-gray-100 transition"
              :class="{ 'bg-blue-50': !notification.read_at }"
            >
              <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                  <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900">{{ notification.title }}</p>
                  <p class="text-sm text-gray-600 mt-1">{{ notification.message }}</p>
                  
                  <!-- Invitation Actions -->
                  <div v-if="notification.type === 'employee_invitation' && notification.data && !notification.is_read" class="mt-2 flex space-x-2">
                    <button
                      @click.stop="acceptInvitation(notification)"
                      class="px-3 py-1 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700 transition"
                    >
                      Chấp nhận
                    </button>
                    <button
                      @click.stop="rejectInvitation(notification)"
                      class="px-3 py-1 text-xs font-medium text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition"
                    >
                      Từ chối
                    </button>
                  </div>
                  <div v-else-if="notification.type === 'employee_invitation' && notification.is_read" class="mt-2">
                    <span class="text-xs text-gray-500 italic">Đã xử lý</span>
                  </div>
                  
                  <p class="text-xs text-gray-400 mt-1">{{ formatDate(notification.created_at) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const notificationRef = ref(null);
const showNotifications = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const loading = ref(false);

const toggleNotifications = async () => {
  showNotifications.value = !showNotifications.value;
  if (showNotifications.value) {
    await loadNotifications();
  }
};

const loadNotifications = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/notifications');
    notifications.value = response.data.notifications;
    unreadCount.value = response.data.unread_count;
  } catch (error) {
    console.error('Error loading notifications:', error);
  } finally {
    loading.value = false;
  }
};

const loadUnreadCount = async () => {
  try {
    const response = await axios.get('/api/notifications/unread-count');
    unreadCount.value = response.data.unread_count;
  } catch (error) {
    console.error('Error loading unread count:', error);
  }
};

const markAllAsRead = async () => {
  try {
    await axios.post('/api/notifications/read-all');
    await loadNotifications();
  } catch (error) {
    console.error('Error marking all as read:', error);
  }
};

const acceptInvitation = async (notification) => {
  try {
    const data = typeof notification.data === 'string' ? JSON.parse(notification.data) : notification.data;
    const token = data.token;
    
    await axios.post(`/api/hr/employee-invitations/${token}/accept`);
    
    // Mark notification as read
    await axios.post(`/api/notifications/${notification.id}/read`);
    
    alert('Đã chấp nhận lời mời thành công!');
    
    // Reload notifications and page
    await loadNotifications();
    await loadUnreadCount();
    window.location.reload();
  } catch (error) {
    console.error('Error accepting invitation:', error);
    alert(error.response?.data?.message || 'Có lỗi xảy ra khi chấp nhận lời mời');
  }
};

const rejectInvitation = async (notification) => {
  try {
    const data = typeof notification.data === 'string' ? JSON.parse(notification.data) : notification.data;
    const token = data.token;
    
    await axios.post(`/api/hr/employee-invitations/${token}/reject`);
    
    // Mark notification as read
    await axios.post(`/api/notifications/${notification.id}/read`);
    
    alert('Đã từ chối lời mời');
    
    // Reload notifications
    await loadNotifications();
    await loadUnreadCount();
  } catch (error) {
    console.error('Error rejecting invitation:', error);
    alert(error.response?.data?.message || 'Có lỗi xảy ra khi từ chối lời mời');
  }
};

const handleNotificationClick = async (notification) => {
  if (!notification.read_at) {
    try {
      await axios.post(`/api/notifications/${notification.id}/read`);
      await loadNotifications();
    } catch (error) {
      console.error('Error marking as read:', error);
    }
  }
  
  // TODO: Navigate based on notification type
  if (notification.type === 'employee_invitation') {
    // Navigate to invitations page
  }
};

const formatDate = (date) => {
  const d = new Date(date);
  const now = new Date();
  const diff = now - d;
  const minutes = Math.floor(diff / 60000);
  const hours = Math.floor(minutes / 60);
  const days = Math.floor(hours / 24);
  
  if (minutes < 1) return 'Vừa xong';
  if (minutes < 60) return `${minutes} phút trước`;
  if (hours < 24) return `${hours} giờ trước`;
  if (days < 7) return `${days} ngày trước`;
  
  return d.toLocaleDateString('vi-VN');
};

const handleClickOutside = (event) => {
  if (notificationRef.value && !notificationRef.value.contains(event.target)) {
    showNotifications.value = false;
  }
};

// Polling every 30 seconds
let pollingInterval;

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  loadUnreadCount();
  pollingInterval = setInterval(loadUnreadCount, 30000);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  if (pollingInterval) {
    clearInterval(pollingInterval);
  }
});
</script>

