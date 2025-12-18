<template>
  <TestPlayer :assignment-id="assignmentId" />
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import TestPlayer from '@/components/examination/player/TestPlayer.vue'

const route = useRoute()
const assignmentId = computed(() => route.params.assignmentId)
</script>
