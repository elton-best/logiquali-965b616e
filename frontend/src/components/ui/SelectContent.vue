<script setup lang="ts">
  import { SelectContent, SelectPortal, SelectViewport } from 'radix-vue'
  import SelectScrollDownButton from './SelectScrollDownButton.vue'
  import SelectScrollUpButton from './SelectScrollUpButton.vue'
  import { cn } from './utils'

  defineOptions({
    name: 'SelectContent',
    inheritAttrs: false,
  })

  const props = withDefaults(defineProps<{
    class?: string
    position?: 'item-aligned' | 'popper'
    side?: 'top' | 'right' | 'bottom' | 'left'
    sideOffset?: number
    align?: 'start' | 'center' | 'end'
    alignOffset?: number
  }>(), {
    position: 'popper',
  })
</script>

<template>
  <SelectPortal>
    <SelectContent
      v-bind="$attrs"
      :align="align"
      :align-offset="alignOffset"
      :class="cn(
        'bg-popover text-popover-foreground data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 relative z-50 max-h-(--radix-select-content-available-height) min-w-[8rem] origin-(--radix-select-content-transform-origin) overflow-x-hidden overflow-y-auto rounded-md border shadow-md',
        position === 'popper' &&
          'data-[side=bottom]:translate-y-1 data-[side=left]:-translate-x-1 data-[side=right]:translate-x-1 data-[side=top]:-translate-y-1',
        props.class
      )"
      data-slot="select-content"
      :position="position"
      :side="side"
      :side-offset="sideOffset"
    >
      <SelectScrollUpButton />
      <SelectViewport
        :class="cn(
          'p-1',
          position === 'popper' &&
            'h-[var(--radix-select-trigger-height)] w-full min-w-[var(--radix-select-trigger-width)] scroll-my-1'
        )"
      >
        <slot />
      </SelectViewport>
      <SelectScrollDownButton />
    </SelectContent>
  </SelectPortal>
</template>
