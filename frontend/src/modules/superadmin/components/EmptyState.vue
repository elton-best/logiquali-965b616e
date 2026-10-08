<template>
  <div class="sa-empty">
    <v-icon class="mb-4" :color="iconColor" :size="iconSize">{{ icon }}</v-icon>
    <h3 class="text-h6 font-weight-bold mb-2">{{ title }}</h3>
    <p class="text-body-2 text-medium-emphasis mb-0">{{ description }}</p>
    <div v-if="$slots.actions" class="mt-4">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup lang="ts">
  withDefaults(defineProps<{
    icon?: string
    title: string
    description: string
    iconColor?: string
    iconSize?: number | string
  }>(), {
    icon: 'mdi-information-outline',
    iconColor: 'grey-lighten-1',
    iconSize: 64,
  })
</script>

<style scoped>
.sa-empty {
  text-align: center;
  padding: 32px 16px;
}
</style>
