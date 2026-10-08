<template>
  <!-- Trial Alert -->
  <div v-if="isInTrial && daysRemaining <= 7" class="pa-4">
    <v-alert
      border="start"
      :color="daysRemaining <= 3 ? 'error' : 'warning'"
      density="compact"
      icon="mdi-alert-circle-outline"
      variant="tonal"
    >
      <div class="text-caption font-weight-medium">
        Essai expire dans <strong>{{ daysRemaining }} jour(s)</strong>
      </div>
    </v-alert>
  </div>
</template>

<script setup lang="ts">
  defineProps<{
    isInTrial: boolean
    daysRemaining: number
  }>()
</script>
