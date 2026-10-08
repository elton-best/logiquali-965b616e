<template>
  <span class="app-badge" :class="badgeClasses">
    <component :is="icon" v-if="icon" class="badge-icon" :size="iconSize" />
    <span class="badge-text">
      <slot>{{ label }}</slot>
    </span>
    <button
      v-if="closable"
      aria-label="Supprimer"
      class="badge-close"
      type="button"
      @click="handleClose"
    >
      <X :size="12" />
    </button>
  </span>
</template>

<script setup lang="ts">
  import { X } from 'lucide-vue-next'
  import { computed } from 'vue'

  interface Props {
    label?: string
    variant?: 'primary' | 'success' | 'warning' | 'error' | 'audit' | 'risk' | 'info' | 'neutral'
    size?: 'sm' | 'md' | 'lg'
    icon?: any
    closable?: boolean
    outlined?: boolean
    rounded?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    variant: 'neutral',
    size: 'md',
    closable: false,
    outlined: false,
    rounded: false,
  })

  const emit = defineEmits<{
    close: []
  }>()

  const badgeClasses = computed(() => ({
    [`badge-${props.variant}`]: true,
    [`badge-${props.size}`]: true,
    'badge-outlined': props.outlined,
    'badge-rounded': props.rounded,
    'badge-closable': props.closable,
  }))

  const iconSize = computed(() => {
    const sizes = { sm: 12, md: 14, lg: 16 }
    return sizes[props.size]
  })

  function handleClose (event: Event) {
    event.stopPropagation()
    emit('close')
  }
</script>

<style scoped>
.app-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-weight: 600;
  white-space: nowrap;
  border-radius: 6px;
  border: 1px solid transparent;
  transition: all 150ms;
}

/* Sizes */
.badge-sm {
  padding: 2px 8px;
  font-size: 11px;
  line-height: 1.4;
}

.badge-md {
  padding: 4px 10px;
  font-size: 12px;
  line-height: 1.4;
}

.badge-lg {
  padding: 6px 12px;
  font-size: 13px;
  line-height: 1.4;
}

.badge-rounded {
  border-radius: 9999px;
}

/* Variants - Filled */
.badge-primary { background: #EEF2FB; color: #365A9D; }
.badge-success { background: #DCFCE7; color: #059669; }
.badge-warning { background: #FEF3C7; color: #D97706; }
.badge-error { background: #FEE2E2; color: #DC2626; }
.badge-audit { background: #F3E8FF; color: #7C3AED; }
.badge-risk { background: #FFEDD5; color: #EA580C; }
.badge-info { background: #DBEAFE; color: #2563EB; }
.badge-neutral { background: #F3F4F6; color: #4B5563; }

/* Variants - Outlined */
.badge-outlined.badge-primary { background: transparent; color: #4471C4; border-color: #4471C4; }
.badge-outlined.badge-success { background: transparent; color: #10B981; border-color: #10B981; }
.badge-outlined.badge-warning { background: transparent; color: #F59E0B; border-color: #F59E0B; }
.badge-outlined.badge-error { background: transparent; color: #EF4444; border-color: #EF4444; }
.badge-outlined.badge-audit { background: transparent; color: #8B5CF6; border-color: #8B5CF6; }
.badge-outlined.badge-risk { background: transparent; color: #F97316; border-color: #F97316; }
.badge-outlined.badge-info { background: transparent; color: #3B82F6; border-color: #3B82F6; }
.badge-outlined.badge-neutral { background: transparent; color: #6B7280; border-color: #D1D5DB; }

/* Icon */
.badge-icon {
  flex-shrink: 0;
}

/* Text */
.badge-text {
  line-height: 1;
}

/* Close button */
.badge-close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
  border: none;
  background: transparent;
  color: currentColor;
  border-radius: 3px;
  cursor: pointer;
  transition: all 150ms;
  flex-shrink: 0;
  opacity: 0.7;
  margin-left: 2px;
}

.badge-close:hover {
  opacity: 1;
  background: rgba(0, 0, 0, 0.1);
}

.badge-close:focus {
  outline: none;
  box-shadow: 0 0 0 2px currentColor;
  opacity: 1;
}

/* Dark mode ready */
@media (prefers-color-scheme: dark) {
  .badge-primary { background: #1E3A8A; color: #93C5FD; }
  .badge-success { background: #064E3B; color: #6EE7B7; }
  .badge-warning { background: #78350F; color: #FCD34D; }
  .badge-error { background: #7F1D1D; color: #FCA5A5; }
  .badge-audit { background: #4C1D95; color: #D8B4FE; }
  .badge-risk { background: #7C2D12; color: #FDBA74; }
  .badge-info { background: #1E3A8A; color: #93C5FD; }
  .badge-neutral { background: #374151; color: #D1D5DB; }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .app-badge,
  .badge-close {
    transition: none !important;
  }
}
</style>
