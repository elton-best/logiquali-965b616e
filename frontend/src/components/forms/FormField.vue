<template>
  <div class="form-field" :class="{ 'form-field--error': errorMessage }">
    <label v-if="label" class="form-field__label" :for="inputId">
      {{ label }}
      <span v-if="required" class="text-red-600 ml-1">*</span>
    </label>

    <div class="form-field__input">
      <slot />
    </div>

    <p v-if="errorMessage" class="form-field__error">
      {{ errorMessage }}
    </p>

    <p v-else-if="hint" class="form-field__hint">
      {{ hint }}
    </p>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    label?: string
    required?: boolean
    errorMessage?: string
    hint?: string
    inputId?: string
  }

  const props = defineProps<Props>()

  const inputId = computed(() => props.inputId || `field-${Math.random().toString(36).slice(2, 11)}`)
</script>

<style scoped>
.form-field {
  @apply mb-4;
}

.form-field__label {
  @apply block text-sm font-medium text-gray-700 mb-2;
}

.form-field__input {
  @apply w-full;
}

.form-field__error {
  @apply mt-1 text-sm text-red-600;
}

.form-field__hint {
  @apply mt-1 text-sm text-gray-500;
}

.form-field--error .form-field__input :deep(input),
.form-field--error .form-field__input :deep(textarea),
.form-field--error .form-field__input :deep(select) {
  @apply border-red-300 focus:border-red-500 focus:ring-red-500;
}
</style>
