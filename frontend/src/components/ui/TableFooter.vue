<script setup lang="ts">
  import { computed } from 'vue'
  import { cn } from './utils'

  interface TableFooterProps {
    class?: string
  }

  const props = defineProps<TableFooterProps>()

  const footerClass = computed(() =>
    cn('bg-muted/50 border-t font-medium [&>tr]:last:border-b-0', props.class),
  )
</script>

<template>
  <tfoot :class="footerClass" data-slot="table-footer">
    <slot />
  </tfoot>
</template>
