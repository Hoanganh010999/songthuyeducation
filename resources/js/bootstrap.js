import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.baseURL = import.meta.env.BASE_URL + 'school/public/';

// Add interceptor to automatically include branch_id in requests
axios.interceptors.request.use(config => {
    const branchId = localStorage.getItem('current_branch_id');

    // Debug log for Google Drive sync
    if (config.url && config.url.includes('google-drive/sync')) {
        console.log('[Axios Interceptor] 🔍 Google Drive sync request');
        console.log('[Axios Interceptor] 📍 Branch ID from localStorage:', branchId);
        console.log('[Axios Interceptor] 📤 Request config:', {
            method: config.method,
            url: config.url,
            data: config.data,
            params: config.params,
        });
    }

    if (branchId) {
        // Set X-Branch-Id header for all requests
        config.headers = config.headers || {};
        if (!config.headers['X-Branch-Id']) {
            config.headers['X-Branch-Id'] = branchId;
        }

        if (config.method === 'get') {
            // For GET requests: add to params
        config.params = config.params || {};
        if (!config.params.branch_id) {
            config.params.branch_id = branchId;
            }
        } else if (['post', 'put', 'patch'].includes(config.method)) {
            // For POST/PUT/PATCH requests: add to data
            if (config.data && typeof config.data === 'object') {
                // Only add if not already present
                if (!config.data.branch_id && !(config.data instanceof FormData)) {
                    config.data.branch_id = branchId;
                }
            } else if (!config.data) {
                // If no data, create object with branch_id
                config.data = { branch_id: branchId };
            }
        }
    }

    // Debug log after modification
    if (config.url && config.url.includes('google-drive/sync')) {
        console.log('[Axios Interceptor] ✅ Modified request data:', config.data);
    }

    return config;
}, error => {
    return Promise.reject(error);
});

