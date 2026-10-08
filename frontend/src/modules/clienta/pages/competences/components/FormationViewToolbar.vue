<template>
  <div class="d-flex justify-space-between align-center mb-4">
    <v-btn-toggle v-model="viewValue" divided mandatory variant="outlined">
      <v-btn icon="mdi-view-grid" value="grid" />
      <v-btn icon="mdi-table" value="table" />
      <v-btn icon="mdi-calendar" value="calendar" />
    </v-btn-toggle>

    <div class="text-body-2 text-grey">
      {{ count }} formations
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed } from 'vue'

  type ViewMode = 'grid' | 'table' | 'calendar'

  const props = defineProps({
    viewMode: {
      type: String as PropType<ViewMode>,
      required: true,
    },
    count: {
      type: Number,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'update:viewMode', value: ViewMode): void
  }>()

  const viewValue = computed({
    get: () => props.viewMode,
    set: value => emit('update:viewMode', value),
  })
</script>
