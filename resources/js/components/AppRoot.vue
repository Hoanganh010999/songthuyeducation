<template>
    <router-view />
</template>

<script setup>
import { useVersionCheck } from '@/composables/useVersionCheck';

// Enable auto version checking and reload on updates
useVersionCheck();
</script>

