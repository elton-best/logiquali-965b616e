<script setup lang="ts">
  import { DialogTitle } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'DialogTitle',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
  }>()
</script>

<template>
  <DialogTitle
    v-bind="$attrs"
    :class="cn('text-lg leading-none font-semibold', props.class)"
    data-slot="dialog-title"
  >
    <slot />
  </DialogTitle>
</template>
