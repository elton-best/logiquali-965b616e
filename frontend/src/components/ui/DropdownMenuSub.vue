<script setup lang="ts">
  import { DropdownMenuSub } from 'radix-vue'

  defineOptions({
    name: 'DropdownMenuSub',
    inheritAttrs: false,
  })

  defineProps<{
    open?: boolean
    defaultOpen?: boolean
  }>()
</script>

<template>
  <DropdownMenuSub v-bind="$attrs" data-slot="dropdown-menu-sub" :default-open="defaultOpen" :open="open">
    <slot />
  </DropdownMenuSub>
</template>
