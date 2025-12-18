import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('token') || null,
        loading: false,
        branchRoles: [] // Roles specific to current branch
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        
        currentUser: (state) => state.user,
        
        userRoles: (state) => {
            // If branchRoles are loaded, use them; otherwise fallback to global roles
            return state.branchRoles.length > 0 ? state.branchRoles : (state.user?.roles || []);
        },
        
        isSuperAdmin: (state) => {
            return state.user?.roles?.some(role => role.name === 'super-admin') || false;
        },
        
        userPermissions: (state) => {
            // If user has all_permissions computed from backend, use it
            if (state.user?.all_permissions) {
                return state.user.all_permissions;
            }
            
            // Fallback to old method
            if (!state.user?.roles) return [];
            const permissions = [];
            state.user.roles.forEach(role => {
                role.permissions?.forEach(permission => {
                    if (!permissions.find(p => p.id === permission.id)) {
                        permissions.push(permission);
                    }
                });
            });
            return permissions;
        }
    },

    actions: {
        async login(credentials) {
            this.loading = true;
            try {
                const response = await axios.post('/api/login', credentials);
                
                if (response.data.success) {
                    this.token = response.data.token;
                    this.user = response.data.user;
                    
                    localStorage.setItem('token', this.token);
                    axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
                    
                    // Save current branch to localStorage
                    if (this.user.current_branch_id) {
                        localStorage.setItem('current_branch_id', this.user.current_branch_id);
                    }
                    
                    return { success: true };
                }
            } catch (error) {
                return {
                    success: false,
                    message: error.response?.data?.message || 'Đăng nhập thất bại'
                };
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            try {
                await axios.post('/api/logout');
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                this.token = null;
                this.user = null;
                this.branchRoles = [];
                localStorage.removeItem('token');
                localStorage.removeItem('current_branch_id');
                delete axios.defaults.headers.common['Authorization'];
            }
        },

        async fetchUser() {
            if (!this.token) return;
            
            try {
                const response = await axios.get('/api/user');
                if (response.data.success) {
                    this.user = response.data.data;
                }
            } catch (error) {
                console.error('Fetch user error:', error);
                this.logout();
            }
        },

        hasPermission(permission) {
            // Super-admin có tất cả permissions
            if (this.isSuperAdmin) {
                return true;
            }
            return this.userPermissions.some(p => p.name === permission);
        },

        hasRole(role) {
            // Super-admin có tất cả roles
            if (this.isSuperAdmin) {
                return true;
            }
            return this.userRoles.some(r => r.name === role);
        },

        hasAnyPermission(permissions) {
            // Super-admin có tất cả permissions
            if (this.isSuperAdmin) {
                return true;
            }
            return permissions.some(permission => this.hasPermission(permission));
        },

        async fetchBranchRoles(branchId) {
            if (!branchId) {
                this.branchRoles = [];
                return;
            }

            try {
                const response = await axios.get('/api/user/branch-roles', {
                    params: { branch_id: branchId }
                });
                
                if (response.data.success) {
                    this.branchRoles = response.data.data || [];
                }
            } catch (error) {
                console.error('Fetch branch roles error:', error);
                this.branchRoles = [];
            }
        },

        initAuth() {
            if (this.token) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
                this.fetchUser();
            }
        }
    }
});

