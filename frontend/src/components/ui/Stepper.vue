/**
 * Stepper Component
 * Multi-step workflow for forms
 */

<template>
  <div class="w-full">
    <!-- Steps Header -->
    <div class="flex items-center justify-between mb-8">
      <div
        v-for="(step, index) in steps"
        :key="index"
        class="flex items-center"
        :class="{ 'flex-1': index < steps.length - 1 }"
      >
        <!-- Step Circle -->
        <div class="flex flex-col items-center">
          <div
            class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-300"
            :class="getStepClasses(index)"
          >
            <CheckCircle2
              v-if="index < currentStep"
              class="w-5 h-5 text-white"
            />
            <span
              v-else
              class="text-sm font-semibold"
            >
              {{ index + 1 }}
            </span>
          </div>

          <!-- Step Label -->
          <span
            class="mt-2 text-xs font-medium text-center max-w-[100px]"
            :class="index <= currentStep ? 'text-primary-600 dark:text-primary-400' : 'text-neutral-500'"
          >
            {{ step.label }}
          </span>
        </div>

        <!-- Connector Line -->
        <div
          v-if="index < steps.length - 1"
          class="flex-1 h-0.5 mx-4 transition-all duration-300"
          :class="index < currentStep ? 'bg-primary-500' : 'bg-neutral-200 dark:bg-neutral-700'"
        />
      </div>
    </div>

    <!-- Step Content -->
    <div class="min-h-[400px]">
      <slot :name="`step-${currentStep}`" />
    </div>

    <!-- Navigation Buttons -->
    <div class="flex justify-between mt-8 pt-6 border-t border-neutral-200 dark:border-neutral-700">
      <button
        v-if="currentStep > 0"
        class="btn-outline flex items-center gap-2"
        :disabled="loading"
        type="button"
        @click="previousStep"
      >
        <ChevronLeft class="w-4 h-4" />
        Précédent
      </button>

      <div v-else />

      <button
        v-if="currentStep < steps.length - 1"
        class="btn-primary flex items-center gap-2"
        :disabled="loading || !canProceed"
        type="button"
        @click="nextStep"
      >
        Suivant
        <ChevronRight class="w-4 h-4" />
      </button>

      <button
        v-else
        class="btn-primary flex items-center gap-2"
        :disabled="loading || !canProceed"
        type="button"
        @click="$emit('complete')"
      >
        <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
        <CheckCircle2 v-else class="w-4 h-4" />
        {{ submitLabel }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { CheckCircle2, ChevronLeft, ChevronRight, Loader2 } from 'lucide-vue-next'
  import { computed } from 'vue'

  interface Step {
    label: string
    completed?: boolean
  }

  interface Props {
    steps: Step[]
    modelValue?: number
    canProceed?: boolean
    loading?: boolean
    submitLabel?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: 0,
    canProceed: true,
    loading: false,
    submitLabel: 'Terminer',
  })

  const emit = defineEmits<{
    'update:modelValue': [value: number]
    'step-change': [step: number]
    'complete': []
  }>()

  const currentStep = computed({
    get: () => props.modelValue,
    set: (value: number) => {
      emit('update:modelValue', value)
      emit('step-change', value)
    },
  })

  function getStepClasses (index: number): string {
    if (index < currentStep.value) {
      return 'bg-primary-500 border-primary-500'
    }
    if (index === currentStep.value) {
      return 'bg-white dark:bg-neutral-800 border-primary-500 text-primary-600 dark:text-primary-400'
    }
    return 'bg-white dark:bg-neutral-800 border-neutral-300 dark:border-neutral-600 text-neutral-400'
  }

  function nextStep () {
    if (currentStep.value < props.steps.length - 1) {
      currentStep.value++
    }
  }

  function previousStep () {
    if (currentStep.value > 0) {
      currentStep.value--
    }
  }
</script>
