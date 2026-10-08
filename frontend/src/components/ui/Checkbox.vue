<script setup lang="ts">
  import { Check } from 'lucide-vue-next'
  import { CheckboxIndicator, CheckboxRoot } from 'radix-vue'
  import { computed } from 'vue'
  import { cn } from './utils'

  interface CheckboxProps {
    checked?: boolean | 'indeterminate'
    defaultChecked?: boolean
    disabled?: boolean
    required?: boolean
    name?: string
    value?: string
    id?: string
    class?: string
  }

  const props = defineProps<CheckboxProps>()

  const emit = defineEmits<{
    'update:checked': [value: boolean | 'indeterminate']
  }>()

  const checkboxClass = computed(() =>
    cn(
      'peer border bg-input-background dark:bg-input/30 data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground dark:data-[state=checked]:bg-primary data-[state=checked]:border-primary focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive size-4 shrink-0 rounded-[4px] border shadow-xs transition-shadow outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50',
      props.class,
    ),
  )
</script>

<template>
  <CheckboxRoot
    :id="id"
    :checked="checked"
    :class="checkboxClass"
    data-slot="checkbox"
    :default-checked="defaultChecked"
    :disabled="disabled"
    :name="name"
    :required="required"
    :value="value"
    @update:checked="emit('update:checked', $event)"
  >
    <CheckboxIndicator
      class="flex items-center justify-center text-current transition-none"
      data-slot="checkbox-indicator"
    >
      <Check class="size-3.5" />
    </CheckboxIndicator>
  </CheckboxRoot>
</template>
