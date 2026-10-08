<script setup lang="ts">
  import { SelectValue } from 'radix-vue'

  defineOptions({
    name: 'SelectValue',
    inheritAttrs: false,
  })

  defineProps<{
    placeholder?: string
  }>()
</script>

<template>
  <SelectValue
    v-bind="$attrs"
    data-slot="select-value"
    :placeholder="placeholder"
  >
    <slot />
  </SelectValue>
</template>
