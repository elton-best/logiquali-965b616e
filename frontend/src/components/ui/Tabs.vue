<script setup lang="ts">
  import { TabsRoot } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'Tabs',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
    modelValue?: string
    defaultValue?: string
    orientation?: 'horizontal' | 'vertical'
    dir?: 'ltr' | 'rtl'
    activationMode?: 'automatic' | 'manual'
  }>()

  const emit = defineEmits<{
    'update:modelValue': [value: string]
  }>()
</script>

<template>
  <TabsRoot
    v-bind="$attrs"
    :activation-mode="activationMode"
    :class="cn('flex flex-col gap-2', props.class)"
    data-slot="tabs"
    :default-value="defaultValue"
    :dir="dir"
    :model-value="modelValue"
    :orientation="orientation"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <slot />
  </TabsRoot>
</template>
