<script setup lang="ts">
  import { Check } from 'lucide-vue-next'
  import { DropdownMenuCheckboxItem, DropdownMenuItemIndicator } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'DropdownMenuCheckboxItem',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
    checked?: boolean | 'indeterminate'
    disabled?: boolean
  }>()

  const emit = defineEmits<{
    'update:checked': [value: boolean]
  }>()
</script>

<template>
  <DropdownMenuCheckboxItem
    v-bind="$attrs"
    :checked="checked"
    :class="cn(
      'focus:bg-accent focus:text-accent-foreground relative flex cursor-default items-center gap-2 rounded-sm py-1.5 pr-2 pl-8 text-sm outline-hidden select-none data-[disabled]:pointer-events-none data-[disabled]:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=\'size-\'])]:size-4',
      props.class
    )"
    data-slot="dropdown-menu-checkbox-item"
    :disabled="disabled"
    @update:checked="emit('update:checked', $event)"
  >
    <span class="pointer-events-none absolute left-2 flex size-3.5 items-center justify-center">
      <DropdownMenuItemIndicator>
        <Check class="size-4" />
      </DropdownMenuItemIndicator>
    </span>
    <slot />
  </DropdownMenuCheckboxItem>
</template>
