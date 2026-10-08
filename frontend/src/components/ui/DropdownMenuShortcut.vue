<script setup lang="ts">
  import { cn } from './utils'

  defineOptions({
    name: 'DropdownMenuShortcut',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
  }>()
</script>

<template>
  <span
    v-bind="$attrs"
    :class="cn('text-muted-foreground ml-auto text-xs tracking-widest', props.class)"
    data-slot="dropdown-menu-shortcut"
  >
    <slot />
  </span>
</template>
