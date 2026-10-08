<template>
  <div class="app-form-group" :class="groupClasses">
    <!-- Label -->
    <label
      v-if="label"
      class="form-label"
      :class="{ 'label-required': required }"
      :for="forId"
    >
      {{ label }}
      <span v-if="required" aria-label="requis" class="required-mark">*</span>
    </label>

    <!-- Slot for form control -->
    <div class="form-control">
      <slot />
    </div>

    <!-- Hint text -->
    <p v-if="hint && !error" class="form-hint">
      {{ hint }}
    </p>

    <!-- Error message -->
    <p v-if="error" class="form-error" role="alert">
      <AlertCircle :size="14" />
      {{ error }}
    </p>
  </div>
</template>

<script setup lang="ts">
  import { AlertCircle } from 'lucide-vue-next'
  import { computed } from 'vue'

  interface Props {
    label?: string
    hint?: string
    error?: string
    required?: boolean
    forId?: string
    inline?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    required: false,
    inline: false,
  })

  const groupClasses = computed(() => ({
    'form-group-inline': props.inline,
    'form-group-error': props.error,
  }))
</script>

<style scoped>
.app-form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  width: 100%;
}

.app-form-group.form-group-inline {
  flex-direction: row;
  align-items: center;
  gap: 12px;
}

.app-form-group.form-group-inline .form-label {
  margin-bottom: 0;
  min-width: 120px;
}

.app-form-group.form-group-inline .form-control {
  flex: 1;
}

/* Label */
.form-label {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  display: flex;
  align-items: center;
  gap: 4px;
  margin-bottom: 0;
}

.required-mark {
  color: #EF4444;
  font-weight: 700;
}

/* Control */
.form-control {
  width: 100%;
}

/* Hint */
.form-hint {
  font-size: 13px;
  color: #6B7280;
  margin: 0;
}

/* Error */
.form-error {
  font-size: 13px;
  color: #EF4444;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Dark mode ready */
@media (prefers-color-scheme: dark) {
  .form-label {
    color: #E5E7EB;
  }
}

/* Responsive */
@media (max-width: 640px) {
  .app-form-group.form-group-inline {
    flex-direction: column;
    align-items: flex-start;
  }

  .app-form-group.form-group-inline .form-label {
    min-width: auto;
  }
}
</style>
