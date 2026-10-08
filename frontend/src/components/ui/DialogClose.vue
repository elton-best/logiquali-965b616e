<script setup lang="ts">
  import { DialogClose } from 'radix-vue'

  defineOptions({
    name: 'DialogClose',
    inheritAttrs: false,
  })
</script>

<template>
  <DialogClose v-bind="$attrs" as-child data-slot="dialog-close">
    <slot />
  </DialogClose>
</template>
