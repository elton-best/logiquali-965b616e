<script setup lang="ts">
  import { AvatarRoot } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'Avatar',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
  }>()
</script>

<template>
  <AvatarRoot
    v-bind="$attrs"
    :class="cn('relative flex size-10 shrink-0 overflow-hidden rounded-full', props.class)"
    data-slot="avatar"
  >
    <slot />
  </AvatarRoot>
</template>
