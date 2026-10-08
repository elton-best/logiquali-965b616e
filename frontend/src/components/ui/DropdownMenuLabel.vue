<script setup lang="ts">
  import { DropdownMenuLabel } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'DropdownMenuLabel',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
    inset?: boolean
  }>()
</script>

<template>
  <DropdownMenuLabel
    v-bind="$attrs"
    :class="cn('px-2 py-1.5 text-sm font-medium data-[inset]:pl-8', props.class)"
    :data-inset="inset"
    data-slot="dropdown-menu-label"
  >
    <slot />
  </DropdownMenuLabel>
</template>
