<script setup lang="ts">
  import { ChevronRight } from 'lucide-vue-next'
  import { DropdownMenuSubTrigger } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'DropdownMenuSubTrigger',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
    inset?: boolean
    disabled?: boolean
  }>()
</script>

<template>
  <DropdownMenuSubTrigger
    v-bind="$attrs"
    :class="cn(
      'focus:bg-accent focus:text-accent-foreground data-[state=open]:bg-accent data-[state=open]:text-accent-foreground flex cursor-default items-center rounded-sm px-2 py-1.5 text-sm outline-hidden select-none data-[inset]:pl-8',
      props.class
    )"
    :data-inset="inset"
    data-slot="dropdown-menu-sub-trigger"
    :disabled="disabled"
  >
    <slot />
    <ChevronRight class="ml-auto size-4" />
  </DropdownMenuSubTrigger>
</template>
