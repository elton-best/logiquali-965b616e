<script setup lang="ts">
  import { SwitchRoot, SwitchThumb } from 'radix-vue'
  import { computed } from 'vue'
  import { cn } from './utils'

  interface SwitchProps {
    checked?: boolean
    defaultChecked?: boolean
    disabled?: boolean
    required?: boolean
    name?: string
    value?: string
    id?: string
    class?: string
  }

  const props = defineProps<SwitchProps>()

  const emit = defineEmits<{
    'update:checked': [value: boolean]
  }>()

  const switchClass = computed(() =>
    cn(
      'peer data-[state=checked]:bg-primary data-[state=unchecked]:bg-switch-background focus-visible:border-ring focus-visible:ring-ring/50 dark:data-[state=unchecked]:bg-input/80 inline-flex h-[1.15rem] w-8 shrink-0 items-center rounded-full border border-transparent transition-all outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50',
      props.class,
    ),
  )
</script>

<template>
  <SwitchRoot
    :id="id"
    :checked="checked"
    :class="switchClass"
    data-slot="switch"
    :default-checked="defaultChecked"
    :disabled="disabled"
    :name="name"
    :required="required"
    :value="value"
    @update:checked="emit('update:checked', $event)"
  >
    <SwitchThumb
      :class="cn(
        'bg-card dark:data-[state=unchecked]:bg-card-foreground dark:data-[state=checked]:bg-primary-foreground pointer-events-none block size-4 rounded-full ring-0 transition-transform data-[state=checked]:translate-x-[calc(100%-2px)] data-[state=unchecked]:translate-x-0'
      )"
      data-slot="switch-thumb"
    />
  </SwitchRoot>
</template>
