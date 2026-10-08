<template>
  <v-card class="mb-4" elevation="1">
    <v-card-text class="pa-4">
      <div class="d-flex flex-wrap gap-2 align-center">
        <v-select
          v-model="yearValue"
          density="comfortable"
          hide-details
          item-title="title"
          item-value="value"
          :items="yearOptions"
          label="Année du plan"
          style="max-width: 220px;"
          variant="outlined"
        />
        <div class="text-caption text-grey">
          Les formations sont rattachées automatiquement au plan annuel par année et site.
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed } from 'vue'

  type YearOption = { title: string, value: number | null }

  const props = defineProps({
    modelValue: {
      type: Number,
      required: true,
    },
    yearOptions: {
      type: Array as PropType<YearOption[]>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'update:modelValue', value: number): void
  }>()

  const yearValue = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value as number),
  })
</script>
