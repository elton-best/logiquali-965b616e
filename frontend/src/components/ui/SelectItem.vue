<script setup lang="ts">
  import { Check } from 'lucide-vue-next'
  import { SelectItem, SelectItemIndicator, SelectItemText } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'SelectItem',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
    value: string | number
    disabled?: boolean
    textValue?: string
  }>()
</script>

<template>
  <SelectItem
    v-bind="$attrs"
    :class="cn(
      'focus:bg-accent focus:text-accent-foreground [&_svg:not([class*=\'text-\'])]:text-muted-foreground relative flex w-full cursor-default items-center gap-2 rounded-sm py-1.5 pr-8 pl-2 text-sm outline-hidden select-none data-[disabled]:pointer-events-none data-[disabled]:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=\'size-\'])]:size-4 *:[span]:last:flex *:[span]:last:items-center *:[span]:last:gap-2',
      props.class
    )"
    data-slot="select-item"
    :disabled="disabled"
    :text-value="textValue"
    :value="String(value)"
  >
    <span class="absolute right-2 flex size-3.5 items-center justify-center">
      <SelectItemIndicator>
        <Check class="size-4" />
      </SelectItemIndicator>
    </span>
    <SelectItemText>
      <slot />
    </SelectItemText>
  </SelectItem>
</template>
