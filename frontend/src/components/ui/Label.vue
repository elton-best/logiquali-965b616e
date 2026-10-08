<script setup lang="ts">
  import { Label as LabelPrimitive } from 'radix-vue'
  import { computed } from 'vue'
  import { cn } from './utils'

  interface LabelProps {
    for?: string
    class?: string
  }

  const props = defineProps<LabelProps>()

  const classes = computed(() => cn(
    'flex items-center gap-2 text-sm leading-none font-medium select-none group-data-[disabled=true]:pointer-events-none group-data-[disabled=true]:opacity-50 peer-disabled:cursor-not-allowed peer-disabled:opacity-50',
    props.class,
  ))
</script>

<template>
  <LabelPrimitive
    :class="classes"
    data-slot="label"
    :for="props.for"
    v-bind="$attrs"
  >
    <slot />
  </LabelPrimitive>
</template>
