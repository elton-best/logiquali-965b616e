<template>
  <span
    :class="[
      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
      statusClasses
    ]"
  >
    <svg
      v-if="showIcon"
      class="status-icon"
      :class="iconClasses"
      fill="currentColor"
      viewBox="0 0 20 20"
    >
      <path
        v-if="status === 'completed'"
        clip-rule="evenodd"
        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
        fill-rule="evenodd"
      />
      <path
        v-else-if="status === 'failed'"
        clip-rule="evenodd"
        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
        fill-rule="evenodd"
      />
      <path
        v-else-if="status === 'processing' || status === 'validating'"
        clip-rule="evenodd"
        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
        fill-rule="evenodd"
      />
      <circle v-else cx="10" cy="10" r="8" />
    </svg>

    <span>{{ statusLabel }}</span>
  </span>
</template>

<script setup lang="ts">
  import type { JobDescriptionImportLog } from '@/services/jobDescriptionImportService'
  import { computed } from 'vue'
  import { jobDescriptionImportService } from '@/services/jobDescriptionImportService'

  interface Props {
    status: JobDescriptionImportLog['status']
    showIcon?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    showIcon: true,
  })

  const statusLabel = computed(() =>
    jobDescriptionImportService.getStatusLabel(props.status),
  )

  const statusClasses = computed(() => {
    const baseClasses = {
      pending: 'bg-gray-100 text-gray-800',
      validating: 'bg-blue-100 text-blue-800',
      processing: 'bg-blue-100 text-blue-800',
      completed: 'bg-green-100 text-green-800',
      failed: 'bg-red-100 text-red-800',
    }

    return baseClasses[props.status] || baseClasses.pending
  })

  const iconClasses = computed(() => {
    if (props.status === 'processing' || props.status === 'validating') {
      return 'animate-spin'
    }
    return ''
  })
</script>

<style scoped>
.status-icon {
  width: 12px;
  height: 12px;
  margin-right: 6px;
  flex-shrink: 0;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
