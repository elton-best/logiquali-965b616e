<script setup lang="ts">
  import { computed } from 'vue'
  import { cn } from './utils'

  interface InputProps {
    type?: string
    class?: string
    modelValue?: string | number
  }

  const props = withDefaults(defineProps<InputProps>(), {
    type: 'text',
  })

  const emit = defineEmits<{
    'update:modelValue': [value: string | number]
  }>()

  const classes = computed(() => cn(
    'input-base',
    props.class,
  ))
</script>

<template>
  <input
    :class="classes"
    data-slot="input"
    :type="type"
    :value="modelValue"
    v-bind="$attrs"
    @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
  >
</template>

<style scoped>
.input-base {
  display: flex;
  width: 100%;
  min-width: 0;
  height: 44px;
  padding: var(--spacing-2) var(--spacing-3);
  font-size: var(--font-size-sm);
  line-height: 1.5;
  color: var(--color-text-primary);
  background: white;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-md);
  outline: none;
  transition: all var(--transition-normal);
}

.input-base::placeholder {
  color: var(--color-text-tertiary);
}

.input-base:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.input-base:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: var(--color-background-secondary);
}

.input-base[aria-invalid="true"] {
  border-color: var(--color-danger);
}

.input-base[aria-invalid="true"]:focus {
  box-shadow: 0 0 0 3px var(--color-danger-light);
}

.input-base[type="file"]::file-selector-button {
  display: inline-flex;
  height: 28px;
  padding: 0 var(--spacing-3);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  background: transparent;
  border: none;
  cursor: pointer;
}
</style>
