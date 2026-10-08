<script setup lang="ts">
  import { DropdownMenuContent, DropdownMenuPortal } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'DropdownMenuContent',
    inheritAttrs: false,
  })

  const props = withDefaults(defineProps<{
    class?: string
    sideOffset?: number
    side?: 'top' | 'right' | 'bottom' | 'left'
    align?: 'start' | 'center' | 'end'
    alignOffset?: number
    forceMount?: boolean
  }>(), {
    sideOffset: 4,
  })
</script>

<template>
  <DropdownMenuPortal>
    <DropdownMenuContent
      v-bind="$attrs"
      :align="align"
      :align-offset="alignOffset"
      :class="cn(
        'bg-popover text-popover-foreground data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 z-50 max-h-(--radix-dropdown-menu-content-available-height) min-w-[8rem] origin-(--radix-dropdown-menu-content-transform-origin) overflow-x-hidden overflow-y-auto rounded-md border p-1 shadow-md',
        props.class
      )"
      data-slot="dropdown-menu-content"
      :force-mount="forceMount"
      :side="side"
      :side-offset="sideOffset"
    >
      <slot />
    </DropdownMenuContent>
  </DropdownMenuPortal>
</template>
