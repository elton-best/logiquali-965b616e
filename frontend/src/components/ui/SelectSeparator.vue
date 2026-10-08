<script setup lang="ts">
  import { SelectSeparator } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'SelectSeparator',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
  }>()
</script>

<template>
  <SelectSeparator
    v-bind="$attrs"
    :class="cn('bg-border pointer-events-none -mx-1 my-1 h-px', props.class)"
    data-slot="select-separator"
  />
</template>
