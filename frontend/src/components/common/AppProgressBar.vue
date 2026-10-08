<template>
  <div
    :aria-valuemax="max"
    :aria-valuemin="0"
    :aria-valuenow="value"
    class="app-progress-bar"
    role="progressbar"
  >
    <div v-if="label || $slots.label" class="progress-label">
      <slot name="label">
        <span class="label-text">{{ label }}</span>
      </slot>
      <span v-if="showPercentage" class="label-percentage">{{ percentage }}%</span>
    </div>

    <div :class="trackClasses">
      <div
        :class="barClasses"
        :style="barStyle"
      >
        <div v-if="indeterminate" class="progress-indeterminate" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    value?: number
    max?: number
    variant?: 'primary' | 'success' | 'warning' | 'error' | 'audit' | 'risk' | 'info'
    size?: 'sm' | 'md' | 'lg'
    label?: string
    showPercentage?: boolean
    indeterminate?: boolean
    striped?: boolean
    animated?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    value: 0,
    max: 100,
    variant: 'primary',
    size: 'md',
    showPercentage: false,
    indeterminate: false,
    striped: false,
    animated: false,
  })

  const percentage = computed(() => {
    if (props.indeterminate) return 0
    return Math.min(Math.max((props.value / props.max) * 100, 0), 100)
  })

  const trackClasses = computed(() => [
    'progress-track',
    `progress-track--${props.size}`,
  ])

  const barClasses = computed(() => [
    'progress-bar',
    `progress-bar--${props.variant}`,
    {
      'progress-bar--striped': props.striped,
      'progress-bar--animated': props.animated,
      'progress-bar--indeterminate': props.indeterminate,
    },
  ])

  const barStyle = computed(() => {
    if (props.indeterminate) {
      return { width: '100%' }
    }
    return {
      width: `${percentage.value}%`,
      transition: 'width 0.3s ease',
    }
  })
</script>

<style scoped>
.app-progress-bar {
  width: 100%;
}

.progress-label {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #475569;
}

.label-percentage {
  color: #64748b;
  font-weight: 600;
}

.progress-track {
  width: 100%;
  background: #e2e8f0;
  border-radius: 9999px;
  overflow: hidden;
  position: relative;
}

.progress-track--sm {
  height: 4px;
}

.progress-track--md {
  height: 8px;
}

.progress-track--lg {
  height: 12px;
}

.progress-bar {
  height: 100%;
  border-radius: 9999px;
  transition: width 0.3s ease;
  position: relative;
  overflow: hidden;
}

/* Variants QHSE */
.progress-bar--primary {
  background: linear-gradient(90deg, #4471c4 0%, #5b8dd9 100%);
}

.progress-bar--success {
  background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
}

.progress-bar--warning {
  background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
}

.progress-bar--error {
  background: linear-gradient(90deg, #ef4444 0%, #f87171 100%);
}

.progress-bar--audit {
  background: linear-gradient(90deg, #8b5cf6 0%, #a78bfa 100%);
}

.progress-bar--risk {
  background: linear-gradient(90deg, #f97316 0%, #fb923c 100%);
}

.progress-bar--info {
  background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 100%);
}

/* Striped */
.progress-bar--striped::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-image: linear-gradient(
    45deg,
    rgba(255, 255, 255, 0.15) 25%,
    transparent 25%,
    transparent 50%,
    rgba(255, 255, 255, 0.15) 50%,
    rgba(255, 255, 255, 0.15) 75%,
    transparent 75%,
    transparent
  );
  background-size: 1rem 1rem;
}

/* Animated */
.progress-bar--animated::before {
  animation: progress-stripes 1s linear infinite;
}

@keyframes progress-stripes {
  0% {
    background-position: 1rem 0;
  }
  100% {
    background-position: 0 0;
  }
}

/* Indeterminate */
.progress-bar--indeterminate {
  background: transparent;
}

.progress-indeterminate {
  position: absolute;
  top: 0;
  left: 0;
  bottom: 0;
  width: 40%;
  background: inherit;
  animation: progress-indeterminate 1.5s ease-in-out infinite;
}

@keyframes progress-indeterminate {
  0% {
    left: -40%;
  }
  100% {
    left: 100%;
  }
}
</style>
