<script setup lang="ts">
  import { computed } from 'vue'
  import { cn } from './utils'

  interface TableRowProps {
    class?: string
  }

  const props = defineProps<TableRowProps>()

  const rowClass = computed(() =>
    cn(
      'hover:bg-muted/50 data-[state=selected]:bg-muted border-b transition-colors',
      props.class,
    ),
  )
</script>

<template>
  <tr :class="rowClass" data-slot="table-row">
    <slot />
  </tr>
</template>
