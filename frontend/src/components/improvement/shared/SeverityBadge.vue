<template>
  <span
    class="severity-badge"
    :class="badgeClass"
  >
    <span class="badge-icon">{{ badgeIcon }}</span>
    <span class="badge-text">{{ badgeText }}</span>
  </span>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    severity: 'low' | 'medium' | 'high' | 'critical'
    size?: 'sm' | 'md' | 'lg'
    showIcon?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    showIcon: true,
  })

  const badgeConfig = {
    low: {
      text: 'Faible',
      icon: '●',
      class: 'badge-low',
    },
    medium: {
      text: 'Moyen',
      icon: '●',
      class: 'badge-medium',
    },
    high: {
      text: 'Élevé',
      icon: '●',
      class: 'badge-high',
    },
    critical: {
      text: 'Critique',
      icon: '⚠',
      class: 'badge-critical',
    },
  }

  const badgeClass = computed(() => {
    const config = badgeConfig[props.severity]
    const sizeClass = `badge-${props.size}`
    return [config.class, sizeClass]
  })

  const badgeText = computed(() => badgeConfig[props.severity].text)
  const badgeIcon = computed(() => props.showIcon ? badgeConfig[props.severity].icon : '')
</script>

<style scoped>
.severity-badge {
  @apply inline-flex items-center gap-1 px-2 py-1 rounded-full font-medium;
}

/* Sizes */
.badge-sm {
  @apply text-xs px-2 py-0.5;
}

.badge-md {
  @apply text-sm px-3 py-1;
}

.badge-lg {
  @apply text-base px-4 py-1.5;
}

/* Severity colors */
.badge-low {
  @apply bg-green-100 text-green-800 border border-green-300;
}

.badge-medium {
  @apply bg-yellow-100 text-yellow-800 border border-yellow-300;
}

.badge-high {
  @apply bg-orange-100 text-orange-800 border border-orange-300;
}

.badge-critical {
  @apply bg-red-100 text-red-800 border border-red-300;
}

.badge-icon {
  @apply text-base;
}

.badge-text {
  @apply font-semibold;
}
</style>
