<template>
  <div class="relative" ref="dropdownRef">
    <button
      @click="isOpen = !isOpen"
      class="flex items-center gap-2 px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
    >
      <span class="text-xl">{{ currentLanguage?.flag || '🌐' }}</span>
      <span class="text-sm font-medium hidden md:inline">{{ currentLanguageName }}</span>
      <svg
        class="w-4 h-4 transition-transform"
        :class="{ 'rotate-180': isOpen }"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Dropdown Menu -->
    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
      >
        <button
          v-for="language in availableLanguages"
          :key="language.id"
          @click="selectLanguage(language)"
          class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
          :class="{ 'bg-blue-50 text-blue-600': language.code === currentLanguageCode }"
        >
          <span class="text-xl">{{ language.flag }}</span>
          <span class="flex-1 text-left">{{ language.name }}</span>
          <svg
            v-if="language.code === currentLanguageCode"
            class="w-5 h-5 text-blue-600"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path
              fill-rule="evenodd"
              d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
              clip-rule="evenodd"
            />
          </svg>
        </button>

        <!-- Divider -->
        <div class="border-t border-gray-200 my-1"></div>

        <!-- Refresh Translations Button -->
        <button
          @click="refreshTranslations"
          class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
          :disabled="isChanging"
        >
          <svg class="w-5 h-5" :class="{ 'animate-spin': isChanging }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span class="flex-1 text-left">{{ isChanging ? 'Refreshing...' : 'Refresh Translations' }}</span>
        </button>

        <!-- Loading State -->
        <div v-if="isChanging" class="px-4 py-2 text-center">
          <div class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useI18n } from '../composables/useI18n';
import { useSwal } from '../composables/useSwal';

const {
  currentLanguage,
  currentLanguageCode,
  currentLanguageName,
  availableLanguages,
  changeLanguage,
} = useI18n();

const swal = useSwal();

const isOpen = ref(false);
const isChanging = ref(false);
const dropdownRef = ref(null);

const selectLanguage = async (language) => {
  if (language.code === currentLanguageCode.value) {
    isOpen.value = false;
    return;
  }

  isChanging.value = true;
  try {
    const success = await changeLanguage(language.code);
    if (success) {
      // Reload page to apply new translations
      window.location.reload();
    }
  } catch (error) {
    console.error('Failed to change language:', error);
    swal.error('Failed to change language');
  } finally {
    isChanging.value = false;
    isOpen.value = false;
  }
};

const refreshTranslations = async () => {
  isChanging.value = true;
  try {
    // Clear localStorage cache
    localStorage.removeItem('app_translations');
    
    // Reload translations from API
    const success = await changeLanguage(currentLanguageCode.value);
    if (success) {
      swal.success('Translations refreshed!').then(() => {
        // Reload page to apply new translations
        window.location.reload();
      });
    }
  } catch (error) {
    console.error('Failed to refresh translations:', error);
    swal.error('Failed to refresh translations');
  } finally {
    isChanging.value = false;
    isOpen.value = false;
  }
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

