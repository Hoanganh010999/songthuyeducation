import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

// Layouts
import AuthLayout from '../layouts/AuthLayout.vue';
import DashboardLayout from '../layouts/DashboardLayout.vue';

// Module routes
import { examinationRoutes } from './examination';

// Auth Pages
import Login from '../pages/auth/Login.vue';
import ForgotPassword from '../pages/auth/ForgotPassword.vue';
import ResetPassword from '../pages/auth/ResetPassword.vue';

// Dashboard Pages
import Dashboard from '../pages/Dashboard.vue';
import UsersList from '../pages/users/UsersList.vue';
import UsersCreate from '../pages/users/UsersCreate.vue';
import UsersEdit from '../pages/users/UsersEdit.vue';
import BranchesList from '../pages/branches/BranchesList.vue';
import SalesIndex from '../pages/sales/SalesIndex.vue';
import CalendarView from '../pages/calendar/CalendarView.vue';
import TodayCalendarView from '../pages/calendar/TodayCalendarView.vue';
import RolesList from '../pages/roles/RolesList.vue';
import RolesCreate from '../pages/roles/RolesCreate.vue';
import RolesEdit from '../pages/roles/RolesEdit.vue';
import PermissionsList from '../pages/permissions/PermissionsList.vue';
import SettingsIndex from '../pages/settings/SettingsIndex.vue';
import HRIndex from '../pages/hr/HRIndex.vue';
import OrganizationChart from '../pages/hr/OrganizationChartSimple.vue';
import EmployeeList from '../pages/hr/EmployeeList.vue';
import InvitationsList from '../pages/hr/InvitationsList.vue';
import JobTitleSettings from '../pages/hr/JobTitleSettings.vue';
import QualityIndex from '../pages/quality/QualityIndex.vue';
import ClassDetail from '../pages/quality/ClassDetail.vue';
import SyllabusDetail from '../pages/quality/SyllabusDetail.vue';
import StudentsList from '../pages/quality/StudentsList.vue';
import ParentsList from '../pages/quality/ParentsList.vue';
import QualitySettings from '../pages/quality/QualitySettings.vue';
import LessonPlanEditor from '../pages/quality/LessonPlanEditor.vue';
import MaterialsList from '../pages/quality/MaterialsList.vue';
import MaterialsManager from '../pages/quality/MaterialsManager.vue';
import CourseIndex from '../pages/course/CourseIndex.vue';
import HolidaysIndex from '../pages/holidays/HolidaysIndex.vue';
import AccountingIndex from '../pages/accounting/AccountingIndex.vue';
import ZaloIndex from '../pages/zalo/ZaloIndex.vue';
import GroupAssignment from '../pages/zalo/GroupAssignment.vue';
import GoogleDriveIndex from '../pages/google-drive/GoogleDriveIndex.vue';
import CustomersList from '../pages/customers/CustomersList.vue';
import CustomersKanban from '../pages/customers/CustomersKanban.vue';
import WorkIndex from '../pages/work/WorkIndex.vue';
import WorkDashboard from '../pages/work/WorkDashboard.vue';
import WorkItemsList from '../pages/work/WorkItemsList.vue';
import WorkItemDetail from '../pages/work/WorkItemDetail.vue';
import WorkItemForm from '../pages/work/WorkItemForm.vue';

