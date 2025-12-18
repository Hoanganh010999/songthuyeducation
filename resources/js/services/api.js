import axios from 'axios';

// Configure axios defaults
// Use Laravel server URL (not Vite dev server)
const apiBaseURL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000';
axios.defaults.baseURL = apiBaseURL;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';
// DON'T set Content-Type globally - let axios auto-detect based on data type
// For JSON: axios sets 'application/json'
// For FormData: axios sets 'multipart/form-data' with boundary
// axios.defaults.headers.common['Content-Type'] = 'application/json';

// Users API
export const usersApi = {
    getAll(params = {}) {
        return axios.get('/api/users', { params });
    },
    
    getById(id) {
        return axios.get(`/api/users/${id}`);
    },
    
    create(data) {
        return axios.post('/api/users', data);
    },
    
    update(id, data) {
        return axios.put(`/api/users/${id}`, data);
    },
    
    delete(id) {
        return axios.delete(`/api/users/${id}`);
    },
    
    assignRole(userId, roleId) {
        return axios.post(`/api/users/${userId}/assign-role`, { role_id: roleId });
    },
    
    removeRole(userId, roleId) {
        return axios.post(`/api/users/${userId}/remove-role`, { role_id: roleId });
    }
};

// Roles API
export const rolesApi = {
    getAll(params = {}) {
        return axios.get('/api/roles', { params });
    },
    
    getById(id) {
        return axios.get(`/api/roles/${id}`);
    },
    
    create(data) {
        return axios.post('/api/roles', data);
    },
    
    update(id, data) {
        return axios.put(`/api/roles/${id}`, data);
    },
    
    delete(id) {
        return axios.delete(`/api/roles/${id}`);
    },
    
    assignPermission(roleId, permissionId) {
        return axios.post(`/api/roles/${roleId}/assign-permission`, { permission_id: permissionId });
    },
    
    revokePermission(roleId, permissionId) {
        return axios.post(`/api/roles/${roleId}/revoke-permission`, { permission_id: permissionId });
    }
};

// Permissions API
export const permissionsApi = {
    getAll(params = {}) {
        return axios.get('/api/permissions', { params });
    },
    
    getModules() {
        return axios.get('/api/permissions/modules');
    },
    
    getByModule(module) {
        return axios.get(`/api/permissions/by-module/${module}`);
    },
    
    getById(id) {
        return axios.get(`/api/permissions/${id}`);
    },
    
    create(data) {
        return axios.post('/api/permissions', data);
    },
    
    update(id, data) {
        return axios.put(`/api/permissions/${id}`, data);
    },
    
    delete(id) {
        return axios.delete(`/api/permissions/${id}`);
    }
};

export default axios;

