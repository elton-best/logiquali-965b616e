<template>
  <div class="progressive-loader">
    <!-- Skeleton Stage -->
    <div v-if="currentStage === 'skeleton'" class="stage-skeleton">
      <UniversalSkeleton :rows="skeletonRows" :type="skeletonType" />
    </div>

    <!-- Partial Stage -->
    <div v-else-if="currentStage === 'partial'" class="stage-partial">
      <div class="partial-content">
        <slot :data="partialData" name="partial">
          <div class="partial-placeholder">
            <div class="animate-pulse bg-gray-200 rounded h-4 w-3/4 mb-2" />
            <div class="animate-pulse bg-gray-200 rounded h-4 w-1/2" />
          </div>
        </slot>
      </div>

      <div class="partial-overlay">
        <LoadingStates :loading="true" loading-text="Chargement complet..." type="inline" />
      </div>
    </div>

    <!-- Complete Stage -->
    <div v-else-if="currentStage === 'complete'" class="stage-complete">
      <Transition appear name="fade-in">
        <slot :data="completeData" name="complete">
          <div>Contenu chargé</div>
        </slot>
      </Transition>
    </div>

    <!-- Error Stage -->
    <div v-else-if="currentStage === 'error'" class="stage-error">
      <UniversalEmptyState
        :description="errorMessage"
        :primary-action="{ text: 'Réessayer', icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' }"
        :title="errorTitle"
        type="error"
        @primary-action="retry"
      />
    </div>

    <!-- Progress Indicator -->
    <div v-if="showProgress && currentStage !== 'complete'" class="progress-indicator">
      <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
        <span>{{ progressMessage }}</span>
        <span>{{ Math.round(progress) }}%</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div
          class="bg-blue-600 h-2 rounded-full transition-all duration-300"
          :style="{ width: `${progress}%` }"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import LoadingStates from './LoadingStates.vue'
  import UniversalEmptyState from './UniversalEmptyState.vue'
  import UniversalSkeleton from './UniversalSkeleton.vue'

  type LoadingStage = 'skeleton' | 'partial' | 'complete' | 'error'

  interface Props {
    stages?: LoadingStage[]
    currentStage?: LoadingStage
    progress?: number
    showProgress?: boolean
    skeletonType?: 'card' | 'table' | 'list' | 'stats' | 'form' | 'chart'
    skeletonRows?: number
    partialData?: any
    completeData?: any
    errorMessage?: string
    errorTitle?: string
    autoProgress?: boolean
    progressDuration?: number
  }

  const props = withDefaults(defineProps<Props>(), {
    stages: () => ['skeleton', 'complete'],
    currentStage: 'skeleton',
    progress: 0,
    showProgress: false,
    skeletonType: 'card',
    skeletonRows: 3,
    errorMessage: 'Une erreur est survenue lors du chargement.',
    errorTitle: 'Erreur de chargement',
    autoProgress: false,
    progressDuration: 2000,
  })

  const emit = defineEmits<{
    'retry': []
    'stage-change': [stage: LoadingStage]
  }>()

  const internalProgress = ref(0)

  const progressMessage = computed(() => {
    switch (props.currentStage) {
      case 'skeleton': {
        return 'Initialisation...'
      }
      case 'partial': {
        return 'Chargement des détails...'
      }
      case 'complete': {
        return 'Terminé'
      }
      case 'error': {
        return 'Erreur'
      }
      default: {
        return 'Chargement...'
      }
    }
  })

  function retry () {
    emit('retry')
  }

  // Auto-progress simulation
  watch(() => props.currentStage, newStage => {
    emit('stage-change', newStage)

    if (props.autoProgress && newStage !== 'complete' && newStage !== 'error') {
      const targetProgress = newStage === 'skeleton'
        ? 30
        : (newStage === 'partial' ? 80 : 100)

      animateProgress(internalProgress.value, targetProgress)
    }
  })

  function animateProgress (from: number, to: number) {
    const duration = props.progressDuration
    const startTime = Date.now()

    const animate = () => {
      const elapsed = Date.now() - startTime
      const progress = Math.min(elapsed / duration, 1)

      internalProgress.value = from + (to - from) * easeOutCubic(progress)

      if (progress < 1) {
        requestAnimationFrame(animate)
      }
    }

    animate()
  }

  function easeOutCubic (t: number) {
    return 1 - Math.pow(1 - t, 3)
  }

  onMounted(() => {
    if (props.autoProgress) {
      internalProgress.value = 0
    }
  })
</script>

<style scoped>
.progressive-loader {
  position: relative;
  width: 100%;
}

.stage-partial {
  position: relative;
}

.partial-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(transparent, rgba(255, 255, 255, 0.9));
  padding: 1rem;
  display: flex;
  justify-content: center;
}

.progress-indicator {
  margin-top: 1rem;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 0.5rem;
  border: 1px solid rgba(0, 0, 0, 0.1);
}

.fade-in-enter-active {
  transition: all 0.3s ease-out;
}

.fade-in-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.fade-in-enter-to {
  opacity: 1;
  transform: translateY(0);
}
</style>
