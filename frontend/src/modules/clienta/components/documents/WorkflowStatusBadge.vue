<template>
  <v-chip
    :color="config.color"
    :prepend-icon="config.icon"
    size="x-small"
    variant="tonal"
  >
    {{ config.label }}
  </v-chip>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { getDocumentBusinessStatusConfig } from '@/modules/clienta/utils/documentStatus'

const props = defineProps<{
  status?: string | null
}>()

const config = computed(() => {
  return getDocumentBusinessStatusConfig({ workflow_status: props.status })
})
</script>
