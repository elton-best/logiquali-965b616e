<template>
  <v-card class="mb-6" elevation="0" rounded="xl">
    <v-card-text class="pa-6">
      <div class="stepper-container">
        <div
          v-for="(step, index) in steps"
          :key="index"
          class="step-item"
          :class="{ active: modelValue === index + 1, completed: modelValue > index + 1 }"
          @click="emit('update:modelValue', index + 1)"
        >
          <div class="step-circle" :style="`background: ${step.color}`">
            <v-icon v-if="modelValue > index + 1" color="white">mdi-check</v-icon>
            <span v-else class="step-number">{{ index + 1 }}</span>
          </div>
          <div class="step-label">{{ step.label }}</div>
          <div v-if="index < steps.length - 1" class="step-line" :class="{ filled: modelValue > index + 1 }" />
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'

  type Step = { label: string, color: string, gradient?: string }

  defineProps({
    steps: {
      type: Array as PropType<Step[]>,
      required: true,
    },
    modelValue: {
      type: Number,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'update:modelValue', value: number): void
  }>()
</script>
