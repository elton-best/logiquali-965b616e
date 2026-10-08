<script setup lang="ts">
  import { AvatarFallback as AvatarFallbackPrimitive } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'AvatarFallback',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
    delayMs?: number
  }>()
</script>

<template>
  <AvatarFallbackPrimitive
    v-bind="$attrs"
    :class="cn('bg-muted flex size-full items-center justify-center rounded-full', props.class)"
    data-slot="avatar-fallback"
    :delay-ms="delayMs"
  >
    <slot />
  </AvatarFallbackPrimitive>
</template>
