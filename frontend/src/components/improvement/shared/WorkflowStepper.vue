<template>
  <div class="workflow-stepper">
    <div class="stepper-container">
      <div
        v-for="(state, index) in states"
        :key="state.id"
        class="stepper-item"
        :class="{
          'stepper-completed': index < currentIndex,
          'stepper-current': index === currentIndex,
          'stepper-pending': index > currentIndex
        }"
      >
        <!-- Step circle -->
        <div class="stepper-circle">
          <svg v-if="index < currentIndex" class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
            <path clip-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" fill-rule="evenodd" />
          </svg>
          <span v-else class="text-sm font-semibold">{{ index + 1 }}</span>
        </div>

        <!-- Connector line -->
        <div v-if="index < states.length - 1" class="stepper-line" />

        <!-- State label -->
        <div class="stepper-label">
          <div class="stepper-name">{{ state.name }}</div>
          <div v-if="getStateDescription(state)" class="stepper-description">{{ getStateDescription(state) }}</div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div v-if="showActions && canTransition" class="stepper-actions mt-6">
      <button
        v-if="currentIndex > 0"
        class="btn-secondary"
        :disabled="loading"
        @click="goToPrevious"
      >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
        Retour
      </button>

      <button
        v-if="currentIndex < states.length - 1"
        class="btn-primary ml-auto"
        :disabled="loading"
        @click="goToNext"
      >
        Suivant
        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { WorkflowState } from '@/types/improvement'
  import { computed } from 'vue'

  interface Props {
    states: WorkflowState[]
    currentState: string
    showActions?: boolean
    canTransition?: boolean
    loading?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    showActions: false,
    canTransition: true,
    loading: false,
  })

  const emit = defineEmits<{
    transition: [stateName: string]
  }>()

  // Find current state index
  const currentIndex = computed(() => {
    return props.states.findIndex(s => s.name === props.currentState)
  })

  function getStateDescription (state: WorkflowState): string {
    return (state as WorkflowState & { description?: string }).description || ''
  }

  // Navigate to previous state
  function goToPrevious () {
    if (currentIndex.value > 0) {
      const previousState = props.states[currentIndex.value - 1]
      if (!previousState) return
      emit('transition', previousState.name)
    }
  }

  // Navigate to next state
  function goToNext () {
    if (currentIndex.value < props.states.length - 1) {
      const nextState = props.states[currentIndex.value + 1]
      if (!nextState) return
      emit('transition', nextState.name)
    }
  }
</script>

<style scoped>
.stepper-container {
  @apply flex items-start gap-0;
}

.stepper-item {
  @apply flex flex-col items-center relative flex-1;
}

.stepper-circle {
  @apply w-10 h-10 rounded-full flex items-center justify-center border-2 z-10 bg-white transition-all;
}

.stepper-completed .stepper-circle {
  @apply bg-green-500 border-green-500;
}

.stepper-current .stepper-circle {
  @apply bg-blue-500 border-blue-500 text-white;
}

.stepper-pending .stepper-circle {
  @apply bg-gray-100 border-gray-300 text-gray-500;
}

.stepper-line {
  @apply absolute top-5 left-1/2 w-full h-0.5 bg-gray-300;
}

.stepper-completed .stepper-line {
  @apply bg-green-500;
}

.stepper-label {
  @apply mt-3 text-center max-w-[120px];
}

.stepper-name {
  @apply text-sm font-medium text-gray-900;
}

.stepper-description {
  @apply text-xs text-gray-500 mt-1;
}

.stepper-current .stepper-name {
  @apply text-blue-600 font-semibold;
}

.stepper-actions {
  @apply flex items-center gap-4;
}

.btn-primary {
  @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center;
}

.btn-secondary {
  @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center;
}
</style>
