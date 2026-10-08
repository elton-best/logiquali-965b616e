<script setup lang="ts">
  import { ChevronDown } from 'lucide-vue-next'
  import { SelectScrollDownButton } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'SelectScrollDownButton',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
  }>()
</script>

<template>
  <SelectScrollDownButton
    v-bind="$attrs"
    :class="cn('flex cursor-default items-center justify-center py-1', props.class)"
    data-slot="select-scroll-down-button"
  >
    <ChevronDown class="size-4" />
  </SelectScrollDownButton>
</template>
