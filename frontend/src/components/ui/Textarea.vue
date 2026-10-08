<script setup lang="ts">
  import { computed } from 'vue'
  import { cn } from './utils'

  interface TextareaProps {
    modelValue?: string
    placeholder?: string
    disabled?: boolean
    readonly?: boolean
    required?: boolean
    name?: string
    id?: string
    rows?: number
    cols?: number
    class?: string
  }

  const props = defineProps<TextareaProps>()

  const emit = defineEmits<{
    'update:modelValue': [value: string]
  }>()

  const textareaClass = computed(() =>
    cn(
      'textarea-field',
      props.class,
    ),
  )

  function handleInput (event: Event) {
    const target = event.target as HTMLTextAreaElement
    emit('update:modelValue', target.value)
  }
</script>

<template>
  <textarea
    :id="id"
    :class="textareaClass"
    :cols="cols"
    data-slot="textarea"
    :disabled="disabled"
    :name="name"
    :placeholder="placeholder"
    :readonly="readonly"
    :required="required"
    :rows="rows"
    :value="modelValue"
    @input="handleInput"
  />
</template>

<style scoped>
.textarea-field {
  width: 100%;
  min-height: 120px;
  padding: var(--spacing-3) var(--spacing-4);
  font-size: var(--font-size-base);
  font-family: inherit;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-base);
  background: var(--bg-primary);
  color: var(--text-primary);
  resize: vertical;
  transition: all var(--transition-base);
  outline: none;
}

.textarea-field::placeholder {
  color: var(--text-tertiary);
}

.textarea-field:hover:not(:disabled) {
  border-color: var(--border-color-hover);
}

.textarea-field:focus {
  border-color: var(--color-primary-500);
  box-shadow: var(--shadow-focus);
}

.textarea-field:disabled {
  background: var(--bg-tertiary);
  color: var(--text-tertiary);
  cursor: not-allowed;
  opacity: 0.6;
}
</style>
