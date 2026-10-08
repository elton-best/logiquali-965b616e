<script setup lang="ts">
  import { AvatarImage as AvatarImagePrimitive } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'AvatarImage',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
    src?: string
    alt?: string
  }>()
</script>

<template>
  <AvatarImagePrimitive
    v-bind="$attrs"
    :alt="props.alt"
    :class="cn('aspect-square size-full', props.class)"
    data-slot="avatar-image"
    :src="props.src || ''"
  />
</template>
