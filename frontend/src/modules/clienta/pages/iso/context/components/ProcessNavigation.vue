<template>
  <v-card class="navigation-card" elevation="0" rounded="xl">
    <v-card-actions class="pa-6">
      <v-btn
        v-if="currentStep > 1"
        prepend-icon="mdi-chevron-left"
        size="x-large"
        variant="tonal"
        @click="emit('prev')"
      >
        Précédent
      </v-btn>
      <v-spacer />
      <v-btn
        v-if="currentStep < totalSteps"
        append-icon="mdi-chevron-right"
        :color="steps[currentStep - 1]?.color || 'primary'"
        size="x-large"
        @click="emit('next')"
      >
        Suivant
      </v-btn>
      <v-btn
        v-else
        color="success"
        :loading="saving"
        prepend-icon="mdi-check-circle"
        size="x-large"
        @click="emit('save')"
      >
        Enregistrer définitivement
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'

  type Step = { label: string, color: string, gradient?: string }

  defineProps({
    currentStep: {
      type: Number,
      required: true,
    },
    steps: {
      type: Array as PropType<Step[]>,
      required: true,
    },
    saving: {
      type: Boolean,
      required: true,
    },
    totalSteps: {
      type: Number,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'prev'): void
    (event: 'next'): void
    (event: 'save'): void
  }>()
</script>
