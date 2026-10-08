<script setup lang="ts">
  import { computed } from 'vue'
  import { cn } from './utils'

  interface TableCaptionProps {
    class?: string
  }

  const props = defineProps<TableCaptionProps>()

  const captionClass = computed(() =>
    cn('text-muted-foreground mt-4 text-sm', props.class),
  )
</script>

<template>
  <caption :class="captionClass" data-slot="table-caption">
    <slot />
  </caption>
</template>
