<script setup lang="ts">
  import { DialogRoot } from 'radix-vue'

  defineOptions({
    name: 'AppDialog',
    inheritAttrs: false,
  })

  defineProps<{
    open?: boolean
    defaultOpen?: boolean
    modal?: boolean
  }>()

  const emit = defineEmits<{
    'update:open': [value: boolean]
  }>()
</script>

<template>
  <DialogRoot
    v-bind="$attrs"
    data-slot="dialog"
    :default-open="defaultOpen"
    :modal="modal"
    :open="open"
    @update:open="emit('update:open', $event)"
  >
    <slot />
  </DialogRoot>
</template>
