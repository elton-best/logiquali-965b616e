<script setup lang="ts">
  import { DropdownMenuRoot } from 'radix-vue'

  defineOptions({
    name: 'DropdownMenu',
    inheritAttrs: false,
  })

  defineProps<{
    open?: boolean
    defaultOpen?: boolean
    modal?: boolean
    dir?: 'ltr' | 'rtl'
  }>()

  const emit = defineEmits<{
    'update:open': [value: boolean]
  }>()
</script>

<template>
  <DropdownMenuRoot
    v-bind="$attrs"
    data-slot="dropdown-menu"
    :default-open="defaultOpen"
    :dir="dir"
    :modal="modal"
    :open="open"
    @update:open="emit('update:open', $event)"
  >
    <slot />
  </DropdownMenuRoot>
</template>
