<script setup lang="ts">
  import { DialogDescription } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'DialogDescription',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
  }>()
</script>

<template>
  <DialogDescription
    v-bind="$attrs"
    :class="cn('text-muted-foreground text-sm', props.class)"
    data-slot="dialog-description"
  >
    <slot />
  </DialogDescription>
</template>
