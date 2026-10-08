<script setup lang="ts">
  import { DropdownMenuRadioGroup } from 'radix-vue'

  defineOptions({
    name: 'DropdownMenuRadioGroup',
    inheritAttrs: false,
  })

  defineProps<{
    modelValue?: string
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: string]
  }>()
</script>

<template>
  <DropdownMenuRadioGroup
    v-bind="$attrs"
    data-slot="dropdown-menu-radio-group"
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <slot />
  </DropdownMenuRadioGroup>
</template>
