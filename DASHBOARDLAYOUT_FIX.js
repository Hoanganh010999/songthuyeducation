// =====================================================================
// HƯỚNG DẪN: Fix DashboardLayout.vue
// File: resources/js/layouts/DashboardLayout.vue
// Vị trí: Line 520-537 (method fetchCustomerZaloUnreadCount)
// =====================================================================

// TRƯỚC (SAI - đang gọi POST endpoint với GET method):
/*
const fetchCustomerZaloUnreadCount = async () => {
  try {
    console.log('🔄 [DashboardLayout] Fetching Customer Zalo unread count...');
    const branchId = localStorage.getItem('current_branch_id');
    const response = await api.get('/api/zalo/customer-unread-counts', {  // ❌ SAI: GET method
      params: { branch_id: branchId }
    });

    console.log('📥 [DashboardLayout] Customer Zalo unread response:', response.data);

    if (response.data.success) {
      customerZaloUnreadCount.value = response.data.data.total_unread || 0;
      console.log('📊 [DashboardLayout] Customer Zalo unread count set to:', customerZaloUnreadCount.value);
    }
  } catch (error) {
    console.error('❌ [DashboardLayout] Error fetching Customer Zalo unread count:', error);
  }
};
*/

// SAU (ĐÚNG - gọi endpoint mới với GET method):
const fetchCustomerZaloUnreadCount = async () => {
  try {
    console.log('🔄 [DashboardLayout] Fetching Customer Zalo unread count...');
    const branchId = localStorage.getItem('current_branch_id');
    const response = await api.get('/api/zalo/customers/unread-total', {  // ✅ ĐÚNG: Endpoint mới
      params: { branch_id: branchId }
    });

    console.log('📥 [DashboardLayout] Customer Zalo unread response:', response.data);

    if (response.data.success) {
      customerZaloUnreadCount.value = response.data.data.total_unread || 0;
      console.log('📊 [DashboardLayout] Customer Zalo unread count set to:', customerZaloUnreadCount.value);
    }
  } catch (error) {
    console.error('❌ [DashboardLayout] Error fetching Customer Zalo unread count:', error);
  }
};

// =====================================================================
// THAY ĐỔI CHỈ CẦN SỬA 1 DÒNG:
// =====================================================================
// TỪ:   const response = await api.get('/api/zalo/customer-unread-counts', {
// THÀNH: const response = await api.get('/api/zalo/customers/unread-total', {
