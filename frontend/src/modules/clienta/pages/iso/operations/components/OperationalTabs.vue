<template>
  <v-tabs v-model="tabModel" class="mb-4 modern-tabs" color="primary" grow>
    <v-tab value="actions">
      <v-icon class="mr-2" size="18">mdi-format-list-checks</v-icon>
      Actions Consolidées
    </v-tab>
    <v-tab value="projects">
      <v-icon class="mr-2" size="18">mdi-view-kanban</v-icon>
      Projets / Activités / Tâches
    </v-tab>
  </v-tabs>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed } from 'vue'

  const props = defineProps({
    modelValue: {
      type: String as PropType<'actions' | 'projects'>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: 'actions' | 'projects'): void
  }>()

  const tabModel = computed({
    get: () => props.modelValue,
    set: (value: 'actions' | 'projects') => emit('update:modelValue', value),
  })
</script>

<style scoped>
.modern-tabs {
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
}
</style>
