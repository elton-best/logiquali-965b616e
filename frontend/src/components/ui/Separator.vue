<script setup lang="ts">
  import { Separator as SeparatorPrimitive } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'Separator',
    inheritAttrs: false,
  })

  const props = withDefaults(defineProps<{
    class?: string
    orientation?: 'horizontal' | 'vertical'
    decorative?: boolean
  }>(), {
    orientation: 'horizontal',
    decorative: true,
  })
</script>

<template>
  <SeparatorPrimitive
    v-bind="$attrs"
    :class="cn(
      'bg-border shrink-0 data-[orientation=horizontal]:h-px data-[orientation=horizontal]:w-full data-[orientation=vertical]:h-full data-[orientation=vertical]:w-px',
      props.class
    )"
    data-slot="separator-root"
    :decorative="decorative"
    :orientation="orientation"
  />
</template>
