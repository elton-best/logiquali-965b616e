<template>
  <div class="app-switch-wrapper">
    <label class="switch-label" :class="{ 'switch-disabled': disabled }">
      <input
        :id="switchId"
        ref="switchRef"
        :aria-checked="modelValue"
        :aria-describedby="error ? `${switchId}-error` : hint ? `${switchId}-hint` : undefined"
        :checked="modelValue"
        class="switch-input"
        :disabled="disabled"
        :required="required"
        role="switch"
        type="checkbox"
        @change="handleChange"
      >

      <span class="switch-track" :class="trackClasses">
        <span class="switch-thumb" />
      </span>

      <span v-if="label || $slots.default" class="switch-text">
        <slot>{{ label }}</slot>
      </span>
    </label>

    <!-- Hint text -->
    <p
      v-if="hint && !error"
      :id="`${switchId}-hint`"
      class="switch-hint"
    >
      {{ hint }}
    </p>

    <!-- Error message -->
    <p
      v-if="error"
      :id="`${switchId}-error`"
      class="switch-error"
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
    modelValue?: boolean
    label?: string
    hint?: string
    error?: string
    disabled?: boolean
    required?: boolean
    size?: 'sm' | 'md' | 'lg'
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    disabled: false,
    required: false,
    size: 'md',
  })

  const emit = defineEmits<{
    'update:modelValue': [value: boolean]
  }>()

  const switchRef = ref<HTMLInputElement>()
  const switchId = `switch-${useId()}`

  const trackClasses = computed(() => ({
    'switch-checked': props.modelValue,
    'switch-error': props.error,
    [`switch-${props.size}`]: true,
  }))

  function handleChange (event: Event) {
    const target = event.target as HTMLInputElement
    emit('update:modelValue', target.checked)
  }

  defineExpose({
    focus: () => switchRef.value?.focus(),
  })
</script>

<style scoped>
.app-switch-wrapper {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

/* Label */
.switch-label {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  user-select: none;
  position: relative;
}

.switch-label.switch-disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

/* Hidden input */
.switch-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

/* Switch track */
.switch-track {
  position: relative;
  display: inline-flex;
  align-items: center;
  width: 44px;
  height: 24px;
  background: #D1D5DB;
  border-radius: 9999px;
  transition: all 200ms cubic-bezier(0.4, 0, 0.2, 1);
  flex-shrink: 0;
}

.switch-track.switch-sm {
  width: 36px;
  height: 20px;
}

.switch-track.switch-lg {
  width: 52px;
  height: 28px;
}

/* Hover state */
.switch-label:hover:not(.switch-disabled) .switch-track {
  background: #9CA3AF;
}

/* Focus state */
.switch-input:focus + .switch-track {
  box-shadow: 0 0 0 3px rgba(68, 113, 196, 0.1);
}

/* Checked state */
.switch-track.switch-checked {
  background: #4471C4;
}

.switch-label:hover:not(.switch-disabled) .switch-track.switch-checked {
  background: #365A9D;
}

/* Error state */
.switch-track.switch-error {
  background: #FCA5A5;
}

.switch-track.switch-error.switch-checked {
  background: #EF4444;
}

.switch-input:focus + .switch-track.switch-error {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

/* Switch thumb */
.switch-thumb {
  position: absolute;
  left: 2px;
  width: 20px;
  height: 20px;
  background: white;
  border-radius: 50%;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  transition: all 200ms cubic-bezier(0.4, 0, 0.2, 1);
}

.switch-sm .switch-thumb {
  width: 16px;
  height: 16px;
}

.switch-lg .switch-thumb {
  width: 24px;
  height: 24px;
}

/* Checked thumb position */
.switch-checked .switch-thumb {
  left: calc(100% - 22px);
}

.switch-checked.switch-sm .switch-thumb {
  left: calc(100% - 18px);
}

.switch-checked.switch-lg .switch-thumb {
  left: calc(100% - 26px);
}

/* Text */
.switch-text {
  font-size: 14px;
  color: #374151;
  line-height: 1.5;
}

.switch-sm .switch-text {
  font-size: 13px;
}

.switch-lg .switch-text {
  font-size: 16px;
}

.switch-disabled .switch-text {
  color: #9CA3AF;
}

/* Hint */
.switch-hint {
  font-size: 13px;
  color: #6B7280;
  margin: 0;
  padding-left: 56px;
}

.switch-sm + .switch-hint {
  padding-left: 48px;
}

.switch-lg + .switch-hint {
  padding-left: 64px;
}

/* Error */
.switch-error {
  font-size: 13px;
  color: #EF4444;
  margin: 0;
  padding-left: 56px;
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Dark mode ready */
@media (prefers-color-scheme: dark) {
  .switch-track {
    background: #4B5563;
  }

  .switch-label:hover:not(.switch-disabled) .switch-track {
    background: #6B7280;
  }

  .switch-text {
    color: #E5E7EB;
  }

  .switch-disabled .switch-text {
    color: #6B7280;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .switch-track,
  .switch-thumb {
    transition: none !important;
  }
}
</style>
