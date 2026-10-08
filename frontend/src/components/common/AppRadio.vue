<template>
  <div class="app-radio-wrapper">
    <label class="radio-label" :class="{ 'radio-disabled': disabled }">
      <input
        :id="radioId"
        ref="radioRef"
        :aria-describedby="error ? `${radioId}-error` : hint ? `${radioId}-hint` : undefined"
        :checked="isChecked"
        class="radio-input"
        :disabled="disabled"
        :name="name"
        :required="required"
        type="radio"
        :value="value"
        @change="handleChange"
      >

      <span class="radio-circle" :class="circleClasses">
        <span v-if="isChecked" class="radio-dot" />
      </span>

      <span v-if="label || $slots.default" class="radio-text">
        <slot>{{ label }}</slot>
      </span>
    </label>

    <!-- Hint text -->
    <p
      v-if="hint && !error"
      :id="`${radioId}-hint`"
      class="radio-hint"
    >
      {{ hint }}
    </p>

    <!-- Error message -->
    <p
      v-if="error"
      :id="`${radioId}-error`"
      class="radio-error"
      role="alert"
    >
      <AlertCircle :size="14" />
      {{ error }}
    </p>
  </div>
</template>

<script setup lang="ts">
  import { AlertCircle } from 'lucide-vue-next'
  import { computed, ref, useId } from 'vue'

  interface Props {
    modelValue?: any
    value: any
    name?: string
    label?: string
    hint?: string
    error?: string
    disabled?: boolean
    required?: boolean
    size?: 'sm' | 'md' | 'lg'
  }

  const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    required: false,
    size: 'md',
  })

  const emit = defineEmits<{
    'update:modelValue': [value: any]
  }>()

  const radioRef = ref<HTMLInputElement>()
  const radioId = `radio-${useId()}`

  const isChecked = computed(() => props.modelValue === props.value)

  const circleClasses = computed(() => ({
    'radio-checked': isChecked.value,
    'radio-error': props.error,
    [`radio-${props.size}`]: true,
  }))

  function handleChange () {
    emit('update:modelValue', props.value)
  }

  defineExpose({
    focus: () => radioRef.value?.focus(),
  })
</script>

<style scoped>
.app-radio-wrapper {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

/* Label */
.radio-label {
  display: inline-flex;
  align-items: flex-start;
  gap: 10px;
  cursor: pointer;
  user-select: none;
  position: relative;
}

.radio-label.radio-disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

/* Hidden input */
.radio-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

/* Radio circle */
.radio-circle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border: 2px solid #D1D5DB;
  border-radius: 50%;
  background: white;
  transition: all 150ms cubic-bezier(0.4, 0, 0.2, 1);
  flex-shrink: 0;
  margin-top: 2px;
}

.radio-circle.radio-sm {
  width: 16px;
  height: 16px;
}

.radio-circle.radio-lg {
  width: 24px;
  height: 24px;
}

/* Hover state */
.radio-label:hover:not(.radio-disabled) .radio-circle {
  border-color: #9CA3AF;
}

/* Focus state */
.radio-input:focus + .radio-circle {
  border-color: #4471C4;
  box-shadow: 0 0 0 3px rgba(68, 113, 196, 0.1);
}

/* Checked state */
.radio-circle.radio-checked {
  border-color: #4471C4;
  background: white;
}

/* Error state */
.radio-circle.radio-error {
  border-color: #EF4444;
}

.radio-circle.radio-error.radio-checked {
  border-color: #EF4444;
}

.radio-input:focus + .radio-circle.radio-error {
  border-color: #EF4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

/* Radio dot */
.radio-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #4471C4;
  transition: all 150ms cubic-bezier(0.4, 0, 0.2, 1);
}

.radio-sm .radio-dot {
  width: 8px;
  height: 8px;
}

.radio-lg .radio-dot {
  width: 12px;
  height: 12px;
}

.radio-error .radio-dot {
  background: #EF4444;
}

/* Text */
.radio-text {
  font-size: 14px;
  color: #374151;
  line-height: 1.5;
  padding-top: 1px;
}

.radio-sm .radio-text {
  font-size: 13px;
}

.radio-lg .radio-text {
  font-size: 16px;
}

.radio-disabled .radio-text {
  color: #9CA3AF;
}

/* Hint */
.radio-hint {
  font-size: 13px;
  color: #6B7280;
  margin: 0;
  padding-left: 30px;
}

/* Error */
.radio-error {
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
  .radio-circle {
    background: #1F2937;
    border-color: #4B5563;
  }

  .radio-circle.radio-checked {
    background: #1F2937;
  }

  .radio-text {
    color: #E5E7EB;
  }

  .radio-disabled .radio-text {
    color: #6B7280;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .radio-circle,
  .radio-dot {
    transition: none !important;
  }
}
</style>
