<template>
  <div class="app-textarea-wrapper">
    <!-- Label -->
    <label
      v-if="label"
      class="textarea-label"
      :class="{ 'label-required': required }"
      :for="textareaId"
    >
      {{ label }}
      <span v-if="required" aria-label="requis" class="required-mark">*</span>
    </label>

    <!-- Textarea container -->
    <div class="textarea-container" :class="containerClasses">
      <textarea
        :id="textareaId"
        ref="textareaRef"
        :aria-describedby="error ? `${textareaId}-error` : hint ? `${textareaId}-hint` : undefined"
        :aria-invalid="error ? 'true' : 'false'"
        class="textarea-field"
        :disabled="disabled"
        :maxlength="maxlength"
        :placeholder="placeholder"
        :readonly="readonly"
        :required="required"
        :rows="rows"
        :value="modelValue"
        @blur="handleBlur"
        @focus="handleFocus"
        @input="handleInput"
      />
    </div>

    <!-- Footer: Hint/Error + Count -->
    <div v-if="hint || error || (maxlength && showCount)" class="textarea-footer">
      <!-- Hint text -->
      <p
        v-if="hint && !error"
        :id="`${textareaId}-hint`"
        class="textarea-hint"
      >
        {{ hint }}
      </p>

      <!-- Error message -->
      <p
        v-if="error"
        :id="`${textareaId}-error`"
        class="textarea-error"
        role="alert"
      >
        <AlertCircle :size="14" />
        {{ error }}
      </p>

      <!-- Character count -->
      <p v-if="maxlength && showCount" class="textarea-count">
        {{ modelValue?.length || 0 }} / {{ maxlength }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { AlertCircle } from 'lucide-vue-next'
  import { computed, nextTick, ref, useId, watch } from 'vue'

  interface Props {
    modelValue?: string
    label?: string
    placeholder?: string
    hint?: string
    error?: string
    disabled?: boolean
    readonly?: boolean
    required?: boolean
    rows?: number
    maxlength?: number
    showCount?: boolean
    autoResize?: boolean
    size?: 'sm' | 'md' | 'lg'
  }

  const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    readonly: false,
    required: false,
    rows: 4,
    showCount: false,
    autoResize: false,
    size: 'md',
  })

  const emit = defineEmits<{
    'update:modelValue': [value: string]
    'blur': [event: FocusEvent]
    'focus': [event: FocusEvent]
  }>()

  const textareaRef = ref<HTMLTextAreaElement>()
  const textareaId = `textarea-${useId()}`

  const containerClasses = computed(() => ({
    'textarea-disabled': props.disabled,
    'textarea-error': props.error,
    'textarea-readonly': props.readonly,
    [`textarea-${props.size}`]: true,
  }))

  function handleInput (event: Event) {
    const target = event.target as HTMLTextAreaElement
    emit('update:modelValue', target.value)

    if (props.autoResize) {
      autoResize()
    }
  }

  function handleBlur (event: FocusEvent) {
    emit('blur', event)
  }

  function handleFocus (event: FocusEvent) {
    emit('focus', event)
  }

  function autoResize () {
    if (!textareaRef.value) return

    textareaRef.value.style.height = 'auto'
    textareaRef.value.style.height = `${textareaRef.value.scrollHeight}px`
  }

  // Auto-resize on mount and value change
  watch(() => props.modelValue, () => {
    if (props.autoResize) {
      nextTick(() => autoResize())
    }
  }, { immediate: true })

  defineExpose({
    focus: () => textareaRef.value?.focus(),
    blur: () => textareaRef.value?.blur(),
  })
</script>

<style scoped>
.app-textarea-wrapper {
  display: flex;
  flex-direction: column;
  gap: 6px;
  width: 100%;
}

/* Label */
.textarea-label {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  display: flex;
  align-items: center;
  gap: 4px;
}

.required-mark {
  color: #EF4444;
  font-weight: 700;
}

/* Textarea container */
.textarea-container {
  position: relative;
  background: white;
  border: 1px solid #E5E7EB;
  border-radius: 8px;
  transition: all 150ms cubic-bezier(0.4, 0, 0.2, 1);
}

.textarea-container:hover:not(.textarea-disabled):not(.textarea-readonly) {
  border-color: #9CA3AF;
}

.textarea-container:focus-within:not(.textarea-disabled):not(.textarea-readonly) {
  border-color: #4471C4;
  box-shadow: 0 0 0 3px rgba(68, 113, 196, 0.1);
  outline: none;
}

.textarea-container.textarea-error {
  border-color: #EF4444;
}

.textarea-container.textarea-error:focus-within {
  border-color: #EF4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.textarea-container.textarea-disabled {
  background: #F3F4F6;
  border-color: #E5E7EB;
  cursor: not-allowed;
}

.textarea-container.textarea-readonly {
  background: #F9FAFB;
  border-color: #E5E7EB;
}

/* Textarea field */
.textarea-field {
  width: 100%;
  border: none;
  background: transparent;
  font-size: 14px;
  color: #1F2937;
  outline: none;
  padding: 12px;
  resize: vertical;
  font-family: inherit;
  line-height: 1.5;
  min-height: 100px;
}

.textarea-sm .textarea-field {
  padding: 10px;
  font-size: 13px;
  min-height: 80px;
}

.textarea-lg .textarea-field {
  padding: 14px;
  font-size: 16px;
  min-height: 120px;
}

.textarea-field::placeholder {
  color: #9CA3AF;
}

.textarea-field:disabled {
  cursor: not-allowed;
  color: #6B7280;
}

.textarea-field:readonly {
  cursor: default;
  resize: none;
}

/* Footer */
.textarea-footer {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

/* Hint */
.textarea-hint {
  font-size: 13px;
  color: #6B7280;
  margin: 0;
  flex: 1;
}

/* Error */
.textarea-error {
  font-size: 13px;
  color: #EF4444;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 4px;
  flex: 1;
}

/* Character count */
.textarea-count {
  font-size: 12px;
  color: #9CA3AF;
  margin: 0;
  white-space: nowrap;
}

/* Dark mode ready */
@media (prefers-color-scheme: dark) {
  .textarea-label {
    color: #E5E7EB;
  }

  .textarea-container {
    background: #1F2937;
    border-color: #374151;
  }

  .textarea-container:hover:not(.textarea-disabled):not(.textarea-readonly) {
    border-color: #4B5563;
  }

  .textarea-field {
    color: #F9FAFB;
  }

  .textarea-field::placeholder {
    color: #6B7280;
  }

  .textarea-container.textarea-disabled {
    background: #111827;
  }

  .textarea-container.textarea-readonly {
    background: #1F2937;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .textarea-container {
    transition: none !important;
  }
}
</style>
