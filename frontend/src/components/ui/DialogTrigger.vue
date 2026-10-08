<script setup lang="ts">
  import { DialogTrigger } from 'radix-vue'

  defineOptions({
    name: 'DialogTrigger',
    inheritAttrs: false,
  })
</script>

<template>
  <DialogTrigger v-bind="$attrs" as-child data-slot="dialog-trigger">
    <slot />
  </DialogTrigger>
</template>
