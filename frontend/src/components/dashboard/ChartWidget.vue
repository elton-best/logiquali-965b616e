/**
 * ChartWidget Component
 * Reusable card wrapper for charts
 */

<script setup lang="ts">
  import { Download, Maximize2 } from 'lucide-vue-next'

  interface Props {
    title: string
    subtitle?: string
    loading?: boolean
  }

  defineProps<Props>()

  const emit = defineEmits<{
    export: []
    fullscreen: []
  }>()
</script>

<template>
  <div class="card p-6">
    <!-- Header -->
    <div class="flex items-start justify-between mb-6">
      <div>
        <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">
          {{ title }}
        </h3>
        <p v-if="subtitle" class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">
          {{ subtitle }}
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          class="p-2 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded-lg transition-colors"
          title="Exporter"
          @click="emit('export')"
        >
          <Download class="w-4 h-4 text-neutral-500" />
        </button>
        <button
          class="p-2 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded-lg transition-colors"
          title="Plein écran"
          @click="emit('fullscreen')"
        >
          <Maximize2 class="w-4 h-4 text-neutral-500" />
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center h-64">
      <div class="w-8 h-8 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin" />
    </div>

    <!-- Chart Content -->
    <div v-else>
      <slot />
    </div>
  </div>
</template>
