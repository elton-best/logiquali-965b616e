<template>
  <div
    class="stat-card"
    :class="[borderColorClass]"
  >
    <div class="stat-header">
      <div class="stat-content">
        <p class="stat-label">{{ title }}</p>
        <div class="stat-value-wrapper">
          <h2 class="stat-value">{{ value }}</h2>
          <span v-if="unit" class="stat-unit">{{ unit }}</span>
        </div>
        <div v-if="trend !== 'stable'" class="stat-trend">
          <v-icon
            :color="trend === 'up' ? 'success' : 'error'"
            size="16"
          >
            {{ trend === 'up' ? 'mdi-trending-up' : 'mdi-trending-down' }}
          </v-icon>
          <span
            class="trend-value"
            :class="trend === 'up' ? 'trend-up' : 'trend-down'"
          >
            {{ trendValue }}
          </span>
          <span class="trend-label">vs mois dernier</span>
        </div>
      </div>
      <div :class="['stat-icon', iconBackgroundClass]">
        <v-icon :color="color" size="32">{{ icon }}</v-icon>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  const props = defineProps<{
    title: string
    value: string | number
    unit?: string
    trend?: 'up' | 'down' | 'stable'
    trendValue?: string
    icon: string
    color: string
  }>()

  const colorClasses: Record<string, { border: string, bg: string }> = {
    green: { border: 'border-success', bg: 'bg-success' },
    orange: { border: 'border-warning', bg: 'bg-warning' },
    blue: { border: 'border-primary', bg: 'bg-primary' },
    red: { border: 'border-danger', bg: 'bg-danger' },
    purple: { border: 'border-purple', bg: 'bg-purple' },
    teal: { border: 'border-teal', bg: 'bg-teal' },
  }

  const borderColorClass = computed(() => {
    return colorClasses[props.color]?.border || 'border-primary'
  })

  const iconBackgroundClass = computed(() => {
    return colorClasses[props.color]?.bg || 'bg-primary'
  })
</script>

<style scoped>
.stat-card {
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  padding: var(--spacing-6);
  box-shadow: var(--shadow-base);
  transition: all var(--transition-base);
  border-left: 4px solid transparent;
}

.stat-card:hover {
  box-shadow: var(--shadow-lg);
  transform: translateY(-2px);
}

.border-primary {
  border-left-color: var(--color-primary-500);
}

.border-success {
  border-left-color: var(--color-success-500);
}

.border-danger {
  border-left-color: var(--color-danger-500);
}

.border-warning {
  border-left-color: var(--color-warning-500);
}

.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: var(--spacing-4);
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  color: var(--text-secondary);
  margin-bottom: var(--spacing-2);
}

.stat-value-wrapper {
  display: flex;
  align-items: baseline;
  gap: var(--spacing-2);
  margin-bottom: var(--spacing-3);
}

.stat-value {
  font-size: var(--font-size-3xl);
  font-weight: var(--font-weight-bold);
  color: var(--text-primary);
}

.stat-unit {
  font-size: var(--font-size-lg);
  color: var(--text-secondary);
}

.stat-trend {
  display: flex;
  align-items: center;
  gap: var(--spacing-1);
}

.trend-value {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
}

.trend-up {
  color: var(--color-success-600);
}

.trend-down {
  color: var(--color-danger-600);
}

.trend-label {
  font-size: var(--font-size-xs);
  color: var(--text-tertiary);
  margin-left: var(--spacing-1);
}

.stat-icon {
  border-radius: var(--radius-full);
  padding: var(--spacing-3);
  display: flex;
  align-items: center;
  justify-content: center;
}

.bg-primary {
  background: var(--color-primary-100);
}

.bg-success {
  background: var(--color-success-100);
}

.bg-danger {
  background: var(--color-danger-100);
}

.bg-warning {
  background: var(--color-warning-100);
}
</style>
