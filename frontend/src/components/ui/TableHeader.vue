<script setup lang="ts">
  import { computed } from 'vue'
  import { cn } from './utils'

  interface TableHeaderProps {
    class?: string
  }

  const props = defineProps<TableHeaderProps>()

  const headerClass = computed(() =>
    cn('[&_tr]:border-b', props.class),
  )
</script>

<template>
  <thead :class="headerClass" data-slot="table-header">
    <slot />
  </thead>
</template>
