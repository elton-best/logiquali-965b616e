<script setup lang="ts">
  import { DropdownMenuTrigger } from 'radix-vue'

  defineOptions({
    name: 'DropdownMenuTrigger',
    inheritAttrs: false,
  })
</script>

<template>
  <DropdownMenuTrigger v-bind="$attrs" as-child data-slot="dropdown-menu-trigger">
    <slot />
  </DropdownMenuTrigger>
</template>
