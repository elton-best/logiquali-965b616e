<script setup lang="ts">
  import { computed } from 'vue'
  import { cn } from './utils'

  interface TableHeadProps {
    class?: string
  }

  const props = defineProps<TableHeadProps>()

  const headClass = computed(() =>
    cn(
      'text-foreground h-10 px-2 text-left align-middle font-medium whitespace-nowrap [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-[2px]',
      props.class,
    ),
  )
</script>

<template>
  <th :class="headClass" data-slot="table-head">
    <slot />
  </th>
</template>