const routes = [
    {
        path: '/auth',
        component: AuthLayout,
        children: [
            {
                path: 'login',
                name: 'login',
                component: Login,
                meta: { guest: true }
            },
            {
                path: 'forgot-password',
                name: 'forgot-password',
                component: ForgotPassword,
                meta: { guest: true }
            },
            {
                path: 'reset-password',
                name: 'reset-password',
                component: ResetPassword,
                meta: { guest: true }
            }
        ]
    },
    {
        path: '/',
        component: DashboardLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                redirect: '/dashboard'
            },
            {
                path: 'dashboard',
                name: 'dashboard',
                component: Dashboard
            },
            {
                path: 'users',
                name: 'users.list',
                component: UsersList,
                meta: { permission: 'users.view' }
            },
            {
                path: 'users/create',
                name: 'users.create',
                component: UsersCreate,
                meta: { permission: 'users.create' }
            },
            {
                path: 'users/:id/edit',
                name: 'users.edit',
                component: UsersEdit,
                meta: { permission: 'users.edit' }
            },
            {
                path: 'branches',
                name: 'branches.list',
                component: BranchesList,
                meta: { permission: 'branches.view' }
            },
            {
                path: 'sales',
                name: 'sales.index',
                component: SalesIndex,
                meta: { permission: 'customers.view' }
            },
            {
                path: 'customers',
                name: 'customers.list',
                component: CustomersList,
                meta: { permission: 'customers.view' }
            },
            {
                path: 'customers/kanban',
                name: 'customers.kanban',
                component: CustomersKanban,
                meta: { permission: 'customers.view' }
            },
            {
                path: 'calendar',
                name: 'calendar',
                component: CalendarView,
                meta: { permission: 'calendar.view' }
            },
            {
                path: 'calendar/today',
                name: 'calendar.today',
                component: TodayCalendarView,
                meta: { permission: 'calendar.view' }
            },
            {
                path: 'roles',
                name: 'roles.list',
                component: RolesList,
                meta: { permission: 'roles.view' }
            },
            {
                path: 'roles/create',
                name: 'roles.create',
                component: RolesCreate,
                meta: { permission: 'roles.create' }
            },
            {
                path: 'roles/:id/edit',
                name: 'roles.edit',
                component: RolesEdit,
                meta: { permission: 'roles.edit' }
            },
            {
                path: 'permissions',
                name: 'permissions.list',
                component: PermissionsList
            },
            {
                path: 'settings',
                name: 'Settings',
                component: SettingsIndex,
                meta: { role: 'super-admin' }
            },
            {
                path: 'hr',
                component: HRIndex,
                meta: { permission: 'hr.view' },
                children: [
                    {
                        path: '',
                        redirect: '/hr/org-chart'
                    },
                    {
                        path: 'org-chart',
                        name: 'hr.org-chart',
                        component: OrganizationChart,
                        meta: { permission: 'org_chart.view' }
                    },
                    {
                        path: 'employees',
                        name: 'hr.employees',
                        component: EmployeeList,
                        meta: { permission: 'employees.view' }
                    },
                    {
                        path: 'invitations',
                        name: 'hr.invitations',
                        component: InvitationsList,
                        meta: { permission: 'invitations.view' }
                    },
                    {
                        path: 'job-titles',
                        name: 'hr.job-titles',
                        component: JobTitleSettings,
                        meta: { permission: 'hr.manage' }
                    },
                    {
                        path: 'departments',
                        name: 'hr.departments',
                        redirect: '/hr/org-chart',
                        meta: { permission: 'departments.view' }
                    }
                ]
            },
            {
                path: 'quality',
                name: 'quality.index',
                component: QualityIndex,
                meta: { permission: 'quality.view' }
            },
            {
                path: 'quality/students',
                name: 'quality.students',
                component: StudentsList,
                meta: { permission: 'quality.view' }
            },
            {
                path: 'quality/parents',
                name: 'quality.parents',
                component: ParentsList,
                meta: { permission: 'quality.view' }
            },
            {
                path: 'quality/settings',
                name: 'quality.settings',
                component: QualitySettings,
                meta: { permission: 'quality.manage_settings' }
            },
            {
                path: 'quality/classes/:id',
                name: 'class.detail',
                component: ClassDetail,
                meta: { permission: 'classes.view' }
            },
            {
                path: 'quality/syllabus/:id',
                name: 'syllabus.detail',
                component: SyllabusDetail,
                meta: { permission: ['lesson_plans.view', 'syllabus.view'] }
            },
            {
                path: 'quality/lesson-plan/:sessionId',
                name: 'lesson-plan.editor',
                component: LessonPlanEditor,
                meta: { permission: 'lesson_plans.edit' }
            },
            {
                path: 'quality/materials/:sessionId',
                name: 'quality.materials-list',
                component: MaterialsList,
                meta: { permission: 'lesson_plans.view' }
            },
            {
                path: 'quality/materials/:sessionId/:materialId',
                name: 'quality.materials-edit',
                component: MaterialsManager,
                meta: { permission: 'lesson_plans.edit' }
            },
            {
                path: 'course',
                name: 'course.index',
                component: CourseIndex,
                meta: { permission: 'course.view' }
            },
            {
                path: 'zalo',
                name: 'zalo.index',
                component: ZaloIndex,
                meta: { permission: 'zalo.view' }
            },
            {
                path: 'zalo/group-assignment',
                name: 'zalo.group-assignment',
                component: GroupAssignment,
                meta: { permission: 'zalo.assign_groups' }
            },
            {
                path: 'google-drive',
                name: 'google-drive.index',
                component: GoogleDriveIndex,
                meta: { permission: 'google-drive.view' }
            },
            {
                path: 'holidays',
                name: 'holidays.index',
                component: HolidaysIndex,
                meta: { permission: 'holidays.view' }
            },
            {
                path: 'accounting',
                name: 'accounting.index',
                component: AccountingIndex,
                meta: { permission: 'accounting.view' }
            },
            {
                path: 'work',
                component: WorkIndex,
                meta: { permission: 'work_items.view_own' },
                children: [
                    {
                        path: '',
                        redirect: '/work/dashboard'
                    },
                    {
                        path: 'dashboard',
                        name: 'work.dashboard',
                        component: WorkDashboard,
                        meta: { permission: 'work_management.dashboard' }
                    },
                    {
                        path: 'items',
                        name: 'work.items.list',
                        component: WorkItemsList,
                        meta: { permission: 'work_items.view_own' }
                    },
                    {
                        path: 'items/create',
                        name: 'work.item.create',
                        component: WorkItemForm,
                        meta: { permission: 'work_items.create' }
                    },
                    {
                        path: 'items/:id/edit',
                        name: 'work.item.edit',
                        component: WorkItemForm,
                        meta: { permission: 'work_items.edit' }
                    },
                    {
                        path: 'items/:id',
                        name: 'work.item.detail',
                        component: WorkItemDetail,
                        meta: { permission: 'work_items.view_own' }
                    }
                ]
            },
            // Examination module routes
            ...examinationRoutes
        ]
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();
    
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'login' });
    } else if (to.meta.guest && authStore.isAuthenticated) {
        next({ name: 'dashboard' });
    } else if (to.meta.permission) {
        // Support multiple permissions (OR logic)
        const permissions = Array.isArray(to.meta.permission) 
            ? to.meta.permission 
            : [to.meta.permission];
        
        const hasAccess = permissions.some(permission => authStore.hasPermission(permission));
        
        if (!hasAccess) {
            next({ name: 'dashboard' });
        } else {
            next();
        }
    } else if (to.meta.role && !authStore.hasRole(to.meta.role)) {
        next({ name: 'dashboard' });
    } else {
        next();
    }
});

export default router;
