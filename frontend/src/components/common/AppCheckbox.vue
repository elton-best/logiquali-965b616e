<template>
  <div class="app-checkbox-wrapper">
    <label class="checkbox-label" :class="{ 'checkbox-disabled': disabled }">
      <input
        :id="checkboxId"
        ref="checkboxRef"
        :aria-describedby="error ? `${checkboxId}-error` : hint ? `${checkboxId}-hint` : undefined"
        :checked="modelValue"
        class="checkbox-input"
        :disabled="disabled"
        :indeterminate="indeterminate"
        :required="required"
        type="checkbox"
        @change="handleChange"
      >

      <span class="checkbox-box" :class="boxClasses">
        <Check v-if="modelValue && !indeterminate" class="checkbox-icon" :size="16" />
        <Minus v-else-if="indeterminate" class="checkbox-icon" :size="16" />
      </span>

      <span v-if="label || $slots.default" class="checkbox-text">
        <slot>{{ label }}</slot>
      </span>
    </label>

    <!-- Hint text -->
    <p
      v-if="hint && !error"
      :id="`${checkboxId}-hint`"
      class="checkbox-hint"
    >
      {{ hint }}
    </p>

    <!-- Error message -->
    <p
      v-if="error"
      :id="`${checkboxId}-error`"
      class="checkbox-error"
      role="alert"
    >
      <AlertCircle :size="14" />
      {{ error }}
    </p>
  </div>
</template>

<script setup lang="ts">
  import { AlertCircle, Check, Minus } from 'lucide-vue-next'
  import { computed, ref, useId, watch } from 'vue'

  interface Props {
    modelValue?: boolean
    label?: string
    hint?: string
    error?: string
    disabled?: boolean
    required?: boolean
    indeterminate?: boolean
    size?: 'sm' | 'md' | 'lg'
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    disabled: false,
    required: false,
    indeterminate: false,
    size: 'md',
  })

  const emit = defineEmits<{
    'update:modelValue': [value: boolean]
  }>()

  const checkboxRef = ref<HTMLInputElement>()
  const checkboxId = `checkbox-${useId()}`

  const boxClasses = computed(() => ({
    'checkbox-checked': props.modelValue,
    'checkbox-indeterminate': props.indeterminate,
    'checkbox-error': props.error,
    [`checkbox-${props.size}`]: true,
  }))

  function handleChange (event: Event) {
    const target = event.target as HTMLInputElement
    emit('update:modelValue', target.checked)
  }

  // Set indeterminate property (can't be set via attribute)
  watch(() => props.indeterminate, value => {
    if (checkboxRef.value) {
      checkboxRef.value.indeterminate = value
    }
  }, { immediate: true })

  defineExpose({
    focus: () => checkboxRef.value?.focus(),
  })
</script>

<style scoped>
.app-checkbox-wrapper {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

/* Label */
.checkbox-label {
  display: inline-flex;
  align-items: flex-start;
  gap: 10px;
  cursor: pointer;
  user-select: none;
  position: relative;
}

.checkbox-label.checkbox-disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

/* Hidden input */
.checkbox-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

/* Checkbox box */
.checkbox-box {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border: 2px solid #D1D5DB;
  border-radius: 4px;
  background: white;
  transition: all 150ms cubic-bezier(0.4, 0, 0.2, 1);
  flex-shrink: 0;
  margin-top: 2px;
}

.checkbox-box.checkbox-sm {
  width: 16px;
  height: 16px;
  border-radius: 3px;
}

.checkbox-box.checkbox-lg {
  width: 24px;
  height: 24px;
  border-radius: 5px;
}

/* Hover state */
.checkbox-label:hover:not(.checkbox-disabled) .checkbox-box {
  border-color: #9CA3AF;
}

/* Focus state */
.checkbox-input:focus + .checkbox-box {
  border-color: #4471C4;
  box-shadow: 0 0 0 3px rgba(68, 113, 196, 0.1);
}

/* Checked state */
.checkbox-box.checkbox-checked,
.checkbox-box.checkbox-indeterminate {
  background: #4471C4;
  border-color: #4471C4;
}

/* Error state */
.checkbox-box.checkbox-error {
  border-color: #EF4444;
}

.checkbox-box.checkbox-error.checkbox-checked,
.checkbox-box.checkbox-error.checkbox-indeterminate {
  background: #EF4444;
  border-color: #EF4444;
}

.checkbox-input:focus + .checkbox-box.checkbox-error {
  border-color: #EF4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

/* Icon */
.checkbox-icon {
  color: white;
  stroke-width: 3;
}

.checkbox-sm .checkbox-icon {
  width: 12px;
  height: 12px;
}

.checkbox-lg .checkbox-icon {
  width: 18px;
  height: 18px;
}

/* Text */
.checkbox-text {
  font-size: 14px;
  color: #374151;
  line-height: 1.5;
  padding-top: 1px;
}

.checkbox-sm .checkbox-text {
  font-size: 13px;
}

.checkbox-lg .checkbox-text {
  font-size: 16px;
}

.checkbox-disabled .checkbox-text {
  color: #9CA3AF;
}

/* Hint */
.checkbox-hint {
  font-size: 13px;
  color: #6B7280;
  margin: 0;
  padding-left: 30px;
}

/* Error */
.checkbox-error {
  font-size: 13px;
  color: #EF4444;
  margin: 0;
  padding-left: 30px;
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Dark mode ready */
@media (prefers-color-scheme: dark) {
  .checkbox-box {
    background: #1F2937;
    border-color: #4B5563;
  }

  .checkbox-text {
    color: #E5E7EB;
  }

  .checkbox-disabled .checkbox-text {
    color: #6B7280;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .checkbox-box {
    transition: none !important;
  }
}
</style>
