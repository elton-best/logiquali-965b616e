<template>
  <div
    :aria-label="`${title}: ${value}${trend ? `, tendance ${trend > 0 ? 'positive' : 'négative'} de ${Math.abs(trend)}%` : ''}`"
    class="app-widget"
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
      <!-- Header: Icon + Trend -->
      <div class="widget-header">
        <div class="widget-title">{{ title }}</div>

        <!-- Trend Badge -->
        <div v-if="trend !== undefined && !loading" class="trend-badge">
          <span class="trend-value" :class="trendClass">
            <component :is="trendIcon" class="trend-icon" />
            {{ Math.abs(trend) }}%
          </span>
        </div>
      </div>

      <div class="widget-body">
        <div class="widget-value">{{ formattedValue }}</div>
        <div class="icon-wrapper" :class="`icon-${variant}`">
          <component :is="iconComponent" class="icon" />
        </div>
      </div>
    </div>

    <!-- Shine effect on hover -->
    <div class="shine-effect" />
  </div>
</template>

<script setup lang="ts">
  import type { Component } from 'vue'
  import {
    AlertCircle,
    AlertTriangle,
    Calendar,
    CheckCircle,
    Clock,
    Info,
    Minus,
    TrendingDown,
    TrendingUp,
  } from 'lucide-vue-next'
  import { computed } from 'vue'

  interface Props {
    title: string
    value: string | number
    icon?: string | Component
    variant?: string
    trend?: number
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
  const iconMap: Record<string, Component> = {
    CheckCircle,
    Clock,
    AlertTriangle,
    Calendar,
    AlertCircle,
    Info,
  }

  const iconComponent = computed(() => {
    if (typeof props.icon === 'string') {
      return iconMap[props.icon] || Info
    }

    return props.icon || Info
  })

  // Formatted value
  const formattedValue = computed(() => {
    if (props.loading) return '---'
    if (typeof props.value === 'number') {
      return props.value.toLocaleString('fr-FR')
    }
    return props.value
  })

  // Trend icon and class
  const trendIcon = computed(() => {
    if (props.trend === undefined || props.trend === 0) return Minus
    return props.trend > 0 ? TrendingUp : TrendingDown
  })

  const trendClass = computed(() => {
    if (props.trend === undefined || props.trend === 0) return 'trend-neutral'
    return props.trend > 0 ? 'trend-positive' : 'trend-negative'
  })

  // Click handler
  function handleClick () {
    if (props.clickable && !props.loading) {
      emit('click')
    }
  }
</script>

<style scoped>
.app-widget {
  position: relative;
  overflow: hidden;
  background: #ffffff;
  border: 1px solid var(--border-line, rgba(226, 232, 240, 0.9));
  border-radius: 14px;
  padding: 17px 18px;
  transition: all 250ms cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  min-height: 112px;
  display: flex;
  flex-direction: column;
}

.app-widget:hover:not(.widget-loading) {
  transform: translateY(-2px);
  box-shadow:
    0 10px 24px -12px rgba(15, 23, 42, 0.28),
    0 3px 8px -5px rgba(15, 23, 42, 0.18);
  border-color: rgba(var(--v-theme-primary), 0.35);
}

.app-widget:hover:not(.widget-loading) .shine-effect {
  animation: shine 1.5s ease-in-out;
}

.app-widget:hover:not(.widget-loading) .icon-wrapper {
  transform: scale(1.06);
}

.app-widget:hover:not(.widget-loading) .decorative-circle {
  opacity: 0.15;
}

.app-widget:focus {
  outline: none;
  ring: 2px;
  ring-color: var(--color-primary-500);
  ring-offset: 2px;
}

.app-widget.widget-loading {
  cursor: default;
  opacity: 0.6;
}

/* Variant backgrounds */
.widget-primary {
  background: linear-gradient(145deg, #ffffff 0%, #f8faff 100%);
}
.widget-success {
  background: linear-gradient(145deg, #ffffff 0%, #fbfffc 100%);
}
.widget-warning {
  background: linear-gradient(145deg, #ffffff 0%, #fffdf8 100%);
}
.widget-error {
  background: linear-gradient(145deg, #ffffff 0%, #fffafa 100%);
}
.widget-audit {
  background: linear-gradient(145deg, #ffffff 0%, #fdfbff 100%);
}
.widget-risk {
  background: linear-gradient(145deg, #ffffff 0%, #fff7ed 100%);
}
.widget-info {
  background: linear-gradient(145deg, #ffffff 0%, #f9fcff 100%);
}

/* Decorative circles */
.decorative-circle {
  position: absolute;
  border-radius: 50%;
  opacity: 0.035;
  pointer-events: none;
  transition: opacity 300ms ease;
  z-index: 0;
}

.circle-1 {
  width: 180px;
  height: 180px;
  top: -90px;
  right: -90px;
}

.circle-2 {
  width: 120px;
  height: 120px;
  bottom: -60px;
  left: -60px;
}

.widget-primary .decorative-circle {
  background: radial-gradient(circle, #4471c4, transparent);
}
.widget-success .decorative-circle {
  background: radial-gradient(circle, #10b981, transparent);
}
.widget-warning .decorative-circle {
  background: radial-gradient(circle, #f59e0b, transparent);
}
.widget-error .decorative-circle {
  background: radial-gradient(circle, #ef4444, transparent);
}
.widget-audit .decorative-circle {
  background: radial-gradient(circle, #8b5cf6, transparent);
}
.widget-risk .decorative-circle {
  background: radial-gradient(circle, #f97316, transparent);
}
.widget-info .decorative-circle {
  background: radial-gradient(circle, #3b82f6, transparent);
}

/* Content */
.widget-content {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 7px;
  flex: 1;
}

/* Header */
.widget-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 22px;
}

.widget-body {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 12px;
  margin-top: auto;
}

/* Icon wrapper */
.icon-wrapper {
  width: 38px;
  height: 38px;
  border-radius: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: none;
  position: relative;
  transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
}

.icon-wrapper::before {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: inherit;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.3), transparent);
  opacity: 0.5;
}

.icon {
  width: 19px;
  height: 19px;
  color: white;
  position: relative;
  z-index: 1;
}

.icon-primary {
  background: linear-gradient(135deg, #4471c4 0%, #6e92d5 100%);
}
.icon-success {
  background: linear-gradient(135deg, #10b981 0%, #4ade80 100%);
}
.icon-warning {
  background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
}
.icon-error {
  background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
}
.icon-audit {
  background: linear-gradient(135deg, #8b5cf6 0%, #c084fc 100%);
}
.icon-risk {
  background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
}
.icon-info {
  background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
}

/* Trend badge */
.trend-badge {
  display: flex;
  align-items: center;
}

.trend-value {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 3px 8px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.trend-icon {
  width: 13px;
  height: 13px;
}

.trend-positive {
  background: #dcfce7;
  color: #059669;
}

.trend-negative {
  background: #fee2e2;
  color: #dc2626;
}

.trend-neutral {
  background: #f3f4f6;
  color: #6b7280;
}

/* Value */
.widget-value {
  font-family: var(--font-display) !important;
  font-size: 30px;
  font-weight: 800;
  color: #1e293b;
  line-height: 1;
  letter-spacing: -0.03em;
}

/* Title */
.widget-title {
  font-family: var(--font-sans) !important;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  letter-spacing: 0;
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
  .app-widget {
    min-height: 120px;
    padding: 20px;
  }

  .icon-wrapper {
    width: 34px;
    height: 34px;
  }

  .icon {
    width: 24px;
    height: 24px;
  }

  .widget-value {
    font-size: 28px;
  }

  .widget-title {
    font-size: 12px;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .app-widget,
  .icon-wrapper,
  .shine-effect {
    animation: none !important;
    transition: none !important;
  }

  .app-widget:hover {
    transform: none !important;
  }
}
</style>
