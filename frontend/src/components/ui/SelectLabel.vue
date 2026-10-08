<script setup lang="ts">
  import { SelectLabel } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'SelectLabel',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
  }>()
</script>

<template>
  <SelectLabel
    v-bind="$attrs"
    :class="cn('text-muted-foreground px-2 py-1.5 text-xs', props.class)"
    data-slot="select-label"
  >
    <slot />
  </SelectLabel>
</template>
