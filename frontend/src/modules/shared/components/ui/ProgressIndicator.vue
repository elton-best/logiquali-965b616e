<template>
  <div class="progress-indicator" :class="{ 'progress-loading': loading }">
    <!-- Linear Progress Bar -->
    <v-progress-linear
      v-if="type === 'linear'"
      :color="color"
      :height="height"
      :indeterminate="indeterminate"
      :model-value="progressValue"
      rounded
      :striped="striped"
    >
      <template v-if="showLabel" #default="{ value: progressValueSlot }">
        <strong class="progress-label">{{ Math.ceil(progressValueSlot) }}%</strong>
      </template>
    </v-progress-linear>

    <!-- Circular Progress -->
    <v-progress-circular
      v-else-if="type === 'circular'"
      :color="color"
      :indeterminate="indeterminate"
      :model-value="progressValue"
      :rotate="rotate"
      :size="size"
      :width="width"
    >
      <template v-if="showLabel" #default>
        <span class="progress-label">{{ Math.ceil(progressValue) }}%</span>
      </template>
    </v-progress-circular>

    <!-- Step Progress -->
    <div v-else-if="type === 'steps'" class="step-progress">
      <div
        v-for="(step, index) in steps"
        :key="index"
        class="step-item"
        :class="getStepClass(index)"
      >
        <div class="step-indicator">
          <v-icon v-if="index < currentStep" color="white" size="20">
            mdi-check
          </v-icon>
          <span v-else class="step-number">{{ index + 1 }}</span>
        </div>
        <div v-if="showStepLabels" class="step-label">{{ step }}</div>
        <div
          v-if="index < steps.length - 1"
          class="step-connector"
          :class="{ 'step-connector-active': index < currentStep }"
        />
      </div>
    </div>

    <!-- Custom Slot -->
    <slot v-else />
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  /**
   * Composant d'indicateur de progression polyvalent
   *
   * @example
   * // Barre de progression linéaire
   * <ProgressIndicator type="linear" :value="75" show-label />
   *
   * @example
   * // Progress circulaire
   * <ProgressIndicator type="circular" :value="60" :size="80" />
   *
   * @example
   * // Progress par étapes
   * <ProgressIndicator
   *   type="steps"
   *   :steps="['Étape 1', 'Étape 2', 'Étape 3']"
   *   :current-step="1"
   *   show-step-labels
   * />
   */

  interface Props {
    /** Type d'indicateur */
    type?: 'linear' | 'circular' | 'steps'
    /** Valeur de progression (0-100) */
    value?: number
    /** Couleur de la progression */
    color?: string
    /** Indéterminé (animation continue) */
    indeterminate?: boolean
    /** Afficher le label de pourcentage */
    showLabel?: boolean
    /** État de chargement */
    loading?: boolean

    // Linear specific
    /** Hauteur de la barre (linear) */
    height?: number
    /** Barre striée (linear) */
    striped?: boolean

    // Circular specific
    /** Taille du cercle (circular) */
    size?: number
    /** Largeur de la ligne (circular) */
    width?: number
    /** Rotation de départ (circular) */
    rotate?: number

    // Steps specific
    /** Liste des étapes (steps) */
    steps?: string[]
    /** Étape actuelle (steps) */
    currentStep?: number
    /** Afficher les labels d'étapes */
    showStepLabels?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    type: 'linear',
    value: 0,
    color: 'primary',
    indeterminate: false,
    showLabel: false,
    loading: false,
    height: 8,
    striped: false,
    size: 64,
    width: 6,
    rotate: 0,
    steps: () => [],
    currentStep: 0,
    showStepLabels: true,
  })

  const progressValue = computed(() => {
    return Math.min(Math.max(props.value, 0), 100)
  })

  function getStepClass (index: number) {
    if (index < props.currentStep) return 'step-completed'
    if (index === props.currentStep) return 'step-active'
    return 'step-pending'
  }
</script>

<style scoped>
.progress-indicator {
  position: relative;
}

.progress-label {
  font-size: 12px;
  font-weight: 600;
}

/* Step Progress Styles */
.step-progress {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 20px 0;
}

.step-item {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
}

.step-indicator {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 2;
  position: relative;
}

.step-completed .step-indicator {
  background: rgb(var(--v-theme-success));
  color: white;
  box-shadow: 0 4px 12px rgba(var(--v-theme-success), 0.3);
}

.step-active .step-indicator {
  background: rgb(var(--v-theme-primary));
  color: white;
  box-shadow: 0 4px 12px rgba(var(--v-theme-primary), 0.3);
  animation: pulse 2s ease-in-out infinite;
}

.step-pending .step-indicator {
  background: rgb(var(--v-theme-surface-variant));
  color: rgb(var(--v-theme-on-surface-variant));
  border: 2px solid rgba(var(--v-border-color), 0.2);
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

.step-number {
  font-size: 16px;
}

.step-label {
  margin-top: 12px;
  font-size: 13px;
  font-weight: 500;
  color: rgb(var(--v-theme-on-surface));
  text-align: center;
  max-width: 120px;
}

.step-connector {
  position: absolute;
  top: 20px;
  left: 50%;
  width: 100%;
  height: 2px;
  background: rgba(var(--v-border-color), 0.2);
  transition: background 0.3s ease;
  z-index: 1;
}

.step-connector-active {
  background: rgb(var(--v-theme-success));
}

.step-item:last-child .step-connector {
  display: none;
}

/* Responsive */
@media (max-width: 768px) {
  .step-indicator {
    width: 32px;
    height: 32px;
  }

  .step-number {
    font-size: 14px;
  }

  .step-label {
    font-size: 11px;
    max-width: 80px;
  }

  .step-connector {
    top: 16px;
  }
}
</style>
