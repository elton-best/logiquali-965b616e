<script setup lang="ts">
  import { computed } from 'vue'
  import { cn } from './utils'

  interface AlertTitleProps {
    class?: string
  }

  const props = defineProps<AlertTitleProps>()

  const titleClass = computed(() =>
    cn(
      'col-start-2 line-clamp-1 min-h-4 font-medium tracking-tight',
      props.class,
    ),
  )
</script>

<template>
  <div :class="titleClass" data-slot="alert-title">
    <slot />
  </div>
</template>
