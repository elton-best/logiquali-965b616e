<script setup lang="ts">
  import { cn } from './utils'

  defineOptions({
    name: 'DialogHeader',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
  }>()
</script>

<template>
  <div
    v-bind="$attrs"
    :class="cn('flex flex-col gap-2 text-center sm:text-left', props.class)"
    data-slot="dialog-header"
  >
    <slot />
  </div>
</template>
