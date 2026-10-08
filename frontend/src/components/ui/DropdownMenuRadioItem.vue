<script setup lang="ts">
  import { Circle } from 'lucide-vue-next'
  import { DropdownMenuItemIndicator, DropdownMenuRadioItem } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'DropdownMenuRadioItem',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
    value: string
    disabled?: boolean
  }>()
</script>

<template>
  <DropdownMenuRadioItem
    v-bind="$attrs"
    :class="cn(
      'focus:bg-accent focus:text-accent-foreground relative flex cursor-default items-center gap-2 rounded-sm py-1.5 pr-2 pl-8 text-sm outline-hidden select-none data-[disabled]:pointer-events-none data-[disabled]:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=\'size-\'])]:size-4',
      props.class
    )"
    data-slot="dropdown-menu-radio-item"
    :disabled="disabled"
    :value="value"
  >
    <span class="pointer-events-none absolute left-2 flex size-3.5 items-center justify-center">
      <DropdownMenuItemIndicator>
        <Circle class="size-2 fill-current" />
      </DropdownMenuItemIndicator>
    </span>
    <slot />
  </DropdownMenuRadioItem>
</template>
