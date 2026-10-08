<template>
  <v-card
    class="stat-card"
    :class="cardClasses"
    :color="variant === 'gradient' ? undefined : color"
    elevation="2"
    hover
    rounded="xl"
  >
    <v-card-text class="pa-6">
      <div class="d-flex align-center justify-space-between mb-4">
        <v-avatar
          :class="`${color}--text`"
          :color="iconBg || `${color}-lighten-4`"
          size="56"
        >
          <v-icon :color="iconColor || color" size="28">{{ icon }}</v-icon>
        </v-avatar>

        <div v-if="trend" class="text-end">
          <v-chip
            :color="trendColor"
            :prepend-icon="trendIcon"
            size="small"
            variant="tonal"
          >
            {{ trend }}
          </v-chip>
        </div>
      </div>

      <h3 class="text-h4 font-weight-bold mb-2" :class="valueColor">
        {{ value }}
      </h3>

      <p class="text-body-2 text-medium-emphasis mb-0">
        {{ label }}
      </p>

      <slot name="extra" />
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    label: string
    value: string | number
    icon: string
    color?: string
    iconColor?: string
    iconBg?: string
    trend?: string
    trendIcon?: string
    trendColor?: string
    variant?: 'default' | 'gradient'
    valueColor?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    color: 'primary',
    variant: 'gradient',
    valueColor: 'text-grey-darken-4',
  })

  const cardClasses = computed(() => {
    if (props.variant === 'gradient') {
      return `stat-card-gradient stat-card-${props.color}`
    }
    return ''
  })
</script>

<style scoped>
.stat-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid rgba(var(--v-border-color), 0.08);
  overflow: hidden;
  background: linear-gradient(145deg, #ffffff 0%, #fafbfc 100%);
  position: relative;
}

.stat-card::after {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 140px;
  height: 140px;
  border-radius: 50%;
  opacity: 0.06;
  pointer-events: none;
  transition: opacity 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 36px rgba(0, 0, 0, 0.08) !important;
  border-color: rgba(var(--v-border-color), 0.15);
}

.stat-card:hover::after {
  opacity: 0.1;
}

.stat-card-gradient {
  position: relative;
}

.stat-card-gradient::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  opacity: 0.04;
  pointer-events: none;
  transition: opacity 0.3s ease;
}

.stat-card-gradient:hover::before {
  opacity: 0.06;
}

/* Couleurs modernes et attractives */
.stat-card-primary::before {
  background: linear-gradient(135deg, rgba(91, 141, 217, 0.12) 0%, rgba(122, 159, 255, 0.02) 100%);
}

.stat-card-primary::after {
  background: radial-gradient(circle, rgba(91, 141, 217, 1) 0%, transparent 70%);
}

.stat-card-success::before {
  background: linear-gradient(135deg, rgba(34, 197, 94, 0.12) 0%, rgba(74, 222, 128, 0.02) 100%);
}

.stat-card-success::after {
  background: radial-gradient(circle, rgba(34, 197, 94, 1) 0%, transparent 70%);
}

.stat-card-warning::before {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.12) 0%, rgba(251, 191, 36, 0.02) 100%);
}

.stat-card-warning::after {
  background: radial-gradient(circle, rgba(245, 158, 11, 1) 0%, transparent 70%);
}

.stat-card-error::before {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.12) 0%, rgba(248, 113, 113, 0.02) 100%);
}

.stat-card-error::after {
  background: radial-gradient(circle, rgba(239, 68, 68, 1) 0%, transparent 70%);
}

.stat-card-info::before {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.12) 0%, rgba(96, 165, 250, 0.02) 100%);
}

.stat-card-info::after {
  background: radial-gradient(circle, rgba(59, 130, 246, 1) 0%, transparent 70%);
}

/* Amélioration de la lisibilité des textes */
.stat-card :deep(.v-card-text) {
  position: relative;
  z-index: 1;
}

.stat-card :deep(.text-h4) {
  color: #1e293b !important;
  font-weight: 700 !important;
  letter-spacing: -0.02em;
}

.stat-card :deep(.text-body-2) {
  color: #64748b !important;
  font-weight: 500 !important;
}

.stat-card :deep(.v-avatar) {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  border: 2px solid rgba(255, 255, 255, 0.8);
}
</style>
