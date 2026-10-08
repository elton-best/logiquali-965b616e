<script setup lang="ts">
  import { DropdownMenuSeparator } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'DropdownMenuSeparator',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
  }>()
</script>

<template>
  <DropdownMenuSeparator
    v-bind="$attrs"
    :class="cn('bg-border -mx-1 my-1 h-px', props.class)"
    data-slot="dropdown-menu-separator"
  />
</template>
