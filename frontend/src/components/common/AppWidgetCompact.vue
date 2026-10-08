<template>
  <div
    :aria-label="`${title}: ${value}`"
    class="app-widget-compact"
    :class="[`widget-${variant}`, { 'widget-loading': loading }]"
    role="button"
    :tabindex="clickable ? 0 : -1"
    @click="handleClick"
    @keydown.enter="handleClick"
    @keydown.space.prevent="handleClick"
  >
    <!-- Decorative circles -->
    <div class="decorative-circle circle-1" />
    <div class="decorative-circle circle-2" />

    <!-- Content -->
    <div class="widget-content">
      <!-- Icon -->
      <div class="icon-wrapper" :class="`icon-${variant}`">
        <component :is="iconComponent" class="icon" />
      </div>

      <!-- Text content -->
      <div class="text-content">
        <div class="widget-value">
          {{ formattedValue }}
        </div>
        <div class="widget-title">
          {{ title }}
        </div>
      </div>
    </div>

    <!-- Shine effect on hover -->
    <div class="shine-effect" />
  </div>
</template>

<script setup lang="ts">
  import {
    AlertCircle,
    AlertTriangle,
    Building,
    Calendar,
    CheckCircle,
    Clock,
    FileText,
    Info,
    Users,
  } from 'lucide-vue-next'
  import { computed } from 'vue'

  interface Props {
    title: string
    value: string | number
    icon?: string
    variant?: 'success' | 'warning' | 'error' | 'audit' | 'risk' | 'info' | 'primary'
    loading?: boolean
    clickable?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    icon: 'Info',
    variant: 'primary',
    loading: false,
    clickable: true,
  })

  const emit = defineEmits<{
    click: []
  }>()

  // Icon mapping
  const iconMap: Record<string, any> = {
    CheckCircle,
    Clock,
    AlertTriangle,
    Calendar,
    AlertCircle,
    Info,
    FileText,
    Users,
    Building,
  }

  const iconComponent = computed(() => iconMap[props.icon] || Info)

  // Formatted value
  const formattedValue = computed(() => {
    if (props.loading) return '---'
    if (typeof props.value === 'number') {
      return props.value.toLocaleString('fr-FR')
    }
    return props.value
  })

  // Click handler
  function handleClick () {
    if (props.clickable && !props.loading) {
      emit('click')
    }
  }
</script>

