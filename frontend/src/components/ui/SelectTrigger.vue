<script setup lang="ts">
  import { ChevronDown } from 'lucide-vue-next'
  import { SelectIcon, SelectTrigger } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'SelectTrigger',
    inheritAttrs: false,
  })

  const props = withDefaults(defineProps<{
    class?: string
    size?: 'sm' | 'default'
    disabled?: boolean
  }>(), {
    size: 'default',
  })
</script>

<template>
  <SelectTrigger
    v-bind="$attrs"
    :class="cn(
      'border-input data-[placeholder]:text-muted-foreground [&_svg:not([class*=\'text-\'])]:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive dark:bg-input/30 dark:hover:bg-input/50 flex w-full items-center justify-between gap-2 rounded-md border bg-input-background px-3 py-2 text-sm whitespace-nowrap transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 data-[size=default]:h-9 data-[size=sm]:h-8 *:data-[slot=select-value]:line-clamp-1 *:data-[slot=select-value]:flex *:data-[slot=select-value]:items-center *:data-[slot=select-value]:gap-2 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=\'size-\'])]:size-4',
      props.class
    )"
    :data-size="size"
    data-slot="select-trigger"
    :disabled="disabled"
  >
    <slot />
    <SelectIcon as-child>
      <ChevronDown class="size-4 opacity-50" />
    </SelectIcon>
  </SelectTrigger>
</template>
