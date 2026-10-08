<template>
  <div>
    <h3 class="text-h6 mb-6">Préférences de notification</h3>
    <v-alert class="mb-4" color="info" icon="mdi-information-outline" variant="tonal">
      Les notifications applicatives sont consultables dans le centre de notifications.
    </v-alert>
    <v-btn color="primary" prepend-icon="mdi-bell-outline" variant="outlined" @click="emit('open-center')">
      Ouvrir le centre de notifications
    </v-btn>
  </div>
</template>

<script setup lang="ts">
  const emit = defineEmits<{
    (event: 'open-center'): void
  }>()
</script>
