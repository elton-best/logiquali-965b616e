<script setup lang="ts">
  import { SelectRoot } from 'radix-vue'

  defineOptions({
    name: 'AppSelect',
    inheritAttrs: false,
  })

  defineProps<{
    modelValue?: string | number
    open?: boolean
    defaultOpen?: boolean
    defaultValue?: string | number
    dir?: 'ltr' | 'rtl'
    name?: string
    autocomplete?: string
    disabled?: boolean
    required?: boolean
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: string | number]
    'update:open': [value: boolean]
  }>()
</script>

<template>
  <SelectRoot
    v-bind="$attrs"
    :autocomplete="autocomplete"
    data-slot="select"
    :default-open="defaultOpen"
    :default-value="defaultValue ? String(defaultValue) : undefined"
    :dir="dir"
    :disabled="disabled"
    :model-value="modelValue ? String(modelValue) : undefined"
    :name="name"
    :open="open"
    :required="required"
    @update:model-value="emit('update:modelValue', $event)"
    @update:open="emit('update:open', $event)"
  >
    <slot />
  </SelectRoot>
</template>
