<script setup lang="ts">
  import { cn } from './utils'

  defineOptions({
    name: 'DialogFooter',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
  }>()
</script>

<template>
  <div
    v-bind="$attrs"
    :class="cn('flex flex-col-reverse gap-2 sm:flex-row sm:justify-end', props.class)"
    data-slot="dialog-footer"
  >
    <slot />
  </div>
</template>
