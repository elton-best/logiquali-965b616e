<script setup lang="ts">
  import { DropdownMenuGroup } from 'radix-vue'

  defineOptions({
    name: 'DropdownMenuGroup',
    inheritAttrs: false,
  })
</script>

<template>
  <DropdownMenuGroup v-bind="$attrs" data-slot="dropdown-menu-group">
    <slot />
  </DropdownMenuGroup>
</template>
