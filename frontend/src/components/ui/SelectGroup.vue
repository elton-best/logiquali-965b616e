<script setup lang="ts">
  import { SelectGroup } from 'radix-vue'

  defineOptions({
    name: 'SelectGroup',
    inheritAttrs: false,
  })
</script>

<template>
  <SelectGroup v-bind="$attrs" data-slot="select-group">
    <slot />
  </SelectGroup>
</template>
