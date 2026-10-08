<template>
  <div class="stat-card">
    <div class="flex items-center gap-3">
      <div class="icon-container" :class="`bg-${color}-100`">
        <i :class="`mdi mdi-${icon} text-${color}-600`" />
      </div>
      <div>
        <p class="text-sm text-gray-600">{{ title }}</p>
        <p class="text-2xl font-bold">{{ displayValue }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    title: string
    value: string | number
    unit?: string
    icon?: string
    color?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    unit: '',
    icon: 'chart-bar',
    color: 'blue',
  })

  const displayValue = computed(() => `${props.value}${props.unit}`)
</script>

<style scoped>
.stat-card { @apply bg-white rounded-lg border border-gray-200 p-4; }
.icon-container { @apply w-12 h-12 rounded-full flex items-center justify-center; }
</style>
