<template>
  <div class="app-tooltip-wrapper" @mouseenter="handleMouseEnter" @mouseleave="handleMouseLeave">
    <slot />
    <Teleport to="body">
      <Transition name="tooltip-fade">
        <div
          v-if="isVisible"
          ref="tooltipRef"
          :aria-hidden="!isVisible"
          :class="tooltipClasses"
          role="tooltip"
          :style="tooltipStyle"
        >
          <div class="tooltip-content">
            {{ text }}
          </div>
          <div class="tooltip-arrow" :style="arrowStyle" />
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
  import { computed, nextTick, ref, watch } from 'vue'

  interface Props {
    text: string
    position?: 'top' | 'bottom' | 'left' | 'right'
    trigger?: 'hover' | 'click' | 'focus'
    disabled?: boolean
    delay?: number
  }

  const props = withDefaults(defineProps<Props>(), {
    position: 'top',
    trigger: 'hover',
    disabled: false,
    delay: 200,
  })

  const isVisible = ref(false)
  const tooltipRef = ref<HTMLElement>()
  const triggerRef = ref<HTMLElement>()
  const timeoutId = ref<number>()
  const tooltipStyle = ref({})
  const arrowStyle = ref({})

  const tooltipClasses = computed(() => [
    'app-tooltip',
    `app-tooltip--${props.position}`,
  ])

  function handleMouseEnter () {
    if (props.disabled || props.trigger !== 'hover') return

    clearTimeout(timeoutId.value)
    timeoutId.value = window.setTimeout(() => {
      showTooltip()
    }, props.delay)
  }

  function handleMouseLeave () {
    if (props.disabled || props.trigger !== 'hover') return

    clearTimeout(timeoutId.value)
    hideTooltip()
  }

  async function showTooltip () {
    isVisible.value = true
    await nextTick()
    updatePosition()
  }

  function hideTooltip () {
    isVisible.value = false
  }

  function updatePosition () {
    if (!tooltipRef.value || !triggerRef.value) return

    const trigger = triggerRef.value.getBoundingClientRect()
    const tooltip = tooltipRef.value.getBoundingClientRect()
    const gap = 8

    let top = 0
    let left = 0
    let arrowTop = ''
    let arrowLeft = ''

    switch (props.position) {
      case 'top': {
        top = trigger.top - tooltip.height - gap
        left = trigger.left + (trigger.width - tooltip.width) / 2
        arrowTop = '100%'
        arrowLeft = '50%'
        break
      }
      case 'bottom': {
        top = trigger.bottom + gap
        left = trigger.left + (trigger.width - tooltip.width) / 2
        arrowTop = '-4px'
        arrowLeft = '50%'
        break
      }
      case 'left': {
        top = trigger.top + (trigger.height - tooltip.height) / 2
        left = trigger.left - tooltip.width - gap
        arrowTop = '50%'
        arrowLeft = '100%'
        break
      }
      case 'right': {
        top = trigger.top + (trigger.height - tooltip.height) / 2
        left = trigger.right + gap
        arrowTop = '50%'
        arrowLeft = '-4px'
        break
      }
    }

    tooltipStyle.value = {
      top: `${top}px`,
      left: `${left}px`,
    }

    arrowStyle.value = {
      top: arrowTop,
      left: arrowLeft,
    }
  }

  watch(() => props.text, () => {
    if (isVisible.value) {
      nextTick(() => updatePosition())
    }
  })
</script>

<style scoped>
.app-tooltip-wrapper {
  display: inline-block;
}

.app-tooltip {
  position: fixed;
  z-index: 9999;
  padding: 8px 12px;
  background: #1e293b;
  color: #ffffff;
  border-radius: 6px;
  font-size: 0.875rem;
  line-height: 1.4;
  max-width: 300px;
  word-wrap: break-word;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  pointer-events: none;
}

.tooltip-content {
  position: relative;
  z-index: 1;
}

.tooltip-arrow {
  position: absolute;
  width: 8px;
  height: 8px;
  background: #1e293b;
  transform: translate(-50%, -50%) rotate(45deg);
}

.app-tooltip--top .tooltip-arrow {
  transform: translate(-50%, 0) rotate(45deg);
}

.app-tooltip--bottom .tooltip-arrow {
  transform: translate(-50%, 0) rotate(45deg);
}

.app-tooltip--left .tooltip-arrow {
  transform: translate(0, -50%) rotate(45deg);
}

.app-tooltip--right .tooltip-arrow {
  transform: translate(0, -50%) rotate(45deg);
}

.tooltip-fade-enter-active,
.tooltip-fade-leave-active {
  transition: opacity 0.15s ease;
}

.tooltip-fade-enter-from,
.tooltip-fade-leave-to {
  opacity: 0;
}
</style>
