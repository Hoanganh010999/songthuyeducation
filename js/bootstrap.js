import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.baseURL = import.meta.env.BASE_URL + 'school/public/';

// Add interceptor to automatically include branch_id in requests
axios.interceptors.request.use(config => {
    const branchId = localStorage.getItem('current_branch_id');
    if (branchId) {
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
            }
        }
    }
    return config;
}, error => {
    return Promise.reject(error);
});
