<template>
  <div class="app-input-wrapper">
    <!-- Label -->
    <label
      v-if="label"
      class="input-label"
      :class="{ 'label-required': required }"
      :for="inputId"
    >
      {{ label }}
      <span v-if="required" aria-label="requis" class="required-mark">*</span>
    </label>

    <!-- Input container -->
    <div class="input-container" :class="containerClasses">
      <!-- Prefix icon -->
      <div v-if="prefixIcon" class="input-icon prefix-icon">
        <component :is="prefixIcon" :size="20" />
      </div>

      <!-- Input field -->
      <input
        :id="inputId"
        ref="inputRef"
        :aria-describedby="
          error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined
        "
        :aria-invalid="error ? 'true' : 'false'"
        :autocomplete="autocomplete"
        class="input-field"
        :disabled="disabled"
        :max="max"
        :maxlength="maxlength"
        :min="min"
        :placeholder="placeholder"
        :readonly="readonly"
        :required="required"
        :step="step"
        :type="type"
        :value="modelValue"
        @blur="handleBlur"
        @focus="handleFocus"
        @input="handleInput"
      >

      <!-- Suffix icon -->
      <div v-if="suffixIcon || clearable" class="input-icon suffix-icon">
        <!-- Clear button -->
        <button
          v-if="clearable && modelValue && !disabled"
          aria-label="Effacer"
          class="clear-button"
          type="button"
          @click="handleClear"
        >
          <X :size="16" />
        </button>
        <!-- Suffix icon -->
        <component :is="suffixIcon" v-else-if="suffixIcon" :size="20" />
      </div>

      <!-- Loading spinner -->
      <div v-if="loading" class="input-spinner">
        <Loader2 class="animate-spin" :size="16" />
      </div>
    </div>

    <!-- Hint text -->
    <p v-if="hint && !error" :id="`${inputId}-hint`" class="input-hint">
      {{ hint }}
    </p>

    <!-- Error message -->
    <p v-if="error" :id="`${inputId}-error`" class="input-error" role="alert">
      <AlertCircle :size="14" />
      {{ error }}
    </p>

    <!-- Character count -->
    <p v-if="maxlength && showCount" class="input-count">
      {{ String(modelValue ?? "").length }} / {{ maxlength }}
    </p>
  </div>
</template>

<script setup lang="ts">
  import { AlertCircle, Loader2, X } from 'lucide-vue-next'
  import { computed, ref, useId } from 'vue'

  interface Props {
    modelValue?: string | number
    type?:
      | 'text'
      | 'email'
      | 'password'
      | 'number'
      | 'tel'
      | 'url'
      | 'search'
      | 'date'
      | 'time'
    label?: string
    placeholder?: string
    hint?: string
    error?: string
    disabled?: boolean
    readonly?: boolean
    required?: boolean
    loading?: boolean
    clearable?: boolean
    showCount?: boolean
    prefixIcon?: any
    suffixIcon?: any
    autocomplete?: string
    min?: number
    max?: number
    step?: number
    maxlength?: number
    size?: 'sm' | 'md' | 'lg'
  }

  const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    disabled: false,
    readonly: false,
    required: false,
    loading: false,
    clearable: false,
    showCount: false,
    size: 'md',
  })

  const emit = defineEmits<{
    'update:modelValue': [value: string | number]
    'blur': [event: FocusEvent]
    'focus': [event: FocusEvent]
  }>()

  const inputRef = ref<HTMLInputElement>()
  const inputId = `input-${useId()}`

  const containerClasses = computed(() => ({
    'input-disabled': props.disabled,
    'input-error': props.error,
    'input-readonly': props.readonly,
    'input-loading': props.loading,
    [`input-${props.size}`]: true,
    'has-prefix': props.prefixIcon,
    'has-suffix': props.suffixIcon || props.clearable || props.loading,
  }))

  function handleInput (event: Event) {
    const target = event.target as HTMLInputElement
    const value = props.type === 'number' ? Number(target.value) : target.value
    emit('update:modelValue', value)
  }

  function handleBlur (event: FocusEvent) {
    emit('blur', event)
  }

  function handleFocus (event: FocusEvent) {
    emit('focus', event)
  }

  function handleClear () {
    emit('update:modelValue', '')
    inputRef.value?.focus()
  }

  defineExpose({
    focus: () => inputRef.value?.focus(),
    blur: () => inputRef.value?.blur(),
  })
