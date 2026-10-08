<template>
  <div v-if="!dismissed" class="app-alert" :class="alertClasses" role="alert">
    <div class="alert-icon">
      <CheckCircle v-if="variant === 'success'" :size="20" />
      <AlertCircle v-else-if="variant === 'error'" :size="20" />
      <AlertTriangle v-else-if="variant === 'warning'" :size="20" />
      <Info v-else :size="20" />
    </div>

    <div class="alert-content">
      <p v-if="title" class="alert-title">{{ title }}</p>
      <div class="alert-message">
        <slot>{{ message }}</slot>
      </div>
    </div>

    <button
      v-if="closable"
      aria-label="Fermer"
      class="alert-close"
      type="button"
      @click="handleClose"
    >
      <X :size="18" />
    </button>
  </div>
</template>

<script setup lang="ts">
  import { AlertCircle, AlertTriangle, CheckCircle, Info, X } from 'lucide-vue-next'
  import { computed, ref } from 'vue'

  interface Props {
    variant?: 'success' | 'error' | 'warning' | 'info'
    title?: string
    message?: string
    closable?: boolean
    outlined?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    variant: 'info',
    closable: false,
    outlined: false,
  })

  const emit = defineEmits<{
    close: []
  }>()

  const dismissed = ref(false)

  const alertClasses = computed(() => ({
    [`alert-${props.variant}`]: true,
    'alert-outlined': props.outlined,
  }))

  function handleClose () {
    dismissed.value = true
    emit('close')
  }
</script>

<style scoped>
.app-alert {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  border-radius: 8px;
  border: 1px solid transparent;
}

/* Variants - Filled */
.alert-success {
  background: #DCFCE7;
  border-color: #BBF7D0;
}

.alert-error {
  background: #FEE2E2;
  border-color: #FECACA;
}

.alert-warning {
  background: #FEF3C7;
  border-color: #FDE68A;
}

.alert-info {
  background: #DBEAFE;
  border-color: #BFDBFE;
}

/* Variants - Outlined */
.alert-outlined.alert-success {
  background: white;
  border-color: #10B981;
}

.alert-outlined.alert-error {
  background: white;
  border-color: #EF4444;
}

.alert-outlined.alert-warning {
  background: white;
  border-color: #F59E0B;
}

.alert-outlined.alert-info {
  background: white;
  border-color: #3B82F6;
}

/* Icon */
.alert-icon {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.alert-success .alert-icon { color: #059669; }
.alert-error .alert-icon { color: #DC2626; }
.alert-warning .alert-icon { color: #D97706; }
.alert-info .alert-icon { color: #2563EB; }

/* Content */
.alert-content {
  flex: 1;
  min-width: 0;
}

.alert-title {
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 4px 0;
}

.alert-success .alert-title { color: #065F46; }
.alert-error .alert-title { color: #991B1B; }
.alert-warning .alert-title { color: #92400E; }
.alert-info .alert-title { color: #1E40AF; }

.alert-message {
  font-size: 14px;
  line-height: 1.5;
}

.alert-success .alert-message { color: #047857; }
.alert-error .alert-message { color: #B91C1C; }
.alert-warning .alert-message { color: #B45309; }
.alert-info .alert-message { color: #1D4ED8; }

/* Close button */
.alert-close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: none;
  background: transparent;
  border-radius: 4px;
  cursor: pointer;
  transition: all 150ms;
  flex-shrink: 0;
}

.alert-success .alert-close { color: #059669; }
.alert-error .alert-close { color: #DC2626; }
.alert-warning .alert-close { color: #D97706; }
.alert-info .alert-close { color: #2563EB; }

.alert-close:hover {
  background: rgba(0, 0, 0, 0.05);
}

.alert-close:focus {
  outline: none;
  box-shadow: 0 0 0 2px currentColor;
}

/* Dark mode ready */
@media (prefers-color-scheme: dark) {
  .alert-success { background: #064E3B; border-color: #065F46; }
  .alert-error { background: #7F1D1D; border-color: #991B1B; }
  .alert-warning { background: #78350F; border-color: #92400E; }
  .alert-info { background: #1E3A8A; border-color: #1E40AF; }

  .alert-success .alert-title,
  .alert-success .alert-message { color: #6EE7B7; }

  .alert-error .alert-title,
  .alert-error .alert-message { color: #FCA5A5; }

  .alert-warning .alert-title,
  .alert-warning .alert-message { color: #FCD34D; }

  .alert-info .alert-title,
  .alert-info .alert-message { color: #93C5FD; }
}
</style>
