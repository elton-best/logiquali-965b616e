<script setup lang="ts">
  import { ChevronUp } from 'lucide-vue-next'
  import { SelectScrollUpButton } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'SelectScrollUpButton',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
  }>()
</script>

<template>
  <SelectScrollUpButton
    v-bind="$attrs"
    :class="cn('flex cursor-default items-center justify-center py-1', props.class)"
    data-slot="select-scroll-up-button"
  >
    <ChevronUp class="size-4" />
  </SelectScrollUpButton>
</template>
