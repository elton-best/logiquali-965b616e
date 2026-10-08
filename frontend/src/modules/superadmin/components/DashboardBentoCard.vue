<template>
  <v-card
    :class="['bento-card', `bento-card--${variant}`, elevated ? 'bento-card--elevated' : '', clickable ? 'bento-card--clickable' : '']"
    :elevation="elevated ? 8 : 0"
    :ripple="clickable"
    :role="clickable ? 'button' : undefined"
    :tabindex="clickable ? 0 : undefined"
    @click="onClick"
    @keydown.enter.prevent="onClick"
    @keydown.space.prevent="onClick"
  >
    <div class="bento-card__gradient" :style="gradientStyle" />

    <v-card-text class="bento-card__content">
      <!-- Icon -->
      <div v-if="icon" class="bento-card__icon-wrapper" :style="iconWrapperStyle">
        <v-icon :color="iconColor" :size="iconSize">{{ icon }}</v-icon>
      </div>

      <!-- Badge (optional) -->
      <v-chip
        v-if="badge"
        class="bento-card__badge"
        :color="badgeColor"
        size="small"
        variant="flat"
      >
        {{ badge }}
      </v-chip>

      <!-- Value -->
      <div class="bento-card__value-wrapper">
        <h2 :class="['bento-card__value', `text-${valueSize}`]" :style="{ color: valueColor }">
          <slot name="value">
            <transition mode="out-in" name="counter">
              <span :key="animatedValue">{{ displayValue }}</span>
            </transition>
          </slot>
        </h2>

        <!-- Trend indicator -->
        <div v-if="trend" class="bento-card__trend">
          <v-icon :color="trendColor" size="16">
            {{ trendIcon }}
          </v-icon>
          <span :class="`text-${trendColor}`">{{ trend }}</span>
        </div>
      </div>

      <!-- Label -->
      <p class="bento-card__label">{{ label }}</p>

      <!-- Subtitle -->
      <p v-if="subtitle" class="bento-card__subtitle">{{ subtitle }}</p>

      <!-- Action slot -->
      <div v-if="$slots.action" class="bento-card__action">
        <slot name="action" />
      </div>

      <!-- Chart/Visual slot -->
      <div v-if="$slots.visual" class="bento-card__visual">
        <slot name="visual" />
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'

  interface Props {
    variant?: 'small' | 'medium' | 'large' | 'wide'
    value?: string | number
    label: string
    subtitle?: string
    icon?: string
    iconColor?: string
    iconSize?: number | string
    badge?: string
    badgeColor?: string
    trend?: string
    trendDirection?: 'up' | 'down' | 'neutral'
    gradient?: string[]
    elevated?: boolean
    clickable?: boolean
    valueColor?: string
    animateValue?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    variant: 'medium',
    iconSize: 28,
    iconColor: 'primary',
    badgeColor: 'primary',
    trendDirection: 'neutral',
    elevated: false,
    clickable: false,
    animateValue: true,
  })

  const emit = defineEmits<{
    click: []
  }>()

  // Animated value for counter effect
  const animatedValue = ref(0)
  const targetValue = ref(0)

  watch(() => props.value, newValue => {
    if (typeof newValue === 'number' && props.animateValue) {
      targetValue.value = newValue
      animateCounter()
    }
  }, { immediate: true })

  function animateCounter () {
    const start = animatedValue.value
    const end = targetValue.value
    const duration = 1000
    const startTime = Date.now()

    const animate = () => {
      const now = Date.now()
      const progress = Math.min((now - startTime) / duration, 1)
      const easeOutQuart = 1 - Math.pow(1 - progress, 4)

      animatedValue.value = Math.floor(start + (end - start) * easeOutQuart)

      if (progress < 1) {
        requestAnimationFrame(animate)
      }
    }

    requestAnimationFrame(animate)
  }

  onMounted(() => {
    if (typeof props.value === 'number' && props.animateValue) {
      animatedValue.value = 0
      targetValue.value = props.value
      animateCounter()
    }
  })

  const displayValue = computed(() => {
    if (props.animateValue && typeof props.value === 'number') {
      return animatedValue.value
    }
    return props.value
  })

  const valueSize = computed(() => {
    switch (props.variant) {
      case 'small': { return 'h5'
      }
      case 'large': { return 'h2'
      }
      default: { return 'h3'
      }
    }
  })

  const gradientStyle = computed(() => {
    if (!props.gradient || props.gradient.length === 0) return {}

    return {
      background: `linear-gradient(135deg, ${props.gradient.join(', ')})`,
    }
  })

  const iconWrapperStyle = computed(() => {
    return {
      backgroundColor: `rgba(var(--v-theme-${props.iconColor}), 0.1)`,
    }
  })

  const trendIcon = computed(() => {
    switch (props.trendDirection) {
      case 'up': { return 'mdi-trending-up'
      }
      case 'down': { return 'mdi-trending-down'
      }
      default: { return 'mdi-trending-neutral'
      }
    }
  })

  const trendColor = computed(() => {
    switch (props.trendDirection) {
      case 'up': { return 'success'
      }
      case 'down': { return 'error'
      }
      default: { return 'grey'
      }
    }
  })

  function onClick () {
    if (props.clickable) {
      emit('click')
    }
  }
</script>

<style scoped>
.bento-card--clickable {
  cursor: pointer;
}

.bento-card--clickable:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 4px;
}
</style>

<style scoped lang="scss">
.bento-card {
  position: relative;
  border-radius: 16px !important;
  border: 1px solid rgba(var(--v-theme-outline), 0.12);
  overflow: hidden;
  transition: all 0.2s ease;
  cursor: default;
  background: rgb(var(--v-theme-surface));

  &--small {
    min-height: 132px;
  }

  &--medium {
    min-height: 160px;
  }

  &--large {
    min-height: 220px;
  }

  &--wide {
    min-height: 156px;
  }

  &:hover {
    border-color: rgba(var(--v-theme-primary), 0.16);
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
  }
}

.bento-card__gradient {
  display: none;
}

.bento-card__content {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 20px !important;
}

.bento-card__icon-wrapper {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 12px;
}

.bento-card__badge {
  position: absolute !important;
  top: 16px;
  right: 16px;
  font-weight: 600;
  letter-spacing: 0.3px;
}

.bento-card__value-wrapper {
  display: flex;
  align-items: baseline;
  gap: 12px;
  margin-bottom: 8px;
  flex-wrap: wrap;
}

.bento-card__value {
  font-weight: 700;
  letter-spacing: -0.02em;
  margin: 0;
}

.bento-card__trend {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.875rem;
  font-weight: 600;
}

.bento-card__label {
  font-size: 0.9375rem;
  font-weight: 600;
  color: rgb(var(--v-theme-on-surface));
  margin: 0 0 4px 0;
  letter-spacing: -0.01em;
}

.bento-card__subtitle {
  font-size: 0.8125rem;
  color: rgb(var(--v-theme-on-surface-variant));
  margin: 0;
}

.bento-card__action {
  margin-top: auto;
  padding-top: 16px;
}

.bento-card__visual {
  margin-top: 12px;
  margin-left: -20px;
  margin-right: -20px;
  margin-bottom: -20px;
}

/* Counter animation */
.counter-enter-active,
.counter-leave-active {
  transition: all 0.2s ease;
}

.counter-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.counter-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
