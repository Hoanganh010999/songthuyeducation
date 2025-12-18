import { ref, computed } from 'vue';
import api from '../services/api';

// Get API base URL
const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000';

// Global state for i18n
const currentLanguage = ref(null);
const translations = ref({});
const availableLanguages = ref([]);
const isLoading = ref(false);

export function useI18n() {
    /**
     * Load available languages
     */
    const loadLanguages = async () => {
        console.log('📡 Loading languages from API...');
        console.log('📡 API Base URL:', API_BASE_URL);
        try {
            const response = await api.get(`${API_BASE_URL}/api/languages`);
            console.log('📡 Languages API response:', response.data);
            if (response.data.success) {
                availableLanguages.value = response.data.data;
                console.log('✅ Languages loaded:', response.data.data.length);
                return response.data.data;
            }
        } catch (error) {
            console.error('❌ Failed to load languages:', error);
            return [];
        }
    };

    /**
     * Load translations for a specific language
     */
    const loadTranslations = async (languageCode) => {
        console.log('📡 Loading translations for:', languageCode);
        isLoading.value = true;
        try {
            const response = await api.get(`${API_BASE_URL}/api/languages/${languageCode}/translations`);
            console.log('📡 Translations API response:', response.data);
            if (response.data.success) {
                currentLanguage.value = response.data.data.language;
                translations.value = response.data.data.translations;
                
                console.log('✅ Translations loaded, groups:', Object.keys(translations.value));
                console.log('📊 Sample - dashboard:', translations.value.dashboard);
                
                // Save to localStorage
                localStorage.setItem('app_language', languageCode);
                localStorage.setItem('app_translations', JSON.stringify(translations.value));
                console.log('💾 Saved to localStorage');
                
                return true;
            }
        } catch (error) {
            console.error('❌ Failed to load translations:', error);
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Initialize i18n system
     */
    const initI18n = async () => {
        console.log('🌍 Initializing i18n...');
        
        // Load available languages
        await loadLanguages();
        console.log('✅ Languages loaded:', availableLanguages.value.length);

        // Try to get saved language from localStorage
        const savedLanguage = localStorage.getItem('app_language');
        const savedTranslations = localStorage.getItem('app_translations');

        if (savedLanguage && savedTranslations) {
            // Use cached translations
            try {
                translations.value = JSON.parse(savedTranslations);
                currentLanguage.value = availableLanguages.value.find(
                    lang => lang.code === savedLanguage
                );
                console.log('📦 Using cached translations for:', savedLanguage);
            } catch (error) {
                console.error('Failed to parse cached translations:', error);
            }
        }

        // Load fresh translations (or default language if no cache)
        const languageCode = savedLanguage || 'vi'; // Default to Vietnamese
        await loadTranslations(languageCode);
        console.log('✅ i18n initialized with language:', languageCode);
        console.log('📊 Total translation groups:', Object.keys(translations.value).length);
    };

    /**
     * Change current language
     */
    const changeLanguage = async (languageCode) => {
        return await loadTranslations(languageCode);
    };

    /**
     * Get translation by key
     * Usage: t('common.welcome') or t('auth.login_button')
     * With params: t('classes.delete_confirm_text', { name: 'Math 101' })
     */
    const t = (key, params = null) => {
        const [group, translationKey] = key.split('.');
        
        if (!group || !translationKey) {
            console.warn(`Invalid translation key: ${key}`);
            return params && typeof params === 'string' ? params : key;
        }

        const groupTranslations = translations.value[group];
        if (!groupTranslations) {
            console.warn(`Translation group not found: ${group}`);
            return params && typeof params === 'string' ? params : key;
        }

        let value = groupTranslations[translationKey];
        if (!value) {
            console.warn(`Translation key not found: ${key}`);
            return params && typeof params === 'string' ? params : key;
        }

        // Replace placeholders with params
        if (params && typeof params === 'object') {
            Object.keys(params).forEach(paramKey => {
                value = value.replace(new RegExp(`\\{${paramKey}\\}`, 'g'), params[paramKey]);
            });
        }

        return value;
    };

    /**
     * Get all translations for a group
     * Usage: tGroup('common') returns { welcome: 'Welcome', ... }
     */
    const tGroup = (group) => {
        return translations.value[group] || {};
    };

    /**
     * Check if a translation exists
     */
    const hasTranslation = (key) => {
        const [group, translationKey] = key.split('.');
        return !!(translations.value[group] && translations.value[group][translationKey]);
    };

    /**
     * Get current language code
     */
    const currentLanguageCode = computed(() => {
        return currentLanguage.value?.code || 'en';
    });

    /**
     * Get current language name
     */
    const currentLanguageName = computed(() => {
        return currentLanguage.value?.name || 'English';
    });

    /**
     * Check if translations are loaded
     */
    const isReady = computed(() => {
        return currentLanguage.value !== null && Object.keys(translations.value).length > 0;
    });

    return {
        // State
        currentLanguage,
        currentLanguageCode,
        currentLanguageName,
        translations,
        availableLanguages,
        isLoading,
        isReady,

        // Methods
        initI18n,
        loadLanguages,
        loadTranslations,
        changeLanguage,
        t,
        tGroup,
        hasTranslation,
    };
}