<style scoped>
.app-widget-compact {
  position: relative;
  overflow: hidden;
  background: linear-gradient(145deg, #ffffff 0%, #fafbfc 100%);
  border: 1px solid rgba(226, 232, 240, 0.5);
  border-radius: 12px;
  padding: 16px;
  transition: all 250ms cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  min-height: 100px;
  display: flex;
  flex-direction: column;
}

.app-widget-compact:hover:not(.widget-loading) {
  transform: translateY(-4px) scale(1.02);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
}

.app-widget-compact:hover:not(.widget-loading) .shine-effect {
  animation: shine 1.5s ease-in-out;
}

.app-widget-compact:hover:not(.widget-loading) .icon-wrapper {
  transform: scale(1.08) rotate(5deg);
}

.app-widget-compact:hover:not(.widget-loading) .decorative-circle {
  opacity: 0.15;
}

.app-widget-compact:focus {
  outline: none;
  ring: 2px;
  ring-color: var(--color-primary-500);
  ring-offset: 2px;
}

.app-widget-compact.widget-loading {
  cursor: default;
  opacity: 0.6;
}

/* Variant backgrounds */
.widget-primary { background: linear-gradient(145deg, #ffffff 0%, #EEF2FB 100%); }
.widget-success { background: linear-gradient(145deg, #ffffff 0%, #F0FDF4 100%); }
.widget-warning { background: linear-gradient(145deg, #ffffff 0%, #FFFBEB 100%); }
.widget-error { background: linear-gradient(145deg, #ffffff 0%, #FEF2F2 100%); }
.widget-audit { background: linear-gradient(145deg, #ffffff 0%, #FAF5FF 100%); }
.widget-risk { background: linear-gradient(145deg, #ffffff 0%, #FFF7ED 100%); }
.widget-info { background: linear-gradient(145deg, #ffffff 0%, #EFF6FF 100%); }

/* Decorative circles */
.decorative-circle {
  position: absolute;
  border-radius: 50%;
  opacity: 0.08;
  pointer-events: none;
  transition: opacity 300ms ease;
  z-index: 0;
}

.circle-1 {
  width: 120px;
  height: 120px;
  top: -60px;
  right: -60px;
}

.circle-2 {
  width: 80px;
  height: 80px;
  bottom: -40px;
  left: -40px;
}

.widget-primary .decorative-circle { background: radial-gradient(circle, #4471C4, transparent); }
.widget-success .decorative-circle { background: radial-gradient(circle, #10B981, transparent); }
.widget-warning .decorative-circle { background: radial-gradient(circle, #F59E0B, transparent); }
.widget-error .decorative-circle { background: radial-gradient(circle, #EF4444, transparent); }
.widget-audit .decorative-circle { background: radial-gradient(circle, #8B5CF6, transparent); }
.widget-risk .decorative-circle { background: radial-gradient(circle, #F97316, transparent); }
.widget-info .decorative-circle { background: radial-gradient(circle, #3B82F6, transparent); }

/* Content */
.widget-content {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 16px;
  flex: 1;
}

/* Icon wrapper */
.icon-wrapper {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  position: relative;
  flex-shrink: 0;
  transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
}

.icon-wrapper::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: inherit;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.3), transparent);
  opacity: 0.5;
}

.icon {
  width: 24px;
  height: 24px;
  color: white;
  position: relative;
  z-index: 1;
}

.icon-primary { background: linear-gradient(135deg, #4471C4 0%, #6E92D5 100%); }
.icon-success { background: linear-gradient(135deg, #10B981 0%, #4ADE80 100%); }
.icon-warning { background: linear-gradient(135deg, #F59E0B 0%, #FBBF24 100%); }
.icon-error { background: linear-gradient(135deg, #EF4444 0%, #F87171 100%); }
.icon-audit { background: linear-gradient(135deg, #8B5CF6 0%, #C084FC 100%); }
.icon-risk { background: linear-gradient(135deg, #F97316 0%, #FB923C 100%); }
.icon-info { background: linear-gradient(135deg, #3B82F6 0%, #60A5FA 100%); }

/* Text content */
.text-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
  min-width: 0;
}

/* Value */
.widget-value {
  font-size: 24px;
  font-weight: 800;
  color: #1e293b;
  line-height: 1;
  letter-spacing: -0.02em;
}

/* Title */
.widget-title {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Shine effect */
.shine-effect {
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: linear-gradient(
    to bottom right,
    rgba(255, 255, 255, 0) 0%,
    rgba(255, 255, 255, 0.13) 50%,
    rgba(255, 255, 255, 0) 100%
  );
  transform: rotate(45deg);
  opacity: 0;
  pointer-events: none;
}

@keyframes shine {
  0% {
    opacity: 0;
    transform: translateX(-100%) rotate(45deg);
  }
  50% {
    opacity: 1;
  }
  100% {
    opacity: 0;
    transform: translateX(100%) rotate(45deg);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .app-widget-compact {
    min-height: 90px;
    padding: 14px;
  }

  .icon-wrapper {
    width: 44px;
    height: 44px;
  }

  .icon {
    width: 20px;
    height: 20px;
  }

  .widget-value {
    font-size: 20px;
  }

  .widget-title {
    font-size: 11px;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .app-widget-compact,
  .icon-wrapper,
  .shine-effect {
    animation: none !important;
    transition: none !important;
  }

  .app-widget-compact:hover {
    transform: none !important;
  }
}
</style>