</script>

<style scoped>
.app-input-wrapper {
  display: flex;
  flex-direction: column;
  gap: 6px;
  width: 100%;
}

/* Label */
.input-label {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  display: flex;
  align-items: center;
  gap: 4px;
}

.required-mark {
  color: #ef4444;
  font-weight: 700;
}

/* Input container */
.input-container {
  position: relative;
  display: flex;
  align-items: center;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  transition: all 150ms cubic-bezier(0.4, 0, 0.2, 1);
}

.input-container:hover:not(.input-disabled):not(.input-readonly) {
  border-color: #9ca3af;
}

.input-container:focus-within:not(.input-disabled):not(.input-readonly) {
  border-color: #4471c4;
  box-shadow: 0 0 0 3px rgba(68, 113, 196, 0.1);
  outline: none;
}

.input-container.input-error {
  border-color: #ef4444;
}

.input-container.input-error:focus-within {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.input-container.input-disabled {
  background: #f3f4f6;
  border-color: #e5e7eb;
  cursor: not-allowed;
}

.input-container.input-readonly {
  background: #f9fafb;
  border-color: #e5e7eb;
}

/* Sizes */
.input-container.input-sm {
  min-height: 36px;
}

.input-container.input-md {
  min-height: 44px;
}

.input-container.input-lg {
  min-height: 52px;
}

/* Input field */
.input-field {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 14px;
  color: #1f2937;
  outline: none;
  padding: 0 12px;
  min-width: 0;
}

.input-sm .input-field {
  padding: 0 10px;
  font-size: 13px;
}

.input-lg .input-field {
  padding: 0 14px;
  font-size: 16px;
}

.input-container.has-prefix .input-field {
  padding-left: 0;
}

.input-container.has-suffix .input-field {
  padding-right: 0;
}

.input-field::placeholder {
  color: #9ca3af;
}

.input-field:disabled {
  cursor: not-allowed;
  color: #6b7280;
}

.input-field:readonly {
  cursor: default;
}

/* Remove number input arrows */
.input-field[type="number"]::-webkit-inner-spin-button,
.input-field[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.input-field[type="number"] {
  -moz-appearance: textfield;
}

/* Icons */
.input-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
  flex-shrink: 0;
}

.prefix-icon {
  padding-left: 12px;
  padding-right: 8px;
}

.suffix-icon {
  padding-right: 12px;
  padding-left: 8px;
}

.input-sm .prefix-icon {
  padding-left: 10px;
}

.input-sm .suffix-icon {
  padding-right: 10px;
}

.input-lg .prefix-icon {
  padding-left: 14px;
}

.input-lg .suffix-icon {
  padding-right: 14px;
}

/* Clear button */
.clear-button {
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  color: #6b7280;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: all 150ms;
}

.clear-button:hover {
  color: #374151;
  background: #f3f4f6;
}

.clear-button:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(68, 113, 196, 0.2);
}

/* Spinner */
.input-spinner {
  display: flex;
  align-items: center;
  padding-right: 12px;
  color: #4471c4;
}

/* Hint */
.input-hint {
  font-size: 13px;
  color: #6b7280;
  margin: 0;
}

/* Error */
.input-error {
  font-size: 13px;
  color: #ef4444;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Character count */
.input-count {
  font-size: 12px;
  color: #9ca3af;
  text-align: right;
  margin: 0;
}

/* Dark mode ready */
@media (prefers-color-scheme: dark) {
  .input-label {
    color: #e5e7eb;
  }

  .input-container {
    background: #1f2937;
    border-color: #374151;
  }

  .input-container:hover:not(.input-disabled):not(.input-readonly) {
    border-color: #4b5563;
  }

  .input-field {
    color: #f9fafb;
  }

  .input-field::placeholder {
    color: #6b7280;
  }

  .input-container.input-disabled {
    background: #111827;
  }

  .input-container.input-readonly {
    background: #1f2937;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .input-container,
  .clear-button {
    transition: none !important;
  }
}
</style>
