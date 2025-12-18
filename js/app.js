import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import { useAuthStore } from './stores/auth';
import { useI18n } from './composables/useI18n';

// Create root component
const app = createApp({
    template: '<router-view />'
});

const pinia = createPinia();

// Use plugins
app.use(pinia);
app.use(router);

// Error handler
app.config.errorHandler = (err, instance, info) => {
    console.error('Vue Error:', err);
    console.error('Info:', info);
};

// Initialize auth and i18n after mounting
router.isReady().then(async () => {
    console.log('🚀 Router ready, starting initialization...');
    
    const authStore = useAuthStore();
    const { initI18n } = useI18n();
    
    console.log('📞 Calling initI18n()...');
    
    // Initialize i18n first
    try {
        await initI18n();
        console.log('✅ i18n initialized successfully');
    } catch (error) {
        console.error('❌ i18n initialization failed:', error);
    }
    
    // Then initialize auth
    console.log('🔐 Initializing auth...');
    authStore.initAuth();
    
    app.mount('#app');
    console.log('✅ Vue app mounted successfully');
});
