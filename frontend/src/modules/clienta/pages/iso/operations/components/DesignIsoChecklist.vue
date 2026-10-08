<template>
  <v-card class="mb-4" elevation="0" rounded="lg" variant="tonal">
    <v-card-text>
      <div class="text-subtitle-2 font-weight-bold mb-3">Éléments ISO 8.3 à tracer</div>
      <div class="d-flex flex-wrap ga-2">
        <v-chip
          v-for="item in items"
          :key="item"
          color="primary"
          size="small"
          variant="tonal"
        >{{ item }}</v-chip>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  defineProps<{
    items: string[]
  }>()
</script>
