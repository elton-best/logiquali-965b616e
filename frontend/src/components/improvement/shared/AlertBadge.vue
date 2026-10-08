<template>
  <span
    class="alert-badge"
    :class="badgeClass"
  >
    <svg v-if="showIcon" class="badge-icon" fill="currentColor" viewBox="0 0 20 20">
      <path clip-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" fill-rule="evenodd" />
    </svg>
    <span class="badge-text">{{ text }}</span>
    <span v-if="count" class="badge-count">{{ count }}</span>
  </span>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    type: 'warning' | 'danger' | 'info' | 'success'
    text: string
    count?: number
    size?: 'sm' | 'md' | 'lg'
    showIcon?: boolean
    pulse?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    showIcon: true,
    pulse: false,
  })

  const badgeClass = computed(() => {
    const classes = [
      `badge-${props.type}`,
      `badge-${props.size}`,
    ]

    if (props.pulse) {
      classes.push('badge-pulse')
    }

    return classes
  })
</script>

<style scoped>
.alert-badge {
  @apply inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-medium;
}

/* Sizes */
.badge-sm {
  @apply text-xs px-2 py-0.5;
}

.badge-sm .badge-icon {
  @apply w-3 h-3;
}

.badge-md {
  @apply text-sm px-3 py-1;
}

.badge-md .badge-icon {
  @apply w-4 h-4;
}

.badge-lg {
  @apply text-base px-4 py-1.5;
}

.badge-lg .badge-icon {
  @apply w-5 h-5;
}

/* Alert types */
.badge-warning {
  @apply bg-yellow-100 text-yellow-800 border border-yellow-300;
}

.badge-danger {
  @apply bg-red-100 text-red-800 border border-red-300;
}

.badge-info {
  @apply bg-blue-100 text-blue-800 border border-blue-300;
}

.badge-success {
  @apply bg-green-100 text-green-800 border border-green-300;
}

.badge-icon {
  @apply shrink-0;
}

.badge-count {
  @apply ml-1 px-1.5 py-0.5 bg-white bg-opacity-60 rounded-full text-xs font-bold;
}

/* Pulse animation */
.badge-pulse {
  @apply animate-pulse;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}
</style>
